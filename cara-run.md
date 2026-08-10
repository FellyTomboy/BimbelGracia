# Cara Menjalankan Bimbel Gracia

## Setup Awal (Pertama Kali)

```bash
# 1. Copy file .env (kalau belum ada)
cp .env.example .env

# 2. Install dependencies composer
composer install --no-dev --optimize-autoloader

# 3. Install dependencies npm (kalau ada perubahan frontend)
npm install && npm run build

# 4. Generate APP_KEY
php artisan key:generate

# 5. Migrasi database
php artisan migrate

# 6. Seed data admin
php artisan db:seed --class=WebsiteDatasetSeeder

# 7. Buat storage symlink
php artisan storage:link

# 8. Copy logo ke folder storage
cp logo_bimbel.jpg storage/app/public/website/logo_bimbel.jpg

# 9. Clear cache
php artisan optimize:clear
```

## Setelah Git Pull (Update dari Repository)

Jalankan perintah berikut **berurutan** setelah `git pull`:

```bash
# 1. Install/update composer dependencies (kalau ada package baru)
composer install --no-dev --optimize-autoloader

# 2. Jalankan migration baru (kalau ada)
php artisan migrate

# 3. Update storage symlink (kalau belum ada)
php artisan storage:link

# 4. Copy logo ke folder storage (kalau belum ada)
mkdir -p storage/app/public/website
cp logo_bimbel.jpg storage/app/public/website/logo_bimbel.jpg

# 5. Buat folder-folder storage yang diperlukan
mkdir -p storage/app/public/pdf/invoice
mkdir -p storage/app/public/pdf/salary
mkdir -p storage/app/public/photo/profile
mkdir -p storage/app/public/photo/attendance
mkdir -p storage/app/public/photo/transfer-proof

# 6. Clear cache (penting! biar view/config/routes yang baru termuat)
php artisan optimize:clear

# 7. Regenerate PDF (kalau ada perubahan data atau template PDF)
php artisan app:generate-all-pdfs
```

## Struktur Folder Storage

```
storage/app/public/
├── website/
│   └── logo_bimbel.jpg              # Logo bimbel + foto founder
├── photo/
│   ├── profile/                     # Foto profile guru
│   ├── attendance/{nama_guru}/      # Foto bukti presensi
│   └── transfer-proof/{nama_parent}/ # Foto bukti transfer
├── pdf/
│   ├── invoice/{nama_parent}/       # Invoice tagihan (MM-YYYY.pdf)
│   └── salary/{nama_guru}/          # Slip gaji (MM-YYYY.pdf)
└── dokumen/                         # Modul pembelajaran
```

## Jalankan Server

```bash
php artisan serve --host=0.0.0.0 --port=8000 &
```

## Akun Login (Data Test)

| Role | Email | Password |
|------|-------|----------|
| **Admin** | `admin@bimbelgracia.test` | `password` |

## Generate Ulang Semua Data + PDF

```bash
# Reset database + seed data test + generate semua PDF
php artisan migrate:fresh --seed --force && php artisan app:generate-all-pdfs
```

## Stop Server

```bash
ps aux | grep "artisan serve"
kill [PID]