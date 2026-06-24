# Sistem Laporan Kerusakan Jalan

Aplikasi web berbasis **Laravel** untuk pelaporan kerusakan jalan yang dilengkapi dengan sistem klasifikasi gambar otomatis menggunakan **CNN (Convolutional Neural Network)** berbasis MobileNetV2.

---

## Fitur Utama

- Pelaporan kerusakan jalan oleh masyarakat dengan upload foto
- Validasi foto otomatis menggunakan model CNN (MobileNetV2 Transfer Learning)
- Klasifikasi binary: **valid** (foto kerusakan jalan) / **invalid** (bukan foto kerusakan)
- Dashboard admin untuk manajemen laporan
- Status laporan: pending, diproses, selesai

---

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel (PHP) |
| Frontend | Blade + Vite |
| Database | SQLite (default) / MySQL |
| CNN Model | Python + TensorFlow/Keras |
| Arsitektur CNN | MobileNetV2 (Transfer Learning + Fine-tuning) |

---

## Struktur Proyek

```
road-damage-report/
├── app/                    # Laravel application logic
├── cnn/
│   ├── dataset/
│   │   ├── valid/          # Foto kerusakan jalan (untuk training)
│   │   └── invalid/        # Foto bukan kerusakan (untuk training)
│   ├── dataset_split/      # Hasil split otomatis (dibuat oleh split_dataset.py)
│   │   ├── train/
│   │   ├── val/
│   │   └── test/
│   ├── train.py            # Script training CNN
│   ├── split_dataset.py    # Script pembagian dataset (70/15/15)
│   ├── predict.py          # Script inferensi (dipanggil oleh Laravel)
│   ├── requirements.txt    # Dependensi Python
│   └── model.keras         # Model terlatih (tidak di-push ke git)
├── public/
├── resources/
├── routes/
└── ...
```

---

## Instalasi & Setup

### 1. Clone Repository

```bash
git clone https://github.com/ankOYaRa/Road-Damage-Reporting.git
cd Road-Damage-Reporting
```

### 2. Setup Laravel

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

### 3. Setup Frontend

```bash
npm install
npm run dev
```

### 4. Setup Python (CNN)

```bash
pip install -r cnn/requirements.txt
```

---

## Alur Training Model CNN

### Langkah 1 — Siapkan Dataset

Letakkan foto di dalam folder yang sesuai:

```
cnn/dataset/
├── valid/      ← foto yang jelas menunjukkan kerusakan jalan
└── invalid/    ← foto bukan kerusakan (jalan normal, spam, dll.)
```

> Dataset yang digunakan: **500 gambar** (250 valid, 250 invalid)

### Langkah 2 — Split Dataset

```bash
python cnn/split_dataset.py
```

Otomatis membagi dataset dengan rasio **70% train / 15% val / 15% test** ke folder `cnn/dataset_split/`.

### Langkah 3 — Training

```bash
python cnn/train.py
```

| Parameter | Nilai |
|-----------|-------|
| Arsitektur | MobileNetV2 (ImageNet pretrained) |
| Input size | 224 × 224 × 3 (RGB) |
| Batch size | 16 |
| Fase 1 (frozen) | 50 epoch, LR = 3e-4 |
| Fase 2 (fine-tuning) | 50 epoch, LR = 1e-5 |
| Optimizer | Adam |
| Loss | Binary Crossentropy |

**Model head:**
```
MobileNetV2 (base) → GlobalAveragePooling2D → BatchNormalization → Dropout(0.5) → Dense(1, sigmoid)
```

**Output training:**
- `cnn/model.keras` — model terlatih
- `cnn/training_plot.png` — grafik accuracy & loss
- `cnn/training_history.json` — riwayat metrik per epoch

### Hasil Evaluasi

| Metrik | Nilai |
|--------|-------|
| Accuracy | 95% |
| Precision | 100% (valid) |
| Recall | 89% (valid) |
| F1-Score | 94% |

---

## Cara Kerja Prediksi

Saat pengguna mengupload foto laporan, Laravel memanggil `predict.py` via subprocess:

```bash
python cnn/predict.py <path/to/image.jpg>
```

Output JSON ke stdout:

```json
{"status": "valid",   "confidence": 0.9231}
{"status": "invalid", "confidence": 0.1234}
```

- `valid` → foto dikenali sebagai kerusakan jalan, laporan diterima
- `invalid` → foto bukan kerusakan jalan, laporan ditolak
- `confidence` → nilai probabilitas prediksi model (0.0 – 1.0)

---

## Menjalankan Aplikasi

```bash
php artisan serve
```

Akses di browser: `http://localhost:8000`

---

## Lisensi

Proyek ini dibuat untuk keperluan penelitian skripsi.
