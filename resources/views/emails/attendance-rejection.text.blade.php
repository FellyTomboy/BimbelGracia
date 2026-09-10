PENOLAKAN PRESENSI - Bimbel Gracia
=================================

Yth. Admin Bimbel Gracia,

Terdapat penolakan presensi yang memerlukan perhatian Anda.

Detail Presensi:
  Murid        : {{ $attendance->students->first()?->display_name ?? '-' }}
  Program      : {{ $attendance->enrollment?->program?->name ?? '-' }}
  Guru         : {{ $attendance->enrollment?->teacher?->displayName ?? '-' }}
  Tanggal Les  : {{ $attendance->lesson_date?->format('d/m/Y') ?? '-' }}
  Bulan/Tahun  : {{ $attendance->month }}/{{ $attendance->year }}
  Diajukan Oleh: {{ $rejectedByName ?? '-' }}
  Waktu        : {{ $attendance->parent_reviewed_at?->format('d/m/Y H:i') ?? '-' }}

Alasan Penolakan:
{{ $rejectionReason }}

--
Bimbel Gracia - email ini dikirim secara otomatis.
