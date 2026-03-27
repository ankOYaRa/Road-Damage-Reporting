"""
CNN Prediction Script - Sistem Laporan Kerusakan Jalan
======================================================
Dipanggil oleh Laravel (CnnService.php) melalui subprocess.

Cara pakai:
    python cnn/predict.py <path/to/image.jpg>

Output JSON ke stdout:
    {"status": "valid",   "confidence": 0.9231}   ← foto kerusakan jalan
    {"status": "invalid", "confidence": 0.1234}   ← bukan foto kerusakan

Exit codes:
    0 = sukses
    1 = error (model tidak ada, file tidak ada, dll.)
"""

import sys
import json
import pathlib
import os

# Paksa TF hanya log error saja (tidak muncul warning panjang)
os.environ["TF_CPP_MIN_LOG_LEVEL"] = "3"

import numpy as np

BASE_DIR    = pathlib.Path(__file__).parent
MODEL_PATH  = BASE_DIR / "model.keras"
IMG_SIZE    = 224
THRESHOLD   = 0.5   # ≥ 0.5 → valid (kerusakan terdeteksi)


def load_model():
    """Muat model dari disk. Keluar dengan error jika belum dilatih."""
    if not MODEL_PATH.exists():
        error_out(
            f"Model belum ditemukan di {MODEL_PATH}. "
            "Jalankan dulu: python cnn/train.py"
        )

    import tensorflow as tf
    tf.get_logger().setLevel("ERROR")

    try:
        model = tf.keras.models.load_model(str(MODEL_PATH))
        return model
    except Exception as e:
        error_out(f"Gagal memuat model: {e}")


def preprocess_image(image_path: str) -> np.ndarray:
    """Baca dan pra-proses gambar agar sesuai input model."""
    import tensorflow as tf

    path = pathlib.Path(image_path)
    if not path.exists():
        error_out(f"File gambar tidak ditemukan: {image_path}")

    try:
        img = tf.keras.utils.load_img(str(path), target_size=(IMG_SIZE, IMG_SIZE))
        arr = tf.keras.utils.img_to_array(img)           # (224, 224, 3)
        arr = arr / 255.0                                 # normalize ke [0,1]
        arr = np.expand_dims(arr, axis=0)                 # (1, 224, 224, 3)
        return arr
    except Exception as e:
        error_out(f"Gagal memproses gambar: {e}")


def predict(image_path: str) -> dict:
    """Jalankan inferensi dan kembalikan dict hasil prediksi."""
    model = load_model()
    img_array = preprocess_image(image_path)

    prob = float(model.predict(img_array, verbose=0)[0][0])

    if prob >= THRESHOLD:
        return {"status": "valid",   "confidence": round(prob, 4)}
    else:
        # Untuk "invalid", confidence adalah keyakinan bahwa foto TIDAK valid.
        # Kita tetap kembalikan nilai probabilitas mentah agar Laravel bisa menampilkannya.
        return {"status": "invalid", "confidence": round(prob, 4)}


def error_out(message: str):
    """Cetak JSON error dan keluar dengan kode 1."""
    print(json.dumps({"error": message, "status": "invalid", "confidence": 0.0}))
    sys.exit(1)


def main():
    if len(sys.argv) < 2:
        error_out("Usage: python predict.py <image_path>")

    image_path = sys.argv[1]

    try:
        result = predict(image_path)
        print(json.dumps(result))
        sys.exit(0)
    except Exception as e:
        error_out(str(e))


if __name__ == "__main__":
    main()
