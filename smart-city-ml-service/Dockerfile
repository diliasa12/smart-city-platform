# ── Stage 1: Builder (install deps + training) ──────────────────────────────
FROM python:3.11-slim AS builder

WORKDIR /app

# Install library sistem (librosa butuh libsndfile1)
RUN apt-get update && apt-get install -y libsndfile1 && rm -rf /var/lib/apt/lists/*

# Copy requirements dulu (untuk layer caching)
COPY requirements.txt .
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

# Copy seluruh source: data, training scripts, app code
COPY . .

# ── AUTO TRAINING ─────────────────────────────────────────────────────────────
# Jalankan kedua script training saat build.
# Output .pkl disimpan ke /app/models/ (sudah di-handle oleh script masing-masing).
RUN echo "[ML Build] Memulai training comfort model..." && \
    python training/train_comfort_model.py && \
    echo "[ML Build] Memulai training busy hour model..." && \
    python training/train_busy_hour_model.py && \
    echo "[ML Build] Semua model berhasil di-training!"

# Verifikasi .pkl ada sebelum lanjut
RUN ls -lh models/ && \
    test -f models/comfort_classifier.pkl || (echo "ERROR: comfort_classifier.pkl tidak terbentuk!" && exit 1) && \
    test -f models/busy_hour_model.pkl || (echo "ERROR: busy_hour_model.pkl tidak terbentuk!" && exit 1)


# ── Stage 2: Production image (lebih kecil, tanpa training overhead) ─────────
FROM python:3.11-slim AS runner

WORKDIR /app

RUN apt-get update && apt-get install -y libsndfile1 && rm -rf /var/lib/apt/lists/*

# Copy deps dari stage builder
COPY --from=builder /usr/local/lib/python3.11/site-packages /usr/local/lib/python3.11/site-packages
COPY --from=builder /usr/local/bin /usr/local/bin

# Copy source code
COPY --from=builder /app/app ./app
COPY --from=builder /app/requirements.txt .

# Copy MODEL yang sudah di-training (inilah inti dari perubahan ini)
COPY --from=builder /app/models ./models

EXPOSE 5000

HEALTHCHECK --interval=30s --timeout=10s --start-period=15s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://localhost:5000/health')" || exit 1

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "5000"]