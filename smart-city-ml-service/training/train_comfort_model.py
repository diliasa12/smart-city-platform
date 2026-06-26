import os
import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder
from sklearn.ensemble import RandomForestClassifier
import joblib

def main():
    # Paths
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    csv_path = os.path.join(base_dir, "data", "smart_ml_dataset.csv")
    models_dir = os.path.join(base_dir, "models")
    
    # Buat folder models jika belum ada
    os.makedirs(models_dir, exist_ok=True)
    
    # Load dataset
    print(f"Memuat dataset dari {csv_path}...")
    try:
        df = pd.read_csv(csv_path)
    except FileNotFoundError:
        print(f"Error: Dataset {csv_path} tidak ditemukan!")
        return
    
    # Memisahkan Fitur (X) dan Target (y)
    X = df[['suhu', 'kelembaban', 'kebisingan', 'hour', 'is_weekend']]
    y_raw = df['ml_classification_status']
    
    # Encoding target string menjadi angka (0, 1, 2)
    print("Melakukan encoding pada variabel target...")
    le = LabelEncoder()
    y = le.fit_transform(y_raw)
    
    # Train-test split (80% train, 20% test)
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
    
    # Melatih model Random Forest
    print("Melatih Random Forest Classifier...")
    model = RandomForestClassifier(n_estimators=100, random_state=42)
    model.fit(X_train, y_train)
    
    # Evaluasi akurasi
    accuracy = model.score(X_test, y_test)
    print(f"Akurasi model pada data test: {accuracy * 100:.2f}%")
    
    # ─── DI SINI KITA SATUKAN KE DALAM DICTIONARY ───
    payload_to_save = {
        'model': model,
        'encoder': le
    }
    
    # Sesuaikan nama file ke rancangan awal: comfort_classifier.pkl
    model_path = os.path.join(models_dir, 'comfort_classifier.pkl')
    
    # Cukup dump satu file ini saja
    joblib.dump(payload_to_save, model_path)
    
    print(f"Model dan Encoder SUKSES disatukan ke: {model_path}")
    print("Pelatihan selesai!")

if __name__ == "__main__":
    main()
