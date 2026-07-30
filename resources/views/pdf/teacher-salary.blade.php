<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $teacher->name }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #059669; padding-bottom: 15px; }
        .header h1 { color: #059669; margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        .info { margin-bottom: 20px; }
        .info table { width: 100%; }
        .info td { padding: 3px 0; }
        .info .label { font-weight: bold; width: 140px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items th { background: #059669; color: white; padding: 8px 10px; text-align: left; font-size: 11px; }
        table.items td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        table.items tr:nth-child(even) { background: #f9fafb; }
        .summary { margin-top: 20px; border-top: 2px solid #059669; padding-top: 10px; }
        .summary table { width: 100%; }
        .summary td { padding: 4px 0; }
        .summary .label { font-weight: bold; }
        .summary .right { text-align: right; }
        .final { font-size: 16px; font-weight: bold; color: #059669; }
        .footer { text-align: center; margin-top: 40px; color: #999; font-size: 10px; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SLIP GAJI</h1>
        <p>Bimbel Gracia</p>
    </div>

    <div class="info">
        <table>
            <tr><td class="label">Nama Guru</td><td>: {{ $teacher->name }}</td></tr>
            <tr><td class="label">Periode</td><td>: {{ $monthName }} {{ $year }}</td></tr>
            <tr><td class="label">Tanggal</td><td>: {{ now()->format('d/m/Y') }}</td></tr>
            @if ($teacher->bank_name)
            <tr><td class="label">Rekening</td><td>: {{ $teacher->bank_name }} a/n {{ $teacher->bank_owner ?? $teacher->name }} ({{ $teacher->bank_account }})</td></tr>
            @endif
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Murid / Program</th>
                <th>Tarif</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
                <th>Denda</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['student'] }} ({{ $row['program'] }})</td>
                    <td>Rp {{ number_format($row['rate']) }}</td>
                    <td>{{ $row['count'] }}x</td>
                    <td>Rp {{ number_format($row['total']) }}</td>
                    <td>{{ $row['penalty'] > 0 ? '-Rp '.number_format($row['penalty']) : '-' }}</td>
                    <td>Rp {{ number_format($row['total'] - $row['penalty']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr><td class="label">Total Gaji Kotor</td><td class="right">Rp {{ number_format($grandTotal) }}</td></tr>
            @if ($totalPenalty > 0)
            <tr><td class="label">Total Denda</td><td class="right">-Rp {{ number_format($totalPenalty) }}</td></tr>
            @endif
            <tr><td class="label final">Gaji Bersih</td><td class="right final">Rp {{ number_format($finalTotal) }}</td></tr>
        </table>
    </div>

    <div class="footer">
        <p>Terima kasih atas dedikasi dan kerja keras Anda</p>
        <p>Dokumen ini dibuat secara otomatis</p>
    </div>
</body>
</html>