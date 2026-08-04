<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $student->name }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #4f46e5; padding-bottom: 15px; }
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
        <h1>INVOICE</h1>
        <p>Bimbel Gracia</p>
    </div>

    <div class="info">
        <table>
            <tr><td class="label">Nama Murid</td><td>: {{ $student->name }}</td></tr>
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
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['program'] }} - {{ $row['teacher'] }}</td>
                    <td>Rp {{ number_format($row['rate']) }}</td>
                    <td>{{ $row['count'] }}x</td>
                    <td>Rp {{ number_format($row['total']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total: Rp {{ number_format($grandTotal) }}
    </div>

    <div class="footer">
        <p>Terima kasih atas kepercayaan Anda kepada Bimbel Gracia</p>
        <p>Dokumen ini dibuat secara otomatis</p>
    </div>
</body>
</html>