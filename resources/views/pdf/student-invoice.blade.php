<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $student->display_name }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #4f46e5; padding-bottom: 15px; }
        .header img { max-height: 60px; margin-bottom: 8px; }
        .header h1 { color: #4f46e5; margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        .info { margin-bottom: 20px; }
        .info table { width: 100%; }
        .info td { padding: 3px 0; }
        .info .label { font-weight: bold; width: 120px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items th { background: #4f46e5; color: white; padding: 8px 10px; text-align: left; font-size: 11px; }
        table.items td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        table.items tr:nth-child(even) { background: #f9fafb; }
        .total { text-align: right; font-size: 16px; font-weight: bold; margin-top: 10px; padding-top: 10px; border-top: 2px solid #4f46e5; }
        .footer { text-align: center; margin-top: 40px; color: #999; font-size: 10px; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('storage/website/logo_bimbel.jpg') }}" alt="Bimbel Gracia" />
        <h1>INVOICE</h1>
        <p>Bimbel Gracia</p>
    </div>

    <div class="info">
        <table>
            <tr><td class="label">Nama Murid</td><td>: {{ $student->display_name }}</td></tr>
            <tr><td class="label">Periode</td><td>: {{ $monthName }} {{ $year }}</td></tr>
            <tr><td class="label">Tanggal</td><td>: {{ now()->format('d/m/Y') }}</td></tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Program / Guru</th>
                <th>Tarif</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
                <th>Diskon</th>
                <th>Denda</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['program'] }} - {{ $row['teacher'] }}{{ $row['detail'] }}</td>
                    <td>Rp {{ number_format($row['rate']) }}</td>
                    <td>{{ $row['count'] }}x</td>
                    <td>Rp {{ number_format($row['subtotal']) }}</td>
                    <td>
                        @if (($row['discount'] ?? 0) > 0)
                            <span style="color:#b91c1c;">-Rp {{ number_format($row['discount']) }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if (($row['penalty'] ?? 0) > 0)
                            <span style="color:#b91c1c;">+Rp {{ number_format($row['penalty']) }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>Rp {{ number_format($row['total']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total: Rp {{ number_format($grandTotal) }}
    </div>

    @if ($penaltyInfo)
        <div class="penalty-info" style="margin-top: 15px; padding: 10px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px;">
            <p style="margin: 0; font-size: 11px; color: #991b1b;">
                <strong>⚠️ Peringatan Absensi Rendah</strong><br>
                Program <strong>{{ $penaltyInfo['program'] }}</strong>: Kehadiran {{ $penaltyInfo['attended'] }}x dari {{ $penaltyInfo['total_sessions'] }} pertemuan (target minimal {{ $penaltyInfo['agreed'] / 2 }}x).<br>
                Tarif per pertemuan akan naik <strong>Rp 5.000</strong> bulan depan jika kehadiran tetap rendah.
            </p>
        </div>
    @endif

    @php
        $bankAccounts = \App\Models\BankAccount::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get();
    @endphp

    @if ($bankAccounts->isNotEmpty())
        <div class="bank-info" style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
            <h3 style="font-size: 13px; font-weight: bold; margin: 0 0 8px;">Informasi Pembayaran</h3>
            <table style="width: 100%; font-size: 11px;">
                @foreach ($bankAccounts as $account)
                    <tr>
                        <td style="padding: 2px 0; width: 100px;">{{ $account->bank_name }}</td>
                        <td style="padding: 2px 0;">: a/n {{ $account->account_holder }} ({{ $account->account_number }})</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <div class="footer">
        <p>Terima kasih atas kepercayaan Anda kepada Bimbel Gracia</p>
        <p>Dokumen ini dibuat secara otomatis</p>
    </div>
</body>
</html>