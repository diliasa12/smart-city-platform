"""
Smart City ML Service - Training Pipeline
Menggenerate data sintetis dan melatih 3 model ML untuk prediksi traffic, klasifikasi air quality, dan anomaly detection.

Struktur:
1. Generate synthetic data jika CSV belum ada
2. Train 3 models: RandomForest (traffic), GradientBoosting (air), IsolationForest (anomaly)
3. Save all models + scalers + encoders ke bundle: models/smartcity_models.pkl

Usage:
    python train_models.py
"""

import pandas as pd
import numpy as np
import joblib
import os
from pathlib import Path
from sklearn.ensemble import RandomForestRegressor, GradientBoostingClassifier, IsolationForest
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.metrics import r2_score, classification_report, accuracy_score
import warnings
warnings.filterwarnings('ignore')

# Ensure folders exist
Path("data").mkdir(exist_ok=True)
Path("models").mkdir(exist_ok=True)

print("=" * 80)
print("SMART CITY ML SERVICE - TRAINING PIPELINE")
print("=" * 80)

# ============================================================================
# 1. SYNTHETIC DATA GENERATORS
# ============================================================================

def generate_traffic_data(n_samples: int = 1000):
    """Generate synthetic traffic data jika file tidak ada."""
    filepath = "data/traffic_history.csv"
    
    if os.path.exists(filepath):
        print(f"✓ Loading: {filepath}")
        return pd.read_csv(filepath)
    
    print(f"\n→ {filepath} tidak ditemukan, generate synthetic data...")
    
    np.random.seed(42)
    locations = ['Sudirman', 'Thamrin', 'Gatot_Subroto', 'Kuningan', 'Tomang']
    
    data = {
        'hour': np.random.randint(0, 24, n_samples),
        'day_of_week': np.random.randint(0, 7, n_samples),
        'weather_code': np.random.randint(0, 4, n_samples),
        'prev_density': np.random.uniform(10, 90, n_samples),
        'location': np.random.choice(locations, n_samples),
        'vehicle_density': np.random.uniform(15, 95, n_samples),
    }
    
    df = pd.DataFrame(data)
    df.to_csv(filepath, index=False)
    print(f"✓ Saved: {filepath} ({len(df)} rows)")
    return df


def generate_air_quality_data(n_samples: int = 1000):
    """Generate synthetic air quality data jika file tidak ada."""
    filepath = "data/air_quality.csv"
    
    if os.path.exists(filepath):
        print(f"✓ Loading: {filepath}")
        return pd.read_csv(filepath)
    
    print(f"\n→ {filepath} tidak ditemukan, generate synthetic data...")
    
    np.random.seed(42)
    aqi_categories = ['Good', 'Moderate', 'Unhealthy', 'Hazardous']
    
    data = {
        'pm25': np.random.uniform(5, 200, n_samples),
        'pm10': np.random.uniform(10, 300, n_samples),
        'no2': np.random.uniform(10, 100, n_samples),
        'co': np.random.uniform(0.5, 10, n_samples),
        'o3': np.random.uniform(20, 150, n_samples),
        'temperature': np.random.uniform(15, 35, n_samples),
        'humidity': np.random.uniform(30, 90, n_samples),
        'aqi_category': np.random.choice(aqi_categories, n_samples),
    }
    
    df = pd.DataFrame(data)
    df.to_csv(filepath, index=False)
    print(f"✓ Saved: {filepath} ({len(df)} rows)")
    return df


def generate_sensor_data(n_samples: int = 1000):
    """Generate synthetic sensor data jika file tidak ada."""
    filepath = "data/sensor_readings.csv"
    
    if os.path.exists(filepath):
        print(f"✓ Loading: {filepath}")
        return pd.read_csv(filepath)
    
    print(f"\n→ {filepath} tidak ditemukan, generate synthetic data...")
    
    np.random.seed(42)
    
    data = {
        'sensor_value': np.random.uniform(20, 120, n_samples),
        'timestamp_hour': np.random.randint(0, 24, n_samples),
        'rolling_mean_1h': np.random.uniform(25, 115, n_samples),
        'z_score': np.abs(np.random.normal(0, 1, n_samples)),
    }
    
    df = pd.DataFrame(data)
    df.to_csv(filepath, index=False)
    print(f"✓ Saved: {filepath} ({len(df)} rows)")
    return df


# ============================================================================
# 2. LOAD/GENERATE DATA
# ============================================================================

print("\nSTEP 1: DATA PREPARATION")
print("-" * 80)

df_traffic = generate_traffic_data(1000)
df_air = generate_air_quality_data(1000)
df_sens = generate_sensor_data(1000)

# ============================================================================
# 3. MODEL TRAINING
# ============================================================================

print("\n\nSTEP 2: MODEL TRAINING")
print("-" * 80)

# ===== MODEL 1: Traffic Density Predictor =====
print("\n[1/3] Training Traffic Density Model (RandomForest)...")

TRAFFIC_FEATS = ['hour', 'day_of_week', 'weather_code', 'prev_density', 'location_enc']
le_loc = LabelEncoder()
df_traffic['location_enc'] = le_loc.fit_transform(df_traffic['location'])

scaler_t = StandardScaler()
X_t = scaler_t.fit_transform(df_traffic[TRAFFIC_FEATS])
y_t = df_traffic['vehicle_density']

mdl_t = RandomForestRegressor(n_estimators=200, max_depth=12, random_state=42, n_jobs=-1)
mdl_t.fit(X_t, y_t)

cv_t = cross_val_score(mdl_t, X_t, y_t, cv=5, scoring='r2')
train_r2_t = r2_score(y_t, mdl_t.predict(X_t))

print(f"  ✓ Traffic R² (CV): {cv_t.mean():.4f} ± {cv_t.std():.4f}")
print(f"  ✓ Traffic R² (train): {train_r2_t:.4f}")

# ===== MODEL 2: Air Quality Classifier =====
print("\n[2/3] Training Air Quality Classifier (GradientBoosting)...")

AIR_FEATS = ['pm25', 'pm10', 'no2', 'co', 'o3', 'temperature', 'humidity']
scaler_a = StandardScaler()
X_a = scaler_a.fit_transform(df_air[AIR_FEATS])

le_aqi = LabelEncoder()
y_a = le_aqi.fit_transform(df_air['aqi_category'])

mdl_a = GradientBoostingClassifier(n_estimators=150, learning_rate=0.1, random_state=42)
mdl_a.fit(X_a, y_a)

cv_a = cross_val_score(mdl_a, X_a, y_a, cv=5, scoring='accuracy')
train_acc_a = accuracy_score(y_a, mdl_a.predict(X_a))

print(f"  ✓ Air Quality Acc (CV): {cv_a.mean():.4f} ± {cv_a.std():.4f}")
print(f"  ✓ Air Quality Acc (train): {train_acc_a:.4f}")
print(f"  ✓ Classes: {le_aqi.classes_}")

# ===== MODEL 3: Anomaly Detector =====
print("\n[3/3] Training Anomaly Detector (IsolationForest)...")

ANOMALY_FEATS = ['sensor_value', 'timestamp_hour', 'rolling_mean_1h', 'z_score']
scaler_s = StandardScaler()
X_s = scaler_s.fit_transform(df_sens[ANOMALY_FEATS])

mdl_s = IsolationForest(n_estimators=200, contamination=0.05, random_state=42, n_jobs=-1)
mdl_s.fit(X_s)

anomalies_detected = (mdl_s.predict(X_s) == -1).sum()
print(f"  ✓ Anomalies detected: {anomalies_detected}/{len(X_s)} ({100*anomalies_detected/len(X_s):.2f}%)")

# ============================================================================
# 4. SAVE ALL MODELS TO BUNDLE
# ============================================================================

print("\n\nSTEP 3: SAVING MODELS BUNDLE")
print("-" * 80)

bundle = {
    'traffic': {
        'model': mdl_t,
        'scaler': scaler_t,
        'le_loc': le_loc,
        'features': TRAFFIC_FEATS
    },
    'air': {
        'model': mdl_a,
        'scaler': scaler_a,
        'le_aqi': le_aqi,
        'features': AIR_FEATS
    },
    'anomaly': {
        'model': mdl_s,
        'scaler': scaler_s,
        'features': ANOMALY_FEATS
    },
}

joblib.dump(bundle, 'models/smartcity_models.pkl')

bundle_size = os.path.getsize('models/smartcity_models.pkl') / 1024
print(f"\n✓ All models saved → models/smartcity_models.pkl")
print(f"  Size: {bundle_size:.2f} KB")
print(f"\n  Bundle contents:")
print(f"    - traffic: RandomForest + scaler + location_encoder")
print(f"    - air: GradientBoosting + scaler + aqi_encoder")
print(f"    - anomaly: IsolationForest + scaler")

print("\n" + "=" * 80)
print("✓ TRAINING COMPLETED SUCCESSFULLY!")
print("=" * 80)
print(f"\nData files: data/")
print(f"  - traffic_history.csv ({len(df_traffic)} rows)")
print(f"  - air_quality.csv ({len(df_air)} rows)")
print(f"  - sensor_readings.csv ({len(df_sens)} rows)")
print(f"\nModels bundle: models/smartcity_models.pkl")
print(f"\nReady to use in FastAPI server!")
print("=" * 80)
