# Daftar Icon untuk Replace Emoji

## Langkah-langkah
1. Download semua icon dari [Flaticon](https://www.flaticon.com) dengan keyword yang tersedia
2. Download format **PNG 512x512** (SVG butuh premium)
3. Simpan di folder `storage/app/public/icons/`
4. Bilang saya kalau sudah selesai, nanti saya langsung replace semua emoji

---

## Navigasi Menu Utama (Dashboard Modal)

| Emoji | Arti | Keyword Flaticon | Nama File |
|-------|------|------------------|-----------|
| 📂 | Data Master | `folder database management` | `icon-data-master.png` |
| ✅ | Presensi | `check list attendance` | `icon-presensi.png` |
| 📋 | Tagihan | `clipboard invoice billing` | `icon-tagihan.png` |
| 📊 | Laporan | `chart analytics report` | `icon-laporan.png` |
| 🏦 | Rekening Bimbel | `bank building` | `icon-bank.png` |

---

## Data Master Sub-menu

| Emoji | Arti | Keyword Flaticon | Nama File |
|-------|------|------------------|-----------|
| 👨‍👩‍👧‍👦 | Parent | `family parent users` | `icon-parent.png` |
| 👨‍🎓 | Murid | `student graduation cap` | `icon-murid.png` |
| 👨‍🏫 | Guru | `teacher instructor` | `icon-guru.png` |
| 📚 | Program | `book open education` | `icon-program.png` |
| 📝 | Enrollment | `document form registration` | `icon-enrollment.png` |
| 🎯 | Tawaran Les | `target offer lesson` | `icon-tawaran.png` |
| 📄 | Dokumen | `document file paper` | `icon-dokumen.png` |
| 🏷️ | Diskon/Promo | `tag price discount` | `icon-diskon.png` |
| 🆕 | Pendaftar Murid | `new user student add` | `icon-pendaftar-murid.png` |
| 🧑‍🏫 | Pendaftar Guru | `new teacher add` | `icon-pendaftar-guru.png` |

---

## Presensi Sub-menu

| Emoji | Arti | Keyword Flaticon | Nama File |
|-------|------|------------------|-----------|
| ✅ | Validasi Presensi Privat | `check validation attendance privat` | `icon-validasi-presensi.png` |
| 🏫 | Presensi & Jadwal Kelas | `school building classroom` | `icon-kelas.png` |

### Navigasi Top Bar (navigation.blade.php)
> Emoji Diskon/Promo dan Rekening Bimbel sudah tercakup di section lain — **tidak perlu download file baru**.

---

## Tagihan Sub-menu

| Emoji | Arti | Keyword Flaticon | Nama File |
|-------|------|------------------|-----------|
| 💬 | Template WA Ortu | `whatsapp chat message` | `icon-wa-ortu.png` |
| 💬 | Template WA Guru | `whatsapp chat message` | `icon-wa-guru.png` |
| 💰 | Pembayaran Ortu | `money dollar billing` | `icon-bayar-ortu.png` |
| 💳 | Pembayaran Guru | `credit card payment salary` | `icon-bayar-guru.png` |

---

## Laporan Sub-menu

| Emoji | Arti | Keyword Flaticon | Nama File |
|-------|------|------------------|-----------|
| 📈 | Laporan Kelas | `chart upward growth` | `icon-laporan-kelas.png` |
| 📋 | Riwayat | `clipboard list checklist` | `icon-riwayat-admin-line.png` |
| 📊 | Keuangan | `finance money chart` | `icon-keuangan.png` |
| 📤 | Export & Backup | `upload export backup` | `icon-export.png` |

> **Catatan**: `icon-laporan.png` (📊 Laporan tile) dan `icon-keuangan.png` (📊 Keuangan) adalah 2 file berbeda karena untuk konteks/usage yang berbeda.

---

## Dashboard Role-Based Quick Menu

### Menu Guru (6 item)

| Emoji | Arti | Keyword Flaticon | Nama File |
|-------|------|------------------|-----------|
| 📝 | Isi Presensi | `pencil document write` | `icon-isi-presensi.png` |
| 📋 | Riwayat Presensi | `clipboard checklist teacher` | `icon-riwayat-presensi.png` |
| 📚 | Riwayat Les | `book history archive` | `icon-riwayat.png` |
| 💰 | Proyeksi Gaji | `money dollar salary` | `icon-proyeksi-gaji.png` |
| 🎯 | Tawaran Les | `target offer lesson colored` | `icon-tawaran-colored.png` |
| 📄 | Dokumen | `document file colored` | `icon-dokumen-colored.png` |

### Menu Parent/Murid (2 item)

| Emoji | Arti | Keyword Flaticon | Nama File |
|-------|------|------------------|-----------|
| 📚 | Presensi Les | `book history lesson` | `icon-presensi-les.png` |
| 💰 | Tagihan | `money dollar billing` | `icon-tagihan.png` |

> **Catatan**: Tidak ada icon yang perlu reuse di section ini. Semua 8 icon adalah file baru.

---

## Empty State Component

| Emoji | Arti | Keyword Flaticon | Nama File |
|-------|------|------------------|-----------|
| 👨‍🏫 | Belum ada guru | `teacher empty` | `empty-guru.png` |
| 📋 | Belum ada presensi | `clipboard empty` | `empty-presensi.png` |
| 👨‍🎓 | Belum ada murid | `student empty` | `empty-murid.png` |
| 📚 | Belum ada program | `book empty` | `empty-program.png` |
| 👤 | Belum ada parent | `user empty` | `empty-parent.png` |
| 🔔 | Tidak ada notifikasi | `bell notification empty` | `empty-notifikasi.png` |
| 🎯 | Belum ada tawaran | `target empty` | `empty-tawaran.png` |
| 📝 | Belum ada enrollment | `document empty` | `empty-enrollment.png` |
| 📭 | Default empty state | `inbox empty` | `empty-default.png` |

---

## Export Page (CSV / Excel / PDF)

| Emoji | Arti | Keyword Flaticon | Nama File |
|-------|------|------------------|-----------|
| 👨‍🎓 | Export Murid | `student export csv` | `export-murid.png` |
| 📝 | Export Enrollment | `document export form` | `export-enrollment.png` |
| ✅ | Export Presensi | `checklist export` | `export-presensi.png` |
| 📋 | Export Enrollments | `clipboard list export` | `export-enrollments.png` |
| 📄 | CSV section | `csv file document` | `format-csv.png` |
| 📊 | Excel section | `excel spreadsheet` | `format-excel.png` |
| 🏫 | Monthly Report | `school report monthly` | `format-laporan.png` |
| 💾 | Backup | `database backup save` | `format-backup.png` |

---

## Dashboard Stat Cards

| Emoji | Arti | Keyword Flaticon | Nama File |
|-------|------|------------------|-----------|
| 💰 | Total Les | `dollar money bag` | `stat-money.png` |
| 👨‍🏫 | Total Guru stat | `teacher instructor` | `stat-guru.png` |
| 👨‍👩‍👧‍👦 | Jumlah Murid stat | `family parent users` | `stat-murid.png` |
| ✅ | Diterima (guru stat) | `check circle accepted` | `stat-diterima.png` |

> **Catatan**: icon `icon-calendar.png`, `icon-money.png`, `icon-dokumen.png`, dan `icon-tawaran.png` sudah tercakup di section lain — tidak perlu download file baru.

---

## Catatan Penting

1. **Ukuran download**: PNG 512x512 untuk semua file. Ini sudah sangat tajam untuk ditampilkan di ukuran kecil (24-96px).

2. **Folder penyimpanan**: `storage/app/public/icons/`

3. **Kalau perlu generate link storage**:
   ```bash
   php artisan storage:link
   ```

4. **Catatan khusus**:
   - 💬 (WhatsApp): pakai icon chat bubble generik tanpa logo WA, untuk hindari trademark
   - 🎯 (Target): cari icon `target` atau `bullseye` style flat
   - 🆕 (New): bisa pakai `plus circle` atau `new badge` style flat
   - 🚀 (Menu Cepat): hapus saja, ganti dengan teks heading biasa

---

## Total File Unik: 53 icon
