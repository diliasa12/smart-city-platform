import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier
import joblib
import os

# Load dataset
data_path = os.path.join(os.path.dirname(__file__), '..', 'data', 'urban_noise_levels.csv')
df = pd.read_csv(data_path)

# Preprocessing
# definisikan ambang batas kebisingan (misal: di atas 70dB dianggap Bising)
df['is_noisy'] = (df['decibel_level'] > 70).astype(int)

# kolom fitur
features = [
   'temperature_c', 
    'humidity_%',
    'decibel_level',      
    'near_construction',
    'population_density', 
    'public_event', 
    'school_zone'
]

X = df[features]
y = df['is_noisy']

# Training Model
model = RandomForestClassifier(n_estimators=100, random_state=42)
model.fit(X, y)

# Save model
if not os.path.exists('models'):
    os.makedirs('models')

joblib.dump(model, 'models/comfort_classifier.pkl')
print("comfort_classifier.pkl berhasil di-update dengan data Urban Noise!")