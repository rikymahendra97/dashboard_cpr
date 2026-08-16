<?php
/**
 * =============================================================================
 * File Name    : export_excel_switch.php
 * Modul        : VM Switch IP
 * Purpose      : Merender data hasil query ke dalam format file .xls (MS Excel).
 * Architecture : Memory-Safe I/O Flushing, Excel Protection Meta
 * =============================================================================
 */

$filename = $filename ?? "Laporan_Switch_IP_" . date("Ymd_His");
$periode = $periode ?? "Periode Tidak Diketahui";
$headers = $headers ?? [];
$temp_fp = $temp_fp ?? null;
$summary = $summary ?? [
    "ganti_ip" => 0,
    "tukar_silang" => 0,
    "done" => 0,
    "pending" => 0,
    "total_tiket" => 0,
];

$filename = str_ireplace([".xls", ".xlsx"], "", $filename);
header("Content-Type: application/vnd-ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename.xls\"");
header("Pragma: no-cache");
header("Expires: 0");

ini_set("memory_limit", "512M");

// Injeksi UTF-8 BOM agar kompatibel dengan Excel versi lawas
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html xmlns:x="urn:schemas-microsoft-com:office:excel">

<head>
    <meta charset="utf-8">
    <meta name="Excel-Injection-Protection" content="active">
    <style>
        body { font-family: 'Calibri', Arial, sans-serif; }
        .str { mso-number-format: "\@"; }
        .gs-table { border-collapse: collapse; font-size: 10pt; width: 100%; }
        .gs-table th, .gs-table td { border: 1px solid #777777; padding: 5px; vertical-align: middle; }
        .gs-table th { background-color: #4A6E8E; font-weight: bold; color: #ffffff; text-align: center; height: 30px; }
        .summary-table { border-collapse: collapse; font-size: 10pt; margin-bottom: 15px; }
        .summary-table th, .summary-table td { border: 1px solid #cbd5e1; padding: 6px 12px; vertical-align: middle; }
        .summary-table th { background-color: #f1f5f9; color: #334155; font-weight: bold; }
        .bg-title { background-color: #2A3F54; color: #ffffff; font-weight: bold; text-align: center; }
    </style>
</head>

<body>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            <td colspan="<?= count(
                $headers,
            ) ?>" style="text-align: left; font-size: 16pt; font-weight: bold; color: #2A3F54;">
                LAPORAN RE-ALOKASI & PERUBAHAN IP VIRTUAL MACHINE
            </td>
        </tr>
        <tr>
            <td colspan="<?= count(
                $headers,
            ) ?>" style="text-align: left; font-size: 10pt; color: #555555; border-bottom: 2px solid #2A3F54;">
                Periode Data: <?= html_escape($periode) ?> &nbsp;|&nbsp; Tanggal Unduh: <?= date(
     "d-M-Y H:i",
 ) ?> WIB
            </td>
        </tr>
    </table>
    <table><tr><td></td></tr></table>

    <table class="summary-table">
        <tr>
            <td colspan="4" class="bg-title" style="font-size: 11pt; padding: 8px;">EXECUTIVE SUMMARY (PERIODE INI)</td>
        </tr>
        <tr>
            <th align="left" style="width: 250px;">METRIKS UTAMA PERTIKETAN</th>
            <th align="center" style="width: 150px; color: #15803d;">SELESAI / DONE</th>
            <th align="center" style="width: 150px; color: #b91d47;">PARSIAL / PENDING</th>
            <th align="center" style="width: 150px;">TOTAL REQ TIKET</th>
        </tr>
        <tr>
            <td><b>Jumlah Tiket IRIS</b></td>
            <td align="center" style="color: #15803d; font-weight: bold;"><?= number_format(
                $summary["done"],
            ) ?> Tiket</td>
            <td align="center" style="color: #b91d47; font-weight: bold;"><?= number_format(
                $summary["pending"],
            ) ?> Tiket</td>
            <td align="center" style="font-weight: bold;"><?= number_format(
                $summary["total_tiket"],
            ) ?> Tiket</td>
        </tr>
        <tr><td colspan="4" style="border: none; height: 15px;"></td></tr>
        <tr>
            <th align="left">BREAKDOWN SKENARIO AKSI</th>
            <th align="center" style="color: #3498db; font-weight: bold;">GANTI IP (SINGLE)</th>
            <th align="center" style="color: #e67e22; font-weight: bold;">TUKAR SILANG (DUAL)</th>
            <th align="center">TOTAL EKSEKUSI</th>
        </tr>
        <tr>
            <td><b>Jumlah Transaksi Perubahan IP</b></td>
            <td align="center" style="color: #3498db; font-weight: bold;"><?= number_format(
                $summary["ganti_ip"],
            ) ?> VM</td>
            <td align="center" style="color: #e67e22; font-weight: bold;"><?= number_format(
                $summary["tukar_silang"],
            ) ?> VM</td>
            <td align="center" style="font-weight: bold;"><?= number_format(
                $summary["ganti_ip"] + $summary["tukar_silang"],
            ) ?> VM</td>
        </tr>
    </table>
    <table><tr><td></td></tr></table>

    <table class="gs-table">
        <thead>
            <tr>
                <?php foreach ($headers as $head): ?>
                    <th><?= strtoupper(html_escape($head)) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php // STREAMING SELURUH DATA DARI MEMORI SEMENTARA TANPA MEMBEBANI RAM

if (isset($temp_fp) && is_resource($temp_fp)) {
                rewind($temp_fp);
                fpassthru($temp_fp);
                fclose($temp_fp);
            } else {
                echo '<tr><td colspan="' .
                    count($headers) .
                    '" align="center" style="font-style: italic;">Tidak ada data log pada filter tanggal tersebut.</td></tr>';
            } ?>
        </tbody>
    </table>
</body>
</html>
