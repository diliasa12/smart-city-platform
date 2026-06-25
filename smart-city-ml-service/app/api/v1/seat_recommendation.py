"""
smart-city-ml-service/app/api/v1/seat_recommendation.py
=========================================================
Endpoint rekomendasi bangku.

POST /api/v1/recommend-seats

Scoring engine (tidak butuh file .pkl):
  - Position Score  (30%): bangku tengah grid lebih baik
  - Isolation Score (50%): jauh dari bangku yang sudah terisi
  - Popularity Score(20%): sweet spot ~50% dari histori booking
"""

from fastapi import APIRouter, HTTPException
from pydantic import BaseModel, Field
from typing import Optional

router = APIRouter()


# ── Schemas ────────────────────────────────────────────────────────────────────

class TelemetrySnapshot(BaseModel):
    temperature: float
    humidity: float
    decibel_level: float
    ml_classification_status: str  # 'nyaman' | 'cukup_nyaman' | 'tidak_nyaman'
    predicted_next_busy_hour: Optional[int] = None


class SeatInfo(BaseModel):
    seat_number: str               # "A1", "B3", dst
    row: str                       # "A", "B", dst
    col: int                       # 1, 2, 3, dst
    is_booked: bool
    booking_count_historical: int = 0


class RecommendationRequest(BaseModel):
    room_id: int
    room_name: str
    booking_date: str
    start_time: str
    end_time: str
    seats: list[SeatInfo]
    telemetry: Optional[TelemetrySnapshot] = None
    requested_seat_count: int = Field(default=1, ge=1, le=10)


class SeatScore(BaseModel):
    seat_number: str
    score: float                   # 0.0 – 1.0
    is_recommended: bool
    reason: str


class RecommendationResponse(BaseModel):
    room_id: int
    room_name: str
    comfort_level: str             # 'nyaman' | 'cukup_nyaman' | 'tidak_nyaman' | 'unknown'
    comfort_summary: str
    recommended_seats: list[str]
    seat_scores: list[SeatScore]
    available_count: int
    total_seats: int
    warning: Optional[str] = None


# ── Scoring helpers ────────────────────────────────────────────────────────────

def _position_score(seat: SeatInfo, all_seats: list[SeatInfo]) -> float:
    """Bangku di tengah grid dapat skor lebih tinggi."""
    cols = sorted(set(s.col for s in all_seats))
    rows = sorted(set(s.row for s in all_seats))
    if not cols or not rows:
        return 0.5

    mid_col = (max(cols) + min(cols)) / 2
    mid_row = len(rows) / 2
    col_dist = abs(seat.col - mid_col) / (max(cols) - min(cols) + 1)
    row_idx  = rows.index(seat.row) if seat.row in rows else 0
    row_dist = abs(row_idx - mid_row) / (len(rows) + 1)
    return 1.0 - ((col_dist + row_dist) / 2)


def _isolation_score(seat: SeatInfo, booked: list[SeatInfo]) -> float:
    """Makin jauh dari bangku terisi = skor makin tinggi."""
    if not booked:
        return 1.0
    min_dist = min(
        abs(seat.col - b.col) + abs(ord(seat.row) - ord(b.row))
        for b in booked
    )
    return min(min_dist / 4.0, 1.0)


def _popularity_score(seat: SeatInfo, max_bookings: int) -> float:
    """Sweet spot di ~50% popularitas historis."""
    if max_bookings == 0:
        return 0.5
    ratio = seat.booking_count_historical / max_bookings
    return 1.0 - abs(ratio - 0.5)


def _build_reason(pos: float, iso: float, pop: float, is_booked: bool) -> str:
    if is_booked:
        return "sudah dibooking"
    parts = []
    if iso >= 0.75:
        parts.append("jarak aman dari pengguna lain")
    elif iso < 0.25:
        parts.append("berdekatan dengan pengguna lain")
    if pos >= 0.7:
        parts.append("posisi strategis di tengah")
    if pop >= 0.7:
        parts.append("sering dipilih pengguna lain")
    return " · ".join(parts) if parts else "tersedia"


# ── Core scoring ───────────────────────────────────────────────────────────────

def score_and_recommend(
    seats: list[SeatInfo],
    requested_count: int,
) -> list[tuple[SeatInfo, float, str, bool]]:
    """
    Returns list of (seat, score, reason, is_recommended).
    Booked seats score = 0.0, is_recommended = False.
    """
    available = [s for s in seats if not s.is_booked]
    booked    = [s for s in seats if s.is_booked]

    max_bookings = max((s.booking_count_historical for s in available), default=0)

    scored_available: list[tuple[SeatInfo, float, str]] = []
    for seat in available:
        pos = _position_score(seat, seats)
        iso = _isolation_score(seat, booked)
        pop = _popularity_score(seat, max_bookings)
        final = round(pos * 0.30 + iso * 0.50 + pop * 0.20, 4)
        scored_available.append((seat, final, _build_reason(pos, iso, pop, False)))

    scored_available.sort(key=lambda x: x[1], reverse=True)

    # Pick top-N, enforcing min Manhattan distance of 2 between recommendations
    recommended_idx: list[int] = []
    for i, (seat, _, _) in enumerate(scored_available):
        if len(recommended_idx) >= requested_count:
            break
        too_close = any(
            abs(seat.col - scored_available[ri][0].col) +
            abs(ord(seat.row) - ord(scored_available[ri][0].row)) < 2
            for ri in recommended_idx
        )
        if not too_close:
            recommended_idx.append(i)

    # Relax distance constraint if we still don't have enough
    if len(recommended_idx) < requested_count:
        for i in range(len(scored_available)):
            if i not in recommended_idx:
                recommended_idx.append(i)
            if len(recommended_idx) >= requested_count:
                break

    rec_set = set(recommended_idx)
    result: list[tuple[SeatInfo, float, str, bool]] = [
        (seat, score, reason, idx in rec_set)
        for idx, (seat, score, reason) in enumerate(scored_available)
    ]

    # Append booked seats with score 0
    for seat in booked:
        result.append((seat, 0.0, "sudah dibooking", False))

    # Sort by seat_number for consistent output
    result.sort(key=lambda x: x[0].seat_number)
    return result


# ── Endpoint ───────────────────────────────────────────────────────────────────

COMFORT_SUMMARY = {
    "nyaman":        "Ruangan dalam kondisi nyaman — suhu & kebisingan ideal.",
    "cukup_nyaman":  "Kondisi ruangan cukup baik namun ada sedikit gangguan.",
    "tidak_nyaman":  "Perhatian: ruangan kurang nyaman saat ini.",
    "unknown":       "Data sensor belum tersedia untuk ruangan ini.",
}


@router.post("/recommend-seats", response_model=RecommendationResponse)
async def recommend_seats(req: RecommendationRequest):
    if not req.seats:
        raise HTTPException(status_code=422, detail="Daftar bangku tidak boleh kosong.")

    available_count = sum(1 for s in req.seats if not s.is_booked)
    if available_count < req.requested_seat_count:
        raise HTTPException(
            status_code=409,
            detail=f"Hanya {available_count} bangku tersedia, diminta {req.requested_seat_count}."
        )

    # Comfort level dari telemetry
    comfort_level = req.telemetry.ml_classification_status if req.telemetry else "unknown"
    comfort_summary = COMFORT_SUMMARY.get(comfort_level, COMFORT_SUMMARY["unknown"])

    warning = None
    if comfort_level == "tidak_nyaman" and req.telemetry:
        t = req.telemetry
        warning = (
            f"Suhu {t.temperature}°C, kelembaban {t.humidity}%, "
            f"kebisingan {t.decibel_level} dB — pertimbangkan ruangan lain."
        )

    scored = score_and_recommend(req.seats, req.requested_seat_count)

    seat_scores = [
        SeatScore(seat_number=s.seat_number, score=score, is_recommended=is_rec, reason=reason)
        for s, score, reason, is_rec in scored
    ]
    recommended_seats = [
        s.seat_number for s, _, _, is_rec in scored if is_rec
    ]

    return RecommendationResponse(
        room_id=req.room_id,
        room_name=req.room_name,
        comfort_level=comfort_level,
        comfort_summary=comfort_summary,
        recommended_seats=recommended_seats,
        seat_scores=seat_scores,
        available_count=available_count,
        total_seats=len(req.seats),
        warning=warning,
    )