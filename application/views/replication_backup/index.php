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

    /* ============================================================
        FILTER
    ============================================================ */
    .rb-filter-wrapper {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 15px;
    }

    .rb-filter-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
    }

    .rb-filter-group {
        min-width: 0;
    }

    .rb-filter-label {
        display: block;
        margin-bottom: 5px;
        font-size: 10px;
        font-weight: bold;
        color: #64748B;
        text-transform: uppercase;
    }

    .rb-filter-select {
        width: 100%;
        height: 34px;
        padding: 5px 8px;
        border: 1px solid #CBD5E1;
        border-radius: 4px;
        background: #fff;
        color: #334155;
        font-size: 11px;
    }

    .rb-filter-select:focus {
        outline: none;
        border-color: #3B82F6;
    }

    .rb-filter-actions {
        margin-top: 10px;
        text-align: right;
    }

    /* ============================================================
    NEED BACKUP REASON - MODAL
    ============================================================ */
    .rb-reason-modal-overlay {
        display: none;
        position: fixed !important;
        top: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        margin: 0 !important;
        padding: 20px;
        background: rgba(15, 23, 42, 0.55);
        align-items: center !important;
        justify-content: center !important;
    }

    .rb-reason-modal-overlay.rb-reason-modal-visible {
        display: flex !important;
    }

    #rb-reason-manager-overlay {
        z-index: 99990;
    }

    #rb-reason-confirm-overlay {
        z-index: 100000;
    }

    #rb-reason-feedback-overlay {
        z-index: 100010;
    }

    .rb-reason-modal-card {
        width: 100%;
        max-width: 620px;
        max-height: 90vh;
        margin: auto !important;
        position: relative;
        float: none !important;
        background: #FFFFFF;
        border-radius: 10px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
        overflow: hidden;
    }

    .rb-reason-dialog-card {
        max-width: 430px;
    }

    .rb-reason-modal-header {
        padding: 16px 18px;
        border-bottom: 1px solid #E2E8F0;
        background: #F8FAFC;
    }

    .rb-reason-modal-title {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #1E293B;
    }

    .rb-reason-modal-body {
        padding: 18px;
    }

    .rb-reason-modal-footer {
        padding: 12px 18px;
        border-top: 1px solid #E2E8F0;
        text-align: right;
        background: #F8FAFC;
    }

    .rb-reason-add-row {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
    }

    .rb-reason-add-input {
        flex: 1;
        min-width: 0;
        height: 36px;
        padding: 7px 10px;
        border: 1px solid #CBD5E1;
        border-radius: 5px;
        font-size: 12px;
        color: #334155;
    }

    .rb-reason-add-input:focus {
        outline: none;
        border-color: #3B82F6;
    }

    .rb-reason-list {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        overflow-y: auto;
        max-height: 330px;
    }

    .rb-reason-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 12px;
        border-bottom: 1px solid #E2E8F0;
    }

    .rb-reason-item:last-child {
        border-bottom: 0;
    }

    .rb-reason-item-main {
        flex: 1;
        min-width: 0;
    }

    .rb-reason-item-name {
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        word-break: break-word;
    }

    .rb-reason-item-count {
        display: inline-block;
        margin-top: 4px;
        padding: 2px 7px;
        border-radius: 10px;
        background: #E2E8F0;
        color: #475569;
        font-size: 10px;
        font-weight: 700;
    }

    .rb-reason-empty {
        padding: 20px;
        text-align: center;
        color: #94A3B8;
        font-size: 12px;
    }

    .rb-reason-confirm-name {
        margin: 12px 0;
        padding: 10px 12px;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        color: #1E293B;
        font-weight: 700;
        word-break: break-word;
    }

    .rb-reason-confirm-message {
        margin: 0 0 10px;
        color: #475569;
        font-size: 12px;
        line-height: 1.6;
    }

    .rb-reason-warning {
        padding: 10px 12px;
        background: #FFF7ED;
        border: 1px solid #FED7AA;
        border-radius: 6px;
        color: #9A3412;
        font-size: 11px;
        line-height: 1.5;
    }

    .rb-reason-dialog-icon {
        width: 56px;
        height: 56px;
        margin: 0 auto 14px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .rb-reason-dialog-icon.warning {
        background: #FEF3C7;
        color: #D97706;
    }

    .rb-reason-dialog-icon.success {
        background: #DCFCE7;
        color: #16A34A;
    }

    .rb-reason-dialog-icon.error {
        background: #FEE2E2;
        color: #DC2626;
    }

    .rb-reason-feedback-title {
        margin: 0 0 8px;
        text-align: center;
        font-size: 17px;
        font-weight: 700;
        color: #1E293B;
    }

    .rb-reason-feedback-message {
        margin: 0;
        text-align: center;
        color: #64748B;
        font-size: 12px;
        line-height: 1.6;
    }

    .rb-reason-btn-no {
        background: #EF4444;
        border-color: #EF4444;
        color: #FFFFFF;
    }

    .rb-reason-btn-no:hover,
    .rb-reason-btn-no:focus {
        background: #DC2626;
        border-color: #DC2626;
        color: #FFFFFF;
    }

    .rb-reason-btn-yes {
        background: #3B82F6;
        border-color: #3B82F6;
        color: #FFFFFF;
    }

    .rb-reason-btn-yes:hover,
    .rb-reason-btn-yes:focus {
        background: #2563EB;
        border-color: #2563EB;
        color: #FFFFFF;
    }

    .rb-reason-btn-ok {
        background: #10B981;
        border-color: #10B981;
        color: #FFFFFF;
    }

    .rb-reason-btn-ok:hover,
    .rb-reason-btn-ok:focus {
        background: #059669;
        border-color: #059669;
        color: #FFFFFF;
    }

    @media (max-width: 600px) {
        .rb-reason-add-row {
            flex-direction: column;
        }

        .rb-reason-item {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 1200px) {
        .rb-filter-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .rb-filter-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        .rb-filter-grid {
            grid-template-columns: 1fr;
        }
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

                        <!-- =========================================================
                            FILTER
                        ========================================================== -->
                        <div class="rb-filter-wrapper">

                            <div class="rb-filter-grid">

                                <!-- SITE -->
                                <div class="rb-filter-group">
                                    <label class="rb-filter-label">
                                        Site
                                    </label>

                                    <select
                                        id="filter-site"
                                        class="rb-filter-select"
                                    >
                                        <option value="">All</option>
                                    </select>
                                </div>


                                <!-- VCENTER -->
                                <div class="rb-filter-group">
                                    <label class="rb-filter-label">
                                        vCenter
                                    </label>

                                    <select
                                        id="filter-vcenter"
                                        class="rb-filter-select"
                                    >
                                        <option value="">All</option>
                                    </select>
                                </div>


                                <!-- ENVIRONMENT -->
                                <div class="rb-filter-group">
                                    <label class="rb-filter-label">
                                        Environment
                                    </label>

                                    <select
                                        id="filter-environment"
                                        class="rb-filter-select"
                                    >
                                        <option value="">All</option>
                                    </select>
                                </div>


                                <!-- CRITICALITY -->
                                <div class="rb-filter-group">
                                    <label class="rb-filter-label">
                                        Criticality
                                    </label>

                                    <select
                                        id="filter-criticality"
                                        class="rb-filter-select"
                                    >
                                        <option value="">All</option>
                                        <option value="Critical">Critical</option>
                                        <option value="Very High">Very High</option>
                                        <option value="High">High</option>
                                        <option value="Medium">Medium</option>
                                        <option value="Low">Low</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>


                                <!-- STATUS BACKUP -->
                                <div class="rb-filter-group">
                                    <label class="rb-filter-label">
                                        Status Backup
                                    </label>

                                    <select
                                        id="filter-status-backup"
                                        class="rb-filter-select"
                                    >
                                        <option value="">All</option>
                                        <option value="DONE BACKUP">DONE BACKUP</option>
                                        <option value="NEED BACKUP">NEED BACKUP</option>
                                        <option value="NO NEED BACKUP">NO NEED BACKUP</option>
                                    </select>
                                </div>

                                <!-- REASON NEED BACKUP -->
                                <div class="rb-filter-group">
                                    <label class="rb-filter-label">
                                        Reason Need Backup
                                    </label>

                                    <select
                                        id="filter-need-backup-reason"
                                        class="rb-filter-select"
                                    >
                                        <option value="">All</option>
                                    </select>
                                </div>

                                <!-- VREPS -->
                                <div class="rb-filter-group">
                                    <label class="rb-filter-label">
                                        vReps
                                    </label>

                                    <select
                                        id="filter-vrep"
                                        class="rb-filter-select"
                                    >
                                        <option value="">All</option>
                                        <option value="YES">YES</option>
                                        <option value="NO">NO</option>
                                    </select>
                                </div>


                                <!-- RUBRIK -->
                                <div class="rb-filter-group">
                                    <label class="rb-filter-label">
                                        Rubrik
                                    </label>

                                    <select
                                        id="filter-rubrik"
                                        class="rb-filter-select"
                                    >
                                        <option value="">All</option>
                                        <option value="YES">YES</option>
                                        <option value="NO">NO</option>
                                    </select>
                                </div>


                                <!-- DB -->
                                <div class="rb-filter-group">
                                    <label class="rb-filter-label">
                                        DB
                                    </label>

                                    <select
                                        id="filter-db"
                                        class="rb-filter-select"
                                    >
                                        <option value="">All</option>
                                        <option value="YES">YES</option>
                                        <option value="NO">NO</option>
                                    </select>
                                </div>


                                <!-- HA -->
                                <div class="rb-filter-group">
                                    <label class="rb-filter-label">
                                        HA
                                    </label>

                                    <select
                                        id="filter-ha"
                                        class="rb-filter-select"
                                    >
                                        <option value="">All</option>
                                        <option value="YES">YES</option>
                                        <option value="NO">NO</option>
                                    </select>
                                </div>

                            </div>

                            <div class="rb-filter-actions">

                                <button
                                    type="button"
                                    id="btn-reset-filter"
                                    class="btn btn-default btn-sm"
                                >
                                    <i class="fa fa-refresh"></i>
                                    Reset Filter
                                </button>

                                <a
                                    href="<?= site_url("replication_backup/report") ?>"
                                    class="btn btn-info btn-sm"
                                >
                                    <i class="fa fa-bar-chart"></i>
                                    Weekly Report
                                </a>

                                <button
                                    type="button"
                                    id="btn-manage-need-backup-reason"
                                    class="btn btn-primary btn-sm"
                                >
                                    <i class="fa fa-tags"></i>
                                    Edit Reason
                                </button>

                                <button
                                    type="button"
                                    id="btn-export-excel"
                                    class="btn btn-success btn-sm"
                                >
                                    <i class="fa fa-file-excel-o"></i>
                                    Export Excel
                                </button>

                            </div>

                        </div>
                        
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

                                            /**
                                             * Current Need Backup Reason.
                                             *
                                             * Hanya dianggap current untuk:
                                             * - Site GTI
                                             * - actual Status Backup NEED BACKUP
                                             */
                                            $need_backup_reason_label = "";

                                            $vm_site =
                                                strtoupper(
                                                    trim(
                                                        (string) ($vm->id_site ?? "")
                                                    )
                                                );

                                            if (
                                                $vm_site === "GTI"
                                                &&
                                                $status === "NEED BACKUP"
                                            ) {
                                                $need_backup_reason_label =
                                                    trim(
                                                        (string) (
                                                            $vm->need_backup_reason_name ?? ""
                                                        )
                                                    );
                                            }

                                            if ($status_label === "") {
                                                $status_label = "-";
                                            }
                                            ?>

                                            <tr
                                                data-vm-id="<?= (int) $vm->id_virtual_machine ?>"
                                                data-need-backup-reason="<?= html_escape($need_backup_reason_label) ?>"
                                            >

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

                                                    <a
                                                        href="<?= site_url(
                                                            "replication_backup/edit_vm/" .
                                                            $vm->id_virtual_machine
                                                        ) ?>"
                                                        class="btn btn-warning btn-xs rb-action-btn"
                                                        title="Edit"
                                                    >
                                                        <i class="fa fa-edit"></i>
                                                    </a>

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

<form
    id="form-export-replication-backup"
    method="post"
    action="<?= site_url("replication_backup/export_excel") ?>"
    target="_blank"
    style="display:none;"
>
    <input
        type="hidden"
        name="vm_ids"
        id="export-vm-ids"
        value=""
    >
</form>

<?php
$need_backup_reasons =
    $need_backup_reasons ?? array();
?>

<!-- =========================================================
     MODAL - KELOLA NEED BACKUP REASON
========================================================== -->
<div
    id="rb-reason-manager-overlay"
    class="rb-reason-modal-overlay"
    aria-hidden="true"
>
    <div
        class="rb-reason-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rb-reason-manager-title"
    >

        <div class="rb-reason-modal-header">
            <h4
                id="rb-reason-manager-title"
                class="rb-reason-modal-title"
            >
                <i class="fa fa-tags"></i>
                Edit Kategori Need Backup
            </h4>
        </div>

        <div class="rb-reason-modal-body">

            <!-- TAMBAH KATEGORI -->
            <form id="rb-reason-add-form">

                <div class="rb-reason-add-row">

                    <input
                        type="text"
                        id="rb-reason-name"
                        class="rb-reason-add-input"
                        maxlength="150"
                        autocomplete="off"
                        placeholder="Masukkan nama kategori baru..."
                    >

                    <button
                        type="submit"
                        id="rb-reason-add-button"
                        class="btn btn-primary btn-sm"
                    >
                        <i class="fa fa-plus"></i>
                        Tambah
                    </button>

                </div>

            </form>


            <!-- LIST KATEGORI -->
            <div class="rb-reason-list">

                <?php if (!empty($need_backup_reasons)): ?>

                    <?php foreach ($need_backup_reasons as $reason): ?>

                        <div class="rb-reason-item">

                            <div class="rb-reason-item-main">

                                <div class="rb-reason-item-name">
                                    <?= html_escape(
                                        $reason["reason_name"]
                                    ) ?>
                                </div>

                                <span class="rb-reason-item-count">
                                    <?= (int) $reason["vm_count"] ?>
                                    VM
                                </span>

                            </div>

                            <button
                                type="button"
                                class="
                                    btn
                                    btn-danger
                                    btn-xs
                                    rb-btn-delete-reason
                                "
                                data-id="<?= (int) $reason[
                                    "id_need_backup_reason"
                                ] ?>"
                                data-name="<?= html_escape(
                                    $reason["reason_name"]
                                ) ?>"
                                data-count="<?= (int) $reason[
                                    "vm_count"
                                ] ?>"
                            >
                                <i class="fa fa-trash"></i>
                                Hapus
                            </button>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="rb-reason-empty">
                        Belum ada kategori Need Backup aktif.
                    </div>

                <?php endif; ?>

            </div>

        </div>

        <div class="rb-reason-modal-footer">

            <button
                type="button"
                id="rb-reason-manager-close"
                class="btn btn-default btn-sm"
            >
                Tutup
            </button>

        </div>

    </div>
</div>


<!-- =========================================================
     MODAL - KONFIRMASI HAPUS
========================================================== -->
<div
    id="rb-reason-confirm-overlay"
    class="rb-reason-modal-overlay"
    aria-hidden="true"
>
    <div
        class="
            rb-reason-modal-card
            rb-reason-dialog-card
        "
        role="dialog"
        aria-modal="true"
    >

        <div class="rb-reason-modal-body">

            <div class="rb-reason-dialog-icon warning">
                <i class="fa fa-exclamation-triangle"></i>
            </div>

            <h4 class="rb-reason-feedback-title">
                Hapus Kategori?
            </h4>

            <p class="rb-reason-feedback-message">
                Apakah Anda yakin ingin menghapus kategori:
            </p>

            <div
                id="rb-reason-confirm-name"
                class="rb-reason-confirm-name"
            ></div>

            <p
                id="rb-reason-confirm-usage"
                class="rb-reason-confirm-message"
            ></p>

            <div class="rb-reason-warning">
                <i class="fa fa-info-circle"></i>
                Kategori tidak akan tersedia untuk pilihan baru,
                tetapi referensi pada data VM lama tetap tersimpan.
            </div>

        </div>

        <div class="rb-reason-modal-footer">

            <button
                type="button"
                id="rb-reason-confirm-no"
                class="
                    btn
                    btn-sm
                    rb-reason-btn-no
                "
            >
                Tidak
            </button>

            <button
                type="button"
                id="rb-reason-confirm-yes"
                class="
                    btn
                    btn-sm
                    rb-reason-btn-yes
                "
            >
                Ya
            </button>

        </div>

    </div>
</div>


<!-- =========================================================
     MODAL - FEEDBACK
========================================================== -->
<div
    id="rb-reason-feedback-overlay"
    class="rb-reason-modal-overlay"
    aria-hidden="true"
>
    <div
        class="
            rb-reason-modal-card
            rb-reason-dialog-card
        "
        role="dialog"
        aria-modal="true"
    >

        <div class="rb-reason-modal-body">

            <div
                id="rb-reason-feedback-icon"
                class="
                    rb-reason-dialog-icon
                    success
                "
            >
                <i class="fa fa-check"></i>
            </div>

            <h4
                id="rb-reason-feedback-title"
                class="rb-reason-feedback-title"
            >
                Berhasil
            </h4>

            <p
                id="rb-reason-feedback-message"
                class="rb-reason-feedback-message"
            ></p>

        </div>

        <div class="rb-reason-modal-footer">

            <button
                type="button"
                id="rb-reason-feedback-ok"
                class="
                    btn
                    btn-sm
                    rb-reason-btn-ok
                "
            >
                OK
            </button>

        </div>

    </div>
</div>

<script>
$(document).ready(function () {

    if (!$.fn.DataTable) {
        return;
    }

    /**
     * ============================================================
     * DATATABLE
     * ============================================================
     */
    var table = $('#table-replication-backup').DataTable({
        pageLength: 25,
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],
        ordering: true,
        searching: true,
        responsive: false
    });

    /**
    * ============================================================
    * CUSTOM FILTER - NEED BACKUP REASON
    * ============================================================
    *
    * Reason tidak mempunyai kolom pada tabel,
    * sehingga filtering dilakukan melalui atribut row.
    */
    $.fn.dataTable.ext.search.push(
        function (settings, data, dataIndex) {

            /**
            * Jangan mempengaruhi DataTable lain.
            */
            if (
                settings.nTable.id !==
                'table-replication-backup'
            ) {
                return true;
            }


            var selectedReason =
                $.trim(
                    String(
                        $('#filter-need-backup-reason')
                            .val() || ''
                    )
                );


            if (selectedReason === '') {
                return true;
            }


            var rowNode =
                table
                    .row(dataIndex)
                    .node();

            if (!rowNode) {
                return false;
            }


            var rowReason =
                $.trim(
                    String(
                        $(rowNode)
                            .attr(
                                'data-need-backup-reason'
                            ) || ''
                    )
                );


            return rowReason === selectedReason;
        }
    );


    /**
     * ============================================================
     * COLUMN INDEX
     * ============================================================
     *
     * 0  = No
     * 1  = Nama VM
     * 2  = Power State
     * 3  = vCenter
     * 4  = Site
     * 5  = Environment
     * 6  = Aplikasi
     * 7  = Criticality
     * 8  = Status Backup
     * 9  = vReps
     * 10 = Rubrik
     * 11 = DB
     * 12 = HA
     * 13 = Standby
     * 14 = Aksi
     */
    var columnIndex = {
        vcenter: 3,
        site: 4,
        environment: 5,
        criticality: 7,
        statusBackup: 8,
        vrep: 9,
        rubrik: 10,
        db: 11,
        ha: 12
    };


    /**
     * ============================================================
     * HELPER - NORMALIZE TEXT
     * ============================================================
     */
    function normalizeText(value) {

        return $('<div>')
            .html(value)
            .text()
            .replace(/\s+/g, ' ')
            .trim();
    }


    /**
     * ============================================================
     * POPULATE DROPDOWN DARI DATA TABLE
     * ============================================================
     */
    function populateFilter(selectId, columnNumber) {

        var $select = $(selectId);

        var values = [];

        table
            .column(columnNumber)
            .data()
            .each(function (value) {

                value = normalizeText(value);

                if (
                    value !== '' &&
                    values.indexOf(value) === -1
                ) {
                    values.push(value);
                }

            });

        values.sort(function (a, b) {
            return a.localeCompare(b);
        });

        values.forEach(function (value) {

            $select.append(
                $('<option>', {
                    value: value,
                    text: value
                })
            );

        });

    }


    /**
     * Site, vCenter dan Environment tidak di-hardcode.
     * Nilainya otomatis mengikuti data yang ada di tabel.
     */
    populateFilter(
        '#filter-site',
        columnIndex.site
    );

    populateFilter(
        '#filter-vcenter',
        columnIndex.vcenter
    );

    populateFilter(
        '#filter-environment',
        columnIndex.environment
    );

    /**
    * ============================================================
    * POPULATE NEED BACKUP REASON FILTER
    * ============================================================
    *
    * Reason tidak mempunyai kolom visual.
    * Nilainya dibaca dari data-need-backup-reason pada setiap row.
    */
    (function populateNeedBackupReasonFilter() {

        var values = [];

        $('#table-replication-backup tbody tr')
            .each(function () {

                var value =
                    $.trim(
                        String(
                            $(this).attr(
                                'data-need-backup-reason'
                            ) || ''
                        )
                    );

                if (
                    value !== ''
                    &&
                    values.indexOf(value) === -1
                ) {
                    values.push(value);
                }
            });


        values.sort(function (a, b) {
            return a.localeCompare(b);
        });


        values.forEach(function (value) {

            $('#filter-need-backup-reason')
                .append(
                    $('<option>', {
                        value: value,
                        text: value
                    })
                );
        });

    })();

    /**
     * ============================================================
     * APPLY EXACT COLUMN FILTER
     * ============================================================
     */
    function applyColumnFilter(columnNumber, value) {

        if (value === '') {

            table
                .column(columnNumber)
                .search('')
                .draw();

            return;
        }

        var escapedValue =
            $.fn.dataTable.util.escapeRegex(
                $.trim(value)
            );

        /**
        * Izinkan whitespace sebelum / sesudah nilai.
        *
        * Ini penting untuk kolom yang menggunakan badge HTML:
        * Criticality
        * Status Backup
        * vReps
        * Rubrik
        * DB
        * HA
        */
        var exactPattern =
            '^\\s*' + escapedValue + '\\s*$';

        table
            .column(columnNumber)
            .search(
                exactPattern,
                true,
                false
            )
            .draw();
    }


    /**
     * ============================================================
     * FILTER EVENTS
     * ============================================================
     */

    $('#filter-site').on('change', function () {
        applyColumnFilter(
            columnIndex.site,
            $(this).val()
        );
    });


    $('#filter-vcenter').on('change', function () {
        applyColumnFilter(
            columnIndex.vcenter,
            $(this).val()
        );
    });


    $('#filter-environment').on('change', function () {
        applyColumnFilter(
            columnIndex.environment,
            $(this).val()
        );
    });


    $('#filter-criticality').on('change', function () {
        applyColumnFilter(
            columnIndex.criticality,
            $(this).val()
        );
    });


    $('#filter-status-backup').on('change', function () {
        applyColumnFilter(
            columnIndex.statusBackup,
            $(this).val()
        );
    });


    $('#filter-need-backup-reason')
    .on('change', function () {

        table.draw();

    });


    $('#filter-vrep').on('change', function () {
        applyColumnFilter(
            columnIndex.vrep,
            $(this).val()
        );
    });


    $('#filter-rubrik').on('change', function () {
        applyColumnFilter(
            columnIndex.rubrik,
            $(this).val()
        );
    });


    $('#filter-db').on('change', function () {
        applyColumnFilter(
            columnIndex.db,
            $(this).val()
        );
    });


    $('#filter-ha').on('change', function () {
        applyColumnFilter(
            columnIndex.ha,
            $(this).val()
        );
    });


    /**
     * ============================================================
     * RESET FILTER
     * ============================================================
     */
    $('#btn-reset-filter').on('click', function () {

        /**
         * Reset seluruh dropdown.
         */
        $('.rb-filter-select').val('');


        /**
         * Reset semua column search.
         */
        table
            .columns()
            .search('');


        /**
         * Reset search box DataTables juga.
         */
        table.search('');


        /**
         * Redraw table.
         */
        table.draw();


        /**
         * Kosongkan input Search bawaan DataTables.
         */
        $('#table-replication-backup_filter input')
            .val('');

    });

    /**
    * ============================================================
    * EXPORT EXCEL
    * ============================================================
    */
    $('#btn-export-excel').on('click', function () {

        var vmIds = [];

        /**
        * Hanya mengambil row yang lolos:
        * - dropdown filter
        * - search DataTables
        *
        * Pagination tidak membatasi export.
        * Jadi seluruh hasil filter ikut diexport,
        * bukan hanya page yang sedang terlihat.
        */
        table
            .rows({
                search: 'applied'
            })
            .nodes()
            .each(function (row) {

                var idVm = $(row).data('vm-id');

                if (idVm) {
                    vmIds.push(String(idVm));
                }

            });


        /**
        * Tidak ada VM hasil filter.
        */
        if (vmIds.length === 0) {

            alert(
                'Tidak ada data VM yang dapat diexport.'
            );

            return;
        }


        /**
        * Kirim list ID ke endpoint export.
        */
        $('#export-vm-ids').val(
            vmIds.join(',')
        );

        $('#form-export-replication-backup')[0].submit();

    });

});
</script>

<script>
$(document).ready(function () {

    /**
     * ============================================================
     * NEED BACKUP REASON
     * ============================================================
     */
    var addReasonUrl =
        <?= json_encode(
            site_url(
                "replication_backup/add_need_backup_reason"
            )
        ) ?>;

    var deleteReasonUrl =
        <?= json_encode(
            site_url(
                "replication_backup/deactivate_need_backup_reason"
            )
        ) ?>;


    var selectedReasonId = 0;

    var feedbackReloadOnClose = false;


    /**
     * ============================================================
     * MODAL HELPER
     * ============================================================
     */
    function openReasonModal($modal) {

        $modal
            .addClass(
                'rb-reason-modal-visible'
            )
            .attr(
                'aria-hidden',
                'false'
            );
    }


    function closeReasonModal($modal) {

        $modal
            .removeClass(
                'rb-reason-modal-visible'
            )
            .attr(
                'aria-hidden',
                'true'
            );
    }


    /**
     * ============================================================
     * FEEDBACK MODAL
     * ============================================================
     */
    function showReasonFeedback(
        title,
        message,
        type,
        reloadOnClose
    ) {

        var $icon =
            $('#rb-reason-feedback-icon');

        var $iconElement =
            $icon.find('i');


        $icon.removeClass(
            'success error'
        );


        if (type === 'success') {

            $icon.addClass('success');

            $iconElement
                .removeClass()
                .addClass(
                    'fa fa-check'
                );

        } else {

            $icon.addClass('error');

            $iconElement
                .removeClass()
                .addClass(
                    'fa fa-times'
                );
        }


        $('#rb-reason-feedback-title')
            .text(title);

        $('#rb-reason-feedback-message')
            .text(message);


        feedbackReloadOnClose =
            reloadOnClose === true;


        openReasonModal(
            $('#rb-reason-feedback-overlay')
        );
    }


    /**
     * ============================================================
     * OPEN / CLOSE MANAGER
     * ============================================================
     */
    $('#btn-manage-need-backup-reason')
        .on(
            'click',
            function () {

                openReasonModal(
                    $('#rb-reason-manager-overlay')
                );

                setTimeout(
                    function () {
                        $('#rb-reason-name')
                            .trigger('focus');
                    },
                    100
                );
            }
        );


    $('#rb-reason-manager-close')
        .on(
            'click',
            function () {

                closeReasonModal(
                    $('#rb-reason-manager-overlay')
                );
            }
        );


    /**
     * Klik backdrop manager = tutup.
     */
    $('#rb-reason-manager-overlay')
        .on(
            'click',
            function (event) {

                if (event.target === this) {

                    closeReasonModal(
                        $(this)
                    );
                }
            }
        );


    /**
     * ============================================================
     * TAMBAH KATEGORI
     * ============================================================
     */
    $('#rb-reason-add-form')
        .on(
            'submit',
            function (event) {

                event.preventDefault();


                var reasonName =
                    $.trim(
                        $('#rb-reason-name')
                            .val()
                    );


                if (reasonName === '') {

                    showReasonFeedback(
                        'Gagal',
                        'Nama kategori tidak boleh kosong.',
                        'error',
                        false
                    );

                    return;
                }


                var $button =
                    $('#rb-reason-add-button');


                $button
                    .prop(
                        'disabled',
                        true
                    );


                $.ajax({

                    url: addReasonUrl,

                    type: 'POST',

                    dataType: 'json',

                    data: {
                        reason_name:
                            reasonName
                    }

                })
                .done(function (response) {

                    if (
                        response &&
                        response.success
                    ) {

                        showReasonFeedback(
                            'Berhasil',
                            response.message ||
                                'Kategori berhasil ditambahkan.',
                            'success',
                            true
                        );

                        return;
                    }


                    showReasonFeedback(
                        'Gagal',
                        (
                            response &&
                            response.message
                        )
                            ? response.message
                            : 'Gagal menambahkan kategori.',
                        'error',
                        false
                    );

                })
                .fail(function () {

                    showReasonFeedback(
                        'Gagal',
                        'Terjadi kesalahan saat menambahkan kategori.',
                        'error',
                        false
                    );

                })
                .always(function () {

                    $button
                        .prop(
                            'disabled',
                            false
                        );
                });
            }
        );


    /**
     * ============================================================
     * OPEN KONFIRMASI HAPUS
     * ============================================================
     */
    $(document)
        .on(
            'click',
            '.rb-btn-delete-reason',
            function () {

                var $button =
                    $(this);


                selectedReasonId =
                    parseInt(
                        $button.data('id'),
                        10
                    ) || 0;


                var reasonName =
                    String(
                        $button.data('name') ||
                        ''
                    );


                var vmCount =
                    parseInt(
                        $button.data('count'),
                        10
                    ) || 0;


                if (selectedReasonId <= 0) {

                    showReasonFeedback(
                        'Gagal',
                        'Kategori Need Backup tidak valid.',
                        'error',
                        false
                    );

                    return;
                }


                $('#rb-reason-confirm-name')
                    .text(
                        reasonName
                    );


                if (vmCount > 0) {

                    $('#rb-reason-confirm-usage')
                        .text(
                            'Kategori ini sedang digunakan oleh ' +
                            vmCount +
                            ' VM GTI dengan status NEED BACKUP.'
                        );

                } else {

                    $('#rb-reason-confirm-usage')
                        .text(
                            'Saat ini kategori ini belum digunakan oleh VM GTI dengan status NEED BACKUP.'
                        );
                }


                openReasonModal(
                    $('#rb-reason-confirm-overlay')
                );
            }
        );


    /**
     * TIDAK
     */
    $('#rb-reason-confirm-no')
        .on(
            'click',
            function () {

                selectedReasonId = 0;

                closeReasonModal(
                    $('#rb-reason-confirm-overlay')
                );
            }
        );


    /**
     * ============================================================
     * YA - HAPUS / NONAKTIFKAN
     * ============================================================
     */
    $('#rb-reason-confirm-yes')
        .on(
            'click',
            function () {

                if (selectedReasonId <= 0) {
                    return;
                }


                var idToDelete =
                    selectedReasonId;


                var $button =
                    $(this);


                $button
                    .prop(
                        'disabled',
                        true
                    );


                $.ajax({

                    url: deleteReasonUrl,

                    type: 'POST',

                    dataType: 'json',

                    data: {
                        id_need_backup_reason:
                            idToDelete
                    }

                })
                .done(function (response) {

                    if (
                        response &&
                        response.success
                    ) {

                        selectedReasonId = 0;


                        closeReasonModal(
                            $('#rb-reason-confirm-overlay')
                        );


                        showReasonFeedback(
                            'Berhasil',
                            response.message ||
                                'Kategori Need Backup berhasil dihapus.',
                            'success',
                            true
                        );

                        return;
                    }


                    showReasonFeedback(
                        'Gagal',
                        (
                            response &&
                            response.message
                        )
                            ? response.message
                            : 'Gagal menghapus kategori.',
                        'error',
                        false
                    );

                })
                .fail(function () {

                    showReasonFeedback(
                        'Gagal',
                        'Terjadi kesalahan saat menghapus kategori.',
                        'error',
                        false
                    );

                })
                .always(function () {

                    $button
                        .prop(
                            'disabled',
                            false
                        );
                });
            }
        );


    /**
     * ============================================================
     * FEEDBACK OK
     * ============================================================
     */
    $('#rb-reason-feedback-ok')
        .on(
            'click',
            function () {

                closeReasonModal(
                    $('#rb-reason-feedback-overlay')
                );


                if (feedbackReloadOnClose) {

                    window.location.reload();

                    return;
                }


                feedbackReloadOnClose = false;
            }
        );

});
</script>