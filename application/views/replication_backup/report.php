<?php
defined("BASEPATH") or exit("No direct script access allowed");


/**
 * ============================================================
 * HELPER - NUMBER FORMAT
 * ============================================================
 */
$rb_report_number = function ($value) {

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
 * 10, delta +2
 * -> 10 (+2)
 *
 * 10, delta -2
 * -> 10 (-2)
 *
 * delta 0
 * -> 10
 *
 * previous belum tersedia
 * -> 10
 */
$rb_report_metric = function ($metric) use (
    $rb_report_number
) {

    $current =
        (int) (
            $metric["current"] ?? 0
        );

    $delta =
        array_key_exists(
            "delta",
            $metric
        )
            ? $metric["delta"]
            : null;


    $html =
        '<span class="rb-report-current">'
        . $rb_report_number($current)
        . '</span>';


    if ($delta === null) {
        return $html;
    }


    $delta =
        (int) $delta;


    if ($delta > 0) {

        $html .=
            ' <span class="rb-report-delta-up">'
            . '(+'
            . $rb_report_number($delta)
            . ')'
            . '</span>';

    } elseif ($delta < 0) {

        $html .=
            ' <span class="rb-report-delta-down">'
            . '('
            . $rb_report_number($delta)
            . ')'
            . '</span>';
    }


    return $html;
};


/**
 * ============================================================
 * SNAPSHOT LABEL
 * ============================================================
 */
$current_snapshot =
    $report_snapshot_period["current"] ?? null;

$previous_snapshot =
    $report_snapshot_period["previous"] ?? null;


$current_snapshot_label = "-";

if (
    $current_snapshot
    &&
    !empty($current_snapshot->snapshot_at)
) {
    $current_snapshot_label =
        date(
            "d-m-Y H:i",
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
    !empty($previous_snapshot->snapshot_at)
) {
    $previous_snapshot_label =
        date(
            "d-m-Y H:i",
            strtotime(
                $previous_snapshot->snapshot_at
            )
        )
        . " WIB";
}
?>

<style>
    .rb-report-page {
        padding-bottom: 25px;
    }

    .rb-report-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 18px;
        padding: 18px 20px;
        border: 1px solid #D9E2EC;
        border-radius: 8px;
        background: #FFFFFF;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .rb-report-page-title {
        margin: 0 0 5px 0;
        color: #2A3F54;
        font-size: 21px;
        font-weight: 700;
    }

    .rb-report-page-subtitle {
        color: #64748B;
        font-size: 12px;
    }

    .rb-report-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 6px;
    }

    .rb-report-period {
        margin-bottom: 18px;
        padding: 12px 16px;
        border: 1px solid #D9E2EC;
        border-radius: 7px;
        background: #F8FAFC;
        color: #475569;
        font-size: 12px;
        line-height: 1.8;
    }

    .rb-report-section {
        margin-bottom: 20px;
        border: 1px solid #D9E2EC;
        border-radius: 8px;
        overflow: hidden;
        background: #FFFFFF;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.04);
    }

    .rb-report-section-title {
        margin: 0;
        padding: 12px 16px;
        background: #34495E;
        color: #FFFFFF;
        font-size: 13px;
        font-weight: 700;
    }

    .rb-report-section-description {
        padding: 10px 16px;
        border-bottom: 1px solid #E2E8F0;
        background: #F8FAFC;
        color: #64748B;
        font-size: 11px;
    }

    .rb-report-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .rb-report-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
        font-size: 12px;
    }

    .rb-report-table th,
    .rb-report-table td {
        padding: 9px 10px;
        border: 1px solid #E2E8F0;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }

    .rb-report-table th {
        background: #F1F5F9;
        color: #334155;
        font-weight: 700;
    }

    .rb-report-table td:first-child,
    .rb-report-table th:first-child {
        text-align: left;
    }

    .rb-report-table .rb-center {
        text-align: center !important;
    }

    .rb-report-grand-total td {
        background: #E9EEF5;
        font-weight: 700;
    }

    .rb-report-current {
        color: #1E293B;
        font-weight: 600;
    }

    .rb-report-delta-up {
        color: #16A34A;
        font-weight: 700;
    }

    .rb-report-delta-down {
        color: #DC2626;
        font-weight: 700;
    }

    .rb-report-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        padding: 15px;
    }

    .rb-report-summary-card {
        padding: 15px 10px;
        border: 1px solid #D9E2EC;
        border-radius: 7px;
        background: #FFFFFF;
        text-align: center;
    }

    .rb-report-summary-label {
        display: block;
        margin-bottom: 7px;
        color: #64748B;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .rb-report-summary-value {
        display: block;
        color: #1E293B;
        font-size: 22px;
        font-weight: 700;
    }

    .rb-report-mini-summary {
        padding: 14px 16px;
        border-top: 1px solid #E2E8F0;
        background: #F8FAFC;
    }

    .rb-report-mini-summary table {
        width: 100%;
        max-width: 520px;
        margin: 0 auto;
        border-collapse: collapse;
        font-size: 12px;
    }

    .rb-report-mini-summary td {
        padding: 7px 10px;
        border: 1px solid #D9E2EC;
    }

    .rb-report-mini-summary td:first-child {
        font-weight: 700;
    }

    .rb-report-warning {
        margin: 12px 16px;
        padding: 10px 12px;
        border: 1px solid #F59E0B;
        border-radius: 6px;
        background: #FFFBEB;
        color: #92400E;
        font-size: 11px;
    }

    .rb-report-no-data {
        padding: 25px;
        border: 1px dashed #CBD5E1;
        border-radius: 8px;
        background: #F8FAFC;
        color: #64748B;
        text-align: center;
    }

    @media (max-width: 991px) {
        .rb-report-summary-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .rb-report-page-header {
            flex-direction: column;
        }

        .rb-report-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 575px) {
        .rb-report-summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>


<div class="right_col" role="main">

    <div class="rb-report-page">


        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->
        <div class="rb-report-page-header">

            <div>

                <h2 class="rb-report-page-title">
                    <i class="fa fa-bar-chart"></i>
                    Replication &amp; Backup Report
                </h2>

                <div class="rb-report-page-subtitle">
                    Weekly report berdasarkan snapshot Replication &amp; Backup.
                </div>

            </div>


            <div class="rb-report-actions">

                <a
                    href="<?= site_url("replication_backup") ?>"
                    class="btn btn-default btn-sm"
                >
                    <i class="fa fa-arrow-left"></i>
                    Kembali
                </a>

                <!--
                    Akan diaktifkan setelah tampilan
                    Report 1 - Report 5 selesai diverifikasi.
                -->
                <a
                    href="<?= site_url("replication_backup/export_report") ?>"
                    class="btn btn-success btn-sm"
                >
                    <i class="fa fa-file-excel-o"></i>
                    Export Report
                </a>

            </div>

        </div>


        <?php if (!empty($report_snapshot_available)): ?>


            <!-- =================================================
                 SNAPSHOT PERIOD
            ================================================== -->
            <div class="rb-report-period">

                <div>
                    <strong>Current Snapshot:</strong>
                    <?= html_escape(
                        $current_snapshot_label
                    ) ?>
                </div>

                <div>
                    <strong>Previous Snapshot:</strong>
                    <?= html_escape(
                        $previous_snapshot_label
                    ) ?>
                </div>

                <?php if (!$previous_snapshot): ?>

                    <div>
                        <strong>Comparison:</strong>
                        Belum tersedia karena baru ada satu snapshot.
                    </div>

                <?php endif; ?>

            </div>



            <!-- =================================================
                 REPORT 1
                 GTI PROTECTION BY CRITICALITY
            ================================================== -->
            <?php
            $report_1 =
                $report_gti_protection
                ?? array();

            $report_1_rows =
                $report_1["rows"]
                ?? array();

            $report_1_total =
                $report_1["grand_total"]
                ?? array();
            ?>

            <div class="rb-report-section">

                <h3 class="rb-report-section-title">
                    Report 1 - GTI Protection by Criticality
                </h3>

                <div class="rb-report-section-description">
                    Jumlah VM GTI berdasarkan criticality dan metode
                    protection vReps, DB, HA dan Rubrik.
                    Angka dalam kurung menunjukkan selisih terhadap
                    snapshot sebelumnya.
                </div>

                <div class="rb-report-table-wrapper">

                    <table class="rb-report-table">

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

                                    <td>
                                        <?= html_escape(
                                            $row["criticality"]
                                            ?? "Others"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $rb_report_metric(
                                            $row["vrep"]
                                            ?? array()
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $rb_report_metric(
                                            $row["db"]
                                            ?? array()
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $rb_report_metric(
                                            $row["ha"]
                                            ?? array()
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $rb_report_metric(
                                            $row["rubrik"]
                                            ?? array()
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>


                            <tr class="rb-report-grand-total">

                                <td>Grand Total</td>

                                <td>
                                    <?= $rb_report_metric(
                                        $report_1_total["vrep"]
                                        ?? array()
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_metric(
                                        $report_1_total["db"]
                                        ?? array()
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_metric(
                                        $report_1_total["ha"]
                                        ?? array()
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_metric(
                                        $report_1_total["rubrik"]
                                        ?? array()
                                    ) ?>
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>



            <!-- =================================================
                 REPORT 2
                 GTI REPLICATION SUMMARY
            ================================================== -->
            <?php
            $report_2 =
                $report_gti_replication_summary
                ?? array();
            ?>

            <div class="rb-report-section">

                <h3 class="rb-report-section-title">
                    Report 2 - GTI Replication Status Summary
                </h3>

                <div class="rb-report-summary-grid">

                    <div class="rb-report-summary-card">

                        <span class="rb-report-summary-label">
                            Done Replication
                        </span>

                        <span class="rb-report-summary-value">
                            <?= $rb_report_number(
                                $report_2[
                                    "done_replication"
                                ]
                                ?? 0
                            ) ?>
                        </span>

                    </div>


                    <div class="rb-report-summary-card">

                        <span class="rb-report-summary-label">
                            Need Replication
                        </span>

                        <span class="rb-report-summary-value">
                            <?= $rb_report_number(
                                $report_2[
                                    "need_replication"
                                ]
                                ?? 0
                            ) ?>
                        </span>

                    </div>


                    <div class="rb-report-summary-card">

                        <span class="rb-report-summary-label">
                            No Need Replication
                        </span>

                        <span class="rb-report-summary-value">
                            <?= $rb_report_number(
                                $report_2[
                                    "no_need_replication"
                                ]
                                ?? 0
                            ) ?>
                        </span>

                    </div>


                    <div class="rb-report-summary-card">

                        <span class="rb-report-summary-label">
                            Grand Total
                        </span>

                        <span class="rb-report-summary-value">
                            <?= $rb_report_number(
                                $report_2[
                                    "grand_total"
                                ]
                                ?? 0
                            ) ?>
                        </span>

                    </div>

                </div>

            </div>



            <!-- =================================================
                 REPORT 3
                 GTI VREPS & RUBRIK
            ================================================== -->
            <?php
            $report_3 =
                $report_gti_vrep_rubrik
                ?? array();

            $report_3_rows =
                $report_3["rows"]
                ?? array();

            $report_3_total =
                $report_3["grand_total"]
                ?? array();

            $report_3_summary =
                $report_3["summary"]
                ?? array();
            ?>

            <div class="rb-report-section">

                <h3 class="rb-report-section-title">
                    Report 3 - GTI vReps &amp; Rubrik by Criticality
                </h3>

                <div class="rb-report-section-description">
                    Sukses mengikuti jumlah VM dengan flag vReps / Rubrik.
                    Gagal saat ini ditetapkan 0 sesuai format report.
                </div>

                <div class="rb-report-table-wrapper">

                    <table class="rb-report-table">

                        <thead>

                            <tr>
                                <th rowspan="2">
                                    Criticality
                                </th>

                                <th
                                    colspan="3"
                                    class="rb-center"
                                >
                                    vReps
                                </th>

                                <th
                                    colspan="3"
                                    class="rb-center"
                                >
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
                                        <?= html_escape(
                                            $row["criticality"]
                                            ?? "Others"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $rb_report_number(
                                            $row["vrep"]["success"]
                                            ?? 0
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $rb_report_number(
                                            $row["vrep"]["failed"]
                                            ?? 0
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $rb_report_number(
                                            $row["vrep"]["total"]
                                            ?? 0
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $rb_report_number(
                                            $row["rubrik"]["success"]
                                            ?? 0
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $rb_report_number(
                                            $row["rubrik"]["failed"]
                                            ?? 0
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $rb_report_number(
                                            $row["rubrik"]["total"]
                                            ?? 0
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>


                            <tr class="rb-report-grand-total">

                                <td>Jumlah VM</td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_3_total[
                                            "vrep"
                                        ]["success"]
                                        ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_3_total[
                                            "vrep"
                                        ]["failed"]
                                        ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_3_total[
                                            "vrep"
                                        ]["total"]
                                        ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_3_total[
                                            "rubrik"
                                        ]["success"]
                                        ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_3_total[
                                            "rubrik"
                                        ]["failed"]
                                        ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_3_total[
                                            "rubrik"
                                        ]["total"]
                                        ?? 0
                                    ) ?>
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>


                <div class="rb-report-mini-summary">

                    <table>

                        <tr>
                            <td>Total Sukses</td>

                            <td>
                                <?= $rb_report_number(
                                    $report_3_summary[
                                        "total_success"
                                    ]
                                    ?? 0
                                ) ?>
                            </td>

                            <td>
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
                            <td>Total Gagal</td>

                            <td>
                                <?= $rb_report_number(
                                    $report_3_summary[
                                        "total_failed"
                                    ]
                                    ?? 0
                                ) ?>
                            </td>

                            <td>
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


                        <tr>
                            <td>Jumlah Replikasi</td>

                            <td colspan="2">
                                <?= $rb_report_number(
                                    $report_3_summary[
                                        "total_replication"
                                    ]
                                    ?? 0
                                ) ?>
                            </td>
                        </tr>

                    </table>

                </div>

            </div>



            <!-- =================================================
                 REPORT 4
                 GTI NEED BACKUP BY REASON
            ================================================== -->
            <?php
            $report_4 =
                $report_gti_need_backup_reason
                ?? array();

            $report_4_criticalities =
                $report_4["criticalities"]
                ?? array();

            $report_4_rows =
                $report_4["rows"]
                ?? array();

            $report_4_total =
                $report_4["grand_total"]
                ?? array();
            ?>

            <div class="rb-report-section">

                <h3 class="rb-report-section-title">
                    Report 4 - GTI NEED BACKUP by Reason
                </h3>

                <div class="rb-report-section-description">
                    Reason bersifat dynamic.
                    Angka dalam kurung menunjukkan selisih terhadap
                    snapshot sebelumnya.
                </div>

                <div class="rb-report-table-wrapper">

                    <table class="rb-report-table">

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
                                        <?= html_escape(
                                            $criticality
                                        ) ?>
                                    </th>

                                <?php endforeach; ?>

                                <th>Total</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach (
                                $report_4_rows
                                as $row
                            ): ?>

                                <tr>

                                    <td>
                                        <?= html_escape(
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
                                                $row[
                                                    "reason_name"
                                                ]
                                                ?? ""
                                            ) !==
                                            "Belum Ditentukan"
                                        ): ?>

                                            <small>
                                                (Nonaktif)
                                            </small>

                                        <?php endif; ?>
                                    </td>


                                    <?php foreach (
                                        $report_4_criticalities
                                        as $criticality
                                    ): ?>

                                        <td>
                                            <?= $rb_report_metric(
                                                $row[
                                                    "criticalities"
                                                ][
                                                    $criticality
                                                ]
                                                ?? array()
                                            ) ?>
                                        </td>

                                    <?php endforeach; ?>


                                    <td>
                                        <?= $rb_report_metric(
                                            $row["total"]
                                            ?? array()
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>


                            <tr class="rb-report-grand-total">

                                <td>Grand Total</td>

                                <?php foreach (
                                    $report_4_criticalities
                                    as $criticality
                                ): ?>

                                    <td>
                                        <?= $rb_report_metric(
                                            $report_4_total[
                                                "criticalities"
                                            ][
                                                $criticality
                                            ]
                                            ?? array()
                                        ) ?>
                                    </td>

                                <?php endforeach; ?>


                                <td>
                                    <?= $rb_report_metric(
                                        $report_4_total[
                                            "total"
                                        ]
                                        ?? array()
                                    ) ?>
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>



            <!-- =================================================
                 REPORT 5
                 TBN BACKUP BY POWER STATE
            ================================================== -->
            <?php
            $report_5 =
                $report_tbn_backup_power_state
                ?? array();
            ?>

            <div class="rb-report-section">

                <h3 class="rb-report-section-title">
                    Report 5 - TBN Backup Status by Power State
                </h3>

                <div class="rb-report-table-wrapper">

                    <table class="rb-report-table">

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

                                <td>DONE BACKUP</td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_5[
                                            "done_backup"
                                        ]["power_on"]
                                        ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_5[
                                            "done_backup"
                                        ]["power_off"]
                                        ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_5[
                                            "done_backup"
                                        ]["total"]
                                        ?? 0
                                    ) ?>
                                </td>

                            </tr>


                            <tr>

                                <td>NEED BACKUP</td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_5[
                                            "need_backup"
                                        ]["power_on"]
                                        ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_5[
                                            "need_backup"
                                        ]["power_off"]
                                        ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_5[
                                            "need_backup"
                                        ]["total"]
                                        ?? 0
                                    ) ?>
                                </td>

                            </tr>


                            <tr>

                                <td>NO NEED BACKUP</td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_5[
                                            "no_need_backup"
                                        ]["power_on"]
                                        ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_5[
                                            "no_need_backup"
                                        ]["power_off"]
                                        ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= $rb_report_number(
                                        $report_5[
                                            "no_need_backup"
                                        ]["total"]
                                        ?? 0
                                    ) ?>
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>


                <?php if (
                    (int) (
                        $report_5[
                            "unmapped_power_state"
                        ]
                        ?? 0
                    ) > 0
                ): ?>

                    <div class="rb-report-warning">

                        Terdapat
                        <strong>
                            <?= $rb_report_number(
                                $report_5[
                                    "unmapped_power_state"
                                ]
                            ) ?>
                        </strong>
                        VM TBN dengan Power State selain
                        <strong>ON</strong> / <strong>OFF</strong>.

                    </div>

                <?php endif; ?>

            </div>


        <?php else: ?>


            <!-- =================================================
                 NO SNAPSHOT
            ================================================== -->
            <div class="rb-report-no-data">

                <i class="fa fa-info-circle"></i>

                Belum tersedia Weekly Snapshot
                untuk Replication &amp; Backup Report.

            </div>


        <?php endif; ?>


    </div>

</div>