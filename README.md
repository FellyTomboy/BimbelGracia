
# BimbelGracia

**BimbelGracia** adalah aplikasi manajemen bimbingan belajar (bimbel) berbasis web yang dibangun dengan Laravel. Sistem ini mendukung pengelolaan murid, guru, program, presensi, tagihan, laporan, dan analisis secara terintegrasi dengan fitur multi-role (admin, guru, murid).

## Fitur Utama

### 1. Manajemen User & Role
- Autentikasi dan otorisasi (Admin, Guru, Murid)
- Pengelolaan password, force password change

### 2. Data Master
- CRUD Murid, Guru, Program, Kelas, dan Bank
- Pengelolaan data siswa, guru, program bimbel, kelas, dan rekening bank

### 3. Presensi & Absensi
- Pengelolaan periode presensi (buka/tutup)
- Validasi presensi bulanan oleh admin
- Guru mengisi presensi, melihat riwayat kehadiran

### 4. Tagihan & Pembayaran
- Murid melihat tagihan bulanan
- Admin mengelola pembayaran, diskon, dan proyeksi keuangan

### 5. Analisis & Laporan
- Laporan kelas, keuangan, dan analisis performa
- Ekspor data ke format tertentu

### 6. Tawaran Les
- Guru dapat melihat tawaran les yang tersedia

### 7. Audit Log
- Setiap perubahan data penting dicatat untuk audit

### 8. Dashboard & Navigasi
- Dashboard berbeda untuk setiap peran
- Navigasi dinamis sesuai role

## Struktur Database

- Tabel utama: users, students, teachers, programs, enrollments, monthly_attendances, class_groups, class_sessions, lesson_offers, audit_logs, bank_accounts, discounts, attendance_windows, dsb.
- Relasi: Banyak tabel menggunakan relasi many-to-many (pivot), soft delete, dan audit.

## Struktur Kode

- Model Eloquent Laravel untuk setiap entitas utama
- Controller terpisah per role (Admin, Guru, Murid)
- View Blade untuk setiap halaman utama (dashboard, presensi, billing, dsb)
- Routing terstruktur dengan middleware role-based

## Instalasi & Penggunaan

1. Clone repository ini
2. Jalankan `composer install` dan `npm install`
3. Copy `.env.example` ke `.env` dan sesuaikan konfigurasi database
4. Jalankan migrasi dan seeder: `php artisan migrate --seed`
5. Jalankan server: `php artisan serve`

## Kontribusi

Pull request dan issue sangat terbuka untuk pengembangan lebih lanjut.

## Lisensi

MIT
