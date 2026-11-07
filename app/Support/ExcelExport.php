<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Unduh tabel sebagai file Excel (.xlsx) yang rapi di Excel.
 *
 * Kenapa bukan CSV: Excel memecah CSV mengikuti "List separator" regional
 * Windows (di Windows Indonesia biasanya ';', di Windows Inggris ','),
 * sehingga satu file CSV yang sama bisa terbuka rapi di satu laptop dan
 * menumpuk di satu kolom di laptop lain. File .xlsx tidak bergantung pada
 * pengaturan itu: tiap nilai selalu di kolomnya sendiri.
 */
class ExcelExport
{
    public static function unduh(array $header, array $rows, string $namaFile)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$header], null, 'A1');
        if ($rows) {
            $sheet->fromArray(array_values($rows), null, 'A2');
        }

        $kolomTerakhir = $sheet->getHighestColumn();
        $barisTerakhir = $sheet->getHighestRow();
        // Header tebal + baris header dibekukan saat scroll.
        $sheet->getStyle("A1:{$kolomTerakhir}1")->getFont()->setBold(true);
        $sheet->freezePane('A2');
        // Lebar kolom mengikuti isi, dibatasi 50 karakter (mis. kolom alamat)
        // dengan teks dibungkus supaya tidak melebar berlebihan.
        $sheet->calculateColumnWidths();
        foreach (range(1, count($header)) as $i) {
            $dim = $sheet->getColumnDimensionByColumn($i);
            if ($dim->getWidth() > 50) {
                $dim->setAutoSize(false);
                $dim->setWidth(50);
                $sheet->getStyleByColumnAndRow($i, 1, $i, $barisTerakhir)
                    ->getAlignment()->setWrapText(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $isi = ob_get_clean();
        $spreadsheet->disconnectWorksheets();

        return response($isi, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$namaFile.'"',
        ]);
    }

    /** Baca kembali file .xlsx (hasil unduh) menjadi array baris — dipakai di test. */
    public static function baca(string $isi): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($tmp, $isi);

        try {
            return \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)
                ->getActiveSheet()->toArray(null, true, true, false);
        } finally {
            @unlink($tmp);
        }
    }
}
