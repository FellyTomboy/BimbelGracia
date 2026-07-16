# Task: Dashboard Admin - Grafik Pertumbuhan Bimbel

## Done / Dikerjakan
- [x] Analisis existing finance dashboard & controller
- [x] Konfirmasi definisi metrik:
  - Laba kotor = semua pembayaran orangtua (privat + kelas)
  - Laba bersih = laba kotor - gaji guru
  - Murid privat = enrollment aktif pada bulan itu (di-proxy oleh `enrollment_attendances.status_validation = valid` per bulan)
  - Yearly count untuk jumlah murid & guru = AVERAGE (rata-rata per bulan)
- [x] Tambah migration snapshot bulanan:
   - `monthly_student_snapshots` dengan unique(year, month)
   - `monthly_teacher_snapshots` dengan unique(year, month)
- [x] Buat artisan command snapshot bulanan:
   - `snapshot:students-monthly`
   - `snapshot:teachers-monthly`
- [x] Jadwalkan snapshot command di Laravel 12 console routing untuk run bulanan otomatis
- [x] Update `App\Http\Controllers\Admin\FinanceController`:
   - Filter `range_start`, `range_end`, dan `mode` (monthly|yearly)
   - Chart laba kotor vs laba bersih, murid privat vs murid kelas, dan jumlah guru
- [x] Update `resources/views/admin/finance/dashboard.blade.php`:
   - UI filter rentang + toggle mode bulanan/tahunan
   - 3 chart (Chart.js)

## Implementasi (Next)
1. [x] Verifikasi query “class students count per end-of-month” dan “teacher count per end-of-month”:
   - Pastikan definisi kolom status/soft delete tersedia di model/tabel.
2. [ ] Test manual:
   - migrate
   - jalankan command snapshot manual untuk 1-2 bulan
   - buka dashboard dan cek grafik + filter
