import pandas as pd
from sklearn.ensemble import RandomForestRegressor
import joblib
import os

# Path resolusi dari lokasi script 
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
data_path = os.path.join(BASE_DIR, 'data', 'smart_ml_dataset.csv')
model_dir = os.path.join(BASE_DIR, 'models')

print(f"[Train BusyHour] Membaca dataset dari: {data_path}")

if not os.path.exists(data_path):
    print(f"ERROR: File tidak ditemukan di {data_path}")
    exit(1)

df = pd.read_csv(data_path)
df.columns = df.columns.str.strip()

# Preprocessing
features = ['temperature', 'humidity', 'decibel_level']

missing = [f for f in features if f not in df.columns]
if missing:
    print(f"ERROR: Kolom berikut tidak ada di CSV: {missing}")
    print(f"Kolom tersedia: {list(df.columns)}")
    exit(1)

X = df[features]
y = df['hour']

# Training
model = RandomForestRegressor(n_estimators=100, random_state=42)
model.fit(X, y)
print(f"[Train BusyHour] Training selesai dengan {len(X)} sampel.")

# Save
os.makedirs(model_dir, exist_ok=True)
output_path = os.path.join(model_dir, 'busy_hour_model.pkl')
joblib.dump(model, output_path)
print(f"[Train BusyHour] Model disimpan ke: {output_path}")