# Dokumentasi Sistem BimbelGracia

## 1. Gambaran Umum

**BimbelGracia** adalah sistem manajemen bimbingan belajar (*tutoring center*) berbasis web yang dibangun dengan Laravel. Sistem ini mengelola seluruh aspek operasional bimbel secara end-to-end: dari rekrutmen guru dan pendaftaran murid, pencatatan presensi, perhitungan tagihan & gaji, hingga distribusi dokumen dan notifikasi WhatsApp.

Sistem ini dirancang untuk menangani dua model pembelajaran:

- **Privat** — Les一对一 antara satu guru dan satu atau beberapa murid.
- **Kelas** — Sesi kelas bersama dengan banyak murid dalam satu kelompok.

Tiga peran utama: **Admin**, **Guru** (teacher), dan **Orang Tua** (parent). Selain itu terdapat satu portal publik tanpa autentikasi untuk pendaftaran mandiri guru dan murid baru.

---

## 2. Arsitektur & Teknologi

| Layer | Teknologi |
|---|---|
| Framework | Laravel 11 (Breeze scaffold) |
| Database | SQLite (`database.sqlite`) |
| Auth | Laravel Breeze (phone + password) |
| PDF | Barryvdh/DomPDF |
| Export | PhpSpreadsheet (Excel), DomPDF (PDF) |
| Image processing | GD (watermarking) |
| Caching | Laravel Cache (DB-backed, 30 hari) |
| Audit | Custom `Auditable` trait → `audit_logs` |
| Frontend | Blade templates + vanilla JS |

### Struktur Folder Aplikasi

```
app/
├── Console/Commands/        # Import spreadsheet, snapshot, PDF generator
├── Enums/UserRole.php       # admin | guru | parent
├── Helpers/WhatsappHelper.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/           # 15+ controller (full CRUD + fitur)
│   │   ├── Auth/            # Breeze auth
│   │   ├── Guru/            # Presensi, history, salary, documents
│   │   ├── Murid/           # Billing & history (single-child view)
│   │   ├── Parent/          # Billing & history (multi-child view)
│   │   ├── ProfileController.php
│   │   ├── PdfController.php
│   │   ├── RegisterStudentController.php
│   │   └── RegisterTeacherController.php
│   ├── Middleware/
│   │   ├── EnsureAccountActive.php    # Blokir akun nonaktif
│   │   ├── EnsurePasswordChanged.php  # Paksa ganti password
│   │   └── EnsureUserRole.php         # RBAC per route
│   └── Requests/
├── Models/                  # 18 model Eloquent
├── Services/
│   ├── CalculationService.php       # Billing & salary engine
│   ├── AttendanceFineService.php    # Feature flag penalty
│   ├── MonthlySnapshotSyncService.php
│   └── Pdf/
│       ├── InvoiceService.php
│       └── PdfTokenService.php
└── Traits/
    ├── Auditable.php              # Auto-change-logging
    ├── FormatsWhatsappNumber.php  # Normalisasi nomor Indonesia
    └── SearchAndSort.php          # Reusable search/sort builder
```

---

## 3. Model Data (Entity-Relationship)

### 3.1 Model Inti Autentikasi

#### User (`users`)
Model utama autentikasi Laravel. Satu user bisa terhubung ke seorang **Teacher** atau **ParentModel** (1:1), atau tidak keduanya (untuk admin).

| Kolom | Tipe | Catatan |
|---|---|---|
| `name` | string | |
| `phone` | string | Kunci login utama |
| `email` | string | |
| `password` | hashed | |
| `role` | `UserRole` enum | `admin`, `guru`, `parent` |
| `must_change_password` | boolean | Di-set saat akun dibuat atau di-reset admin |
| `deleted_at` | datetime | Soft delete — akun nonaktif |

#### Teacher (`teachers`)
Profil guru, terhubung 1:1 ke `User`.

| Kolom | Tipe | Catatan |
|---|---|---|
| `user_id` | FK | |
| `nickname` / `full_name` | string | |
| `whatsapp` / `whatsapp_number` | string | Normalisasi otomatis |
| `major` | string | Bidang keahlian utama |
| `subjects` | string | Mata pelajaran yang diampu |
| `address` | string | |
| `bank_name`, `bank_account`, `bank_owner` | string | |
| `class_rate` | integer | Tarif dasar per sesi |
| `profile_photo_path` | string | |
| `profile_photo_approved` | boolean | Perlu persetujuan admin |
| `is_founder` | boolean | Apakah guru ini founder |
| `status` | string | `active` / `hibernasi` |

Relasi: `user()`, `students()` (N:M), `enrollments()` (1:N), `programRates()` (1:N).

#### ParentModel (`parents`)
Profil orang tua, terhubung 1:1 ke `User`.

| Kolom | Tipe | Catatan |
|---|---|---|
| `user_id` | FK | |
| `name` | string | |
| `address` | string | |

Relasi: `user()`, `students()` (1:N). Aksesor `whatsapp` mengambil dari `$this->user->phone`.

#### Student (`students`)
Profil murid, milik satu `ParentModel`.

| Kolom | Tipe | Catatan |
|---|---|---|
| `parent_id` | FK | |
| `nickname` / `full_name` | string | |
| `address` | string | |
| `status` | string | `active` / `hibernasi` |

Relasi: `parent()`, `teachers()` (N:M), `enrollments()` (N:M).

---

### 3.2 Model Pembelajaran

#### Program (`programs`)
Program les. Tiap program punya `type` = `privat` atau `kelas`.

| Kolom | Tipe | Catatan |
|---|---|---|
| `name` | string | |
| `division` | string | |
| `type` | string | `privat` / `kelas` |
| `subject` | string | |
| `default_parent_rate` | integer | Tarif default orang tua |
| `default_teacher_rate` | integer | Tarif default guru |

#### Enrollment (`enrollments`)
Ikatan many-to-many antara Student dan Program melalui pivot `enrollment_student`, ditambah field pricing dan status.Satu enrollment terhubung ke satu Teacher (guru pengampu) dan satu Program.

| Kolom | Tipe | Catatan |
|---|---|---|
| `program_id` | FK | |
| `teacher_id` | FK | Guru pengampu |
| `type` | string | `privat` / `kelas` |
| `parent_rate` | integer | Tarif orang tua per sesi |
| `teacher_rate` | integer | Tarif guru per sesi |
| `pricing_tiers` | JSON | Tier harga berdasarkan jumlah murid |
| `agreed_sessions_per_month` | integer | Default 4; untuk logika penalty |
| `validation_status` | integer | 0 = belum, 1 = sudah divalidasi |
| `status` | string | `active` / `hibernasi` |

Logika pricing tiers: JSON dengan key jumlah murid (`1`, `2`, dst.) dan value berisi `parent_rate` serta `teacher_rate`. Ini memungkinkan harga berbeda berdasarkan jumlah murid yang hadir dalam satu sesi privat.

#### ClassSession (`class_sessions`)
Jadwal sesi untuk program **kelas** (bukan privat).

| Kolom | Tipe | Catatan |
|---|---|---|
| `program_id` | FK | |
| `session_date` | date | Tanggal sesi |
| `notes` | string | Catatan opsional |

Relasi: pivot `teachers()` dengan kolom `rate` (tarif per sesi per guru di pivot `class_session_teacher`).

#### MonthlyAttendance (`enrollment_attendances`)
Pencatatan kehadiran per sesi. Untuk privat: satu record per sesi. Untuk kelas: satu record per `ClassSession`.

| Kolom | Tipe | Catatan |
|---|---|---|
| `enrollment_id` | FK | |
| `class_session_id` | FK | Nullable; untuk kelas |
| `session_teacher_id` | FK | Guru yang mengajar sesi ini |
| `lesson_date` | date | Tanggal les |
| `month` / `year` | integer | Snapshot dari lesson_date |
| `parent_rate` / `teacher_rate` | integer | Snapshot tarif saat dibuat |
| `status_validation` | string | `pending` / `terima` / `terlambat` / `ditolak` |
| `parent_payment_status` | string | `unpaid` / `paid` |
| `teacher_payment_status` | string | `unpaid` / `paid` / `held` |
| `parent_review_status` | string | `pending` (ortu keberatan) |
| `parent_rejection_reason` | string | |
| `payment_proof` | string | Path file bukti transfer |
| `payment_proof_status` | string | |
| `image` | string | Foto bukti kehadiran |
| `validated_at` / `validated_by` | | |

Pivot `students()` (`attendance_student`) menyimpan `total_present` — jumlah pertemuan yang murid hadiri.

---

### 3.3 Model Pendukung

#### TeacherProgramRate (`teacher_program_rates`)
Tabel per-program rate kustom per guru. acts as join table untuk `Program.teachers()` N:M.

| Kolom | Tipe |
|---|---|
| `teacher_id` | FK |
| `program_id` | FK |
| `rate` | integer |

#### EnrollmentStudentDiscount (`enrollment_student_discounts`)
Diskon per enrollment–student–periode.

| Kolom | Tipe |
|---|---|
| `enrollment_id` | FK |
| `student_id` | FK |
| `month` / `year` | integer |
| `discount_type` | `percent` / `amount` / `final` |
| `discount_value` | integer |

Tiga tipe diskon:
- `percent` — persen dari subtotal (0–100%)
- `amount` — jumlah flat, tidak boleh melebihi subtotal
- `final` — tarif final yang harus dibayar (bayar lebih kecil = dapat diskon besar)

#### Document (`documents`)
File yang diunggah admin untuk diakses guru.

| Kolom | Tipe |
|---|---|
| `file_path`, `file_name`, `file_type`, `file_size` | |
| `access_type` | `password` / `teacher` |
| `access_password` | Hash bcrypt |
| `access_password_plain` | Teks asli (untuk display/reset) |
| `protection_level` | `strict` / `standard` |

Sistem otorisasi: admin selalu bisa akses; guru bisa jika (1) assigned langsung, atau (2) password doc di-unlock via session.

#### LessonOffer (`lesson_offers`)
Iklan penawaran les yang terlihat di portal guru.

| Kolom | Tipe |
|---|---|
| `code` | Auto: `LO-XXXXXX` |
| `education_level` | Jenjang pendidikan |
| `subject` | Mata pelajaran |
| `schedules` | JSON array hari+waktu |
| `status` | `open` / `closed` |

#### NewStudent (`new_students`)
Lead pre-registrasi murid. Konversi ke Student + Parent saat admin approve.

#### TeacherRegistrant (`teacher_registrants`)
Lead pre-registrasi guru. Konversi ke Teacher + User.

#### AttendanceWindow (`attendance_windows`)
Gate temporal — presensi hanya bisa dicatat jika window untuk bulan tersebut sedang terbuka.

| Kolom | Tipe |
|---|---|
| `month` / `year` | integer |
| `is_open` | boolean |

#### AuditLog (`audit_logs`)
Log perubahan semua model yang pakai trait `Auditable`. Polimorfik (`auditable_type` / `auditable_id`).

#### WaNotificationLog (`wa_notification_logs`)
Trail notifikasi WhatsApp yang dikirim ke ortu/guru per periode.

#### PdfAccessToken (`pdf_access_tokens`)
Token akses PDF sekali-pakai (7 hari) untuk berbagi invoice/gaji tanpa login.

---

## 4. Sistem Role & Middleware

### 4.1 UserRole Enum

```php
enum UserRole: string {
    case Admin  = 'admin';
    case Guru   = 'guru';
    case Parent = 'parent';
}
```

### 4.2 Middleware Stack

| Middleware | Fungsi |
|---|---|
| `auth` | Wajib login (Laravel Breeze) |
| `password.force` | Redirect ke `/password/force` jika `must_change_password = true` |
| `account.active` | Cek DB trashed — blokir guru/ortu nonaktif |
| `role:admin/guru/parent` | `EnsureUserRole` — RBAC per route group |

### 4.3 Routing Groups

```
/                    → Landing page publik
/login               → Auth (guest only)
/dashboard          → Dashboard umum (semua role, via middleware)
/admin/*            → Middleware: role:admin
/guru/*             → Middleware: role:guru + account.active
/parent/*           → Middleware: role:parent + account.active
/register-student/* → Token-based public form
/register-teacher/* → Token-based public form
/pdf/parent/{token} → Token-based PDF serving (tanpa login)
/pdf/guru/{token}   → Token-based PDF serving (tanpa login)
```

---

## 5. Modul Admin

### 5.1 Manajemen Murid (`StudentController`)

- **Index**: Daftar murid aktif dengan pencarian (`full_name`, `nickname`, `status`) dan sorting.
- **Inactive**: Daftar murid hibernasi (soft-deleted).
- **Create**: Buat murid + otomatis buat Parent + User terkait (dari nomor HP ortu).
- **Destroy** (Hibernate): Set status `hibernasi`, soft-delete, sync snapshot.
- **BulkDestroy**: Hibernasi massal.
- **Restore**: Restore murid + restorasi enrollment terkait + sync snapshot.

Phone normalization: strip non-digit, pastikan prefix `08`.

### 5.2 Manajemen Guru (`TeacherController`)

- **CRUD** lengkap + soft-delete pattern.
- **BulkDestroy** massal.
- **approvePhoto**: Set `profile_photo_approved = true`.
- **changePassword**: Reset password, clear `must_change_password`.
- **completeData / submitCompleteData**: Alur self-service bagi guru baru untuk melengkapi profil (nama, mata pelajaran, bank, tarif).

Phone uniqueness enforced via DB constraint.

### 5.3 Manajemen Orang Tua (`ParentController`)

- **CRUD** lengkap + cascading.
- **hibernate**: Hibernasi parent + semua student-nya + user parent.
- **restore**: Restore parent + semua student + user parent.
- **bulkDestroy**: Massal cascading hibernasi.
- **addStudent**: Tambah student ke parent yang ada (dengan cek duplikat nickname per parent).
- **removeStudent**: Hibernasi satu student dari parent.
- **changePassword**: Reset password ortu.

### 5.4 Manajemen Program (`ProgramController`)

- CRUD + soft-delete.
- Tipe: `privat` atau `kelas`.
- **syncTeacherRates**: Untuk program `kelas`, sync per-guru rate via `TeacherProgramRate`.

### 5.5 Manajemen Enrollment (`EnrollmentController`)

Enrollments menghubungkan Murid–Program–Guru dengan pricing.

**Pricing Tiers (JSON):**
```json
{
  "1": {"parent_rate": 150000, "teacher_rate": 100000},
  "2": {"parent_rate": 200000, "teacher_rate": 130000}
}
```
Tier `1` = 1 murid hadir, tier `2` = 2 murid hadir, dst.

**Mode `kelas`** vs **`privat`**:
- `kelas`: exactly 1 student per enrollment; teacher_id nullable (dikelola via `ClassSession`).
- `privat` with multiple students: `teacher_rate` dan `parent_rate` nullable (mengacu ke tier).

**Hibernate (destroy)**: Set status `hibernasi`, soft-delete. Attendances tetap utuh (historical record).
**BulkDestroy**: Hibernasi yang tidak punya attendance; sisanya tetap di-hibernate.

### 5.6 Presensi Admin (`Admin\MonthlyAttendanceController`)

- **Index**: Daftar semua attendance record.
- **Show**: Detail satu record + deteksi placeholder student untuk kelas.
- **updateEnrollment**: Pindahkan attendance ke enrollment lain. Guard: placeholder student hanya bisa ke enrollment kelas.
- **validateAttendance**: Set status `terima` / `terlambat` / `ditolak`, catat `validated_by`.

### 5.7 Presensi Kelas (`ClassStudentSessionController`)

Mengelola `ClassSession` dan auto-generate `MonthlyAttendance`.

**Alur create (dalam transaksi DB):**
1. Buat `ClassSession` (tanggal, program, catatan).
2. Attach guru ke session (pivot `class_session_teacher` + rate).
3. Untuk setiap enrollment di kelas tersebut:
   - Buat `MonthlyAttendance` (status `pending`, tariff dari enrollment pricing).
   - Attach student dengan `total_present = 1`.
   - Set `enrollment.validation_status = 1`.

**Edit session**: Deteksi perubahan enrollment/student, hapus attendance orphan, buat ulang yang baru.

**Destroy session**: Hapus semua attendance terkait, reset `validation_status`.

### 5.8 Edit Student Kelas (`ClassAttendanceController`)

Step terpisah setelah `ClassSession` dibuat — mengisi student yang hadir pada attendance kelas.

### 5.9 Review Keberatan Ortu (`AttendanceReviewController`)

Ketika ortu keberatan atas suatu presensi:

- **upholdParentRejection**: `status_validation = 'ditolak'`, `parent_review_status = 'rejected'`. Record keluar dari kalkulasi billing.
- **dismiss**: `parent_review_status = 'dismissed'`. Record tetap dalam billing.

### 5.10 Analisis & Keuangan (`AnalysisController`)

Halaman paling kompleks — financial hub admin.

**`ortu()` view**: Billing per ortu per periode.
- Privat: per-session, Rp 5.000 penalty jika attendance < 50% agreed sessions.
- Kelas: 50% rule (≤50% kehadiran = bayar 50% dari tarif).
- Diskon per enrollment–student–periode.
- Generate invoice PDF.
- Tampilkan status notifikasi WA.

**`guru()` view**: Salary per guru per periode.
- 10% penalty per sesi `terlambat` (jika fitur aktif).
- Generate slip gaji PDF.
- Tampilkan status notifikasi WA.

**`paymentsOrtu()` / `paymentsGuru()`**: Tracking pembayaran. Upload/approve bukti transfer (ortu).

**`updateParentPayment` / `updateTeacherPayment`**: Set status bayar.

**`confirmPaymentProof`**: Approve/reject bukti transfer.

**`updateEnrollmentDiscount`**: Set diskon (percent / amount / final / none).

**`generateInvoice` / `generateSalary`**: Generate + serve PDF.

### 5.11 Keuangan Dashboard (`FinanceController`)

Dashboard finansial dengan gross revenue, teacher cost, net income, dan chart historical.

**Perhitungan Gross Revenue:**
- Privat: `SUM(total_present × parent_rate)` — exclude `pending` & `rejected`.
- Kelas: Subquery korelasi hitung attendance percentage per enrollment-student. Jika ≤50% → charge 50% parent_rate. Jika >50% → full rate.

**Teacher Cost:**
- `terima`: `SUM(rate)`
- `terlambat`: `SUM(rate × 0.9)` (potong 10%)

**Net Income**: `gross - teacherCost`

**Chart**: Monthly/yearly aggregate dari snapshot tables + raw data.

### 5.12 Dokumen (`Admin\DocumentController`)

Upload file (max 50MB, tipe: PDF/Office/gambar).

**Access control**:
- `teacher`: Hanya guru yang di-assign via pivot.
- `password`: Dokter kata sandi (session-based unlock).

**Protection levels**:
- `standard`: Signed URL 5 menit untuk bookmark.
- `strict`: Tidak bisa didownload; watermark di-view.

**Watermarking**: GD-based, tile diagonal semi-transparent (nama guru + timestamp). Fallback ke original jika error.

### 5.13 Lesson Offers (`LessonOfferController`)

Iklan penawaran les yang dilihat guru di `/guru/tawaran`.

- Code auto-generate: `LO-XXXXXX`.
- Schedules disimpan sebagai JSON array.
- Status: `open` / `closed`.

### 5.14 Diskon (`DiscountController`)

Bulk discount per enrollment–student–periode. Tipe: percent / amount / final. Jika value = 0, hapus record diskon.

### 5.15 Pengaturan Denda (`FineSettingsController`)

Dua toggle di `settings` table:
- `fine.attendance_penalty_enabled` — Aktifkan penalty attendance Rp 5.000/sesi.
- `fine.late_penalty_enabled` — Aktifkan penalty 10% per sesi terlambat.

Cached 30 hari via `AttendanceFineService`.

### 5.16 Akun Bank (`BankAccountController`)

CRUD rekening bank bimbel (untuk ditampilkan di WA message tagihan).

### 5.17 Riwayat & Audit (`HistoryController`)

- Riwayat presensi per murid atau guru (filterable by bulan/tahun).
- Riwayat pembayaran per periode.
- Audit log lengkap (who, what, before, after).

### 5.18 Export (`ExportController`)

Export ke CSV, Excel (.xlsx), PDF untuk:
- Master murid & guru
- Enrollment / les
- Presensi (keseluruhan & bulanan)
- Audit log
- **Database backup** (SQLite file download)

### 5.19 Pendaftaran Baru (`NewStudentController`)

Konversi lead pendaftaran murid → Student + ParentModel + User.

### 5.20 Pendaftaran Guru (`TeacherRegistrantController`)

Konversi lead pendaftaran guru → Teacher + User (role: guru).

---

## 6. Modul Guru

### 6.1 Dashboard Guru

Ringkasan personal: enrollments aktif, rata-rata siswa per enrollment, statistik bulanan.

### 6.2 Presensi (`Guru\MonthlyAttendanceController`)

**Mode `privat`**:
- Buat presensi per sesi: pilih enrollment → pilih siswa yang hadir → simpan.
- Validasi: minimal 1 siswa dipilih.
- Status: `terima` jika ≤3 hari dari tanggal les; `terlambat` jika lebih dari 3 hari.
- Image upload (foto bukti).

**Mode `kelas`** (dari halaman guru):
- Tampilan serupa, enrollment bertipe `kelas`.

**Bulk create**: Banyak sesi sekaligus untuk enrollment yang sama. Skip baris duplikat tanggal, tampilkan semua error sekaligus.

**Edit**: Hanya record `terlambat` yang bisa diedit.

### 6.3 Proyeksi Gaji (`Guru\SalaryProjectionController`)

 Kalkulasi gaji bulanan + chart 6 bulan historical.
- Filter: hanya `terima` & `terlambat` (validasi, bukan pending/rejected).
- Penalty: 10% potong per sesi `terlambat` (jika fitur aktif).
- Session count × rate, bukan dari total `total_present`.

### 6.4 Riwayat (`Guru\HistoryController`)

Riwayat presensi per periode. Group by enrollment. Tampilkan: jumlah sesi, rate, total salary, student list, payment status.

### 6.5 Dokumen (`Guru\DocumentController`)

Lihat, view dengan watermark, dan download dokumen.

- **Password unlock**: POST verify → session flag → access granted.
- **Strict documents**: Tidak bisa didownload; watermark mandatory.
- **Access logging**: Semua view/download dicatat ke `document_access_logs`.

### 6.6 Tawaran Les (`Guru\LessonOfferController`)

Melihat semua `LessonOffer` dengan status `open`.

---

## 7. Modul Orang Tua & Murid

### 7.1 Dashboard Ortu

Ringkasan: daftar anak, enrollment aktif, ringkasan tagihan.

### 7.2 Tagihan (`Parent\BillingController`)

- **Index**: Grup attendances per periode (YYYY-MM). Total per periode dari `CalculationService`.
- **Upload bukti transfer**: Simpan ke `photo/transfer-proof/parent_{id}/`. Set `payment_proof_status = pending`.
- **Lengkapi data**: Form untuk nama ortu, alamat, nama/nickname anak. Diperlukan sebelum invoice bisa digenerate.
- **Download invoice**: Generate PDF via `InvoiceService` → serve.

### 7.3 Riwayat (`Parent\HistoryController`)

- Daftar attendances per periode.
- **Tolak presensi**: `parent_review_status = pending`, simpan alasan, kirim email ke admin via job.
- **Batalkan penolakan**: Clear status.

### 7.4 Murid (single-child view) (`Murid\*`)

Controller `Murid/BillingController` dan `Murid/HistoryController` adalah versi single-student dari portal ortu. Mengasumsikan satu akun ortu = satu anak. Tidak menampilkan tabs/selector antar-anak.

---

## 8. Portal Publik (Tanpa Login)

### 8.1 Pendaftaran Murid (`RegisterStudentController`)

URL: `/register-student/{token}`

- Token tetap (`daftar-murid-bimbel-gracia`) → pendaftaran terbuka kapan saja.
- Token random → dari link yang admin bagikan.
- Form: nama ortu, WhatsApp, alamat, array siswa (nickname).
- Duplicate check: phone + student nickname yang belum dikonversi → blokir.
- Simpan ke `new_students`. Admin mengkonversi manual.

### 8.2 Pendaftaran Guru (`RegisterTeacherController`)

URL: `/register-teacher/{token}`

- Form: nama, WhatsApp, major, subjects, alamat, bank.
- Simpan ke `teacher_registrants`. Admin mengkonversi manual.

---

## 9. Sistem Billing & Perhitungan Gaji

### 9.1 CalculationService

**`calculateStudentBilling(Student, month, year, attendances)`**

*Privat:*
```
baseRate = enrollment.getParentRateForCount(presentCount)
sessions = attendances where student.total_present > 0
subtotal = sessions.count × baseRate
if penaltyEnabled && enrollment.hasAttendancePenalty():
    subtotal += 5000 × sessions.count
discount = EnrollmentStudentDiscount (applied on inflated subtotal)
total = subtotal - discount
```

*Kelas:*
```
studentAttendance = SUM(total_present) across all sessions
attendancePercent = studentAttendance / enrollment.agreed_sessions_per_month
rate = attendancePercent <= 0.5
         ? parent_rate × 0.5
         : parent_rate
discount applied on final rate
```

**`calculateTeacherSalary(teacherId, month, year, attendances)`**

*Privat:*
```
gross = SUM(sessions.count × rate)
latePenalty = SUM(rate × 0.1) for each terlamat session
total = gross - latePenalty
```

*Kelas:*
- Rate dari pivot `class_session_teacher`.
- Late penalty sama.

### 9.2 AttendanceFineService

Feature flag dari `settings` table, cached 30 hari.

- `isAttendancePenaltyEnabled()` → gate Rp 5.000/sesi penalty
- `isLatePenaltyEnabled()` → gate 10% penalty per sesi terlambat

---

## 10. Sistem PDF

### 10.1 InvoiceService

Generate tiga jenis PDF via DomPDF:

1. **Student Invoice** (`pdf/student-invoice.blade.php`)
   - Per-student, per-periode
   - Rincian per enrollment: jumlah sesi, rate, subtotal, diskon, penalty, total
   - Info pembayaran: rekening tujuan dari `config/bimbel.payment_accounts`

2. **Parent Invoice** (`pdf/parent-invoice.blade.php`)
   - Gabungan semua anak satu ortu
   - Grand total across all students

3. **Teacher Salary Slip** (`pdf/teacher-salary.blade.php`)
   - Per-guru, per-periode
   - Rincian per enrollment: sesi, rate, late count, penalty, net salary

### 10.2 PdfTokenService

- Generate 32-char random token → `pdf_access_tokens` table.
- Expire: 7 hari.
- Cleanup: hapus fisik file + record saat expire.

### 10.3 PdfController

Serve PDF via token (tanpa login):
- Cek kelengkapan data (redirect ke complete-data jika belum lengkap).
- Stream from public storage dengan filename renamed (`Tagihan_YYYY_MM.pdf`, `Slip_Gaji_YYYY_MM.pdf`).

---

## 11. Template & View Utama

### 11.1 Blade Layouts

| File | Fungsi |
|---|---|
| `layouts/app.blade.php` | Layout utama sidebar + navbar |
| `layouts/guest.blade.php` | Layout guest (login, register) |
| `layouts/navigation.blade.php` | Navbar dinamis per role |

### 11.2 Dashboard

- `dashboard.blade.php` — generic dashboard (Breeze default, untuk semua role)
- `admin/finance/dashboard.blade.php` — dashboard finansial admin
- `guru/dashboard.blade.php` — dashboard guru
- `parent/dashboard.blade.php` — dashboard ortu

### 11.3 PDF Templates

- `pdf/student-invoice.blade.php`
- `pdf/parent-invoice.blade.php`
- `pdf/teacher-salary.blade.php`

---

## 12. Trait & Helper

### Auditable Trait
Auto-logs semua perubahan model ke `audit_logs`. Event: `created`, `updated`, `deleted`, `restored`. Menyimpan before/after snapshot. Mendukung `$auditExclude` untuk field sensitif (password, dll).

### FormatsWhatsappNumber Trait
Normalisasi nomor HP Indonesia:
```
081234567890    → 081234567890
+6281234567890  → 081234567890
6281234567890   → 081234567890
```
Strip non-digit → truncate 13 char → convert leading `62` to `0` → ensure `0` prefix.

### SearchAndSort Trait
Reusable query builder:
- `applySearch($query, $search, $columns)` — multi-column OR search, handles dot-notation relation prefix
- `applySort($query, $sort, $direction, $allowedColumns)` — whitelist sorting
- `getSearchSortParams($request)` — extract dari request

---

## 13. Alur Data Utama

### 13.1 Alur Pendaftaran Murid Baru

```
1. Admin bagikan link: /register-student/{permanent_token}
2. Orang tua mengisi form → NewStudent record created
3. Admin buka /admin/new-students
4. Admin klik "Konversi" → buat ParentModel + User(Parent) + Student(s)
5. Orang tua login dengan phone + default password
6. Diminta ganti password → redirect /password/force
7. Lengkapi profil → /parent/complete-data
```

### 13.2 Alur Pendaftaran Guru Baru

```
1. Admin bagikan link: /register-teacher/{token}
2. Guru mengisi form → TeacherRegistrant record
3. Admin buka /admin/teacher-registrants
4. Admin klik "Konversi" → buat User(Guru) + Teacher
5. Guru login, ganti password, lengkapi data diri
```

### 13.3 Alur Pencatatan Presensi Privat

```
1. Guru buka /guru/presensi/create
2. Pilih enrollment → pilih siswa yang hadir
3. Submit → MonthlyAttendance created (status: terima/terlambat)
4. Admin buka /admin/presensi → validasi
5. Ortu buka /parent/riwayat → bisa menolak (pending)
6. Admin review /admin/notifikasi-presensi
7. Uphold / Dismiss penolakan
```

### 13.4 Alur Penagihan

```
1. Admin buka /admin/analysis/ortu
2. Pilih bulan/tahun → kalkulasi per ortu via CalculationService
3. Terapkan diskon jika ada
4. Generate invoice PDF → token URL
5. Kirim WA (manual) dengan link PDF
6. Ortu bayar → upload bukti transfer /parent/tagihan
7. Admin approve bukti /admin/payments/ortu
8. Set parent_payment_status = paid
```

### 13.5 Alur Pembayaran Gaji Guru

```
1. Admin buka /admin/analysis/guru
2. Pilih bulan/tahun → kalkulasi per guru
3. Late penalty 10% jika ada sesi terlambat
4. Generate salary slip PDF → token URL
5. Kirim WA dengan slip
6. Admin set teacher_payment_status = paid / held
```

### 13.6 Alur Snapshot Bulanan

```
Enrollment created/updated/deleted
  → EnrollmentController → MonthlySnapshotSyncService::syncAll()
  → monthly_student_snapshots (private_students_count, class_students_count)
  → monthly_teacher_snapshots (teachers_count)
  → FinanceController charts (historical data)
```

---

## 14. Konfigurasi Global (`config/bimbel.php`)

| Key | Default | Fungsi |
|---|---|---|
| `default_password` | `'password'` | Password default semua akun baru |
| `admin_whatsapp` | `081703027942` | Nomor WA admin (untuk notifikasi) |
| `admin_email` | `mybimbelgracia@gmail.com` | Email admin (untuk invoice) |
| `payment_accounts` | BCA, Mandiri, OVO | Rekening tujuan pembayaran |
| `class_student_placeholder` | `'Murid Kelas Bersama'` | Label student placeholder di kelas |

---

## 15. Console Commands

| Command | Fungsi |
|---|---|
| `import:enrollment-privat` | Bulk import dari Google Spreadsheet CSV |
| `snapshot:monthly-students` | Sync student snapshot untuk bulan ini |
| `snapshot:monthly-teachers` | Sync teacher snapshot untuk bulan ini |
| `cleanup:old-files` | Hapus file lama (tempat sampah) |
| `generate:all-pdfs` | Regenerate semua PDF invoice/slip |

---

## 16. Middleware & Keamanan

### Rate Limiting Login
- 5 percobaan per phone+IP, lockout via Laravel throttle.
- Soft-deleted account tetap bisa login attempt tapi selalu gagal (tidak reveal apakah akun ada).

### Password Force
- Semua akun baru: `must_change_password = true`.
- Setelah admin reset password → juga di-set `true`.
- Middleware `EnsurePasswordChanged` redirect semua request ke `/password/force`.

### Account Deactivation
- Soft-delete user (bukan hard delete).
- Middleware `EnsureAccountActive` mendeteksi via `withoutGlobalScopes` + `onlyTrashed()`.

### Document Security
- Strict documents: tidak bisa didownload, watermark wajib, signed URL 5 menit.
- Password documents: session-based unlock.
- Full access logging ke `document_access_logs`.

---

## 17. ER Summary

```
User ──────────(1:1)── Teacher
  │                        │
  │                        ├── enrollments (1:N) ──── Program
  │                        │                            │
  │                        │                      teacher_program_rates
  │                        │                            │
  └────(1:1)── ParentModel─┴── students (1:N) ───────────┘
                            │
                            └── enrollments (N:M via enrollment_student)
                                     │
                                     └── enrollments.attendances (1:N) ─── MonthlyAttendance
                                                    │
                                                    └── attendance_student (N:M) ─── Student
```
