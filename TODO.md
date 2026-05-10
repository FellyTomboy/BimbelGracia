# Task: Dashboard Admin - Grafik Pertumbuhan Bimbel

## Done / Dikerjakan
- [x] Analisis existing finance dashboard & controller
- [x] Konfirmasi definisi metrik:
  - Laba kotor = semua pembayaran orangtua (privat + kelas)
  - Laba bersih = laba kotor - gaji guru
  - Murid privat = enrollment aktif pada bulan itu (di-proxy oleh `enrollment_attendances.status_validation = valid` per bulan)
  - Yearly count untuk jumlah murid & guru = AVERAGE (rata-rata per bulan)

## Implementasi (Next)
1. [ ] Tambah migration baru:
   - monthly_student_snapshots (private vs class) dengan unique(year, month)
   - monthly_teacher_snapshots (teachers_count) dengan unique(year, month)
2. [ ] Buat 2 command artisan:
   - snapshot:students-monthly (ambil bulan sebelumnya, hitung counts, upsert snapshot)
   - snapshot:teachers-monthly (ambil bulan sebelumnya, hitung counts, upsert snapshot)
3. [ ] Jadwalkan command di `app/Console/Kernel.php` untuk dieksekusi tiap akhir bulan.
4. [ ] Update `App\Http\Controllers\Admin\FinanceController`:
   - Tambah filter `range_start`, `range_end`, dan `mode` (monthly|yearly)
   - Hitung chart:
     - Laba Bersih vs Laba Kotor
     - Murid Privat vs Murid Kelas (berbasis snapshot)
     - Jumlah Guru (berbasis snapshot)
5. [ ] Update `resources/views/admin/finance/dashboard.blade.php`:
   - UI filter rentang + toggle mode bulanan/tahunan
   - Tambah 3 chart (Chart.js)
6. [ ] Verifikasi query “class students count per end-of-month” dan “teacher count per end-of-month”:
   - Pastikan definisi kolom status/soft delete tersedia di model/tabel.
7. [ ] Test manual:
   - migrate
   - jalankan command snapshot manual untuk 1-2 bulan
   - buka dashboard dan cek grafik + filter
