<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Cetak') — SMK Negeri 5 Surakarta</title>
    <style>
        @page { size: A4; margin: 12mm 13mm; }
        body { font-family: "Times New Roman", Times, serif; font-size: 11.5px; color: #111; margin: 24px; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { text-align: center; font-weight: bold; }
        .kop { text-align: center; border-bottom: 3px double #333; padding-bottom: 8px; margin-bottom: 10px; }
        .kop h1 { margin: 0; font-size: 17px; text-transform: uppercase; }
        .kop p { margin: 2px 0; font-size: 11px; }
        .judul-bagian { text-align: center; font-weight: bold; text-transform: uppercase; font-size: 12.5px; margin: 18px 0 4px; letter-spacing: .3px; }
        .subjudul { font-weight: bold; margin: 12px 0 2px; }
        /* Biodata ala HAL_PRINT: tanpa garis, nomor - label - titik dua - isi */
        table.bio, table.bio td { border: none; padding: 2px 4px; }
        table.bio td.no { width: 26px; text-align: right; }
        table.bio td.lbl { width: 36%; }
        table.bio td.titik { width: 12px; text-align: center; }
        table.bio td.sub { padding-left: 30px; }
        table.bio td.isi { text-transform: uppercase; }
        /* Blok identitas laporan nilai */
        table.identitas, table.identitas td { border: none; padding: 2px 4px; }
        table.identitas td.lbl { width: 30%; font-weight: bold; }
        table.identitas td.titik { width: 12px; text-align: center; }
        /* Tabel nilai & tabel isian */
        table.nilai { font-size: 11px; }
        table.nilai th { background: #eee; font-size: 10.5px; }
        table.nilai td.angka { text-align: center; width: 44px; }
        table.nilai td.no { text-align: center; width: 26px; }
        table.nilai tr.grup td { font-weight: bold; background: #f2f2f2; }
        table.nilai td.tinggi { height: 26px; }
        td.tengah { text-align: center; }
        .catatan { font-size: 10.5px; font-style: italic; margin-top: 4px; }
        .halaman-baru { page-break-before: always; }
        table.ttd, table.ttd td { border: none; text-align: center; padding: 2px 6px; }
        table.ttd td.ruang { height: 56px; }
        .no-print { margin-bottom: 16px; font-family: Arial, Helvetica, sans-serif; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            table.nilai tr.grup td { background: #f2f2f2; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
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
