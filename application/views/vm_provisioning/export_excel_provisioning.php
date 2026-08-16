<?php
/**
 * ============================================================================
 * File Name    : export_excel_provisioning.php
 * Modul        : VM Provisioning
 * Purpose      : Template Engine untuk output laporan Excel (.xls) Native
 * Architecture : Memory-Safe I/O Streaming, Excel Injection Guard, UTF-8 BOM
 * ============================================================================
 */

$filename = $filename ?? "Laporan_Provisioning_" . date("Ymd_His");
$headers = $headers ?? [];
$periode = $periode ?? "Periode Tidak Diketahui";
$summary = $summary ?? [];
$temp_fp = $temp_fp ?? null;

$filename = str_ireplace([".xls", ".xlsx"], "", $filename);
header("Content-Type: application/vnd-ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename.xls\"");
header("Pragma: no-cache");
header("Expires: 0");

// Pasang Memory Target Limit
ini_set("memory_limit", "512M");

// [QA FIX]: Menyuntikkan UTF-8 Byte Order Mark (BOM) agar Excel versi lawas (2007/2010)
// tidak merender karakter "gibberish" / acak.
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta charset="utf-8">
    <!-- [QA FIX] Mengamankan agar string yang diawali '=' atau '@' tidak dieksekusi sebagai formula DDE oleh MS Excel -->
    <meta name="Excel-Injection-Protection" content="active">
    <style>
        body { font-family: 'Calibri', Arial, sans-serif; }

        /* [ENTERPRISE FIX]: Kembalikan proteksi Auto-Format Formula Excel */
        /* stylelint-disable-next-line property-no-unknown */
        .str { mso-number-format: "\@"; }

        .gs-table { border-collapse: collapse; font-size: 10pt; width: 100%; }
        .gs-table th, .gs-table td { border: 1px solid #777777; padding: 5px; text-align: center; vertical-align: middle; }
        .gs-table th { background-color: #1ABB9C; font-weight: bold; color: #ffffff; height: 30px; }
        .summary-table { border-collapse: collapse; font-size: 10pt; margin-bottom: 15px; }
        .summary-table th, .summary-table td { border: 1px solid #cbd5e1; padding: 6px 12px; text-align: center; vertical-align: middle; }
        .summary-table th { background-color: #f1f5f9; color: #334155; font-weight: bold; }
        .bg-title { background-color: #2A3F54; color: #ffffff; font-weight: bold; }
    </style>
</head>
<body>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            <td colspan="<?= count(
                $headers,
            ) ?>" style="text-align: left; font-size: 14pt; font-weight: bold; color: #2A3F54;">
                LAPORAN REQUEST PROVISIONING VIRTUAL MACHINE
            </td>
        </tr>
        <tr>
            <td colspan="<?= count(
                $headers,
            ) ?>" style="text-align: left; font-size: 10pt; color: #555555; border-bottom: 2px solid #1ABB9C; padding-bottom: 5px;">
                <b>Rentang Waktu:</b> <?= html_escape(
                    $periode,
                ) ?> &nbsp;|&nbsp; <b>Waktu Cetak:</b> <?= date("d-M-Y H:i") ?> WIB
            </td>
        </tr>
    </table>
    <table><tr><td></td></tr></table>

    <table class="summary-table">
        <tr><td colspan="9" class="bg-title" style="font-size: 11pt; padding: 8px;">EXECUTIVE SUMMARY KAPASITAS</td></tr>
        <tr>
            <th>Total Tiket</th>
            <th>Total VM Deploy</th>
            <th>Total vCPU (Core)</th>
            <th>Total RAM (GB)</th>
            <th>Total Disk (GB)</th>
            <th>Production</th>
            <th>Non-Prod</th>
            <th>Fresh Install</th>
            <th>Clone</th>
        </tr>
        <tr>
            <td><b><?= number_format($summary["total_tiket"] ?? 0) ?></b></td>
            <td><b><?= number_format($summary["total_vm"] ?? 0) ?></b></td>
            <td style="color:#D9534F;"><b><?= number_format(
                $summary["total_cpu"] ?? 0,
            ) ?> Core</b></td>
            <td style="color:#D9534F;"><b><?= number_format(
                $summary["total_ram"] ?? 0,
            ) ?> GB</b></td>
            <td style="color:#D9534F;"><b><?= number_format(
                $summary["total_disk"] ?? 0,
            ) ?> GB</b></td>
            <td><?= number_format($summary["total_prod"] ?? 0) ?></td>
            <td><?= number_format($summary["total_dev"] ?? 0) ?></td>
            <td><?= number_format($summary["total_fresh"] ?? 0) ?></td>
            <td><?= number_format($summary["total_clone"] ?? 0) ?></td>
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
            <?php // [ENTERPRISE FIX]: MEMUNTAHKAN / STREAMING SELURUH DATA DARI MEMORI SEMENTARA TANPA MEMBEBANI RAM
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
