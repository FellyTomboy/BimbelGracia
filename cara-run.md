Berikut langkah-langkah yang bisa kamu jalankan satu per satu di terminal VS Code:

---

## Langkah 1: Setup Environment
```bash
# Copy file .env (kalau belum ada)
cp .env.example .env

# Generate APP_KEY
php artisan key:generate
```

## Langkah 2: Migrasi Database + Seeder
```bash
# Hapus semua tabel, buat ulang, dan isi data
php artisan migrate:fresh --seed
```

## Langkah 3: Jalankan Server
```bash
# Start server di background
php artisan serve --host=0.0.0.0 --port=8000 &
```

## Langkah 4: Forward Port (biar bisa diakses dari browser)
Setelah server jalan, buka **tab PORTS** di panel bawah VS Code (sebelah terminal). Kamu akan lihat port **8000** terdaftar di sana. Klik kanan → **"Port Visibility"** → pilih **"Public"**. Lalu klik link **"Forwarded Address"** yang muncul.

Atau kalau mau lewat command:
```bash
# Forward port 8000
gh codespace ports visibility 8000:public
```

## Langkah 5: Buka di Browser
Klik link yang muncul di tab PORTS, atau buka:
```
https://sturdy-fiesta-wr7q6v4795xq2p77-8000.app.github.dev
```

---

## Akun Login:
| Role | Email | Password |
|------|-------|----------|
| **Admin** | `	admin@bimbelgracia.test` | `password` |
| **Guru** | `andi.pratama@bimbelgracia.test` | `password` |
| **Murid** | `	alya.putri@bimbelgracia.test` | `password` |

## Kalau mau stop server:
```bash
# Cari PID proses artisan serve
ps aux | grep "artisan serve"

# Kill proses (ganti PID dengan angka dari hasil di atas)
kill 53781
```