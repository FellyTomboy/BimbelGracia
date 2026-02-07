<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 24px;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }
        th {
            background: #f3f4f6;
        }
        .meta {
            color: #6b7280;
            font-size: 10px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Generated at {{ now()->format('Y-m-d H:i') }}</div>
    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}">No data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
