"""
Training Script untuk 3 Model Machine Learning
Smart City Platform - ML Service

Fungsi:
1. Load data dari CSV atau generate synthetic data jika file tidak ada
2. Train 3 models (Traffic, Air Quality, Anomaly Detection)
3. Evaluate models dengan metrics
4. Save models ke satu bundle file: 'models/smartcity_models.pkl'

Penggunaan:
    python train_models.py

Models yang dihasilkan:
    - models/smartcity_models.pkl (Bundle: Traffic + Air Quality + Anomaly models)

Data Files:
    - data/traffic_history.csv (dihasilkan jika tidak ada)
    - data/air_quality_history.csv (dihasilkan jika tidak ada)
    - data/sensor_anomaly_history.csv (dihasilkan jika tidak ada)

Author: Python ML Service
Version: 2.0.0
"""

import os
import sys
import numpy as np
import pandas as pd
import joblib
from pathlib import Path
from datetime import datetime, timedelta

# Machine Learning imports
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestRegressor, GradientBoostingClassifier, IsolationForest
from sklearn.preprocessing import LabelEncoder, StandardScaler
from sklearn.metrics import mean_squared_error, r2_score, classification_report, confusion_matrix, accuracy_score
import warnings
warnings.filterwarnings('ignore')

# ============================================================================
# CONFIGURATION
# ============================================================================

RANDOM_STATE = 42
TEST_SIZE = 0.2
MODELS_DIR = "models"
DATA_DIR = "data"
BUNDLE_FILE = os.path.join(MODELS_DIR, "smartcity_models.pkl")

# CSV file paths
TRAFFIC_CSV = os.path.join(DATA_DIR, "traffic_history.csv")
AIR_QUALITY_CSV = os.path.join(DATA_DIR, "air_quality_history.csv")
SENSOR_ANOMALY_CSV = os.path.join(DATA_DIR, "sensor_anomaly_history.csv")

# Ensure directories exist
Path(MODELS_DIR).mkdir(parents=True, exist_ok=True)
Path(DATA_DIR).mkdir(parents=True, exist_ok=True)

print("=" * 80)
print("PYTHON ML SERVICE - MODEL TRAINING SCRIPT v2.0")
print("=" * 80)


# ============================================================================
# 0. DATA LOADING / GENERATION
# ============================================================================

def load_or_generate_traffic_data(n_samples: int = 1000) -> pd.DataFrame:
    """
    Load traffic data dari CSV, atau generate synthetic jika file tidak ada.
    Jika generate, otomatis simpan ke CSV.
    
    Returns:
        DataFrame dengan traffic data
    """
    # Coba load dari CSV
    if os.path.exists(TRAFFIC_CSV):
        print(f"✓ Loading traffic data dari: {TRAFFIC_CSV}")
        df = pd.read_csv(TRAFFIC_CSV)
        print(f"  Loaded: {df.shape[0]} records")
        return df
    
    # Jika CSV tidak ada, generate synthetic
    print(f"ℹ Traffic CSV tidak ditemukan, generate synthetic data...")
    df = generate_synthetic_traffic_data(n_samples)
    
    # Otomatis simpan ke CSV
    df.to_csv(TRAFFIC_CSV, index=False)
    print(f"✓ Traffic data disimpan ke: {TRAFFIC_CSV}")
    return df


def load_or_generate_air_quality_data(n_samples: int = 1000) -> pd.DataFrame:
    """
    Load air quality data dari CSV, atau generate synthetic jika file tidak ada.
    Jika generate, otomatis simpan ke CSV.
    
    Returns:
        DataFrame dengan air quality data
    """
    # Coba load dari CSV
    if os.path.exists(AIR_QUALITY_CSV):
        print(f"✓ Loading air quality data dari: {AIR_QUALITY_CSV}")
        df = pd.read_csv(AIR_QUALITY_CSV)
        print(f"  Loaded: {df.shape[0]} records")
        return df
    
    # Jika CSV tidak ada, generate synthetic
    print(f"ℹ Air quality CSV tidak ditemukan, generate synthetic data...")
    df = generate_synthetic_air_quality_data(n_samples)
    
    # Otomatis simpan ke CSV
    df.to_csv(AIR_QUALITY_CSV, index=False)
    print(f"✓ Air quality data disimpan ke: {AIR_QUALITY_CSV}")
    return df


def load_or_generate_sensor_anomaly_data(n_samples: int = 1000) -> pd.DataFrame:
    """
    Load sensor anomaly data dari CSV, atau generate synthetic jika file tidak ada.
    Jika generate, otomatis simpan ke CSV.
    
    Returns:
        DataFrame dengan sensor anomaly data
    """
    # Coba load dari CSV
    if os.path.exists(SENSOR_ANOMALY_CSV):
        print(f"✓ Loading sensor anomaly data dari: {SENSOR_ANOMALY_CSV}")
        df = pd.read_csv(SENSOR_ANOMALY_CSV)
        print(f"  Loaded: {df.shape[0]} records")
        return df
    
    # Jika CSV tidak ada, generate synthetic
    print(f"ℹ Sensor anomaly CSV tidak ditemukan, generate synthetic data...")
    df = generate_synthetic_sensor_anomaly_data(n_samples)
    
    # Otomatis simpan ke CSV
    df.to_csv(SENSOR_ANOMALY_CSV, index=False)
    print(f"✓ Sensor anomaly data disimpan ke: {SENSOR_ANOMALY_CSV}")
    return df




# ============================================================================
# 1. SYNTHETIC DATA GENERATION
# ============================================================================

def generate_synthetic_traffic_data(n_samples: int = 1000) -> pd.DataFrame:
    """
    Generate synthetic traffic data untuk model training.
    
    Features:
    - hour: Jam dalam sehari (0-23)
    - day_of_week: Hari dalam seminggu (0-6)
    - weather_code: Kode cuaca (0-3)
    - prev_density: Kepadatan sebelumnya (0-100)
    - location: Lokasi (string, akan di-encode)
    - vehicle_density: Target - kepadatan traffic (0-100)
    
    Returns:
        DataFrame dengan synthetic traffic data
    """
    print(f"\n[1/3] Generating synthetic traffic data ({n_samples} samples)...")
    
    np.random.seed(RANDOM_STATE)
    
    locations = ['downtown', 'highway', 'suburb', 'residential', 'airport']
    
    data = {
        'hour': np.random.randint(0, 24, n_samples),
        'day_of_week': np.random.randint(0, 7, n_samples),
        'weather_code': np.random.randint(0, 4, n_samples),
        'prev_density': np.random.uniform(0, 100, n_samples),
        'location': np.random.choice(locations, n_samples),
    }
    
    df = pd.DataFrame(data)
    
    # Generate target vehicle_density dengan logic sederhana
    # Rush hour (7-9, 16-18) + bad weather = kepadatan tinggi
    rush_hour_mask = ((df['hour'] >= 7) & (df['hour'] <= 9)) | ((df['hour'] >= 16) & (df['hour'] <= 18))
    bad_weather_mask = df['weather_code'] >= 2
    
    df['vehicle_density'] = (
        df['prev_density'] * 0.6 +  # 60% dari density sebelumnya
        np.random.normal(20, 10, n_samples) +  # random noise
        (30 if rush_hour_mask.any() else 0) +  # tambah 30 saat rush hour
        (15 if bad_weather_mask.any() else 0)   # tambah 15 saat cuaca buruk
    )
    
    # Clip ke range 0-100
    df['vehicle_density'] = np.clip(df['vehicle_density'], 0, 100)
    
    print(f"✓ Traffic data generated: {df.shape}")
    return df


def generate_synthetic_air_quality_data(n_samples: int = 1000) -> pd.DataFrame:
    """
    Generate synthetic air quality data untuk model training.
    
    Features:
    - pm25, pm10: Particulate matter (µg/m³)
    - no2, co, o3: Gas pollutants (ppb/ppm)
    - temperature: Suhu (°C)
    - humidity: Kelembaban (%)
    - aqi_category: Target - kategori AQI (text: Good, Moderate, Unhealthy, Hazardous)
    
    Returns:
        DataFrame dengan synthetic air quality data
    """
    print(f"[1/3] Generating synthetic air quality data ({n_samples} samples)...")
    
    np.random.seed(RANDOM_STATE)
    
    data = {
        'pm25': np.random.uniform(5, 200, n_samples),
        'pm10': np.random.uniform(10, 300, n_samples),
        'no2': np.random.uniform(10, 100, n_samples),
        'co': np.random.uniform(0.5, 10, n_samples),
        'o3': np.random.uniform(20, 150, n_samples),
        'temperature': np.random.uniform(15, 35, n_samples),
        'humidity': np.random.uniform(30, 90, n_samples),
    }
    
    df = pd.DataFrame(data)
    
    # Generate target kategori AQI berdasarkan PM2.5 (simplified logic)
    def categorize_aqi(pm25):
        if pm25 < 35:
            return 'Good'
        elif pm25 < 75:
            return 'Moderate'
        elif pm25 < 115:
            return 'Unhealthy'
        else:
            return 'Hazardous'
    
    df['aqi_category'] = df['pm25'].apply(categorize_aqi)
    
    print(f"✓ Air quality data generated: {df.shape}")
    return df


def generate_synthetic_sensor_anomaly_data(n_samples: int = 1000) -> pd.DataFrame:
    """
    Generate synthetic sensor data untuk anomaly detection.
    
    Features:
    - sensor_value: Nilai sensor raw
    - timestamp_hour: Jam pengukuran (0-23)
    - rolling_mean_1h: Moving average 1 jam
    - z_score: Z-score untuk anomaly detection
    - is_anomaly: Target - boolean apakah anomali
    
    Returns:
        DataFrame dengan synthetic sensor data
    """
    print(f"[1/3] Generating synthetic sensor anomaly data ({n_samples} samples)...")
    
    np.random.seed(RANDOM_STATE)
    
    # Generate normal data
    normal_data = np.random.normal(50, 10, n_samples)
    
    # Inject some anomalies (20% dari total)
    n_anomalies = int(n_samples * 0.2)
    anomaly_indices = np.random.choice(n_samples, n_anomalies, replace=False)
    normal_data[anomaly_indices] = np.random.uniform(80, 150, n_anomalies)
    
    data = {
        'sensor_value': np.clip(normal_data, 0, 200),
        'timestamp_hour': np.random.randint(0, 24, n_samples),
        'rolling_mean_1h': normal_data + np.random.normal(0, 2, n_samples),
        'z_score': np.abs(np.random.normal(1, 0.5, n_samples)),
    }
    
    df = pd.DataFrame(data)
    
    # Create is_anomaly target (simplified: z_score > 2 or deviation > 30)
    df['is_anomaly'] = (
        (np.abs(df['sensor_value'] - df['rolling_mean_1h']) > 30) | 
        (df['z_score'] > 2.5)
    ).astype(int)
    
    print(f"✓ Sensor anomaly data generated: {df.shape}")
    return df



# ============================================================================
# 2. MODEL TRAINING
# ============================================================================

def train_traffic_model(df: pd.DataFrame) -> tuple:
    """
    Train Random Forest Regressor untuk traffic density prediction.
    
    Args:
        df: DataFrame dengan traffic data
        
    Returns:
        Tuple (model, X_test, y_test, feature_names, scaler, le_location)
    """
    print("\n" + "=" * 80)
    print("MODEL 1: TRAFFIC DENSITY PREDICTION (Random Forest)")
    print("=" * 80)
    
    # Prepare features - encode 'location' dengan LabelEncoder
    df_copy = df.copy()
    le_location = LabelEncoder()
    df_copy['location_enc'] = le_location.fit_transform(df_copy['location'])
    
    X = df_copy[['hour', 'day_of_week', 'weather_code', 'prev_density', 'location_enc']]
    y = df_copy['vehicle_density']
    
    feature_names = ['hour', 'day_of_week', 'weather_code', 'prev_density', 'location_enc']
    
    print(f"Features shape: {X.shape}")
    print(f"Target shape: {y.shape}")
    print(f"Features: {feature_names}")
    print(f"Location classes: {le_location.classes_}")
    
    # Normalize features
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    
    # Train-test split
    X_train, X_test, y_train, y_test = train_test_split(
        X_scaled, y, test_size=TEST_SIZE, random_state=RANDOM_STATE
    )
    
    print(f"Training samples: {X_train.shape[0]}, Test samples: {X_test.shape[0]}")
    
    # Train model
    print("\nTraining Random Forest model...")
    model = RandomForestRegressor(
        n_estimators=100,
        max_depth=15,
        min_samples_split=5,
        random_state=RANDOM_STATE,
        n_jobs=-1,
        verbose=0
    )
    model.fit(X_train, y_train)
    
    # Evaluate
    y_pred = model.predict(X_test)
    mse = mean_squared_error(y_test, y_pred)
    rmse = np.sqrt(mse)
    r2 = r2_score(y_test, y_pred)
    
    print(f"\n✓ Model trained successfully")
    print(f"  MSE:  {mse:.4f}")
    print(f"  RMSE: {rmse:.4f}")
    print(f"  R²:   {r2:.4f}")
    
    # Feature importance
    feature_importance = pd.DataFrame({
        'feature': feature_names,
        'importance': model.feature_importances_
    }).sort_values('importance', ascending=False)
    
    print(f"\nFeature Importance:")
    print(feature_importance.to_string(index=False))
    
    return model, X_test, y_test, feature_names, scaler, le_location


def train_air_quality_model(df: pd.DataFrame) -> tuple:
    """
    Train Gradient Boosting Classifier untuk air quality classification.
    
    Args:
        df: DataFrame dengan air quality data
        
    Returns:
        Tuple (model, X_test, y_test, feature_names, scaler, le_aqi)
    """
    print("\n" + "=" * 80)
    print("MODEL 2: AIR QUALITY CLASSIFICATION (Gradient Boosting)")
    print("=" * 80)
    
    # Prepare features dan target
    X = df[['pm25', 'pm10', 'no2', 'co', 'o3', 'temperature', 'humidity']]
    y = df['aqi_category']
    
    feature_names = ['pm25', 'pm10', 'no2', 'co', 'o3', 'temperature', 'humidity']
    
    print(f"Features shape: {X.shape}")
    print(f"Target shape: {y.shape}")
    print(f"Features: {feature_names}")
    
    # LabelEncode aqi_category (text to numeric)
    le_aqi = LabelEncoder()
    y_encoded = le_aqi.fit_transform(y)
    
    label_mapping = {idx: label for idx, label in enumerate(le_aqi.classes_)}
    print(f"AQI Classes: {label_mapping}")
    
    # Class distribution
    print(f"\nClass distribution:")
    for class_idx, class_name in label_mapping.items():
        count = (y_encoded == class_idx).sum()
        print(f"  {class_name}: {count} samples")
    
    # Normalize features
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    
    # Train-test split dengan stratify
    X_train, X_test, y_train, y_test = train_test_split(
        X_scaled, y_encoded, test_size=TEST_SIZE, random_state=RANDOM_STATE, stratify=y_encoded
    )
    
    print(f"Training samples: {X_train.shape[0]}, Test samples: {X_test.shape[0]}")
    
    # Train model
    print("\nTraining Gradient Boosting model...")
    model = GradientBoostingClassifier(
        n_estimators=100,
        learning_rate=0.1,
        max_depth=5,
        random_state=RANDOM_STATE,
        verbose=0
    )
    model.fit(X_train, y_train)
    
    # Evaluate
    y_pred = model.predict(X_test)
    accuracy = accuracy_score(y_test, y_pred)
    
    print(f"\n✓ Model trained successfully")
    print(f"  Accuracy: {accuracy:.4f}")
    
    print(f"\nClassification Report:")
    print(classification_report(y_test, y_pred, target_names=list(label_mapping.values())))
    
    print(f"\nConfusion Matrix:")
    cm = confusion_matrix(y_test, y_pred)
    print(cm)
    
    return model, X_test, y_test, feature_names, scaler, le_aqi


def train_anomaly_model(df: pd.DataFrame) -> tuple:
    """
    Train Isolation Forest untuk anomaly detection (unsupervised).
    
    Args:
        df: DataFrame dengan sensor data
        
    Returns:
        Tuple (model, X_test, y_test, feature_names, scaler)
    """
    print("\n" + "=" * 80)
    print("MODEL 3: ANOMALY DETECTION (Isolation Forest - Unsupervised)")
    print("=" * 80)
    
    # Prepare features (unsupervised - tidak ada target saat training)
    X = df[['sensor_value', 'timestamp_hour', 'rolling_mean_1h', 'z_score']]
    y_true = df['is_anomaly']
    
    feature_names = ['sensor_value', 'timestamp_hour', 'rolling_mean_1h', 'z_score']
    
    print(f"Features shape: {X.shape}")
    print(f"Features: {feature_names}")
    
    # Anomaly distribution
    print(f"\nAnomaly distribution:")
    print(f"  Normal: {(y_true == 0).sum()} samples")
    print(f"  Anomaly: {(y_true == 1).sum()} samples")
    
    # Normalize features
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    
    # Train-test split (untuk evaluasi)
    X_train, X_test, y_true_train, y_true_test = train_test_split(
        X_scaled, y_true, test_size=TEST_SIZE, random_state=RANDOM_STATE
    )
    
    print(f"Training samples: {X_train.shape[0]}, Test samples: {X_test.shape[0]}")
    
    # Train model (Unsupervised - tidak butuh y)
    print("\nTraining Isolation Forest model...")
    model = IsolationForest(
        n_estimators=100,
        contamination=0.1,
        random_state=RANDOM_STATE,
        n_jobs=-1
    )
    model.fit(X_train)
    
    # Evaluate (menggunakan label untuk evaluasi saja)
    y_pred = model.predict(X_test)  # Returns -1 for anomalies, 1 for normal
    y_pred_binary = (y_pred == -1).astype(int)  # Convert to 1=anomaly, 0=normal
    
    accuracy = accuracy_score(y_true_test, y_pred_binary)
    
    print(f"\n✓ Model trained successfully")
    print(f"  Accuracy (pada test set): {accuracy:.4f}")
    
    print(f"\nClassification Report:")
    print(classification_report(y_true_test, y_pred_binary, target_names=['Normal', 'Anomaly']))
    
    print(f"\nConfusion Matrix:")
    cm = confusion_matrix(y_true_test, y_pred_binary)
    print(cm)
    
    return model, X_test, y_true_test, feature_names, scaler


# ============================================================================
# 3. MODEL BUNDLE SAVING
# ============================================================================

def save_models_bundle(traffic_model, traffic_scaler, le_location,
                       aq_model, aq_scaler, le_aqi,
                       anomaly_model, anomaly_scaler):
    """
    Save SEMUA models, scalers, dan LabelEncoders ke SATU bundle file.
    
    Args:
        traffic_model: Trained traffic model
        traffic_scaler: Traffic scaler
        le_location: LabelEncoder untuk traffic location
        aq_model: Trained air quality model
        aq_scaler: Air quality scaler
        le_aqi: LabelEncoder untuk air quality category
        anomaly_model: Trained anomaly model
        anomaly_scaler: Anomaly scaler
    """
    
    # Struktur bundle dict sesuai spesifikasi user
    bundle = {
        'traffic': {
            'model': traffic_model,
            'scaler': traffic_scaler,
            'le_loc': le_location,
            'features': ['hour', 'day_of_week', 'weather_code', 'prev_density', 'location_enc']
        },
        'air': {
            'model': aq_model,
            'scaler': aq_scaler,
            'le_aqi': le_aqi,
            'features': ['pm25', 'pm10', 'no2', 'co', 'o3', 'temperature', 'humidity']
        },
        'anomaly': {
            'model': anomaly_model,
            'scaler': anomaly_scaler,
            'features': ['sensor_value', 'timestamp_hour', 'rolling_mean_1h', 'z_score']
        }
    }
    
    # Save bundle ke single file
    joblib.dump(bundle, BUNDLE_FILE)
    
    file_size = os.path.getsize(BUNDLE_FILE) / 1024  # KB
    print(f"✓ Models bundle saved: {BUNDLE_FILE}")
    print(f"  Size: {file_size:.2f} KB")
    print(f"\n  Bundle contents:")
    print(f"    - traffic: model + scaler + location_encoder")
    print(f"    - air: model + scaler + aqi_encoder")
    print(f"    - anomaly: model + scaler")


# ============================================================================
# 4. MAIN TRAINING PIPELINE
# ============================================================================

def main():
    """Main function untuk menjalankan training pipeline"""
    
    try:
        # ===== STEP 1: Load atau Generate Data =====
        print("\n" + "=" * 80)
        print("STEP 1: LOADING/GENERATING DATA")
        print("=" * 80)
        
        traffic_data = load_or_generate_traffic_data(n_samples=1000)
        aq_data = load_or_generate_air_quality_data(n_samples=1000)
        sensor_data = load_or_generate_sensor_anomaly_data(n_samples=1000)
        
        # ===== STEP 2: Train Models =====
        print("\n" + "=" * 80)
        print("STEP 2: TRAINING MODELS")
        print("=" * 80)
        
        traffic_model, _, _, _, traffic_scaler, le_location = train_traffic_model(traffic_data)
        aq_model, _, _, _, aq_scaler, le_aqi = train_air_quality_model(aq_data)
        anomaly_model, _, _, _, anomaly_scaler = train_anomaly_model(sensor_data)
        
        # ===== STEP 3: Save Models Bundle =====
        print("\n" + "=" * 80)
        print("STEP 3: SAVING MODELS BUNDLE")
        print("=" * 80)
        
        save_models_bundle(
            traffic_model, traffic_scaler, le_location,
            aq_model, aq_scaler, le_aqi,
            anomaly_model, anomaly_scaler
        )
        
        # ===== SUMMARY =====
        print("\n" + "=" * 80)
        print("TRAINING COMPLETED SUCCESSFULLY!")
        print("=" * 80)
        print(f"\n✓ All models trained and bundled.")
        print(f"✓ Bundle location: {os.path.abspath(BUNDLE_FILE)}")
        print(f"✓ Data files location: {os.path.abspath(DATA_DIR)}/")
        
        print(f"\nData files generated/loaded:")
        print(f"  - traffic_history.csv")
        print(f"  - air_quality_history.csv")
        print(f"  - sensor_anomaly_history.csv")
        
        print(f"\nBundle file structure:")
        print(f"  smartcity_models.pkl contains:")
        print(f"    → traffic (RandomForest + scaler + location_encoder)")
        print(f"    → air (GradientBoosting + scaler + aqi_encoder)")
        print(f"    → anomaly (IsolationForest + scaler)")
        
        print(f"\n✓ Ready to use with FastAPI in main.py")
        print(f"\n  Usage in main.py:")
        print(f"    bundle = joblib.load('models/smartcity_models.pkl')")
        print(f"    traffic_model = bundle['traffic']['model']")
        print(f"    traffic_scaler = bundle['traffic']['scaler']")
        print(f"    le_location = bundle['traffic']['le_loc']")
        print("=" * 80)
        
        return True
    
    except Exception as e:
        print(f"\n✗ Error during training: {str(e)}")
        import traceback
        traceback.print_exc()
        return False


if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)

