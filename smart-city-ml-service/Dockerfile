# versi Python 
FROM python:3.11-slim

# direktori kerja di dalam container
WORKDIR /app

# Menyalin file requirements untuk caching
COPY requirements.txt .

# Menginstal library Python
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

# Menyalin seluruh kode dan folder models, data, dll ke dalam container
COPY . .

# Mengekspos port 5000 agar bisa diakses
EXPOSE 5000

# Menjalankan server Uvicorn
# CATATAN PENTING: host 0.0.0.0 wajib agar bisa diakses dari luar container Docker
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "5000"]