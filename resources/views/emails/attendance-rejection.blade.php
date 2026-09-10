<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Penolakan Presensi - Bimbel Gracia</title>
    <style>
        body { font-family: 'Figtree', 'Segoe UI', sans-serif; background: #f9fafb; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .header { background: #4f46e5; padding: 24px 32px; }
        .header h1 { color: #ffffff; font-size: 20px; margin: 0; }
        .body { padding: 32px; }
        .badge { display: inline-block; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 6px; padding: 4px 12px; font-size: 12px; font-weight: 600; margin-bottom: 16px; }
        .detail-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .detail-table td { padding: 10px 0; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .detail-table td:first-child { color: #6b7280; width: 40%; font-size: 13px; }
        .detail-table td:last-child  { color: #111827; font-size: 14px; font-weight: 500; }
        .reason-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; margin: 20px 0; }
        .reason-box .label { font-size: 11px; font-weight: 700; color: #92400e; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
        .reason-box .text  { font-size: 14px; color: #78350f; margin: 0; }
        .footer { background: #f9fafb; padding: 16px 32px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bimbel Gracia</h1>
        </div>
        <div class="body">
            <div class="badge">PENOLAKAN PRESENSI</div>
            <p style="font-size: 14px; color: #374151; margin: 0 0 20px;">
                Yth. Admin Bimbel Gracia,<br>
                Terdapat penolakan presensi dari orang tua/wali yang memerlukan perhatian Anda.
            </p>

            <table class="detail-table">
                <tr>
                    <td>Murid</td>
                    <td>{{ $attendance->students->first()?->display_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Program</td>
                    <td>{{ $attendance->enrollment?->program?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Guru</td>
                    <td>{{ $attendance->enrollment?->teacher?->displayName ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Tanggal Les</td>
                    <td>{{ $attendance->lesson_date?->format('d/m/Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Bulan / Tahun</td>
                    <td>{{ $attendance->month }}/{{ $attendance->year }}</td>
                </tr>
                <tr>
                    <td>Diajukan Oleh</td>
                    <td>{{ $rejectedByName ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Waktu Penolakan</td>
                    <td>{{ $attendance->parent_reviewed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                </tr>
            </table>

            <div class="reason-box">
                <div class="label">Alasan Penolakan</div>
                <p class="text">{{ $rejectionReason }}</p>
            </div>

            <p style="font-size: 13px; color: #6b7280;">
                Silakan buka dashboard admin untuk mengonfirmasi atau menolak penolakan ini.
            </p>
        </div>
        <div class="footer">
            Bimbel Gracia &mdash; email ini dikirim secara otomatis.
        </div>
    </div>
</body>
</html>
