<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Cetak') — SMK Negeri 5 Surakarta</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #eee; }
        .kop { text-align: center; border-bottom: 3px double #333; padding-bottom: 10px; margin-bottom: 12px; }
        .kop h1 { margin: 0; font-size: 18px; }
        .kop p { margin: 2px 0; }
        .no-print { margin-bottom: 16px; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 16px; background:#166534; color:#fff; border:none; border-radius:6px; cursor:pointer;">Cetak / Simpan PDF</button>
        <a href="javascript:history.back()" style="margin-left:12px;">Kembali</a>
    </div>
    @yield('content')
</body>
</html>
