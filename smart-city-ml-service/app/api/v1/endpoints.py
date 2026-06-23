import io
import numpy as np
import librosa

from fastapi import APIRouter, HTTPException, UploadFile, File, Request

router = APIRouter()

# ──────────────────────────────────────────────
# Konstanta: Peta Rekomendasi Berbasis Aturan
# Sesuai spesifikasi: Rule-Based AI Recommendation System
# ──────────────────────────────────────────────
RECOMMENDATIONS = {
    1: {
        "iot_command": "TOGGLE_SOUND_MASKING_ON",
        "recommendation": (
            "Kondisi ruangan terdeteksi BISING. Sistem peredam suara (sound masking) "
            "diaktifkan secara otomatis untuk meningkatkan kenyamanan penghuni."
        ),
        "comfort_status": "Bising/Ramai",
    },
    0: {
        "iot_command": "TOGGLE_SOUND_MASKING_OFF",
        "recommendation": (
            "Kondisi ruangan TENANG dan normal. Sistem peredam suara dimatikan "
            "untuk menghemat konsumsi energi perangkat."
        ),
        "comfort_status": "Sepi/Normal",
    },
}


# ──────────────────────────────────────────────
# Endpoint Utama: POST /api/v1/analyze-telemetry
# ──────────────────────────────────────────────
@router.post(
    "/analyze-telemetry",
    summary="Analisis Kenyamanan Akustik Ruangan",
    description=(
        "Menerima file audio (WAV/MP3) dari Node.js Gateway, "
        "mengekstrak fitur MFCC menggunakan librosa, lalu mengklasifikasikan "
        "kondisi akustik ruangan melalui model Random Forest. "
        "Mengembalikan hasil prediksi beserta perintah otomatisasi IoT."
    ),
)
async def analyze_telemetry(
    request: Request,
    audio_file: UploadFile = File(
        ...,
        description="File audio lingkungan ruangan (format WAV atau MP3).",
    ),
):
    """
    Pipeline pemrosesan:
    1. Baca konten file audio dari upload multipart.
    2. Ekstrak fitur MFCC (40 koefisien) menggunakan librosa.
    3. Rata-rata MFCC di sepanjang dimensi waktu → vektor fitur 1D (40 nilai).
    4. Jalankan prediksi menggunakan model Random Forest dari app.state.
    5. SISTEM HYBRID: Validasi silang hasil AI dengan energi RMS fisik (Koreksi Crowd Noise).
    6. Kembalikan hasil klasifikasi + rekomendasi tindakan IoT.
    """
    # ── Ambil model dari app.state (dimuat saat startup di main.py) ──
    model = getattr(request.app.state, "comfort_model", None)

    if model is None:
        raise HTTPException(
            status_code=503,
            detail="Model klasifikasi belum dimuat. Coba lagi dalam beberapa detik.",
        )

    # ── Validasi tipe file ──
    allowed_content_types = {"audio/wav", "audio/x-wav", "audio/mpeg", "audio/mp3"}
    if audio_file.content_type and audio_file.content_type not in allowed_content_types:
        raise HTTPException(
            status_code=415,
            detail=f"Format file tidak didukung: '{audio_file.content_type}'. Gunakan WAV atau MP3.",
        )

    try:
        # ── 1. Baca byte file audio dari upload ──
        contents = await audio_file.read()
        if not contents:
            raise HTTPException(status_code=400, detail="File audio kosong atau tidak terbaca.")

        # ── 2 & 3. Ekstrak fitur MFCC ──
        # sr=22050 → resample ke 22.050 Hz
        y, sr = librosa.load(io.BytesIO(contents), sr=22050)
        mfccs = librosa.feature.mfcc(y=y, sr=sr, n_mfcc=40)

        # Rata-rata sepanjang sumbu waktu → vektor fitur (40,) → reshape ke (1, 40)
        mfccs_scaled = np.mean(mfccs.T, axis=0).reshape(1, -1)

        # ── 4. Prediksi awal dengan model Random Forest ──
        prediction = int(model.predict(mfccs_scaled)[0])

        # ── 5. SISTEM HYBRID: Deteksi Durasi Kebisingan (Anti-Impulsive Noise) ──
        # Hitung energi RMS per frame (tidak langsung di-mean)
        rms_frames = librosa.feature.rms(y=y)[0]
        
        # Threshold batas volume suara keras
        RMS_THRESHOLD = 0.035 
        
        # Hitung berapa persen jumlah frame yang volumenya di atas threshold
        frames_di_atas_threshold = np.sum(rms_frames > RMS_THRESHOLD)
        persentase_durasi_bising = frames_di_atas_threshold / len(rms_frames)

        # Batasan durasi: Suara harus keras minimal 40% dari total durasi file
        # Ini otomatis akan memfilter suara gelas jatuh (yang kerasnya cuma < 10% durasi)
        MIN_DURATION_RATIO = 0.40

        # KONDISI A: Koreksi untuk Crowd Noise (Suara keras dan durasinya panjang)
        if persentase_durasi_bising >= MIN_DURATION_RATIO and prediction == 0:
            prediction = 1

        # KONDISI B: Koreksi untuk Gelas Jatuh / Tepuk Tangan (AI mengira bising, tapi durasi bisingnya terlalu singkat)
        elif persentase_durasi_bising < MIN_DURATION_RATIO and prediction == 1:
            prediction = 0
            
        # Hitung rata-rata RMS keseluruhan hanya untuk keperluan tampilan metadata
        rms_energy = float(np.mean(rms_frames))

        # ── 6. Ambil rekomendasi berbasis aturan ──
        rec = RECOMMENDATIONS[prediction]

        return {
            "status": "success",
            "class_result": prediction,
            "comfort_status": rec["comfort_status"],
            "iot_command": rec["iot_command"],
            "recommendation": rec["recommendation"],
            "metadata": {
                "filename": audio_file.filename,
                "sample_rate_hz": int(sr),
                "duration_seconds": round(float(len(y) / sr), 2),
                "mfcc_coefficients": 40,
                "rms_energy_level": round(rms_energy, 4), # Kita lampirkan agar bisa dipantau
                "noise_duration_ratio": round(persentase_durasi_bising, 2)
            },
        }

    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Kesalahan saat memproses audio: {str(e)}",
        )