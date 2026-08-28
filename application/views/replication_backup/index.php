<?php
defined("BASEPATH") or exit("No direct script access allowed");

$summary = $summary ?? [];
$list_vm = $list_vm ?? [];
?>

<style>
    .rb-kpi-wrapper {
        margin-bottom: 10px;
    }

    .rb-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 12px;
    }

    .rb-kpi-card {
        background: #fff;
        border-radius: 8px;
        padding: 12px 15px;
        border: 1px solid #E2E8F0;
        border-bottom: 4px solid;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);

        display: flex;
        align-items: center;
        justify-content: space-between;

        min-height: 82px;
    }

    .rb-kpi-title {
        margin: 0;
        font-size: 10px;
        color: #64748B;
        font-weight: bold;
        text-transform: uppercase;
    }

    .rb-kpi-value {
        margin: 5px 0 0 0;
        font-size: 23px;
        font-weight: 800;
        color: #1E293B;
    }

    .rb-kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 17px;
        color: #fff;
    }

    .rb-table {
        font-size: 12px;
        color: #2A3F54;
        margin-bottom: 0 !important;
        width: 100% !important;
    }

    .rb-table thead tr th {
        background: #34495E !important;
        color: #ECF0F1 !important;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        padding: 12px 8px;
        vertical-align: middle !important;
        text-align: center !important;
        white-space: nowrap;
    }

    .rb-table tbody tr td {
        padding: 7px 8px !important;
        vertical-align: middle !important;
        text-align: center !important;
        border-top: 1px solid #E2E8F0;
        white-space: nowrap;
    }

    .rb-table tbody tr:hover {
        background-color: #F1F5F9;
    }

    .rb-vm-name {
        font-weight: bold;
        color: #2A3F54;
        text-align: left !important;
    }

    .rb-yes {
        display: inline-block;
        min-width: 45px;
        padding: 3px 8px;
        border-radius: 12px;
        background: #10B981;
        color: #fff;
        font-size: 10px;
        font-weight: bold;
    }

    .rb-no {
        display: inline-block;
        min-width: 45px;
        padding: 3px 8px;
        border-radius: 12px;
        background: #CBD5E1;
        color: #475569;
        font-size: 10px;
        font-weight: bold;
    }

    .rb-status {
        display: inline-block;
        min-width: 95px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        text-align: center;
    }

    /* DONE BACKUP */
    .rb-status-done {
        background: #10B981;
        color: #fff;
    }

    /* NEED BACKUP */
    .rb-status-need {
        background: #F59E0B;
        color: #fff;
    }

    /* NO NEED BACKUP */
    .rb-status-no-need {
        background: #EF4444;
        color: #fff;
    }

    /* Status selain 3 status utama */
    .rb-status-default {
        background: #94A3B8;
        color: #fff;
    }

    .rb-criticality {
        display: inline-block;
        min-width: 70px;
        padding: 4px 9px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        text-align: center;
    }

    .rb-criticality-critical {
        background: #DC2626;
        color: #fff;
    }

    .rb-criticality-very-high {
        background: #F97316;
        color: #fff;
    }

    .rb-criticality-high {
        background: #EAB308;
        color: #fff;
    }

    .rb-criticality-medium {
        background: #3B82F6;
        color: #fff;
    }

    .rb-criticality-low {
        background: #10B981;
        color: #fff;
    }

    .rb-criticality-other {
        background: #94A3B8;
        color: #fff;
    }

    .rb-action-btn {
        width: 28px;
        height: 28px;
        padding: 4px 0;
        margin: 1px;
    }

    @media (max-width: 1200px) {
        .rb-kpi-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .rb-kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        .rb-kpi-grid {
            grid-template-columns: 1fr;
        }
    }

    #table-replication-backup {
        min-width: 1700px;
    }
</style>

<section class="scrollable wrapper">
    <div class="right_col" role="main">

        <div class="clearfix"></div>

        <!-- =========================================================
             KPI
        ========================================================== -->
        <div class="rb-kpi-wrapper">

            <div class="rb-kpi-grid">

                <!-- DONE REPLICATION -->
                <div class="rb-kpi-card" style="border-color:#10B981;">
                    <div>
                        <h4 class="rb-kpi-title">Done Replication</h4>
                        <h2 class="rb-kpi-value">
                            <?= $summary["done_replication"] ?? 0 ?>
                        </h2>
                    </div>

                    <div class="rb-kpi-icon" style="background:#10B981;">
                        <i class="fa fa-check"></i>
                    </div>
                </div>

                <!-- NEED REPLICATION -->
                <div class="rb-kpi-card" style="border-color:#F59E0B;">
                    <div>
                        <h4 class="rb-kpi-title">Need Replication</h4>
                        <h2 class="rb-kpi-value">
                            <?= $summary["need_replication"] ?? 0 ?>
                        </h2>
                    </div>

                    <div class="rb-kpi-icon" style="background:#F59E0B;">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                </div>

                <!-- NO NEED REPLICATION -->
                <div class="rb-kpi-card" style="border-color:#94A3B8;">
                    <div>
                        <h4 class="rb-kpi-title">No Need Replication</h4>
                        <h2 class="rb-kpi-value">
                            <?= $summary["no_need_replication"] ?? 0 ?>
                        </h2>
                    </div>

                    <div class="rb-kpi-icon" style="background:#64748B;">
                        <i class="fa fa-minus-circle"></i>
                    </div>
                </div>

                <!-- DONE BACKUP -->
                <div class="rb-kpi-card" style="border-color:#10B981;">
                    <div>
                        <h4 class="rb-kpi-title">Done Backup</h4>
                        <h2 class="rb-kpi-value">
                            <?= $summary["done_backup"] ?? 0 ?>
                        </h2>
                    </div>

                    <div class="rb-kpi-icon" style="background:#10B981;">
                        <i class="fa fa-check-circle"></i>
                    </div>
                </div>

                <!-- NEED BACKUP -->
                <div class="rb-kpi-card" style="border-color:#F59E0B;">
                    <div>
                        <h4 class="rb-kpi-title">Need Backup</h4>
                        <h2 class="rb-kpi-value">
                            <?= $summary["need_backup"] ?? 0 ?>
                        </h2>
                    </div>

                    <div class="rb-kpi-icon" style="background:#F59E0B;">
                        <i class="fa fa-warning"></i>
                    </div>
                </div>

                <!-- NO NEED BACKUP -->
                <div class="rb-kpi-card" style="border-color:#94A3B8;">
                    <div>
                        <h4 class="rb-kpi-title">No Need Backup</h4>
                        <h2 class="rb-kpi-value">
                            <?= $summary["no_need_backup"] ?? 0 ?>
                        </h2>
                    </div>

                    <div class="rb-kpi-icon" style="background:#64748B;">
                        <i class="fa fa-ban"></i>
                    </div>
                </div>

                <!-- VREPS -->
                <div class="rb-kpi-card" style="border-color:#3B82F6;">
                    <div>
                        <h4 class="rb-kpi-title">vReps</h4>
                        <h2 class="rb-kpi-value">
                            <?= $summary["vrep"] ?? 0 ?>
                        </h2>
                    </div>

                    <div class="rb-kpi-icon" style="background:#3B82F6;">
                        <i class="fa fa-refresh"></i>
                    </div>
                </div>

                <!-- RUBRIK -->
                <div class="rb-kpi-card" style="border-color:#8B5CF6;">
                    <div>
                        <h4 class="rb-kpi-title">Rubrik</h4>
                        <h2 class="rb-kpi-value">
                            <?= $summary["rubrik"] ?? 0 ?>
                        </h2>
                    </div>

                    <div class="rb-kpi-icon" style="background:#8B5CF6;">
                        <i class="fa fa-database"></i>
                    </div>
                </div>

                <!-- HA -->
                <div class="rb-kpi-card" style="border-color:#06B6D4;">
                    <div>
                        <h4 class="rb-kpi-title">HA</h4>
                        <h2 class="rb-kpi-value">
                            <?= $summary["ha"] ?? 0 ?>
                        </h2>
                    </div>

                    <div class="rb-kpi-icon" style="background:#06B6D4;">
                        <i class="fa fa-server"></i>
                    </div>
                </div>

                <!-- DB -->
                <div class="rb-kpi-card" style="border-color:#EF4444;">
                    <div>
                        <h4 class="rb-kpi-title">DB</h4>
                        <h2 class="rb-kpi-value">
                            <?= $summary["db"] ?? 0 ?>
                        </h2>
                    </div>

                    <div class="rb-kpi-icon" style="background:#EF4444;">
                        <i class="fa fa-database"></i>
                    </div>
                </div>

            </div>
        </div>

        <!-- =========================================================
             TABLE
        ========================================================== -->
        <div class="row animated fadeInUp">

            <div class="col-md-12 col-sm-12 col-xs-12">

                <div
                    class="x_panel"
                    style="border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1);"
                >

                    <div class="x_title">

                        <h2 style="font-weight:bold; color:#2A3F54;">
                            <i class="fa fa-database"></i>
                            Replication & Backup
                            <small style="font-weight:normal;">
                                Virtual Machine Protection
                            </small>
                        </h2>

                        <div class="clearfix"></div>

                    </div>

                    <div class="x_content">

                        <div
                            class="table-responsive"
                            style="overflow-x:auto; width:100%;"
                        >

                            <table
                                id="table-replication-backup"
                                class="table table-striped responsive-utilities jambo_table rb-table"
                            >

                                <thead>

                                    <tr class="headings">

                                        <th>No</th>
                                        <th>Nama VM</th>
                                        <th>Power State</th>
                                        <th>vCenter</th>
                                        <th>Site</th>
                                        <th>Environment</th>
                                        <th>Aplikasi</th>
                                        <th>Criticality</th>
                                        <th>Status Backup</th>
                                        <th>vReps</th>
                                        <th>Rubrik</th>
                                        <th>DB</th>
                                        <th>HA</th>
                                        <th>Standby</th>
                                        <th>Aksi</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php if (!empty($list_vm)): ?>

                                        <?php foreach ($list_vm as $index => $vm): ?>

                                            <?php
                                            $status = strtoupper(
                                                trim((string) ($vm->backup_status ?? ""))
                                            );

                                            switch ($status) {
                                                case "DONE BACKUP":
                                                    $status_class = "rb-status-done";
                                                    break;

                                                case "NEED BACKUP":
                                                    $status_class = "rb-status-need";
                                                    break;

                                                case "NO NEED BACKUP":
                                                    $status_class = "rb-status-no-need";
                                                    break;

                                                default:
                                                    $status_class = "rb-status-default";
                                                    break;
                                            }

                                            $status_label =
                                                trim((string) ($vm->backup_status ?? ""));

                                            if ($status_label === "") {
                                                $status_label = "-";
                                            }
                                            ?>

                                            <tr>

                                                <td>
                                                    <?= $index + 1 ?>
                                                </td>

                                                <td class="rb-vm-name">
                                                    <?= html_escape(
                                                        $vm->virtual_machine_name
                                                    ) ?>
                                                </td>

                                                <td>
                                                    <?= html_escape(
                                                        $vm->power_state ?: "-"
                                                    ) ?>
                                                </td>

                                                <td>
                                                    <?= html_escape(
                                                        $vm->vcenter_name ?: "-"
                                                    ) ?>
                                                </td>

                                                <td>
                                                    <?= html_escape(
                                                        $vm->id_site ?: "-"
                                                    ) ?>
                                                </td>

                                                <td>
                                                    <?= html_escape(
                                                        $vm->environment ?: "-"
                                                    ) ?>
                                                </td>

                                                <td
                                                    style="
                                                        text-align:left !important;
                                                        white-space:normal;
                                                        min-width:180px;
                                                    "
                                                >
                                                    <?= html_escape(
                                                        $vm->application_systems ?: "-"
                                                    ) ?>
                                                </td>

                                                <td>
                                                    <?php
                                                    $criticality = trim(
                                                        (string) ($vm->criticality ?? "")
                                                    );

                                                    switch (strtolower($criticality)) {
                                                        case "critical":
                                                            $criticality_class =
                                                                "rb-criticality-critical";
                                                            break;

                                                        case "very high":
                                                            $criticality_class =
                                                                "rb-criticality-very-high";
                                                            break;

                                                        case "high":
                                                            $criticality_class =
                                                                "rb-criticality-high";
                                                            break;

                                                        case "medium":
                                                            $criticality_class =
                                                                "rb-criticality-medium";
                                                            break;

                                                        case "low":
                                                            $criticality_class =
                                                                "rb-criticality-low";
                                                            break;

                                                        default:
                                                            $criticality_class =
                                                                "rb-criticality-other";
                                                            break;
                                                    }

                                                    $criticality_label =
                                                        $criticality !== ""
                                                            ? $criticality
                                                            : "-";
                                                    ?>

                                                    <span
                                                        class="rb-criticality <?= $criticality_class ?>"
                                                    >
                                                        <?= html_escape(
                                                            $criticality_label
                                                        ) ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <span
                                                        class="rb-status <?= $status_class ?>"
                                                    >
                                                        <?= html_escape(
                                                            $status_label
                                                        ) ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <?php if ((int) $vm->vrep === 1): ?>
                                                        <span class="rb-yes">
                                                            YES
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="rb-no">
                                                            NO
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <?php if ((int) $vm->rubrik === 1): ?>
                                                        <span class="rb-yes">
                                                            YES
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="rb-no">
                                                            NO
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <?php if ((int) $vm->db === 1): ?>
                                                        <span class="rb-yes">
                                                            YES
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="rb-no">
                                                            NO
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <?php if ((int) $vm->ha === 1): ?>
                                                        <span class="rb-yes">
                                                            YES
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="rb-no">
                                                            NO
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <?php if ((int) $vm->standby === 1): ?>
                                                        <span class="rb-yes">
                                                            YES
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="rb-no">
                                                            NO
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <td>

                                                    <a
                                                        href="<?= site_url(
                                                            "replication_backup/details_vm/" .
                                                            $vm->id_virtual_machine
                                                        ) ?>"
                                                        class="btn btn-info btn-xs rb-action-btn"
                                                        title="Detail"
                                                    >
                                                        <i class="fa fa-search"></i>
                                                    </a>

                                                    <button
                                                        type="button"
                                                        class="btn btn-warning btn-xs rb-action-btn"
                                                        title="Edit"
                                                        disabled
                                                    >
                                                        <i class="fa fa-edit"></i>
                                                    </button>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <tr>

                                            <td colspan="15">
                                                Belum ada data Virtual Machine.
                                            </td>

                                        </tr>

                                    <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
</section>

<script>
$(document).ready(function () {

    if ($.fn.DataTable) {

        $('#table-replication-backup').DataTable({
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            ordering: true,
            searching: true,
            responsive: false
        });

    }

});
</script>