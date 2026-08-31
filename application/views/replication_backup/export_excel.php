<?php
/**
 * =============================================================================
 * File Name : export_excel.php
 * Modul     : Replication & Backup
 * Purpose   : Export data lengkap Virtual Machine ke format .xls
 * =============================================================================
 */

$filename =
    $filename ??
    "Replication_Backup_" .
    date("Ymd_His") .
    ".xls";

$export_data =
    $export_data ??
    array();

$generated_at =
    $generated_at ??
    date("d-m-Y H:i:s");


/**
 * ============================================================
 * HELPER - EXCEL SAFE TEXT
 * ============================================================
 *
 * Selain HTML escape, helper ini juga mencegah
 * formula injection pada Excel.
 */
$excel_safe = function ($value) {

    if ($value === null) {
        return "-";
    }

    $value =
        trim(
            strip_tags(
                (string) $value
            )
        );

    if ($value === "") {
        return "-";
    }

    /**
     * Excel Formula Injection Protection.
     */
    $check_value =
        ltrim(
            $value
        );

    if (
        preg_match(
            "/^[=\+\-@]/",
            $check_value
        )
    ) {
        $value =
            "'" .
            $value;
    }

    return html_escape(
        $value
    );
};


/**
 * ============================================================
 * HELPER - YES / NO
 * ============================================================
 */
$flag_text = function ($value) {

    return ((int) $value === 1)
        ? "YES"
        : "NO";
};


/**
 * ============================================================
 * HELPER - MULTI VM PAIR
 * ============================================================
 *
 * Semua VM pasangan ditampilkan dalam satu cell
 * dan dipisahkan dengan line break.
 */
$pair_text = function ($pairs) use ($excel_safe) {

    if (
        empty($pairs) ||
        !is_array($pairs)
    ) {
        return "-";
    }

    $result = array();

    foreach ($pairs as $pair) {

        $pair =
            trim(
                (string) $pair
            );

        if ($pair === "") {
            continue;
        }

        $result[] =
            $excel_safe(
                $pair
            );
    }

    if (empty($result)) {
        return "-";
    }

    return implode(
        "<br>",
        $result
    );
};


/**
 * ============================================================
 * HELPER - STATUS CLASS
 * ============================================================
 */
$status_class = function ($value) {

    $value =
        strtoupper(
            trim(
                (string) $value
            )
        );

    if ($value === "DONE BACKUP") {
        return "status-done";
    }

    if ($value === "NEED BACKUP") {
        return "status-need";
    }

    if ($value === "NO NEED BACKUP") {
        return "status-no-need";
    }

    return "";
};


/**
 * Filename sudah diberi extension oleh Controller.
 * Hilangkan extension terlebih dahulu agar tidak menjadi:
 * file.xls.xls
 */
$filename =
    str_ireplace(
        array(
            ".xls",
            ".xlsx"
        ),
        "",
        $filename
    );


/**
 * ============================================================
 * EXCEL RESPONSE HEADER
 * ============================================================
 */
header(
    "Content-Type: application/vnd-ms-excel; charset=utf-8"
);

header(
    'Content-Disposition: attachment; filename="' .
    $filename .
    '.xls"'
);

header(
    "Pragma: no-cache"
);

header(
    "Expires: 0"
);


/**
 * UTF-8 BOM
 *
 * Membantu Excel membaca karakter UTF-8 dengan benar.
 */
echo "\xEF\xBB\xBF";
?>

<!DOCTYPE html>
<html xmlns:x="urn:schemas-microsoft-com:office:excel">

<head>

    <meta charset="utf-8">

    <meta
        name="Excel-Injection-Protection"
        content="active"
    >

    <style>

        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 10pt;
        }


        /**
         * ========================================================
         * TITLE
         * ========================================================
         */
        .report-title {
            font-size: 15pt;
            font-weight: bold;
            color: #2A3F54;
        }

        .report-meta {
            font-size: 10pt;
            color: #555555;
            border-bottom: 2px solid #4A6E8E;
            padding-bottom: 6px;
        }


        /**
         * ========================================================
         * DATA TABLE
         * ========================================================
         */
        .export-table {
            border-collapse: collapse;
            width: 100%;
            font-size: 10pt;
        }

        .export-table th,
        .export-table td {
            border: 1px solid #777777;
            padding: 5px;
            vertical-align: top;
        }


        /**
         * ========================================================
         * GROUP HEADER
         * ========================================================
         */
        .header-group {
            background-color: #2A3F54;
            color: #FFFFFF;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .header-column {
            background-color: #4A6E8E;
            color: #FFFFFF;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }


        /**
         * ========================================================
         * CELL
         * ========================================================
         */
        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .pair-cell {
            white-space: normal;
            vertical-align: top;
        }


        /**
         * ========================================================
         * FLAG
         * ========================================================
         */
        .flag-yes {
            color: #15803D;
            font-weight: bold;
            text-align: center;
        }

        .flag-no {
            color: #64748B;
            font-weight: bold;
            text-align: center;
        }


        /**
         * ========================================================
         * STATUS BACKUP
         * ========================================================
         */
        .status-done {
            background-color: #DCFCE7;
            color: #166534;
            font-weight: bold;
            text-align: center;
        }

        .status-need {
            background-color: #FEF3C7;
            color: #92400E;
            font-weight: bold;
            text-align: center;
        }

        .status-no-need {
            background-color: #FEE2E2;
            color: #991B1B;
            font-weight: bold;
            text-align: center;
        }

    </style>

</head>


<body>


    <!-- =========================================================
         REPORT HEADER
    ========================================================== -->

    <table
        style="
            width:100%;
            border-collapse:collapse;
            margin-bottom:15px;
        "
    >

        <tr>

            <td
                colspan="21"
                class="report-title"
            >
                REPLICATION &amp; BACKUP - VIRTUAL MACHINE REPORT
            </td>

        </tr>


        <tr>

            <td
                colspan="21"
                class="report-meta"
            >

                <b>Jumlah VM:</b>

                <?= number_format(
                    count(
                        $export_data
                    )
                ) ?>

                VM

                &nbsp;|&nbsp;

                <b>Waktu Export:</b>

                <?= $excel_safe(
                    $generated_at
                ) ?>

            </td>

        </tr>

    </table>


    <!-- =========================================================
         DATA TABLE
    ========================================================== -->

    <table class="export-table">

        <thead>


            <!-- =================================================
                 GROUP HEADER
            ================================================== -->

            <tr>

                <th
                    rowspan="2"
                    class="header-group"
                >
                    NO
                </th>


                <th
                    colspan="8"
                    class="header-group"
                >
                    VM INFORMATION
                </th>


                <th
                    colspan="8"
                    class="header-group"
                >
                    REPLICATION &amp; BACKUP
                </th>


                <th
                    colspan="4"
                    class="header-group"
                >
                    VM PASANGAN
                </th>

            </tr>


            <!-- =================================================
                 COLUMN HEADER
            ================================================== -->

            <tr>

                <!-- VM INFORMATION -->

                <th class="header-column">
                    NAMA VM
                </th>

                <th class="header-column">
                    POWER STATE
                </th>

                <th class="header-column">
                    VCENTER
                </th>

                <th class="header-column">
                    SITE
                </th>

                <th class="header-column">
                    ENVIRONMENT
                </th>

                <th class="header-column">
                    APLIKASI
                </th>

                <th class="header-column">
                    CRITICALITY
                </th>

                <th class="header-column">
                    SLA RUBRIK
                </th>


                <!-- REPLICATION & BACKUP -->

                <th class="header-column">
                    STATUS BACKUP
                </th>

                <th class="header-column">
                    STATUS REFERENSI
                </th>

                <th class="header-column">
                    VREPS
                </th>

                <th class="header-column">
                    RUBRIK
                </th>

                <th class="header-column">
                    DB
                </th>

                <th class="header-column">
                    HA
                </th>

                <th class="header-column">
                    SLAVE
                </th>

                <th class="header-column">
                    STANDBY
                </th>


                <!-- VM PASANGAN -->

                <th class="header-column">
                    VM PASANGAN DB
                </th>

                <th class="header-column">
                    VM PASANGAN HA
                </th>

                <th class="header-column">
                    VM PASANGAN SLAVE
                </th>

                <th class="header-column">
                    VM PASANGAN STANDBY
                </th>

            </tr>

        </thead>


        <tbody>

            <?php if (!empty($export_data)): ?>


                <?php
                $no = 1;

                foreach ($export_data as $vm):
                    ?>

                    <?php

                    $backup_status =
                        $vm["backup_status"] ?? "";

                    $vrep =
                        $flag_text(
                            $vm["vrep"] ?? 0
                        );

                    $rubrik =
                        $flag_text(
                            $vm["rubrik"] ?? 0
                        );

                    $db =
                        $flag_text(
                            $vm["db"] ?? 0
                        );

                    $ha =
                        $flag_text(
                            $vm["ha"] ?? 0
                        );

                    $slave =
                        $flag_text(
                            $vm["slave"] ?? 0
                        );

                    $standby =
                        $flag_text(
                            $vm["standby"] ?? 0
                        );

                    $vm_pairs =
                        isset($vm["vm_pairs"]) &&
                        is_array($vm["vm_pairs"])
                            ? $vm["vm_pairs"]
                            : array();

                    ?>


                    <tr>


                        <!-- NO -->

                        <td class="text-center">
                            <?= $no++ ?>
                        </td>


                        <!-- =====================================
                             VM INFORMATION
                        ====================================== -->

                        <td class="font-bold">

                            <?= $excel_safe(
                                $vm["virtual_machine_name"] ?? null
                            ) ?>

                        </td>


                        <td class="text-center">

                            <?= $excel_safe(
                                $vm["power_state"] ?? null
                            ) ?>

                        </td>


                        <td>

                            <?= $excel_safe(
                                $vm["vcenter_name"] ?? null
                            ) ?>

                        </td>


                        <td class="text-center">

                            <?= $excel_safe(
                                $vm["id_site"] ?? null
                            ) ?>

                        </td>


                        <td>

                            <?= $excel_safe(
                                $vm["environment"] ?? null
                            ) ?>

                        </td>


                        <td>

                            <?= $excel_safe(
                                $vm["application_systems"] ?? null
                            ) ?>

                        </td>


                        <td class="text-center">

                            <?= $excel_safe(
                                $vm["criticality"] ?? null
                            ) ?>

                        </td>


                        <td class="text-center">

                            <?= $excel_safe(
                                $vm["sla_rubrik"] ?? null
                            ) ?>

                        </td>


                        <!-- =====================================
                             REPLICATION & BACKUP
                        ====================================== -->

                        <td
                            class="<?= $status_class(
                                $backup_status
                            ) ?>"
                        >

                            <?= $excel_safe(
                                $backup_status
                            ) ?>

                        </td>


                        <td class="text-center">

                            <?= $excel_safe(
                                $vm["status_referensi"] ?? null
                            ) ?>

                        </td>


                        <td
                            class="<?= $vrep === "YES"
                                ? "flag-yes"
                                : "flag-no" ?>"
                        >
                            <?= $vrep ?>
                        </td>


                        <td
                            class="<?= $rubrik === "YES"
                                ? "flag-yes"
                                : "flag-no" ?>"
                        >
                            <?= $rubrik ?>
                        </td>


                        <td
                            class="<?= $db === "YES"
                                ? "flag-yes"
                                : "flag-no" ?>"
                        >
                            <?= $db ?>
                        </td>


                        <td
                            class="<?= $ha === "YES"
                                ? "flag-yes"
                                : "flag-no" ?>"
                        >
                            <?= $ha ?>
                        </td>


                        <td
                            class="<?= $slave === "YES"
                                ? "flag-yes"
                                : "flag-no" ?>"
                        >
                            <?= $slave ?>
                        </td>


                        <td
                            class="<?= $standby === "YES"
                                ? "flag-yes"
                                : "flag-no" ?>"
                        >
                            <?= $standby ?>
                        </td>


                        <!-- =====================================
                             VM PASANGAN
                        ====================================== -->

                        <td class="pair-cell">

                            <?= $pair_text(
                                $vm_pairs["DB"] ?? array()
                            ) ?>

                        </td>


                        <td class="pair-cell">

                            <?= $pair_text(
                                $vm_pairs["HA"] ?? array()
                            ) ?>

                        </td>


                        <td class="pair-cell">

                            <?= $pair_text(
                                $vm_pairs["SLAVE"] ?? array()
                            ) ?>

                        </td>


                        <td class="pair-cell">

                            <?= $pair_text(
                                $vm_pairs["STANDBY"] ?? array()
                            ) ?>

                        </td>


                    </tr>


                <?php endforeach; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="21"
                        class="text-center"
                    >
                        Tidak ada data Virtual Machine untuk diexport.
                    </td>

                </tr>


            <?php endif; ?>

        </tbody>

    </table>


</body>

</html>