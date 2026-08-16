<?php
/**
 * ========================================================================
 * NAMA FILE: export_excel_vm_incident.php
 * TUJUAN FILE: Template Native HTML-to-Excel Generator (Matrix Pivot Integration)
 * ARSITEKTUR: Unbuffered Stream Re-rendering (Zero RAM Overhead), UTF-8 BOM
 * ========================================================================
 */

$filename = $filename ?? "Tiket_SCR_Utilisasi_VM_" . date("Ymd_His");
$periode = $periode ?? "Semua Waktu";
$summary = $summary ?? [];
$headers = $headers ?? [];
$temp_fp = $temp_fp ?? null;

$filename = str_ireplace([".xls", ".xlsx"], "", $filename);
header("Content-Type: application/vnd-ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename.xls\"");
header("Pragma: no-cache");
header("Expires: 0");

// [ENTERPRISE FIX]: Pasang Memory Target Limit
ini_set("memory_limit", "512M");

// [ENTERPRISE FIX]: Injeksi UTF-8 BOM untuk kompatibilitas MS Excel / Anti-Mojibake
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html xmlns:x="urn:schemas-microsoft-com:office:excel">

<head>
    <meta charset="utf-8">
    <meta name="Excel-Injection-Protection" content="active">
    <style>
        body { font-family: 'Calibri', Arial, sans-serif; }

        /* [ENTERPRISE FIX]: Kembalikan proteksi Auto-Format Formula Excel */
        /* stylelint-disable-next-line property-no-unknown */
        .str { mso-number-format: "\@"; }

        .gs-table { border-collapse: collapse; font-size: 10pt; width: 100%; }
        .gs-table th, .gs-table td { border: 1px solid #777777; padding: 5px; vertical-align: top; }
        .gs-table th { background-color: #2A3F54; font-weight: bold; color: #ffffff; text-align: center; }
        .summary-table { border-collapse: collapse; font-size: 10pt; margin-bottom: 25px; width: auto; }
        .summary-table th, .summary-table td { border: 1px solid #000; padding: 6px 12px; vertical-align: middle; }
        .summary-table th { background-color: #f1f5f9; color: #333; font-weight: bold; }
    </style>
</head>

<body>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            <td colspan="<?= count(
                $headers,
            ) ?>" style="text-align: left; font-size: 16pt; font-weight: bold; color: #2A3F54;">EXECUTIVE REPORT - TIKET UTILISASI VIRTUAL MACHINE</td>
        </tr>
        <tr>
            <td colspan="<?= count(
                $headers,
            ) ?>" style="text-align: left; font-size: 10pt; color: #555555; border-bottom: 2px solid #2A3F54; padding-bottom: 5px;">
                <b>Rentang Waktu:</b> <?= html_escape(
                    $periode,
                ) ?> &nbsp;|&nbsp; <b>Waktu Ekstrak:</b> <?= date("d M Y H:i") ?> WIB
            </td>
        </tr>
    </table>
    <table><tr><td></td></tr></table>

    <table class="summary-table">
        <tr>
            <th>Status Tiket</th>
            <th>Action</th>
            <th>Total</th>
        </tr>
        <?php
        $grand_total_1 = 0;
        foreach (
            ["Apply Solution by Owner", "Done/Close", "Open Tiket", "Review by Owner"]
            as $status_key
        ) {
            $actions = $summary["status_action"][$status_key] ?? [];
            if (empty($actions)) {
                $actions = ["None" => 0];
            }

            $rowspan = count($actions);
            $first = true;
            foreach ($actions as $act => $qty) {
                echo "<tr>";
                if ($first) {
                    $display_status = $status_key == "Done/Close" ? "Done" : $status_key;
                    echo '<td rowspan="' .
                        $rowspan .
                        '" style="vertical-align:middle; font-weight:bold;">' .
                        html_escape($display_status) .
                        "</td>";
                    $first = false;
                }
                echo "<td>" . html_escape($act) . '</td><td align="center">' . (int) $qty . "</td>";
                echo "</tr>";
                $grand_total_1 += $qty;
            }
        }
        ?>
        <tr>
            <th colspan="2" align="right">Grand Total</th>
            <th align="center"><?= $grand_total_1 ?></th>
        </tr>
    </table>
    <table><tr><td></td></tr></table>

    <?php
    $order_kritis = ["Critical", "Very High", "High", "Other"];
    if (isset($summary["tipe_kritis"]) && is_array($summary["tipe_kritis"])):
        foreach ($summary["tipe_kritis"] as $tipe => $kritis_data):
            $sub_totals = [
                "Open Tiket" => 0,
                "Review by Owner" => 0,
                "Apply Solution by Owner" => 0,
                "Done/Close" => 0,
                "Total" => 0,
            ]; ?>
        <table class="summary-table">
            <tr><th colspan="6" align="center" style="font-size: 11pt;">Tiket Utilisasi<br><?= strtoupper(
                html_escape($tipe),
            ) ?></th></tr>
            <tr>
                <th rowspan="2" align="center">Kritikalitas VM</th>
                <th colspan="4" align="center">Status Tiket</th>
                <th rowspan="2" align="center">Grand Total</th>
            </tr>
            <tr>
                <th align="center">Open Tiket</th>
                <th align="center">Review by Owner</th>
                <th align="center">Apply Solution by Owner</th>
                <th align="center">Done</th>
            </tr>
            <?php foreach ($order_kritis as $kr):
                $data = $kritis_data[$kr] ?? [
                    "Open Tiket" => 0,
                    "Review by Owner" => 0,
                    "Apply Solution by Owner" => 0,
                    "Done/Close" => 0,
                    "Total" => 0,
                ]; ?>
                <tr>
                    <td><i><?= html_escape($kr) ?></i></td>
                    <td align="center"><?= (int) $data["Open Tiket"] ?></td>
                    <td align="center"><?= (int) $data["Review by Owner"] ?></td>
                    <td align="center"><?= (int) $data["Apply Solution by Owner"] ?></td>
                    <td align="center"><?= (int) $data["Done/Close"] ?></td>
                    <td align="center" style="font-weight:bold;"><?= (int) $data["Total"] ?></td>
                </tr>
            <?php
            $sub_totals["Open Tiket"] += $data["Open Tiket"];
            $sub_totals["Review by Owner"] += $data["Review by Owner"];
            $sub_totals["Apply Solution by Owner"] += $data["Apply Solution by Owner"];
            $sub_totals["Done/Close"] += $data["Done/Close"];
            $sub_totals["Total"] += $data["Total"];

            endforeach; ?>
            <tr>
                <th align="right">Grand Total</th>
                <th align="center"><?= $sub_totals["Open Tiket"] ?></th>
                <th align="center"><?= $sub_totals["Review by Owner"] ?></th>
                <th align="center"><?= $sub_totals["Apply Solution by Owner"] ?></th>
                <th align="center"><?= $sub_totals["Done/Close"] ?></th>
                <th align="center" style="color: #b91d47; font-size:11pt;"><?= $sub_totals[
                    "Total"
                ] ?></th>
            </tr>
        </table>
        <table><tr><td></td></tr></table>
    <?php
        endforeach;
    endif;
    ?>

    <table class="gs-table">
        <thead>
            <tr><?php foreach ($headers as $head): ?><th><?= strtoupper(
    html_escape($head),
) ?></th><?php endforeach; ?></tr>
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
