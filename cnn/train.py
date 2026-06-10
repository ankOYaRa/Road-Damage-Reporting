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
from tensorflow.keras import layers, regularizers
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

IMG_SIZE        = 224
BATCH_SIZE      = 16     # Larger batch = better gradient estimates, less noise
EPOCHS_FROZEN   = 30    # tahap 1: hanya melatih head (base frozen)
EPOCHS_FINETUNE = 50    # tahap 2: fine-tune lapisan atas MobileNetV2
LEARNING_RATE   = 3e-4  # Good default for Adam with frozen base
FINETUNE_LR     = 1e-5  # Very low to avoid destroying pretrained weights
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

    Head yang disederhanakan untuk mencegah overfitting pada dataset kecil:
        GlobalAveragePooling -> BatchNorm -> Dropout(0.5) -> Dense(1, sigmoid)

    Dense(256) yang sebelumnya ada DIHAPUS karena dengan hanya 350 gambar
    training, layer tersebut justru menghafal data (overfitting) bukan belajar.
    L2 regularization ditambahkan pada layer output.
    """
    base = MobileNetV2(
        input_shape=(IMG_SIZE, IMG_SIZE, 3),
        include_top=False,
        weights="imagenet",
    )
    base.trainable = trainable_base

    inputs = keras.Input(shape=(IMG_SIZE, IMG_SIZE, 3))
    # preprocess_input expects [0,255] — do NOT use rescale=1/255 in generator
    x = tf.keras.applications.mobilenet_v2.preprocess_input(inputs)
    x = base(x, training=False)  # BN layers always in inference mode
    x = layers.GlobalAveragePooling2D()(x)
    x = layers.BatchNormalization()(x)          # stabilize feature distribution
    x = layers.Dropout(0.5)(x)                  # stronger dropout vs 0.4 before
    outputs = layers.Dense(
        1,
        activation="sigmoid",
        kernel_regularizer=regularizers.l2(1e-4),  # L2 to penalize large weights
    )(x)

    return keras.Model(inputs, outputs)


def get_data_generators():
    """Buat data generator dengan augmentasi untuk training.
    Split: 70% train, 15% validation, 15% test

    FIX: Removed rescale=1/255 — MobileNetV2's preprocess_input inside the model
    already handles normalization and expects raw [0,255] pixel values.
    Using rescale here caused double-preprocessing and killed learning.

    FIX: Fixed the val/test split — previously val_test_gen read from the same
    full dataset directory without a proper held-out split, causing overlap.
    Now using dataset_split/ directory which has proper train/val/test folders.
    """
    # Use dataset_split if available (proper pre-split dataset)
    split_dir = BASE_DIR / "dataset_split"
    if (split_dir / "train").exists():
        print("[INFO] Menggunakan dataset_split/ yang sudah di-split…")
        # FIX: No rescale — preprocess_input inside model handles normalization
        # Aggressive augmentation to artificially multiply 175 training images
        train_gen = ImageDataGenerator(
            rotation_range=30,           # more rotation variety
            width_shift_range=0.2,
            height_shift_range=0.2,
            shear_range=0.15,
            zoom_range=0.25,             # stronger zoom
            horizontal_flip=True,
            vertical_flip=True,          # road damage appears at any angle
            brightness_range=[0.7, 1.3], # wider brightness range
            channel_shift_range=30.0,    # color jitter for lighting variation
            fill_mode="reflect",         # reflect is better than nearest for roads
        )
        val_test_gen = ImageDataGenerator()  # No augmentation for val/test

        train_flow = train_gen.flow_from_directory(
            split_dir / "train",
            target_size=(IMG_SIZE, IMG_SIZE),
            batch_size=BATCH_SIZE,
            class_mode="binary",
            classes=CLASS_NAMES,
            seed=SEED,
        )
        val_flow = val_test_gen.flow_from_directory(
            split_dir / "val",
            target_size=(IMG_SIZE, IMG_SIZE),
            batch_size=BATCH_SIZE,
            class_mode="binary",
            classes=CLASS_NAMES,
            seed=SEED,
        )
        test_flow = val_test_gen.flow_from_directory(
            split_dir / "test",
            target_size=(IMG_SIZE, IMG_SIZE),
            batch_size=BATCH_SIZE,
            class_mode="binary",
            classes=CLASS_NAMES,
            seed=SEED,
        )
    else:
        print("[INFO] dataset_split/ tidak ada, pakai dataset/ dengan validation_split…")
        train_gen = ImageDataGenerator(
            validation_split=0.2,
            rotation_range=20,
            width_shift_range=0.15,
            height_shift_range=0.15,
            shear_range=0.1,
            zoom_range=0.2,
            horizontal_flip=True,
            brightness_range=[0.8, 1.2],
            fill_mode="nearest",
        )
        val_gen = ImageDataGenerator(validation_split=0.2)  # No augmentation

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
        test_flow = val_flow  # Use same val as test when no split dir

    print(f"  Class indices: {train_flow.class_indices}")
    print(f"  Training   samples: {train_flow.samples}")
    print(f"  Validation samples: {val_flow.samples}")
    print(f"  Test       samples: {test_flow.samples}\n")

    return train_flow, val_flow, test_flow


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

    # Jika validation data terlalu kecil, skip detailed report
    if len(np.unique(y_true)) < 2:
        print(f"\n[WARNING] Validation data terlalu kecil untuk classification report")
        print(f"  Accuracy: {(y_pred == y_true).mean():.4f}")
        return

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
    train_flow, val_flow, test_flow = get_data_generators()

    # Hitung class weights untuk menangani dataset kecil/tidak seimbang
    total = train_flow.samples
    n_invalid = sum(1 for label in train_flow.classes if label == 0)
    n_valid   = total - n_invalid
    class_weight = {
        0: total / (2 * n_invalid) if n_invalid > 0 else 1.0,
        1: total / (2 * n_valid)   if n_valid   > 0 else 1.0,
    }
    print(f"[INFO] Class weights: invalid={class_weight[0]:.3f}, valid={class_weight[1]:.3f}")

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
        # Monitor val_loss — more honest than val_accuracy for detecting overfitting
        keras.callbacks.EarlyStopping(
            monitor="val_loss", patience=8,
            restore_best_weights=True, verbose=1
        ),
        keras.callbacks.ReduceLROnPlateau(
            monitor="val_loss", factor=0.5, patience=4, min_lr=1e-7, verbose=1
        ),
    ]

    h1 = model.fit(
        train_flow,
        validation_data=val_flow,
        epochs=EPOCHS_FROZEN,
        callbacks=callbacks_frozen,
        class_weight=class_weight,
        verbose=1,
    )

    # ─────────────────────────────────────────────────────────────
    # TAHAP 2: Fine-tune 30 lapisan terakhir MobileNetV2
    # ─────────────────────────────────────────────────────────────
    print("\n[STEP 4] Tahap 2 — Fine-tuning lapisan atas MobileNetV2…\n")

    # Buka sebagian base model untuk fine-tuning
    # Cari layer MobileNetV2 (bukan preprocessing layers)
    base_model = None
    for layer in model.layers:
        if "mobilenet" in layer.name.lower():
            base_model = layer
            break

    if base_model is None:
        print("[ERROR] Tidak bisa menemukan MobileNetV2 layer")
        sys.exit(1)

    base_model.trainable = True
    # FIX: Freeze all BatchNormalization layers — critical for fine-tuning with small data
    for layer in base_model.layers:
        if isinstance(layer, tf.keras.layers.BatchNormalization):
            layer.trainable = False
    # Only unfreeze the last 30 non-BN layers
    finetune_from = len(base_model.layers) - 30
    for layer in base_model.layers[:finetune_from]:
        layer.trainable = False

    # FIX: Use much lower LR for fine-tuning to avoid destroying pretrained weights
    model.compile(
        optimizer=keras.optimizers.Adam(learning_rate=FINETUNE_LR),
        loss="binary_crossentropy",
        metrics=["accuracy"],
    )

    callbacks_ft = [
        keras.callbacks.EarlyStopping(
            monitor="val_loss", patience=12,
            restore_best_weights=True, verbose=1
        ),
        keras.callbacks.ReduceLROnPlateau(
            monitor="val_loss", factor=0.3, patience=5, min_lr=1e-8, verbose=1
        ),
        # Save best model based on val_loss — more reliable than val_accuracy
        keras.callbacks.ModelCheckpoint(
            str(MODEL_PATH), save_best_only=True, monitor="val_loss",
            mode="min", verbose=1
        ),
    ]

    h2 = model.fit(
        train_flow,
        validation_data=val_flow,
        epochs=EPOCHS_FINETUNE,
        callbacks=callbacks_ft,
        class_weight=class_weight,
        verbose=1,
    )

    # ── Simpan model final ────────────────────────────────────────
    model.save(MODEL_PATH)
    print(f"\n[INFO] Model disimpan → {MODEL_PATH}")

    # ── Evaluasi ──────────────────────────────────────────────────
    print("\n[STEP 5] Evaluasi model…")
    print("\n─── Validasi Set ───────────────────────────────────────────")
    evaluate_model(model, val_flow)
    print("\n─── Test Set ───────────────────────────────────────────────")
    evaluate_model(model, test_flow)

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
