<?php
/**
 * =============================================================================
 * File Name    : export_excel_vm_restart.php
 * Modul        : VM Restart
 * Purpose      : Merender data hasil query ke dalam format file .xls (MS Excel).
 * Architecture : Memory-Safe I/O Streaming (Unbuffered php://temp)
 * =============================================================================
 */

$filename = $filename ?? "Laporan_Restart_Server_" . date("Ymd_His");
$headers = $headers ?? [];
$periode = $periode ?? "Periode Tidak Diketahui";
$temp_fp = $temp_fp ?? null;
$summary = $summary ?? [
    "ticket" => ["done" => 0, "pending" => 0, "total" => 0],
    "vm" => ["sudah" => 0, "cancel" => 0, "menunggu" => 0, "total" => 0],
];

$filename = str_ireplace([".xls", ".xlsx"], "", $filename);
header("Content-Type: application/vnd-ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename.xls\"");
header("Pragma: no-cache");
header("Expires: 0");

ini_set("memory_limit", "512M");

// [ENTERPRISE FIX]: Injeksi UTF-8 BOM untuk kompatibilitas MS Excel
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
        .gs-table th, .gs-table td { border: 1px solid #777777; padding: 5px; vertical-align: top; }
        .gs-table th { background-color: #4A6E8E; font-weight: bold; color: #ffffff; text-align: center; }
        .summary-table { border-collapse: collapse; font-size: 10pt; margin-bottom: 15px; }
        .summary-table th, .summary-table td { border: 1px solid #cbd5e1; padding: 6px 12px; vertical-align: middle; }
        .summary-table th { background-color: #f1f5f9; color: #334155; font-weight: bold; }
        .bg-title { background-color: #e2e8f0; color: #1e293b; font-weight: bold; text-align: center; }
        .font-bold { font-weight: bold; }
        .text-success { color: #15803d; font-weight: bold; }
        .text-danger { color: #b91d47; font-weight: bold; }
    </style>
</head>

<body>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            <td colspan="<?= count(
                $headers,
            ) ?>" style="text-align: left; font-size: 14pt; font-weight: bold; color: #2A3F54;">
                LOG RESTART VIRTUAL MACHINE
            </td>
        </tr>
        <tr>
            <td colspan="<?= count(
                $headers,
            ) ?>" style="text-align: left; font-size: 10pt; color: #555555; border-bottom: 2px solid #4A6E8E; padding-bottom: 5px;">
                <b>Rentang Waktu:</b> <?= html_escape(
                    $periode,
                ) ?> &nbsp;|&nbsp; <b>Waktu Cetak:</b> <?= date("d M Y H:i") ?> WIB
            </td>
        </tr>
    </table>

    <table><tr><td></td></tr><tr><td></td></tr></table>

    <table class="summary-table">
        <tr>
            <td style="border: none;"></td>
            <td colspan="4" class="bg-title" style="font-size: 11pt; padding: 8px;">EXECUTIVE SUMMARY REPORT (PERIODE INI)</td>
        </tr>
        <tr>
            <td style="border: none;"></td>
            <th align="left" style="width: 250px;">METRIKS UTAMA PERTIKETAN</th>
            <th align="center" class="text-success" style="width: 150px;">SELESAI / DONE</th>
            <th align="center" class="text-danger" style="width: 150px;">PARSIAL / PENDING</th>
            <th align="center" style="width: 150px;">TOTAL REQ TIKET</th>
        </tr>
        <tr>
            <td style="border: none;"></td>
            <td><b>Jumlah Tiket IRIS (Kolektif)</b></td>
            <td align="center" class="text-success"><?= number_format(
                $summary["ticket"]["done"],
            ) ?> Tiket</td>
            <td align="center" class="text-danger"><?= number_format(
                $summary["ticket"]["pending"],
            ) ?> Tiket</td>
            <td align="center" class="font-bold"><?= number_format(
                $summary["ticket"]["total"],
            ) ?> Tiket</td>
        </tr>

        <tr><td colspan="5" style="border: none; height: 15px;"></td></tr>

        <tr>
            <td style="border: none;"></td>
            <th align="left">BREAKDOWN TARGET VM</th>
            <th align="center" class="text-success">SUDAH DIEKSEKUSI</th>
            <th align="center" style="color: #b45309; font-weight: bold;">CANCEL BY USER</th>
            <th align="center" class="text-danger">MENUNGGU EKSEKUSI</th>
            <th align="center">TOTAL VM TARGET</th>
        </tr>
        <tr>
            <td style="border: none;"></td>
            <td><b>Jumlah Akumulasi Objek VM</b></td>
            <td align="center" class="text-success"><?= number_format(
                $summary["vm"]["sudah"],
            ) ?> VM</td>
            <td align="center" style="color: #b45309; font-weight: bold;"><?= number_format(
                $summary["vm"]["cancel"],
            ) ?> VM</td>
            <td align="center" class="text-danger"><?= number_format(
                $summary["vm"]["menunggu"],
            ) ?> VM</td>
            <td align="center" class="font-bold"><?= number_format(
                $summary["vm"]["total"],
            ) ?> VM</td>
        </tr>
    </table>

    <table><tr><td></td></tr><tr><td></td></tr></table>

    <table class="gs-table">
        <thead>
            <tr>
                <?php foreach ($headers as $head): ?>
                    <th><?= strtoupper(html_escape($head)) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php // [ENTERPRISE FIX]: STREAMING SELURUH DATA DARI MEMORI SEMENTARA TANPA MEMBEBANI RAM

if (isset($temp_fp) && is_resource($temp_fp)) {
                rewind($temp_fp);
                fpassthru($temp_fp);
                fclose($temp_fp);
            } else {
                echo '<tr><td colspan="' .
                    count($headers) .
                    '" align="center" style="font-style: italic;">Tidak ada rincian data pada filter tanggal tersebut.</td></tr>';
            } ?>
        </tbody>
    </table>
</body>
</html>
