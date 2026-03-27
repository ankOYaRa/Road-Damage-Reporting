"""
CNN Training Script - Sistem Laporan Kerusakan Jalan
=====================================================
Arsitektur : MobileNetV2 (Transfer Learning) + Fine-tuning
Klasifikasi : Binary  →  valid (kerusakan jalan) / invalid (bukan kerusakan)
Input       : 224 × 224 × 3  (RGB)
Framework   : TensorFlow / Keras

Struktur Dataset yang diharapkan:
    cnn/
    ├── dataset/
    │   ├── valid/          ← foto yang menunjukkan kerusakan jalan
    │   │   ├── img_001.jpg
    │   │   └── ...
    │   └── invalid/        ← foto bukan kerusakan / tidak relevan
    │       ├── img_001.jpg
    │       └── ...
    └── train.py

Cara Pakai:
    pip install -r requirements.txt
    python cnn/train.py
"""

import os
import sys
import json
import pathlib
import numpy as np
import tensorflow as tf
from tensorflow import keras
from tensorflow.keras import layers
from tensorflow.keras.applications import MobileNetV2
from tensorflow.keras.preprocessing.image import ImageDataGenerator
from sklearn.metrics import classification_report, confusion_matrix
import matplotlib
matplotlib.use('Agg')  # non-interactive backend
import matplotlib.pyplot as plt

# ─── Konfigurasi ──────────────────────────────────────────────────────────────
BASE_DIR     = pathlib.Path(__file__).parent
DATASET_DIR  = BASE_DIR / "dataset"
MODEL_PATH   = BASE_DIR / "model.keras"
HISTORY_PATH = BASE_DIR / "training_history.json"
PLOT_PATH    = BASE_DIR / "training_plot.png"

IMG_SIZE     = 224
BATCH_SIZE   = 32
EPOCHS_FROZEN   = 10   # tahap 1: hanya melatih head (base frozen)
EPOCHS_FINETUNE = 10   # tahap 2: fine-tune lapisan atas MobileNetV2
LEARNING_RATE   = 1e-4
FINETUNE_LR     = 1e-5
VALIDATION_SPLIT= 0.2
SEED            = 42

# Label: 0 = invalid, 1 = valid
CLASS_NAMES = ["invalid", "valid"]


def check_dataset():
    """Validasi struktur dataset sebelum training."""
    for cls in CLASS_NAMES:
        cls_dir = DATASET_DIR / cls
        if not cls_dir.exists():
            print(f"[ERROR] Folder dataset tidak ditemukan: {cls_dir}")
            print("Buat folder cnn/dataset/valid/ dan cnn/dataset/invalid/ lalu isi dengan foto.")
            sys.exit(1)
        count = len(list(cls_dir.glob("*.[jJpPwW][pPnNgGeE]*")))
        print(f"  [{cls:>8}] {count} gambar ditemukan")
        if count < 10:
            print(f"[WARNING] Disarankan minimal 10 gambar per kelas (saat ini {count}).")
    print()


def build_model(trainable_base: bool = False) -> keras.Model:
    """
    Bangun model CNN dengan MobileNetV2 sebagai base model.
    Head: GlobalAveragePooling → Dense(256) → Dropout → Dense(1, sigmoid)
    """
    base = MobileNetV2(
        input_shape=(IMG_SIZE, IMG_SIZE, 3),
        include_top=False,
        weights="imagenet",
    )
    base.trainable = trainable_base

    inputs = keras.Input(shape=(IMG_SIZE, IMG_SIZE, 3))
    x = tf.keras.applications.mobilenet_v2.preprocess_input(inputs)
    x = base(x, training=trainable_base)
    x = layers.GlobalAveragePooling2D()(x)
    x = layers.Dense(256, activation="relu")(x)
    x = layers.Dropout(0.4)(x)
    outputs = layers.Dense(1, activation="sigmoid")(x)

    return keras.Model(inputs, outputs)


def get_data_generators():
    """Buat data generator dengan augmentasi untuk training."""
    train_gen = ImageDataGenerator(
        rescale=1.0 / 255,
        validation_split=VALIDATION_SPLIT,
        rotation_range=20,
        width_shift_range=0.15,
        height_shift_range=0.15,
        shear_range=0.1,
        zoom_range=0.2,
        horizontal_flip=True,
        brightness_range=[0.8, 1.2],
        fill_mode="nearest",
    )

    val_gen = ImageDataGenerator(
        rescale=1.0 / 255,
        validation_split=VALIDATION_SPLIT,
    )

    train_flow = train_gen.flow_from_directory(
        DATASET_DIR,
        target_size=(IMG_SIZE, IMG_SIZE),
        batch_size=BATCH_SIZE,
        class_mode="binary",
        classes=CLASS_NAMES,
        subset="training",
        seed=SEED,
    )

    val_flow = val_gen.flow_from_directory(
        DATASET_DIR,
        target_size=(IMG_SIZE, IMG_SIZE),
        batch_size=BATCH_SIZE,
        class_mode="binary",
        classes=CLASS_NAMES,
        subset="validation",
        seed=SEED,
    )

    print(f"  Class indices: {train_flow.class_indices}")
    print(f"  Training   samples: {train_flow.samples}")
    print(f"  Validation samples: {val_flow.samples}\n")

    return train_flow, val_flow


def plot_history(histories: list, output_path: str):
    """Simpan grafik accuracy dan loss training ke file PNG."""
    acc  = []
    val_acc  = []
    loss = []
    val_loss = []
    for h in histories:
        acc      += h.history["accuracy"]
        val_acc  += h.history["val_accuracy"]
        loss     += h.history["loss"]
        val_loss += h.history["val_loss"]

    epochs = range(1, len(acc) + 1)
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(14, 5))

    ax1.plot(epochs, acc, "b-o", label="Train Accuracy")
    ax1.plot(epochs, val_acc, "r-o", label="Val Accuracy")
    ax1.axvline(x=EPOCHS_FROZEN, color="gray", linestyle="--", label="Start Fine-tuning")
    ax1.set_title("Accuracy")
    ax1.set_xlabel("Epoch")
    ax1.set_ylabel("Accuracy")
    ax1.legend()
    ax1.grid(alpha=0.3)

    ax2.plot(epochs, loss, "b-o", label="Train Loss")
    ax2.plot(epochs, val_loss, "r-o", label="Val Loss")
    ax2.axvline(x=EPOCHS_FROZEN, color="gray", linestyle="--", label="Start Fine-tuning")
    ax2.set_title("Loss")
    ax2.set_xlabel("Epoch")
    ax2.set_ylabel("Loss")
    ax2.legend()
    ax2.grid(alpha=0.3)

    fig.suptitle("CNN Training — Road Damage Classifier (MobileNetV2)", fontsize=13)
    plt.tight_layout()
    plt.savefig(output_path, dpi=150)
    plt.close()
    print(f"[INFO] Grafik disimpan → {output_path}")


def evaluate_model(model, val_flow):
    """Cetak classification report dan confusion matrix."""
    val_flow.reset()
    y_true = val_flow.classes
    y_pred_prob = model.predict(val_flow, verbose=0)
    y_pred = (y_pred_prob.ravel() >= 0.5).astype(int)

    print("\n══ Classification Report ══════════════════════════════════")
    print(classification_report(y_true, y_pred, target_names=CLASS_NAMES))

    cm = confusion_matrix(y_true, y_pred)
    print("Confusion Matrix:")
    print(f"  {'':>10}  {'Pred invalid':>14}  {'Pred valid':>12}")
    print(f"  {'True invalid':>10}  {cm[0][0]:>14}  {cm[0][1]:>12}")
    print(f"  {'True valid':>10}  {cm[1][0]:>14}  {cm[1][1]:>12}")
    print()


def save_history(histories: list):
    combined = {
        "accuracy":     [],
        "val_accuracy": [],
        "loss":         [],
        "val_loss":     [],
    }
    for h in histories:
        for k in combined:
            combined[k] += h.history.get(k, [])

    with open(HISTORY_PATH, "w") as f:
        json.dump(combined, f, indent=2)
    print(f"[INFO] Riwayat training disimpan → {HISTORY_PATH}")


def main():
    print("=" * 60)
    print("  Road Damage CNN Trainer — MobileNetV2 Transfer Learning")
    print("=" * 60)
    print(f"\n[INFO] TensorFlow versi: {tf.__version__}")
    print(f"[INFO] GPU tersedia:     {len(tf.config.list_physical_devices('GPU'))} unit\n")

    # ── Cek dataset ──────────────────────────────────────────────
    print("[STEP 1] Memeriksa dataset…")
    check_dataset()

    # ── Data generators ──────────────────────────────────────────
    print("[STEP 2] Menyiapkan data…")
    train_flow, val_flow = get_data_generators()

    # ─────────────────────────────────────────────────────────────
    # TAHAP 1: Latih head saja (base frozen)
    # ─────────────────────────────────────────────────────────────
    print("[STEP 3] Tahap 1 — Melakukan feature extraction (base frozen)…\n")
    model = build_model(trainable_base=False)
    model.compile(
        optimizer=keras.optimizers.Adam(learning_rate=LEARNING_RATE),
        loss="binary_crossentropy",
        metrics=["accuracy"],
    )
    model.summary()

    callbacks_frozen = [
        keras.callbacks.EarlyStopping(patience=4, restore_best_weights=True, verbose=1),
        keras.callbacks.ReduceLROnPlateau(factor=0.5, patience=2, verbose=1),
    ]

    h1 = model.fit(
        train_flow,
        validation_data=val_flow,
        epochs=EPOCHS_FROZEN,
        callbacks=callbacks_frozen,
        verbose=1,
    )

    # ─────────────────────────────────────────────────────────────
    # TAHAP 2: Fine-tune 30 lapisan terakhir MobileNetV2
    # ─────────────────────────────────────────────────────────────
    print("\n[STEP 4] Tahap 2 — Fine-tuning lapisan atas MobileNetV2…\n")

    # Buka sebagian base model untuk fine-tuning
    base_model = model.layers[2]           # layer MobileNetV2
    base_model.trainable = True
    finetune_from = len(base_model.layers) - 30
    for layer in base_model.layers[:finetune_from]:
        layer.trainable = False

    model.compile(
        optimizer=keras.optimizers.Adam(learning_rate=FINETUNE_LR),
        loss="binary_crossentropy",
        metrics=["accuracy"],
    )

    callbacks_ft = [
        keras.callbacks.EarlyStopping(patience=5, restore_best_weights=True, verbose=1),
        keras.callbacks.ReduceLROnPlateau(factor=0.3, patience=3, verbose=1),
        keras.callbacks.ModelCheckpoint(
            str(MODEL_PATH), save_best_only=True, monitor="val_accuracy", verbose=1
        ),
    ]

    h2 = model.fit(
        train_flow,
        validation_data=val_flow,
        epochs=EPOCHS_FINETUNE,
        callbacks=callbacks_ft,
        verbose=1,
    )

    # ── Simpan model final ────────────────────────────────────────
    model.save(MODEL_PATH)
    print(f"\n[INFO] Model disimpan → {MODEL_PATH}")

    # ── Evaluasi ──────────────────────────────────────────────────
    print("\n[STEP 5] Evaluasi model…")
    evaluate_model(model, val_flow)

    # ── Plot & riwayat ────────────────────────────────────────────
    save_history([h1, h2])
    plot_history([h1, h2], str(PLOT_PATH))

    print("\n✓ Training selesai.")
    print(f"  Model    : {MODEL_PATH}")
    print(f"  Grafik   : {PLOT_PATH}")
    print(f"  Riwayat  : {HISTORY_PATH}")
    print("\nSelanjutnya jalankan Laravel dan sistem siap digunakan.")


if __name__ == "__main__":
    main()
