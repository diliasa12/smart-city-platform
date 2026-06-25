import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier
import joblib
import os

# Path resolusi konsisten dari lokasi script ini
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
data_path = os.path.join(BASE_DIR, 'data', 'urban_noise_levels.csv')
model_dir = os.path.join(BASE_DIR, 'models')

print(f"[Train Comfort] Membaca dataset dari: {data_path}")

if not os.path.exists(data_path):
    print(f"ERROR: File tidak ditemukan di {data_path}")
    exit(1)

df = pd.read_csv(data_path)

# Preprocessing
df['is_noisy'] = (df['decibel_level'] > 70).astype(int)

features = [
    'temperature_c',
    'humidity_%',
    'decibel_level',
    'near_construction',
    'population_density',
    'public_event',
    'school_zone'
]

missing = [f for f in features if f not in df.columns]
if missing:
    print(f"ERROR: Kolom berikut tidak ada di CSV: {missing}")
    print(f"Kolom tersedia: {list(df.columns)}")
    exit(1)

X = df[features]
y = df['is_noisy']

# Training
model = RandomForestClassifier(n_estimators=100, random_state=42)
model.fit(X, y)
print(f"[Train Comfort] Training selesai. Akurasi OOB: model fitted dengan {len(X)} sampel.")

# Save
os.makedirs(model_dir, exist_ok=True)
output_path = os.path.join(model_dir, 'comfort_classifier.pkl')
joblib.dump(model, output_path)
print(f"[Train Comfort] Model disimpan ke: {output_path}")