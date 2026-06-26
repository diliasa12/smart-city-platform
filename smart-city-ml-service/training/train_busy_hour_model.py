import pandas as pd
from sklearn.ensemble import RandomForestRegressor
import joblib
import os

# Path resolusi konsisten dari lokasi script ini
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
data_path = os.path.join(BASE_DIR, 'data', 'room_occupancy.csv')
model_dir = os.path.join(BASE_DIR, 'models')

print(f"[Train BusyHour] Membaca dataset dari: {data_path}")

if not os.path.exists(data_path):
    print(f"ERROR: File tidak ditemukan di {data_path}")
    exit(1)

df = pd.read_csv(data_path)
df.columns = df.columns.str.strip()

# Preprocessing
df['Time'] = pd.to_datetime(df['Time'], format='%H:%M:%S').dt.hour

features = ['S1_Temp', 'S1_Light', 'S1_Sound', 'S5_CO2', 'S6_PIR']

missing = [f for f in features if f not in df.columns]
if missing:
    print(f"ERROR: Kolom berikut tidak ada di CSV: {missing}")
    print(f"Kolom tersedia: {list(df.columns)}")
    exit(1)

X = df[features]
y = df['Time']

# Training
model = RandomForestRegressor(n_estimators=100, random_state=42)
model.fit(X, y)
print(f"[Train BusyHour] Training selesai dengan {len(X)} sampel.")

# Save
os.makedirs(model_dir, exist_ok=True)
output_path = os.path.join(model_dir, 'busy_hour_model.pkl')
joblib.dump(model, output_path)
print(f"[Train BusyHour] Model disimpan ke: {output_path}")