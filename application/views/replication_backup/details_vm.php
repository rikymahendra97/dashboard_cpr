<?php
defined("BASEPATH") or exit("No direct script access allowed");

$vm_detail = $vm_detail ?? null;

if (!$vm_detail) {
    show_404();
    return;
}

/**
 * ------------------------------------------------------------
 * Helper tampilan
 * ------------------------------------------------------------
 */
$vm_name = $vm_detail->virtual_machine_name ?: "-";
$power_state = $vm_detail->power_state ?: "-";
$vcenter_name = $vm_detail->vcenter_name ?: "-";
$site = $vm_detail->id_site ?: "-";
$environment = $vm_detail->environment ?: "-";
$applications = $vm_detail->application_systems ?: "-";
$criticality = $vm_detail->criticality ?: "Others";
$sla_rubrik = $vm_detail->sla_rubrik ?: "-";

$backup_status = $vm_detail->backup_status ?: "-";
$status_referensi = $vm_detail->status_referensi ?: "-";

function rb_detail_backup_status_class($value)
{
    $value = strtoupper(trim((string) $value));

    switch ($value) {
        case "DONE BACKUP":
            return "rb-status-done";

        case "NEED BACKUP":
            return "rb-status-need";

        case "NO NEED BACKUP":
            return "rb-status-no-need";

        default:
            return "rb-status-default";
    }
}

$pair_groups = array(
    "DB" => array(),
    "HA" => array(),
    "SLAVE" => array(),
    "STANDBY" => array()
);

if (!empty($vm_pairs)) {
    foreach ($vm_pairs as $pair) {
        if (isset($pair_groups[$pair->pair_type])) {
            $pair_groups[$pair->pair_type][] = $pair;
        }
    }
}

/**
 * ------------------------------------------------------------
 * Criticality badge
 * ------------------------------------------------------------
 */
switch (strtolower(trim($criticality))) {
    case "critical":
        $criticality_class = "rb-detail-criticality-critical";
        break;

    case "very high":
        $criticality_class = "rb-detail-criticality-very-high";
        break;

    case "high":
        $criticality_class = "rb-detail-criticality-high";
        break;

    case "medium":
        $criticality_class = "rb-detail-criticality-medium";
        break;

    case "low":
        $criticality_class = "rb-detail-criticality-low";
        break;

    default:
        $criticality_class = "rb-detail-criticality-other";
        break;
}

/**
 * ------------------------------------------------------------
 * Backup status badge
 * ------------------------------------------------------------
 */
$status = strtolower(trim((string) $backup_status));

switch ($status) {
    case "done":
        $status_class = "rb-detail-status-done";
        break;

    case "need":
        $status_class = "rb-detail-status-need";
        break;

    case "no need":
        $status_class = "rb-detail-status-no-need";
        break;

    default:
        $status_class = "rb-detail-status-other";
        break;
}

/**
 * ------------------------------------------------------------
 * Protection badge helper
 * ------------------------------------------------------------
 */
function rb_detail_protection_badge($value)
{
    return (int) $value === 1
        ? '<span class="rb-detail-badge rb-detail-yes">YES</span>'
        : '<span class="rb-detail-badge rb-detail-no">NO</span>';
}
?>

<style>
    .rb-detail-wrapper {
        margin-bottom: 20px;
    }

    .rb-detail-header {
        background: #fff;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    }

    .rb-detail-header-title {
        margin: 0;
        color: #2A3F54;
        font-size: 22px;
        font-weight: 700;
    }

    .rb-detail-header-subtitle {
        margin-top: 5px;
        color: #64748B;
        font-size: 12px;
    }

    .rb-detail-section {
        background: #fff;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }

    .rb-detail-section-title {
        margin: 0;
        padding: 13px 18px;
        background: #34495E;
        color: #ECF0F1;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .rb-detail-section-title i {
        margin-right: 7px;
    }

    .rb-detail-content {
        padding: 0px;
    }

    .rb-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0;
    }

    .rb-detail-item {
        padding: 14px;
        border-bottom: 1px solid #E2E8F0;
    }

    .rb-detail-item:nth-child(odd) {
        border-right: 1px solid #E2E8F0;
    }

    .rb-detail-label {
        display: block;
        margin-bottom: 5px;
        color: #64748B;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .rb-detail-value {
        color: #2A3F54;
        font-size: 13px;
        font-weight: 600;
        word-break: break-word;
    }

    .rb-detail-badge {
        display: inline-block;
        min-width: 48px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        text-align: center;
    }

    .rb-detail-yes {
        background: #10B981;
        color: #fff;
    }

    .rb-detail-no {
        background: #CBD5E1;
        color: #475569;
    }

    .rb-detail-status {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .rb-detail-status-done {
        background: #10B981;
        color: #fff;
    }

    .rb-detail-status-need {
        background: #F59E0B;
        color: #fff;
    }

    .rb-detail-status-no-need {
        background: #94A3B8;
        color: #fff;
    }

    .rb-detail-status-other {
        background: #CBD5E1;
        color: #475569;
    }

    .rb-detail-criticality {
        display: inline-block;
        min-width: 75px;
        padding: 5px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        text-align: center;
    }

    .rb-detail-criticality-critical {
        background: #DC2626;
        color: #fff;
    }

    .rb-detail-criticality-very-high {
        background: #F97316;
        color: #fff;
    }

    .rb-detail-criticality-high {
        background: #EAB308;
        color: #fff;
    }

    .rb-detail-criticality-medium {
        background: #3B82F6;
        color: #fff;
    }

    .rb-detail-criticality-low {
        background: #10B981;
        color: #fff;
    }

    .rb-detail-criticality-other {
        background: #94A3B8;
        color: #fff;
    }

    .rb-detail-applications {
        white-space: normal;
        line-height: 1.6;
    }

    .rb-detail-actions {
        margin-bottom: 15px;
    }

    .rb-detail-back {
        display: inline-block;
        padding: 7px 13px;
        border-radius: 4px;
        background: #34495E;
        color: #fff !important;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none !important;
    }

    .rb-detail-back:hover {
        background: #2A3F54;
        color: #fff !important;
    }

    @media (max-width: 768px) {
        .rb-detail-grid {
            grid-template-columns: 1fr;
        }

        .rb-detail-item:nth-child(odd) {
            border-right: none;
        }
    }

    .rb-status-backup {
        display: inline-block;
        min-width: 100px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        text-align: center;
        color: #fff;
    }

    .rb-status-done {
        background: #10B981;
        color: #fff;
    }

    .rb-status-need {
        background: #F59E0B;
        color: #fff;
    }

    .rb-status-no-need {
        background: #EF4444;
        color: #fff;
    }

    .rb-status-default {
        background: #94A3B8;
        color: #fff;
    }
</style>

<section class="scrollable wrapper">
    <div class="right_col" role="main">

        <div class="clearfix"></div>

        <!-- =========================================================
             HEADER
        ========================================================== -->
        <div class="rb-detail-wrapper">

            <div class="rb-detail-header">

                <h2 class="rb-detail-header-title">
                    <i class="fa fa-database"></i>
                    Virtual Machine Detail
                </h2>

                <div class="rb-detail-header-subtitle">
                    Replication & Backup Protection Information
                </div>

            </div>

            <!-- =====================================================
                 VM INFORMATION
            ====================================================== -->
            <div class="rb-detail-section">

                <h3 class="rb-detail-section-title">
                    <i class="fa fa-server"></i>
                    VM Information
                </h3>

                <div class="rb-detail-content">

                    <div class="rb-detail-grid">

                        <!-- VM Name -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                Nama VM
                            </span>

                            <span class="rb-detail-value">
                                <?= html_escape($vm_name) ?>
                            </span>

                        </div>

                        <!-- Power State -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                Power State
                            </span>

                            <span class="rb-detail-value">
                                <?= html_escape($power_state) ?>
                            </span>

                        </div>

                        <!-- vCenter -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                vCenter
                            </span>

                            <span class="rb-detail-value">
                                <?= html_escape($vcenter_name) ?>
                            </span>

                        </div>

                        <!-- Site -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                Site
                            </span>

                            <span class="rb-detail-value">
                                <?= html_escape($site) ?>
                            </span>

                        </div>

                        <!-- Environment -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                Environment
                            </span>

                            <span class="rb-detail-value">
                                <?= html_escape($environment) ?>
                            </span>

                        </div>

                        <!-- Criticality -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                Criticality
                            </span>

                            <span class="rb-detail-value">

                                <span
                                    class="rb-detail-criticality <?= $criticality_class ?>"
                                >
                                    <?= html_escape($criticality) ?>
                                </span>

                            </span>

                        </div>

                        <!-- Application -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                Aplikasi
                            </span>

                            <span class="rb-detail-value rb-detail-applications">
                                <?= html_escape($applications) ?>
                            </span>

                        </div>

                        <!-- SLA Rubrik -->
                        <div class="rb-detail-item">
                            <span class="rb-detail-label">
                                SLA Rubrik
                            </span>

                            <span class="rb-detail-value">
                                <?= html_escape($sla_rubrik) ?>
                            </span>
                        </div>

                    </div>

                </div>

            </div>

            <!-- =====================================================
                 REPLICATION & BACKUP
            ====================================================== -->
            <div class="rb-detail-section rb-detail-section-protection">

                <h3 class="rb-detail-section-title">
                    <i class="fa fa-shield"></i>
                    Replication & Backup
                </h3>

                <div class="rb-detail-content">

                    <div class="rb-detail-grid">

                        <!-- Status Backup -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                Status Backup
                            </span>

                            <span class="rb-detail-value">
                                <span
                                    class="rb-status-backup <?= rb_detail_backup_status_class($backup_status) ?>"
                                >
                                    <?= html_escape($backup_status) ?>
                                </span>
                            </span>

                        </div>

                        <!-- Status Referensi -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                Status Referensi
                            </span>

                            <span class="rb-detail-value">
                                <?= html_escape($status_referensi) ?>
                            </span>

                        </div>

                        <!-- vReps -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                vReps
                            </span>

                            <span class="rb-detail-value">
                                <?= rb_detail_protection_badge($vm_detail->vrep) ?>
                            </span>

                        </div>

                        <!-- Rubrik -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                Rubrik
                            </span>

                            <span class="rb-detail-value">
                                <?= rb_detail_protection_badge($vm_detail->rubrik) ?>
                            </span>

                        </div>

                        <!-- DB -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                DB
                            </span>

                            <span class="rb-detail-value">
                                <?= rb_detail_protection_badge($vm_detail->db) ?>
                            </span>

                        </div>

                        <!-- HA -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                HA
                            </span>

                            <span class="rb-detail-value">
                                <?= rb_detail_protection_badge($vm_detail->ha) ?>
                            </span>

                        </div>

                        <!-- Slave -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                Slave
                            </span>

                            <span class="rb-detail-value">
                                <?= rb_detail_protection_badge($vm_detail->slave) ?>
                            </span>

                        </div>

                        <!-- Standby -->
                        <div class="rb-detail-item">

                            <span class="rb-detail-label">
                                Standby
                            </span>

                            <span class="rb-detail-value">
                                <?= rb_detail_protection_badge($vm_detail->standby) ?>
                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <!-- =====================================================
                 PASANGAN VM
            ====================================================== -->
            <div class="rb-detail-section">

                <h3 class="rb-detail-section-title">
                    <i class="fa fa-shield"></i>
                    VM Pasangan
                </h3>

                <div class="rb-detail-grid">

                    <!-- VM Pasangan DB -->
                    <div class="rb-detail-item">

                        <span class="rb-detail-label">
                            VM Pasangan DB
                        </span>

                        <span class="rb-detail-value">
                            <?php if (!empty($pair_groups["DB"])): ?>

                                <?php foreach ($pair_groups["DB"] as $pair): ?>
                                    <div>
                                        <?= html_escape($pair->virtual_machine_name) ?>
                                    </div>
                                <?php endforeach; ?>

                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </span>

                    </div>

                    <!-- VM Pasangan HA -->
                    <div class="rb-detail-item">

                        <span class="rb-detail-label">
                            VM Pasangan HA
                        </span>

                        <span class="rb-detail-value">
                            <?php if (!empty($pair_groups["HA"])): ?>

                                <?php foreach ($pair_groups["HA"] as $pair): ?>
                                    <div>
                                        <?= html_escape($pair->virtual_machine_name) ?>
                                    </div>
                                <?php endforeach; ?>

                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </span>

                    </div>

                    <!-- VM Pasangan Slave -->
                    <div class="rb-detail-item">

                        <span class="rb-detail-label">
                            VM Pasangan Slave
                        </span>

                        <span class="rb-detail-value">
                            <?php if (!empty($pair_groups["SLAVE"])): ?>

                                <?php foreach ($pair_groups["SLAVE"] as $pair): ?>
                                    <div>
                                        <?= html_escape($pair->virtual_machine_name) ?>
                                    </div>
                                <?php endforeach; ?>

                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </span>

                    </div>

                    <!-- VM Pasangan Standby -->
                    <div class="rb-detail-item">

                        <span class="rb-detail-label">
                            VM Pasangan Standby
                        </span>

                        <span class="rb-detail-value">
                            <?php if (!empty($pair_groups["STANDBY"])): ?>

                                <?php foreach ($pair_groups["STANDBY"] as $pair): ?>
                                    <div>
                                        <?= html_escape($pair->virtual_machine_name) ?>
                                    </div>
                                <?php endforeach; ?>

                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </span>

                    </div>

                </div>

            </div>

            <!-- =====================================================
                 ACTION
            ====================================================== -->
            <div class="rb-detail-actions">

                <a
                    href="<?= site_url("replication_backup") ?>"
                    class="rb-detail-back"
                >
                    <i class="fa fa-arrow-left"></i>
                    Back to List
                </a>

            </div>

        </div>

    </div>
</section>