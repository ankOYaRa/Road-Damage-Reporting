"""
split_dataset.py - Membagi dataset ke folder train / val / test
================================================================
Struktur input  (cnn/dataset/):
    dataset/
    |- valid/       foto kerusakan jalan
    |- invalid/     foto bukan kerusakan

Struktur output (cnn/dataset_split/):
    dataset_split/
    |- train/  |- valid/  |- invalid/
    |- val/    |- valid/  |- invalid/
    |- test/   |- valid/  |- invalid/

Rasio split   : 70% train | 15% val | 15% test
Cara pakai    : python cnn/split_dataset.py
"""

import os
import sys
import shutil
import random
import pathlib

# Force UTF-8 output on Windows to avoid cp1252 encoding errors
if sys.stdout.encoding and sys.stdout.encoding.lower() != "utf-8":
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8", errors="replace")

# --- Konfigurasi ---------------------------------------------------------
BASE_DIR    = pathlib.Path(__file__).parent
DATASET_DIR = BASE_DIR / "dataset"
SPLIT_DIR   = BASE_DIR / "dataset_split"

TRAIN_RATIO = 0.70
VAL_RATIO   = 0.15
# TEST_RATIO  = 1 - TRAIN_RATIO - VAL_RATIO = 0.15

CLASS_NAMES = ["valid", "invalid"]
SEED        = 42
IMG_EXTS    = {".jpg", ".jpeg", ".png", ".webp", ".JPG", ".JPEG", ".PNG"}
# -------------------------------------------------------------------------


def get_images(class_dir: pathlib.Path) -> list:
    """Ambil semua file gambar dari sebuah folder kelas."""
    return sorted([
        f for f in class_dir.iterdir()
        if f.is_file() and f.suffix in IMG_EXTS
    ])


def copy_files(files: list, dest_dir: pathlib.Path):
    """Salin file ke folder tujuan, buat folder jika belum ada."""
    dest_dir.mkdir(parents=True, exist_ok=True)
    for f in files:
        shutil.copy2(f, dest_dir / f.name)


def main():
    print("=" * 55)
    print("  Dataset Splitter - Road Damage Classifier")
    print("=" * 55)

    # --- Validasi folder input -------------------------------------------
    if not DATASET_DIR.exists():
        print(f"[ERROR] Folder dataset tidak ditemukan: {DATASET_DIR}")
        print("Pastikan folder cnn/dataset/ berisi subfolder per kelas.")
        return

    # --- Hapus split lama jika ada ---------------------------------------
    if SPLIT_DIR.exists():
        print("[INFO] Menghapus folder dataset_split/ lama dan membuat ulang...")
        shutil.rmtree(SPLIT_DIR)

    random.seed(SEED)
    summary = {}

    # --- Proses setiap kelas --------------------------------------------
    for cls in CLASS_NAMES:
        cls_dir = DATASET_DIR / cls
        if not cls_dir.exists():
            print(f"[ERROR] Folder kelas tidak ditemukan: {cls_dir}")
            return

        images = get_images(cls_dir)
        if len(images) == 0:
            print(f"[ERROR] Tidak ada gambar di {cls_dir}")
            return

        random.shuffle(images)
        total   = len(images)
        n_train = int(total * TRAIN_RATIO)
        n_val   = int(total * VAL_RATIO)
        n_test  = total - n_train - n_val   # sisa supaya tidak ada gambar terbuang

        train_files = images[:n_train]
        val_files   = images[n_train : n_train + n_val]
        test_files  = images[n_train + n_val :]

        copy_files(train_files, SPLIT_DIR / "train" / cls)
        copy_files(val_files,   SPLIT_DIR / "val"   / cls)
        copy_files(test_files,  SPLIT_DIR / "test"  / cls)

        summary[cls] = {
            "total": total,
            "train": n_train,
            "val":   n_val,
            "test":  n_test,
        }

    # --- Ringkasan -------------------------------------------------------
    print("\n[OK] Split selesai!\n")
    print(f"  {'Kelas':<10} {'Total':>7} {'Train':>7} {'Val':>7} {'Test':>7}")
    print(f"  {'-'*42}")

    grand_total = grand_train = grand_val = grand_test = 0
    for cls, c in summary.items():
        print(f"  {cls:<10} {c['total']:>7} {c['train']:>7} {c['val']:>7} {c['test']:>7}")
        grand_total += c["total"]
        grand_train += c["train"]
        grand_val   += c["val"]
        grand_test  += c["test"]

    print(f"  {'-'*42}")
    print(f"  {'TOTAL':<10} {grand_total:>7} {grand_train:>7} {grand_val:>7} {grand_test:>7}")
    print(f"\n  Rasio aktual : train={grand_train/grand_total:.0%}  "
          f"val={grand_val/grand_total:.0%}  test={grand_test/grand_total:.0%}")
    print(f"\n  Output       : {SPLIT_DIR}")
    print("\nSelanjutnya jalankan: python cnn/train.py")


if __name__ == "__main__":
    main()