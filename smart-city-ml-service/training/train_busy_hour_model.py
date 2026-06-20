import pandas as pd
from sklearn.ensemble import RandomForestRegressor
import joblib
import os


base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
data_path = os.path.join(base_dir, 'data', 'room_occupancy.csv')

print(f"Mencoba membaca file dari: {data_path}")

if not os.path.exists(data_path):
    print(f"ERROR: File tidak ditemukan di {data_path}. Tolong cek lagi lokasinya!")
    exit()

df = pd.read_csv(data_path)

df.columns = df.columns.str.strip()

# Preprocessing
df['Time'] = pd.to_datetime(df['Time'], format='%H:%M:%S').dt.hour

features = ['S1_Temp', 'S2_Temp', 'S3_Temp', 'S4_Temp', 
            'S1_Light', 'S2_Light', 'S3_Light', 'S4_Light', 
            'S1_Sound', 'S2_Sound', 'S3_Sound', 'S4_Sound', 
            'S5_CO2', 'S6_PIR', 'S7_PIR']

# fitur di dataframe
missing_features = [f for f in features if f not in df.columns]
if missing_features:
    print(f"ERROR: Kolom berikut tidak ada di CSV: {missing_features}")
    exit()

X = df[features]
y = df['Time']

# Training
model = RandomForestRegressor(n_estimators=100, random_state=42)
model.fit(X, y)

# Save
model_dir = os.path.join(base_dir, 'models')
if not os.path.exists(model_dir):
    os.makedirs(model_dir)

joblib.dump(model, os.path.join(model_dir, 'busy_hour_forecaster.pkl'))
print("busy_hour_forecaster.pkl berhasil dibuat!")