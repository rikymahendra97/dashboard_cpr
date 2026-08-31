<?php
defined("BASEPATH") or exit("No direct script access allowed");


/**
 * =============================================================================
 * File Name : export_report.php
 * Modul     : Replication & Backup
 * Purpose   : Export Report 1 - Report 5 ke format .xls
 * =============================================================================
 */


/**
 * ============================================================
 * DEFAULT DATA
 * ============================================================
 */
$filename =
    $filename
    ?? (
        "Replication_Backup_Report_"
        . date("Ymd")
        . ".xls"
    );

$generated_at =
    $generated_at
    ?? date("d-m-Y H:i:s");

$current_snapshot =
    $current_snapshot
    ?? null;

$previous_snapshot =
    $previous_snapshot
    ?? null;

$report_gti_protection =
    $report_gti_protection
    ?? array();

$report_gti_replication_summary =
    $report_gti_replication_summary
    ?? array();

$report_gti_vrep_rubrik =
    $report_gti_vrep_rubrik
    ?? array();

$report_gti_need_backup_reason =
    $report_gti_need_backup_reason
    ?? array();

$report_tbn_backup_power_state =
    $report_tbn_backup_power_state
    ?? array();


/**
 * ============================================================
 * HELPER - EXCEL SAFE TEXT
 * ============================================================
 *
 * Mencegah Excel Formula Injection untuk text dinamis.
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
            "'"
            . $value;
    }


    return html_escape(
        $value
    );
};


/**
 * ============================================================
 * HELPER - NUMBER
 * ============================================================
 */
$report_number = function ($value) {

    return number_format(
        (int) $value,
        0,
        ",",
        "."
    );
};


/**
 * ============================================================
 * HELPER - CURRENT + DELTA
 * ============================================================
 *
 * 10 + delta 2
 * -> 10 (+2)
 *
 * 10 + delta -2
 * -> 10 (-2)
 *
 * delta 0
 * -> 10
 *
 * previous belum tersedia
 * -> 10
 */
$report_metric = function ($metric) use (
    $report_number
) {

    $current =
        (int) (
            $metric["current"]
            ?? 0
        );


    $text =
        $report_number(
            $current
        );


    if (
        !array_key_exists(
            "delta",
            $metric
        )
        ||
        $metric["delta"] === null
    ) {
        return $text;
    }


    $delta =
        (int) $metric["delta"];


    if ($delta > 0) {

        $text .=
            " (+"
            . $report_number($delta)
            . ")";

    } elseif ($delta < 0) {

        $text .=
            " ("
            . $report_number($delta)
            . ")";
    }


    return $text;
};


/**
 * ============================================================
 * SNAPSHOT LABEL
 * ============================================================
 */
$current_snapshot_label = "-";

if (
    $current_snapshot
    &&
    !empty(
        $current_snapshot->snapshot_at
    )
) {
    $current_snapshot_label =
        date(
            "d-m-Y H:i:s",
            strtotime(
                $current_snapshot->snapshot_at
            )
        )
        . " WIB";
}


$previous_snapshot_label = "-";

if (
    $previous_snapshot
    &&
    !empty(
        $previous_snapshot->snapshot_at
    )
) {
    $previous_snapshot_label =
        date(
            "d-m-Y H:i:s",
            strtotime(
                $previous_snapshot->snapshot_at
            )
        )
        . " WIB";
}


/**
 * ============================================================
 * RESPONSE HEADER
 * ============================================================
 *
 * Mengikuti pola export .xls existing project.
 */
header(
    "Content-Type: application/vnd-ms-excel; charset=UTF-8"
);

header(
    'Content-Disposition: attachment; filename="'
    . str_replace(
        '"',
        "",
        $filename
    )
    . '"'
);

header(
    "Pragma: no-cache"
);

header(
    "Expires: 0"
);


/**
 * UTF-8 BOM.
 */
echo "\xEF\xBB\xBF";
?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <title>
        Replication &amp; Backup Report
    </title>

    <style>

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
        }

        .report-title {
            font-size: 16pt;
            font-weight: bold;
        }

        .report-info {
            margin-top: 5px;
            margin-bottom: 15px;
        }

        .section-title {
            margin-top: 22px;
            margin-bottom: 5px;
            font-size: 12pt;
            font-weight: bold;
            background: #D9EAF7;
        }

        table {
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        th,
        td {
            border: 1px solid #000000;
            padding: 5px 7px;
            vertical-align: middle;
        }

        th {
            font-weight: bold;
            text-align: center;
            background: #E7E6E6;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .grand-total {
            font-weight: bold;
            background: #D9EAD3;
        }

        .sub-header {
            font-weight: bold;
            background: #F2F2F2;
        }

    </style>

</head>


<body>


    <!-- =========================================================
         REPORT INFORMATION
    ========================================================== -->
    <table>

        <tr>
            <td
                colspan="8"
                class="report-title"
            >
                Replication &amp; Backup Report
            </td>
        </tr>

        <tr>
            <td>
                Current Snapshot
            </td>

            <td colspan="7">
                <?= $excel_safe(
                    $current_snapshot_label
                ) ?>
            </td>
        </tr>

        <tr>
            <td>
                Previous Snapshot
            </td>

            <td colspan="7">
                <?= $excel_safe(
                    $previous_snapshot_label
                ) ?>
            </td>
        </tr>

        <tr>
            <td>
                Generated At
            </td>

            <td colspan="7">
                <?= $excel_safe(
                    $generated_at
                ) ?>
                WIB
            </td>
        </tr>

    </table>



    <!-- =========================================================
         REPORT 1
         GTI PROTECTION BY CRITICALITY
    ========================================================== -->
    <?php
    $report_1_rows =
        $report_gti_protection["rows"]
        ?? array();

    $report_1_total =
        $report_gti_protection["grand_total"]
        ?? array();
    ?>

    <div class="section-title">
        Report 1 - GTI Protection by Criticality
    </div>

    <table>

        <thead>

            <tr>
                <th>Criticality</th>
                <th>vReps</th>
                <th>DB</th>
                <th>HA</th>
                <th>Rubrik</th>
            </tr>

        </thead>

        <tbody>

            <?php foreach (
                $report_1_rows
                as $row
            ): ?>

                <tr>

                    <td class="text-left">
                        <?= $excel_safe(
                            $row["criticality"]
                            ?? "Others"
                        ) ?>
                    </td>

                    <td class="text-center">
                        <?= $report_metric(
                            $row["vrep"]
                            ?? array()
                        ) ?>
                    </td>

                    <td class="text-center">
                        <?= $report_metric(
                            $row["db"]
                            ?? array()
                        ) ?>
                    </td>

                    <td class="text-center">
                        <?= $report_metric(
                            $row["ha"]
                            ?? array()
                        ) ?>
                    </td>

                    <td class="text-center">
                        <?= $report_metric(
                            $row["rubrik"]
                            ?? array()
                        ) ?>
                    </td>

                </tr>

            <?php endforeach; ?>


            <tr class="grand-total">

                <td>
                    Grand Total
                </td>

                <td class="text-center">
                    <?= $report_metric(
                        $report_1_total["vrep"]
                        ?? array()
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_metric(
                        $report_1_total["db"]
                        ?? array()
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_metric(
                        $report_1_total["ha"]
                        ?? array()
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_metric(
                        $report_1_total["rubrik"]
                        ?? array()
                    ) ?>
                </td>

            </tr>

        </tbody>

    </table>



    <!-- =========================================================
         REPORT 2
         GTI REPLICATION STATUS SUMMARY
    ========================================================== -->
    <?php
    $report_2 =
        $report_gti_replication_summary;
    ?>

    <div class="section-title">
        Report 2 - GTI Replication Status Summary
    </div>

    <table>

        <thead>

            <tr>
                <th>Done Replication</th>
                <th>Need Replication</th>
                <th>No Need Replication</th>
                <th>Grand Total</th>
            </tr>

        </thead>

        <tbody>

            <tr>

                <td class="text-center">
                    <?= $report_number(
                        $report_2[
                            "done_replication"
                        ]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_2[
                            "need_replication"
                        ]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_2[
                            "no_need_replication"
                        ]
                        ?? 0
                    ) ?>
                </td>

                <td
                    class="
                        text-center
                        grand-total
                    "
                >
                    <?= $report_number(
                        $report_2[
                            "grand_total"
                        ]
                        ?? 0
                    ) ?>
                </td>

            </tr>

        </tbody>

    </table>



    <!-- =========================================================
         REPORT 3
         GTI VREPS & RUBRIK BY CRITICALITY
    ========================================================== -->
    <?php
    $report_3_rows =
        $report_gti_vrep_rubrik["rows"]
        ?? array();

    $report_3_total =
        $report_gti_vrep_rubrik["grand_total"]
        ?? array();

    $report_3_summary =
        $report_gti_vrep_rubrik["summary"]
        ?? array();
    ?>

    <div class="section-title">
        Report 3 - GTI vReps &amp; Rubrik by Criticality
    </div>

    <table>

        <thead>

            <tr>

                <th rowspan="2">
                    Criticality
                </th>

                <th colspan="3">
                    vReps
                </th>

                <th colspan="3">
                    Rubrik
                </th>

            </tr>

            <tr>

                <th>Sukses</th>
                <th>Gagal</th>
                <th>Jumlah</th>

                <th>Sukses</th>
                <th>Gagal</th>
                <th>Jumlah</th>

            </tr>

        </thead>

        <tbody>

            <?php foreach (
                $report_3_rows
                as $row
            ): ?>

                <tr>

                    <td>
                        <?= $excel_safe(
                            $row["criticality"]
                            ?? "Others"
                        ) ?>
                    </td>

                    <td class="text-center">
                        <?= $report_number(
                            $row["vrep"]["success"]
                            ?? 0
                        ) ?>
                    </td>

                    <td class="text-center">
                        <?= $report_number(
                            $row["vrep"]["failed"]
                            ?? 0
                        ) ?>
                    </td>

                    <td class="text-center">
                        <?= $report_number(
                            $row["vrep"]["total"]
                            ?? 0
                        ) ?>
                    </td>

                    <td class="text-center">
                        <?= $report_number(
                            $row["rubrik"]["success"]
                            ?? 0
                        ) ?>
                    </td>

                    <td class="text-center">
                        <?= $report_number(
                            $row["rubrik"]["failed"]
                            ?? 0
                        ) ?>
                    </td>

                    <td class="text-center">
                        <?= $report_number(
                            $row["rubrik"]["total"]
                            ?? 0
                        ) ?>
                    </td>

                </tr>

            <?php endforeach; ?>


            <tr class="grand-total">

                <td>
                    Jumlah VM
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_3_total[
                            "vrep"
                        ]["success"]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_3_total[
                            "vrep"
                        ]["failed"]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_3_total[
                            "vrep"
                        ]["total"]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_3_total[
                            "rubrik"
                        ]["success"]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_3_total[
                            "rubrik"
                        ]["failed"]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_3_total[
                            "rubrik"
                        ]["total"]
                        ?? 0
                    ) ?>
                </td>

            </tr>

        </tbody>

    </table>


    <table>

        <thead>

            <tr>
                <th>Summary</th>
                <th>Jumlah</th>
                <th>Percentage</th>
            </tr>

        </thead>

        <tbody>

            <tr>

                <td>
                    Total Sukses
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_3_summary[
                            "total_success"
                        ]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= number_format(
                        (float) (
                            $report_3_summary[
                                "success_percentage"
                            ]
                            ?? 0
                        ),
                        2,
                        ",",
                        "."
                    ) ?>%
                </td>

            </tr>


            <tr>

                <td>
                    Total Gagal
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_3_summary[
                            "total_failed"
                        ]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= number_format(
                        (float) (
                            $report_3_summary[
                                "failed_percentage"
                            ]
                            ?? 0
                        ),
                        2,
                        ",",
                        "."
                    ) ?>%
                </td>

            </tr>


            <tr class="grand-total">

                <td>
                    Jumlah Replikasi
                </td>

                <td
                    colspan="2"
                    class="text-center"
                >
                    <?= $report_number(
                        $report_3_summary[
                            "total_replication"
                        ]
                        ?? 0
                    ) ?>
                </td>

            </tr>

        </tbody>

    </table>



    <!-- =========================================================
         REPORT 4
         GTI NEED BACKUP BY REASON
    ========================================================== -->
    <?php
    $report_4_criticalities =
        $report_gti_need_backup_reason[
            "criticalities"
        ]
        ?? array();

    $report_4_rows =
        $report_gti_need_backup_reason[
            "rows"
        ]
        ?? array();

    $report_4_total =
        $report_gti_need_backup_reason[
            "grand_total"
        ]
        ?? array();
    ?>

    <div class="section-title">
        Report 4 - GTI NEED BACKUP by Reason
    </div>

    <table>

        <thead>

            <tr>

                <th>
                    Need Backup Reason
                </th>

                <?php foreach (
                    $report_4_criticalities
                    as $criticality
                ): ?>

                    <th>
                        <?= $excel_safe(
                            $criticality
                        ) ?>
                    </th>

                <?php endforeach; ?>

                <th>
                    Total
                </th>

            </tr>

        </thead>

        <tbody>

            <?php foreach (
                $report_4_rows
                as $row
            ): ?>

                <tr>

                    <td>

                        <?= $excel_safe(
                            $row["reason_name"]
                            ?? "-"
                        ) ?>

                        <?php if (
                            isset(
                                $row["is_active"]
                            )
                            &&
                            (int) $row["is_active"] === 0
                            &&
                            (
                                $row["reason_name"]
                                ?? ""
                            ) !== "Belum Ditentukan"
                        ): ?>

                            (Nonaktif)

                        <?php endif; ?>

                    </td>


                    <?php foreach (
                        $report_4_criticalities
                        as $criticality
                    ): ?>

                        <td class="text-center">

                            <?= $report_metric(
                                $row[
                                    "criticalities"
                                ][$criticality]
                                ?? array()
                            ) ?>

                        </td>

                    <?php endforeach; ?>


                    <td class="text-center">

                        <?= $report_metric(
                            $row["total"]
                            ?? array()
                        ) ?>

                    </td>

                </tr>

            <?php endforeach; ?>


            <tr class="grand-total">

                <td>
                    Grand Total
                </td>


                <?php foreach (
                    $report_4_criticalities
                    as $criticality
                ): ?>

                    <td class="text-center">

                        <?= $report_metric(
                            $report_4_total[
                                "criticalities"
                            ][$criticality]
                            ?? array()
                        ) ?>

                    </td>

                <?php endforeach; ?>


                <td class="text-center">

                    <?= $report_metric(
                        $report_4_total[
                            "total"
                        ]
                        ?? array()
                    ) ?>

                </td>

            </tr>

        </tbody>

    </table>



    <!-- =========================================================
         REPORT 5
         TBN BACKUP STATUS BY POWER STATE
    ========================================================== -->
    <?php
    $report_5 =
        $report_tbn_backup_power_state;
    ?>

    <div class="section-title">
        Report 5 - TBN Backup Status by Power State
    </div>

    <table>

        <thead>

            <tr>
                <th>Status Backup</th>
                <th>Power On</th>
                <th>Power Off</th>
                <th>Jumlah</th>
            </tr>

        </thead>

        <tbody>

            <tr>

                <td>
                    DONE BACKUP
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_5[
                            "done_backup"
                        ]["power_on"]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_5[
                            "done_backup"
                        ]["power_off"]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_5[
                            "done_backup"
                        ]["total"]
                        ?? 0
                    ) ?>
                </td>

            </tr>


            <tr>

                <td>
                    NEED BACKUP
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_5[
                            "need_backup"
                        ]["power_on"]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_5[
                            "need_backup"
                        ]["power_off"]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_5[
                            "need_backup"
                        ]["total"]
                        ?? 0
                    ) ?>
                </td>

            </tr>


            <tr>

                <td>
                    NO NEED BACKUP
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_5[
                            "no_need_backup"
                        ]["power_on"]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_5[
                            "no_need_backup"
                        ]["power_off"]
                        ?? 0
                    ) ?>
                </td>

                <td class="text-center">
                    <?= $report_number(
                        $report_5[
                            "no_need_backup"
                        ]["total"]
                        ?? 0
                    ) ?>
                </td>

            </tr>

        </tbody>

    </table>


    <?php if (
        (int) (
            $report_5[
                "unmapped_power_state"
            ]
            ?? 0
        ) > 0
    ): ?>

        <table>

            <tr>

                <td class="sub-header">
                    Warning
                </td>

                <td>
                    Terdapat
                    <?= $report_number(
                        $report_5[
                            "unmapped_power_state"
                        ]
                    ) ?>
                    VM TBN dengan Power State selain ON / OFF.
                </td>

            </tr>

        </table>

    <?php endif; ?>


</body>

</html>