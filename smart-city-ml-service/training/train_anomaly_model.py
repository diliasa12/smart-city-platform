import pandas as pd
from sklearn.ensemble import IsolationForest
import joblib
import os

# Path resolusi dari lokasi script
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
data_path = os.path.join(BASE_DIR, 'data', 'smart_ml_dataset.csv')
model_dir = os.path.join(BASE_DIR, 'models')

print(f"[Train Anomaly] Membaca dataset dari: {data_path}")

if not os.path.exists(data_path):
    print(f"ERROR: File tidak ditemukan di {data_path}")
    exit(1)

df = pd.read_csv(data_path)
df.columns = df.columns.str.strip()

# menggunakan suhu dan kebisingan sebagai fitur untuk mendeteksi anomali
# Jika data sensor melompat jauh dari pola normal kedua fitur ini, akan dianggap anomali
features = ['temperature', 'humidity', 'decibel_level', 'hour']

X = df[features]

# Training Isolation Forest
# contamination=0.02 berarti asumsikan ~2% data historis mungkin anomali
print("[Train Anomaly] Melatih model Isolation Forest...")
model = IsolationForest(n_estimators=100, contamination=0.05, random_state=42)
model.fit(X)
print(f"[Train Anomaly] Training selesai dengan {len(X)} sampel.")

# Save
os.makedirs(model_dir, exist_ok=True)
output_path = os.path.join(model_dir, 'anomaly_detector.pkl')
joblib.dump(model, output_path)
print(f"[Train Anomaly] Model disimpan ke: {output_path}")