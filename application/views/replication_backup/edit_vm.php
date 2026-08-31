<?php
defined("BASEPATH") or exit("No direct script access allowed");

$vm_detail = $vm_detail ?? null;
$vm_pairs = $vm_pairs ?? [];
$vm_options = $vm_options ?? [];

if (!$vm_detail) {
    return;
}

/**
 * ============================================================
 * BASIC VM INFO
 * ============================================================
 */
$nama_vm = $vm_detail->virtual_machine_name ?? "-";
$power_state = $vm_detail->power_state ?? "-";
$vcenter = $vm_detail->vcenter_name ?? "-";
$site = $vm_detail->id_site ?? "-";
$environment = $vm_detail->environment ?? "-";
$aplikasi = $vm_detail->application_systems ?? "-";
$criticality = $vm_detail->criticality ?? "Others";
$sla_rubrik = $vm_detail->sla_rubrik ?? "-";

$backup_status = strtoupper(trim((string) ($vm_detail->backup_status ?? "-")));
$status_referensi = strtoupper(trim((string) ($vm_detail->status_referensi ?? "NEED BACKUP")));

$vrep = (int) ($vm_detail->vrep ?? 0);
$rubrik = (int) ($vm_detail->rubrik ?? 0);
$db = (int) ($vm_detail->db ?? 0);
$ha = (int) ($vm_detail->ha ?? 0);
$slave = (int) ($vm_detail->slave ?? 0);
$standby = (int) ($vm_detail->standby ?? 0);

/**
 * ============================================================
 * GROUP EXISTING PAIRS
 * ============================================================
 */
$selected_pairs = array(
    "DB" => array(),
    "HA" => array(),
    "SLAVE" => array(),
    "STANDBY" => array()
);

if (!empty($vm_pairs)) {
    foreach ($vm_pairs as $pair) {
        if (isset($selected_pairs[$pair->pair_type])) {
            $selected_pairs[$pair->pair_type][] = (string) $pair->id_vm_pair;
        }
    }
}

/**
 * ============================================================
 * HELPER: CRITICALITY BADGE
 * ============================================================
 */
function rb_edit_criticality_badge_class($value)
{
    $value = strtolower(trim((string) $value));

    switch ($value) {
        case "critical":
            return "rb-criticality-critical";
        case "very high":
            return "rb-criticality-very-high";
        case "high":
            return "rb-criticality-high";
        case "medium":
            return "rb-criticality-medium";
        case "low":
            return "rb-criticality-low";
        default:
            return "rb-criticality-other";
    }
}

/**
 * ============================================================
 * HELPER: STATUS BACKUP BADGE
 * ============================================================
 */
function rb_edit_backup_status_class($value)
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

/**
 * ============================================================
 * HELPER: YES / NO BADGE
 * ============================================================
 */
function rb_edit_protection_badge($value)
{
    if ((int) $value === 1) {
        return '<span class="rb-yes">YES</span>';
    }

    return '<span class="rb-no">NO</span>';
}
?>

<style>
    .rb-edit-page-title {
        margin-bottom: 14px;
    }

    .rb-edit-page-title h2 {
        margin: 0 0 3px 0;
        font-weight: bold;
        color: #2A3F54;
    }

    .rb-edit-page-title p {
        margin: 0;
        color: #7F8C8D;
        font-size: 12px;
    }

    .rb-detail-section {
        background: #fff;
        border: 1px solid #E6E9ED;
        border-radius: 8px;
        margin-bottom: 14px;
        overflow: visible;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    }

    .rb-detail-section-title {
        margin: 0;
        padding: 10px 14px;
        background: #34495E;
        color: #fff;
        font-size: 14px;
        font-weight: bold;
        text-transform: uppercase;
        border-radius: 8px 8px 0 0;
    }

    .rb-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0;
        border-radius: 0 0 8px 8px;
    }

    .rb-detail-item {
        padding: 14px;
        border-bottom: 1px solid #E6E9ED;
    }

    .rb-detail-item:nth-child(odd) {
        border-right: 1px solid #E6E9ED;
    }

    .rb-detail-label {
        display: block;
        margin-bottom: 6px;
        font-size: 10px;
        font-weight: bold;
        color: #7F8C8D;
        text-transform: uppercase;
    }

    .rb-detail-value {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #2A3F54;
        line-height: 1.5;
        word-break: break-word;
    }

    .rb-detail-value-multi div {
        margin-bottom: 4px;
    }

    .rb-detail-value-multi div:last-child {
        margin-bottom: 0;
    }

    .rb-criticality {
        display: inline-block;
        min-width: 70px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        text-align: center;
        color: #fff;
    }

    .rb-criticality-critical { background: #DC2626; }
    .rb-criticality-very-high { background: #F97316; }
    .rb-criticality-high { background: #EAB308; }
    .rb-criticality-medium { background: #3B82F6; }
    .rb-criticality-low { background: #10B981; }
    .rb-criticality-other { background: #94A3B8; }

    .rb-status {
        display: inline-block;
        min-width: 100px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        text-align: center;
        color: #fff;
    }

    .rb-status-done { background: #10B981; }
    .rb-status-need { background: #F59E0B; }
    .rb-status-no-need { background: #EF4444; }
    .rb-status-default { background: #94A3B8; }

    .rb-yes,
    .rb-no {
        display: inline-block;
        min-width: 45px;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        text-align: center;
    }

    .rb-yes {
        background: #10B981;
        color: #fff;
    }

    .rb-no {
        background: #CBD5E1;
        color: #475569;
    }

    .rb-select {
        width: 100%;
        height: 38px;
        padding: 6px 10px;
        border: 1px solid #CCD0D4;
        border-radius: 4px;
        background: #fff;
        color: #2A3F54;
        font-size: 12px;
    }

    .rb-select:focus {
        outline: none;
        border-color: #3B82F6;
    }

    .rb-form-note {
        margin-top: 5px;
        font-size: 11px;
        color: #7F8C8D;
    }

    /**
     * ============================================================
     * MULTI SELECT
     * ============================================================
     */
    .rb-multi-select {
        position: relative;
        width: 100%;
    }

    .rb-multi-control {
        min-height: 40px;
        padding: 5px 35px 5px 6px;
        border: 1px solid #CCD0D4;
        border-radius: 4px;
        background: #fff;
        cursor: text;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 5px;
        position: relative;
    }

    .rb-multi-control.active {
        border-color: #3B82F6;
        box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.12);
    }

    .rb-multi-placeholder {
        color: #94A3B8;
        font-size: 12px;
        padding: 4px 3px;
    }

    .rb-multi-arrow {
        position: absolute;
        right: 11px;
        top: 11px;
        color: #64748B;
        pointer-events: none;
    }

    .rb-multi-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        max-width: 100%;
        padding: 4px 8px;
        background: #E2E8F0;
        border-radius: 4px;
        color: #334155;
        font-size: 11px;
        font-weight: 600;
    }

    .rb-multi-tag-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .rb-multi-tag-remove {
        border: 0;
        background: transparent;
        padding: 0;
        line-height: 1;
        font-size: 15px;
        color: #64748B;
        cursor: pointer;
    }

    .rb-multi-tag-remove:hover {
        color: #DC2626;
    }

    .rb-multi-dropdown {
        display: none;
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 4px);
        z-index: 1050;
        background: #fff;
        border: 1px solid #CBD5E1;
        border-radius: 5px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    .rb-multi-select.open .rb-multi-dropdown {
        display: block;
    }

    .rb-multi-search-wrapper {
        padding: 8px;
        border-bottom: 1px solid #E2E8F0;
    }

    .rb-multi-search {
        width: 100%;
        height: 34px;
        padding: 6px 9px;
        border: 1px solid #CBD5E1;
        border-radius: 4px;
        outline: none;
        font-size: 12px;
    }

    .rb-multi-search:focus {
        border-color: #3B82F6;
    }

    .rb-multi-options {
        max-height: 260px;
        overflow-y: auto;
    }

    .rb-multi-option {
        padding: 9px 11px;
        border-bottom: 1px solid #F1F5F9;
        cursor: pointer;
        color: #334155;
        font-size: 12px;
    }

    .rb-multi-option:last-child {
        border-bottom: 0;
    }

    .rb-multi-option:hover {
        background: #F1F5F9;
    }

    .rb-multi-option.selected {
        background: #EFF6FF;
        color: #2563EB;
        font-weight: 700;
    }

    .rb-multi-option-name {
        display: block;
    }

    .rb-multi-option-meta {
        display: block;
        margin-top: 2px;
        color: #94A3B8;
        font-size: 10px;
        font-weight: normal;
    }

    .rb-multi-empty {
        padding: 14px 10px;
        text-align: center;
        color: #94A3B8;
        font-size: 11px;
    }

    .rb-action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .rb-action-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .rb-save-note {
        color: #94A3B8;
        font-size: 11px;
    }

    @media (max-width: 768px) {
        .rb-detail-grid {
            grid-template-columns: 1fr;
        }

        .rb-detail-item:nth-child(odd) {
            border-right: 0;
        }
    }

    /* ============================================================
        CUSTOM MODAL
    ============================================================ */
    .rb-modal-overlay {
        display: none;

        position: fixed !important;
        top: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        left: 0 !important;

        width: 100vw !important;
        height: 100vh !important;

        z-index: 99999;

        background: rgba(15, 23, 42, 0.55);

        align-items: center !important;
        justify-content: center !important;

        padding: 20px;

        margin: 0 !important;
    }

    .rb-modal-overlay.rb-modal-visible {
        display: flex !important;
    }

    .rb-modal-card {
        width: 100%;
        max-width: 430px;

        margin: auto !important;
        float: none !important;
        position: relative;

        background: #fff;
        border-radius: 10px;

        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);

        overflow: hidden;

        animation: rbModalShow 0.18s ease-out;
    }

    @keyframes rbModalShow {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .rb-modal-header {
        padding: 18px 20px 10px 20px;
        text-align: center;
    }

    .rb-modal-icon {
        width: 48px;
        height: 48px;

        margin: 0 auto 12px auto;

        border-radius: 50%;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 20px;
    }

    .rb-modal-icon-warning {
        background: #FEF3C7;
        color: #D97706;
    }

    .rb-modal-icon-success {
        background: #D1FAE5;
        color: #059669;
    }

    .rb-modal-icon-info {
        background: #DBEAFE;
        color: #2563EB;
    }

    .rb-modal-icon-error {
        background: #FEE2E2;
        color: #DC2626;
    }

    .rb-modal-title {
        margin: 0;

        font-size: 18px;
        font-weight: 700;

        color: #2A3F54;
    }

    .rb-modal-body {
        padding: 5px 25px 18px 25px;

        text-align: center;

        color: #64748B;
        font-size: 13px;
        line-height: 1.6;
    }

    .rb-modal-actions {
        padding: 14px 20px 18px 20px;

        display: flex;
        justify-content: center;
        gap: 8px;

        border-top: 1px solid #E2E8F0;
    }

    .rb-modal-btn {
        min-width: 90px;

        border: 0;
        border-radius: 5px;

        padding: 8px 15px;

        font-size: 12px;
        font-weight: 600;

        cursor: pointer;
    }

    .rb-modal-btn-secondary {
        background: #EF4444;
        color: #fff;
    }

    .rb-modal-btn-secondary:hover {
        background: #DC2626;
        color: #fff;
    }

    .rb-modal-btn-primary {
        background: #3B82F6;
        color: #fff;
    }

    .rb-modal-btn-primary:hover {
        background: #2563EB;
        color: #fff;
    }

    .rb-modal-btn-success {
        background: #10B981;
        color: #fff;
    }

    .rb-modal-btn-success:hover {
        background: #059669;
    }

</style>

<section class="scrollable wrapper">
    <div class="right_col" role="main">

        <div class="clearfix"></div>

        <div class="rb-edit-page-title">
            <h2>
                <i class="fa fa-edit"></i>
                Edit Replication & Backup
            </h2>
            <p>
                Edit Status Referensi dan VM pasangan Virtual Machine.
            </p>
        </div>

        <form
            id="form-replication-backup-edit"
            method="post"
            action="<?= site_url(
                "replication_backup/update_vm/"
                . $vm_detail->id_virtual_machine
            ) ?>"
            autocomplete="off"
        >

            <!-- =====================================================
                 VM INFORMATION
            ====================================================== -->
            <div class="rb-detail-section">

                <h3 class="rb-detail-section-title">
                    <i class="fa fa-server"></i>
                    VM Information
                </h3>

                <div class="rb-detail-grid">

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">Nama VM</span>
                        <span class="rb-detail-value">
                            <?= html_escape($nama_vm) ?>
                        </span>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">Power State</span>
                        <span class="rb-detail-value">
                            <?= html_escape($power_state) ?>
                        </span>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">vCenter</span>
                        <span class="rb-detail-value">
                            <?= html_escape($vcenter) ?>
                        </span>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">Site</span>
                        <span class="rb-detail-value">
                            <?= html_escape($site) ?>
                        </span>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">Environment</span>
                        <span class="rb-detail-value">
                            <?= html_escape($environment ?: "-") ?>
                        </span>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">Criticality</span>
                        <span class="rb-detail-value">
                            <span class="rb-criticality <?= rb_edit_criticality_badge_class($criticality) ?>">
                                <?= html_escape($criticality ?: "Others") ?>
                            </span>
                        </span>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">Aplikasi</span>
                        <span class="rb-detail-value">
                            <?= html_escape($aplikasi ?: "-") ?>
                        </span>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">SLA Rubrik</span>
                        <span class="rb-detail-value">
                            <?= html_escape($sla_rubrik ?: "-") ?>
                        </span>
                    </div>

                </div>

            </div>


            <!-- =====================================================
                 REPLICATION & BACKUP
            ====================================================== -->
            <div class="rb-detail-section">

                <h3 class="rb-detail-section-title">
                    <i class="fa fa-shield"></i>
                    Replication & Backup
                </h3>

                <div class="rb-detail-grid">

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">Status Backup</span>
                        <span class="rb-detail-value">
                            <span class="rb-status <?= rb_edit_backup_status_class($backup_status) ?>">
                                <?= html_escape($backup_status ?: "-") ?>
                            </span>
                        </span>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">Status Referensi</span>

                        <select
                            name="status_referensi"
                            class="rb-select"
                        >
                            <option
                                value="NEED BACKUP"
                                <?= $status_referensi === "NEED BACKUP" ? "selected" : "" ?>
                            >
                                NEED BACKUP
                            </option>

                            <option
                                value="NO NEED BACKUP"
                                <?= $status_referensi === "NO NEED BACKUP" ? "selected" : "" ?>
                            >
                                NO NEED BACKUP
                            </option>
                        </select>

                        <div class="rb-form-note">
                            Field ini bisa diubah manual.
                        </div>
                    </div>

                    <?php
                    $need_backup_reason_required =
                        strtoupper(
                            trim(
                                (string) ($vm_detail->id_site ?? "")
                            )
                        ) === "GTI"
                        &&
                        strtoupper(
                            trim(
                                (string) ($vm_detail->backup_status ?? "")
                            )
                        ) === "NEED BACKUP";


                    $current_need_backup_reason_id =
                        (int) (
                            $vm_detail->id_need_backup_reason ?? 0
                        );

                    $current_need_backup_reason_name =
                        trim(
                            (string) (
                                $vm_detail->need_backup_reason_name ?? ""
                            )
                        );


                    /**
                     * Index kategori aktif.
                     *
                     * Dipakai untuk mengetahui apakah reason
                     * existing masih aktif atau sudah dinonaktifkan.
                     */
                    $active_need_backup_reason_ids = array();

                    foreach (
                        (array) ($need_backup_reasons ?? array())
                        as $reason
                    ) {
                        $active_need_backup_reason_ids[
                            (int) $reason->id_need_backup_reason
                        ] = true;
                    }


                    $current_need_backup_reason_is_active =
                        $current_need_backup_reason_id > 0
                        &&
                        isset(
                            $active_need_backup_reason_ids[
                                $current_need_backup_reason_id
                            ]
                        );
                    ?>

                    <div class="rb-detail-item">

                        <span class="rb-detail-label">
                            Reason Need Backup

                            <?php if ($need_backup_reason_required): ?>
                                <span style="color:#EF4444;">*</span>
                            <?php endif; ?>
                        </span>

                        <select
                            name="id_need_backup_reason"
                            id="id_need_backup_reason"
                            class="rb-select"
                            data-required="<?= $need_backup_reason_required ? "1" : "0" ?>"
                            <?= !$need_backup_reason_required ? "disabled" : "" ?>
                        >

                            <?php
                            /**
                             * Jika reason existing sudah nonaktif,
                             * tetap tampilkan sebagai informasi historis.
                             *
                             * Tetapi option dibuat disabled sehingga
                             * tidak boleh disimpan kembali sebagai pilihan baru.
                             */
                            if (
                                $current_need_backup_reason_id > 0
                                &&
                                !$current_need_backup_reason_is_active
                            ):
                            ?>

                                <option
                                    value="<?= $current_need_backup_reason_id ?>"
                                    selected
                                    disabled
                                    data-inactive="1"
                                >
                                    <?= html_escape(
                                        $current_need_backup_reason_name !== ""
                                            ? $current_need_backup_reason_name
                                                . " (Nonaktif)"
                                            : "Kategori lama (Nonaktif)"
                                    ) ?>
                                </option>

                            <?php elseif ($current_need_backup_reason_id <= 0): ?>

                                <option
                                    value=""
                                    selected
                                >
                                    -- Pilih Reason Need Backup --
                                </option>

                            <?php else: ?>

                                <option value="">
                                    -- Pilih Reason Need Backup --
                                </option>

                            <?php endif; ?>


                            <?php foreach (
                                (array) ($need_backup_reasons ?? array())
                                as $reason
                            ): ?>

                                <?php
                                $reason_id =
                                    (int) $reason->id_need_backup_reason;
                                ?>

                                <option
                                    value="<?= $reason_id ?>"
                                    <?=
                                        $current_need_backup_reason_is_active
                                        &&
                                        $current_need_backup_reason_id === $reason_id
                                            ? "selected"
                                            : ""
                                    ?>
                                >
                                    <?= html_escape(
                                        $reason->reason_name
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>


                        <?php if ($need_backup_reason_required): ?>

                            <div class="rb-form-note">
                                Wajib dipilih karena VM ini berada di
                                <strong>GTI</strong> dengan actual Status Backup
                                <strong>NEED BACKUP</strong>.
                            </div>

                            <?php if (
                                $current_need_backup_reason_id > 0
                                &&
                                !$current_need_backup_reason_is_active
                            ): ?>

                                <div
                                    class="rb-form-note"
                                    style="color:#B45309;"
                                >
                                    Kategori existing sudah nonaktif.
                                    Pilih kategori aktif sebelum menyimpan perubahan.
                                </div>

                            <?php endif; ?>

                        <?php else: ?>

                            <div class="rb-form-note">
                                Reason hanya dapat diubah untuk VM GTI dengan
                                actual Status Backup NEED BACKUP.
                                Nilai historis tidak dihapus otomatis.
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">vReps</span>
                        <span class="rb-detail-value">
                            <?= rb_edit_protection_badge($vrep) ?>
                        </span>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">Rubrik</span>
                        <span class="rb-detail-value">
                            <?= rb_edit_protection_badge($rubrik) ?>
                        </span>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">DB</span>

                        <select
                            name="db"
                            class="rb-select"
                        >
                            <option
                                value="1"
                                <?= $db === 1 ? "selected" : "" ?>
                            >
                                YES
                            </option>

                            <option
                                value="0"
                                <?= $db === 0 ? "selected" : "" ?>
                            >
                                NO
                            </option>
                        </select>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">HA</span>

                        <select
                            name="ha"
                            class="rb-select"
                        >
                            <option
                                value="1"
                                <?= $ha === 1 ? "selected" : "" ?>
                            >
                                YES
                            </option>

                            <option
                                value="0"
                                <?= $ha === 0 ? "selected" : "" ?>
                            >
                                NO
                            </option>
                        </select>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">Slave</span>

                        <select
                            name="slave"
                            class="rb-select"
                        >
                            <option
                                value="1"
                                <?= $slave === 1 ? "selected" : "" ?>
                            >
                                YES
                            </option>

                            <option
                                value="0"
                                <?= $slave === 0 ? "selected" : "" ?>
                            >
                                NO
                            </option>
                        </select>
                    </div>

                    <div class="rb-detail-item">
                        <span class="rb-detail-label">Standby</span>

                        <select
                            name="standby"
                            class="rb-select"
                        >
                            <option
                                value="1"
                                <?= $standby === 1 ? "selected" : "" ?>
                            >
                                YES
                            </option>

                            <option
                                value="0"
                                <?= $standby === 0 ? "selected" : "" ?>
                            >
                                NO
                            </option>
                        </select>
                    </div>

                </div>

            </div>


            <!-- =====================================================
                 VM PASANGAN
            ====================================================== -->
            <div class="rb-detail-section">

                <h3 class="rb-detail-section-title">
                    <i class="fa fa-link"></i>
                    VM Pasangan
                </h3>

                <div class="rb-detail-grid">

                    <!-- VM PASANGAN DB -->
                    <div class="rb-detail-item">
                        <span class="rb-detail-label">VM Pasangan DB</span>

                        <div
                            class="rb-multi-select"
                            data-name="id_vm_db[]"
                            data-selected='<?= html_escape(json_encode($selected_pairs["DB"])) ?>'
                        >
                            <div class="rb-multi-control">
                                <span class="rb-multi-placeholder">
                                    Pilih VM Pasangan DB
                                </span>
                                <i class="fa fa-angle-down rb-multi-arrow"></i>
                            </div>

                            <div class="rb-multi-dropdown">
                                <div class="rb-multi-search-wrapper">
                                    <input
                                        type="text"
                                        class="rb-multi-search"
                                        placeholder="Search VM..."
                                    >
                                </div>

                                <div class="rb-multi-options">
                                    <?php foreach ($vm_options as $option): ?>
                                        <div
                                            class="rb-multi-option"
                                            data-value="<?= html_escape($option->id_virtual_machine) ?>"
                                            data-label="<?= html_escape($option->virtual_machine_name) ?>"
                                        >
                                            <span class="rb-multi-option-name">
                                                <?= html_escape($option->virtual_machine_name) ?>
                                            </span>
                                            <span class="rb-multi-option-meta">
                                                <?= html_escape(($option->id_site ?: "-") . " | " . ($option->vcenter_name ?: "-")) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- VM PASANGAN HA -->
                    <div class="rb-detail-item">
                        <span class="rb-detail-label">VM Pasangan HA</span>

                        <div
                            class="rb-multi-select"
                            data-name="id_vm_ha[]"
                            data-selected='<?= html_escape(json_encode($selected_pairs["HA"])) ?>'
                        >
                            <div class="rb-multi-control">
                                <span class="rb-multi-placeholder">
                                    Pilih VM Pasangan HA
                                </span>
                                <i class="fa fa-angle-down rb-multi-arrow"></i>
                            </div>

                            <div class="rb-multi-dropdown">
                                <div class="rb-multi-search-wrapper">
                                    <input
                                        type="text"
                                        class="rb-multi-search"
                                        placeholder="Search VM..."
                                    >
                                </div>

                                <div class="rb-multi-options">
                                    <?php foreach ($vm_options as $option): ?>
                                        <div
                                            class="rb-multi-option"
                                            data-value="<?= html_escape($option->id_virtual_machine) ?>"
                                            data-label="<?= html_escape($option->virtual_machine_name) ?>"
                                        >
                                            <span class="rb-multi-option-name">
                                                <?= html_escape($option->virtual_machine_name) ?>
                                            </span>
                                            <span class="rb-multi-option-meta">
                                                <?= html_escape(($option->id_site ?: "-") . " | " . ($option->vcenter_name ?: "-")) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- VM PASANGAN SLAVE -->
                    <div class="rb-detail-item">
                        <span class="rb-detail-label">VM Pasangan Slave</span>

                        <div
                            class="rb-multi-select"
                            data-name="id_vm_slave[]"
                            data-selected='<?= html_escape(json_encode($selected_pairs["SLAVE"])) ?>'
                        >
                            <div class="rb-multi-control">
                                <span class="rb-multi-placeholder">
                                    Pilih VM Pasangan Slave
                                </span>
                                <i class="fa fa-angle-down rb-multi-arrow"></i>
                            </div>

                            <div class="rb-multi-dropdown">
                                <div class="rb-multi-search-wrapper">
                                    <input
                                        type="text"
                                        class="rb-multi-search"
                                        placeholder="Search VM..."
                                    >
                                </div>

                                <div class="rb-multi-options">
                                    <?php foreach ($vm_options as $option): ?>
                                        <div
                                            class="rb-multi-option"
                                            data-value="<?= html_escape($option->id_virtual_machine) ?>"
                                            data-label="<?= html_escape($option->virtual_machine_name) ?>"
                                        >
                                            <span class="rb-multi-option-name">
                                                <?= html_escape($option->virtual_machine_name) ?>
                                            </span>
                                            <span class="rb-multi-option-meta">
                                                <?= html_escape(($option->id_site ?: "-") . " | " . ($option->vcenter_name ?: "-")) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- VM PASANGAN STANDBY -->
                    <div class="rb-detail-item">
                        <span class="rb-detail-label">VM Pasangan Standby</span>

                        <div
                            class="rb-multi-select"
                            data-name="id_vm_standby[]"
                            data-selected='<?= html_escape(json_encode($selected_pairs["STANDBY"])) ?>'
                        >
                            <div class="rb-multi-control">
                                <span class="rb-multi-placeholder">
                                    Pilih VM Pasangan Standby
                                </span>
                                <i class="fa fa-angle-down rb-multi-arrow"></i>
                            </div>

                            <div class="rb-multi-dropdown">
                                <div class="rb-multi-search-wrapper">
                                    <input
                                        type="text"
                                        class="rb-multi-search"
                                        placeholder="Search VM..."
                                    >
                                </div>

                                <div class="rb-multi-options">
                                    <?php foreach ($vm_options as $option): ?>
                                        <div
                                            class="rb-multi-option"
                                            data-value="<?= html_escape($option->id_virtual_machine) ?>"
                                            data-label="<?= html_escape($option->virtual_machine_name) ?>"
                                        >
                                            <span class="rb-multi-option-name">
                                                <?= html_escape($option->virtual_machine_name) ?>
                                            </span>
                                            <span class="rb-multi-option-meta">
                                                <?= html_escape(($option->id_site ?: "-") . " | " . ($option->vcenter_name ?: "-")) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>


            <!-- =====================================================
                 ACTION BUTTON
            ====================================================== -->
            <div class="rb-action-bar">
                <div>
                    <button
                        type="button"
                        id="btn-back-replication-backup"
                        class="btn btn-default"
                    >
                        <i class="fa fa-arrow-left"></i>
                        Kembali
                    </button>
                </div>

                <div class="rb-action-right">
                    <button
                        type="submit"
                        id="btn-save-replication-backup"
                        class="btn btn-success"
                    >
                        <i class="fa fa-save"></i>
                        Simpan
                    </button>
                </div>
            </div>

        </form>

    </div>
</section>

<!-- ============================================================
     CUSTOM MODAL
============================================================= -->
<div
    id="rb-custom-modal"
    class="rb-modal-overlay"
    aria-hidden="true"
>
    <div class="rb-modal-card">

        <div class="rb-modal-header">

            <div
                id="rb-modal-icon"
                class="rb-modal-icon rb-modal-icon-warning"
            >
                <i
                    id="rb-modal-icon-symbol"
                    class="fa fa-exclamation-triangle"
                ></i>
            </div>

            <h3
                id="rb-modal-title"
                class="rb-modal-title"
            >
                Konfirmasi
            </h3>

        </div>

        <div
            id="rb-modal-message"
            class="rb-modal-body"
        >
        </div>

        <div class="rb-modal-actions">

            <button
                type="button"
                id="rb-modal-no"
                class="rb-modal-btn rb-modal-btn-secondary"
            >
                Tidak
            </button>

            <button
                type="button"
                id="rb-modal-yes"
                class="rb-modal-btn rb-modal-btn-primary"
            >
                Ya
            </button>

            <button
                type="button"
                id="rb-modal-ok"
                class="rb-modal-btn rb-modal-btn-success"
                style="display:none;"
            >
                OK
            </button>

        </div>

    </div>
</div>

<script>
    $(document).ready(function () {

        /**
        * ============================================================
        * INITIALIZE MULTI SELECT
        * ============================================================
        */
        $(".rb-multi-select").each(function () {

            var $wrapper = $(this);
            var $control = $wrapper.find(".rb-multi-control");
            var $placeholder = $wrapper.find(".rb-multi-placeholder");
            var $options = $wrapper.find(".rb-multi-option");

            var inputName = $wrapper.data("name");
            var selectedValues = [];

            try {
                selectedValues = JSON.parse(
                    $wrapper.attr("data-selected") || "[]"
                );
            } catch (e) {
                selectedValues = [];
            }

            selectedValues = selectedValues.map(function (value) {
                return String(value);
            });

            function renderSelected() {

                $control.find(".rb-multi-tag").remove();
                $control.find("input[type='hidden']").remove();

                if (selectedValues.length === 0) {
                    $placeholder.show();
                } else {
                    $placeholder.hide();
                }

                $options.removeClass("selected");

                selectedValues.forEach(function (value) {

                    var $option = $options.filter(function () {
                        return String($(this).data("value")) === value;
                    }).first();

                    if (!$option.length) {
                        return;
                    }

                    var label = $option.data("label");

                    $option.addClass("selected");

                    var $tag = $("<span>", {
                        "class": "rb-multi-tag"
                    });

                    var $tagText = $("<span>", {
                        "class": "rb-multi-tag-text",
                        text: label
                    });

                    var $remove = $("<button>", {
                        type: "button",
                        "class": "rb-multi-tag-remove",
                        "data-value": value,
                        html: "&times;"
                    });

                    var $hidden = $("<input>", {
                        type: "hidden",
                        name: inputName,
                        value: value
                    });

                    $tag.append($tagText);
                    $tag.append($remove);

                    $control.append($tag);
                    $control.append($hidden);
                });
            }

            $control.on("click", function (event) {

                if ($(event.target).hasClass("rb-multi-tag-remove")) {
                    return;
                }

                $(".rb-multi-select")
                    .not($wrapper)
                    .removeClass("open");

                $(".rb-multi-control")
                    .not($control)
                    .removeClass("active");

                $wrapper.toggleClass("open");
                $control.toggleClass("active");

                if ($wrapper.hasClass("open")) {
                    $wrapper
                        .find(".rb-multi-search")
                        .val("")
                        .trigger("input")
                        .focus();
                }
            });

            $options.on("click", function (event) {

                event.stopPropagation();

                var value = String($(this).data("value"));
                var index = selectedValues.indexOf(value);

                if (index === -1) {
                    selectedValues.push(value);
                } else {
                    selectedValues.splice(index, 1);
                }

                renderSelected();
            });

            $control.on("click", ".rb-multi-tag-remove", function (event) {

                event.stopPropagation();

                var value = String($(this).data("value"));
                var index = selectedValues.indexOf(value);

                if (index !== -1) {
                    selectedValues.splice(index, 1);
                }

                renderSelected();
            });

            $wrapper.find(".rb-multi-search").on("input", function () {

                var keyword = $.trim($(this).val().toLowerCase());
                var visibleCount = 0;

                $options.each(function () {

                    var $option = $(this);
                    var searchableText = $option.text().toLowerCase();

                    var visible = keyword === ""
                        || searchableText.indexOf(keyword) !== -1;

                    $option.toggle(visible);

                    if (visible) {
                        visibleCount++;
                    }
                });

                $wrapper.find(".rb-multi-empty").remove();

                if (visibleCount === 0) {
                    $wrapper.find(".rb-multi-options").append(
                        '<div class="rb-multi-empty">VM tidak ditemukan.</div>'
                    );
                }
            });

            renderSelected();
        });

        $(document).on("click", function (event) {
            if ($(event.target).closest(".rb-multi-select").length === 0) {
                $(".rb-multi-select").removeClass("open");
                $(".rb-multi-control").removeClass("active");
            }
        });

        /**
        * ============================================================
        * FORM CHANGE DETECTION
        * ============================================================
        */

        var $form = $("#form-replication-backup-edit");

        var listUrl =
            <?= json_encode(site_url("replication_backup")) ?>;


        /**
        * Ambil semua ID pasangan yang sedang terpilih.
        *
        * Di-sort agar:
        * [100, 200]
        *
        * dianggap sama dengan:
        * [200, 100]
        */
        function getPairValues(name) {

            var values = [];

            $form
                .find("input[name='" + name + "']")
                .each(function () {

                    values.push(
                        String($(this).val())
                    );

                });

            values.sort();

            return values;
        }


        /**
        * Ambil snapshot kondisi form.
        */
        function getFormState() {

            return JSON.stringify({

                status_referensi:
                    String(
                        $form
                            .find("[name='status_referensi']")
                            .val() || ""
                    ),

                id_need_backup_reason:
                    String(
                        $form
                            .find("[name='id_need_backup_reason']")
                            .val() || ""
                    ),

                db:
                    String(
                        $form
                            .find("[name='db']")
                            .val() || ""
                    ),

                ha:
                    String(
                        $form
                            .find("[name='ha']")
                            .val() || ""
                    ),

                slave:
                    String(
                        $form
                            .find("[name='slave']")
                            .val() || ""
                    ),

                standby:
                    String(
                        $form
                            .find("[name='standby']")
                            .val() || ""
                    ),

                pair_db:
                    getPairValues("id_vm_db[]"),

                pair_ha:
                    getPairValues("id_vm_ha[]"),

                pair_slave:
                    getPairValues("id_vm_slave[]"),

                pair_standby:
                    getPairValues("id_vm_standby[]")

            });
        }


        /**
        * PENTING:
        *
        * Snapshot dibuat SETELAH renderSelected()
        * selesai membuat hidden input pasangan.
        */
        var initialFormState = getFormState();


        /**
        * Cek apakah ada perubahan.
        */
        function hasFormChanged() {

            return (
                getFormState() !== initialFormState
            );

        }

        /**
        * ============================================================
        * NEED BACKUP REASON VALIDATION
        * ============================================================
        */
        var needBackupReasonRequired =
            <?= $need_backup_reason_required
                ? "true"
                : "false" ?>;


        /**
        * Ambil nama reason yang sedang dipilih.
        */
        function getSelectedNeedBackupReasonName() {

            var $selected =
                $form
                    .find(
                        "[name='id_need_backup_reason'] option:selected"
                    );

            return $.trim(
                $selected.text() || ""
            );
        }


        /**
        * Validasi sebelum native submit.
        */
        function validateNeedBackupReason() {

            /**
            * Bukan GTI + NEED BACKUP.
            * Tidak ada validasi reason.
            */
            if (!needBackupReasonRequired) {
                return true;
            }


            var $select =
                $form.find(
                    "[name='id_need_backup_reason']"
                );

            var value =
                String(
                    $select.val() || ""
                );

            var $selected =
                $select.find(
                    "option:selected"
                );


            /**
            * Kosong atau option historical yang
            * sudah dinonaktifkan.
            */
            if (
                value === ""
                ||
                $selected.prop("disabled")
                ||
                $selected.data("inactive") === 1
            ) {

                showRbAlert(
                    "Data Belum Lengkap",
                    "Reason Need Backup wajib dipilih dari kategori yang masih aktif.",
                    "error"
                );

                $select.trigger("focus");

                return false;
            }


            return true;
        }


        /**
        * Pesan modal konfirmasi Save.
        *
        * Untuk GTI + NEED BACKUP, nama reason
        * ikut diperlihatkan kepada user.
        */
        function getSaveConfirmationMessage() {

            var message =
                "Ada perubahan data.";

            if (needBackupReasonRequired) {

                var reasonName =
                    getSelectedNeedBackupReasonName();

                if (reasonName !== "") {

                    message +=
                        " Reason Need Backup: " +
                        reasonName +
                        ".";
                }
            }


            message +=
                " Apakah perubahan ingin disimpan?";

            return message;
        }

        /**
        * ============================================================
        * CUSTOM MODAL
        * ============================================================
        */
        var $rbModal = $("#rb-custom-modal");
        var $rbModalIcon = $("#rb-modal-icon");
        var $rbModalIconSymbol = $("#rb-modal-icon-symbol");
        var $rbModalTitle = $("#rb-modal-title");
        var $rbModalMessage = $("#rb-modal-message");

        var $rbModalYes = $("#rb-modal-yes");
        var $rbModalNo = $("#rb-modal-no");
        var $rbModalOk = $("#rb-modal-ok");


        function hideRbModal() {

            $rbModal.removeClass("rb-modal-visible");
            $rbModal.attr("aria-hidden", "true");

        }


        function setRbModalType(type) {

            $rbModalIcon.removeClass(
                "rb-modal-icon-warning "
                + "rb-modal-icon-success "
                + "rb-modal-icon-info "
                + "rb-modal-icon-error"
            );

            if (type === "success") {

                $rbModalIcon.addClass(
                    "rb-modal-icon-success"
                );

                $rbModalIconSymbol.attr(
                    "class",
                    "fa fa-check"
                );

            } else if (type === "error") {

                $rbModalIcon.addClass(
                    "rb-modal-icon-error"
                );

                $rbModalIconSymbol.attr(
                    "class",
                    "fa fa-times"
                );

            } else if (type === "info") {

                $rbModalIcon.addClass(
                    "rb-modal-icon-info"
                );

                $rbModalIconSymbol.attr(
                    "class",
                    "fa fa-info"
                );

            } else {

                $rbModalIcon.addClass(
                    "rb-modal-icon-warning"
                );

                $rbModalIconSymbol.attr(
                    "class",
                    "fa fa-exclamation-triangle"
                );

            }

        }


        /**
        * Modal konfirmasi YES / NO.
        */
        function showRbConfirm(
            title,
            message,
            onYes,
            onNo
        ) {

            setRbModalType("warning");

            $rbModalTitle.text(title);
            $rbModalMessage.text(message);

            $rbModalOk.hide();
            $rbModalNo.show();
            $rbModalYes.show();

            $rbModalYes
                .off(".rbModal")
                .on("click.rbModal", function () {

                    hideRbModal();

                    if (typeof onYes === "function") {
                        onYes();
                    }

                });


            $rbModalNo
                .off(".rbModal")
                .on("click.rbModal", function () {

                    hideRbModal();

                    if (typeof onNo === "function") {
                        onNo();
                    }

                });


            $rbModal
                .addClass("rb-modal-visible")
                .attr("aria-hidden", "false");

        }


        /**
        * Modal notification dengan tombol OK.
        */
        function showRbAlert(
            title,
            message,
            type,
            onClose
        ) {

            setRbModalType(type || "info");

            $rbModalTitle.text(title);
            $rbModalMessage.text(message);

            $rbModalYes.hide();
            $rbModalNo.hide();
            $rbModalOk.show();

            $rbModalOk
                .off(".rbModal")
                .on("click.rbModal", function () {

                    hideRbModal();

                    if (typeof onClose === "function") {
                        onClose();
                    }

                });


            $rbModal
                .addClass("rb-modal-visible")
                .attr("aria-hidden", "false");

        }

        /**
        * ============================================================
        * SAVE RESULT
        * ============================================================
        */
        var saveSuccess =
            <?= !empty($save_success) ? "true" : "false" ?>;

        var errorMessage =
            <?= json_encode(
                (string) ($error_message ?? "")
            ) ?>;


        /**
        * Save berhasil.
        */
        if (saveSuccess) {

            showRbAlert(
                "Berhasil",
                "Konfigurasi Replication & Backup berhasil disimpan.",
                "success",
                function () {

                    /**
                    * Bersihkan ?saved=1 dari URL
                    * setelah user klik OK.
                    */
                    if (window.history.replaceState) {

                        window.history.replaceState(
                            {},
                            document.title,
                            <?= json_encode(
                                site_url(
                                    "replication_backup/edit_vm/"
                                    . $vm_detail->id_virtual_machine
                                )
                            ) ?>
                        );

                    }

                }
            );

        }

        /**
        * Save gagal.
        */
        if (errorMessage) {

            showRbAlert(
                "Gagal",
                errorMessage,
                "error"
            );

        }

        /**
        * ============================================================
        * BUTTON KEMBALI
        * ============================================================
        */
        $("#btn-back-replication-backup").on(
            "click",
            function () {

                /**
                * Tidak ada perubahan.
                * Langsung kembali ke List.
                */
                if (!hasFormChanged()) {

                    window.location.href = listUrl;

                    return;
                }


                /**
                * Ada perubahan.
                */
                showRbConfirm(
                    "Simpan Perubahan?",
                    getSaveConfirmationMessage(),

                    /**
                    * YES
                    */
                    function () {

                        /**
                        * Native submit.
                        *
                        * Setelah berhasil Controller
                        * kembali ke halaman Edit.
                        */
                        if (!validateNeedBackupReason()) {
                            return;
                        }

                        $form[0].submit();

                    },

                    /**
                    * NO
                    */
                    function () {

                        /**
                        * Buang perubahan dan kembali ke List.
                        */
                        window.location.href = listUrl;

                    }
                );

            }
        );


        /**
        * ============================================================
        * BUTTON SIMPAN
        * ============================================================
        */
        $form.on(
            "submit",
            function (event) {

                event.preventDefault();


                /**
                * Tidak ada perubahan.
                */
                if (!hasFormChanged()) {

                    showRbAlert(
                        "Tidak Ada Perubahan",
                        "Tidak ada perubahan. Data sudah tersimpan.",
                        "info"
                    );

                    return;
                }


                /**
                * Ada perubahan.
                */
                showRbConfirm(
                    "Simpan Perubahan?",
                    getSaveConfirmationMessage(),

                    /**
                    * YES
                    */
                    function () {

                        /**
                        * Native submit supaya event submit
                        * tidak terpanggil kembali.
                        */
                        if (!validateNeedBackupReason()) {
                            return;
                        }

                        $form[0].submit();

                    },

                    /**
                    * NO
                    */
                    function () {

                        /**
                        * Tidak melakukan apa-apa.
                        * User tetap di halaman Edit.
                        */

                    }
                );

            }
        );


        /**
        * ============================================================
        * SAVE NOTIFICATION
        * ============================================================
        */

        var saveSuccess =
            <?= !empty($save_success) ? "true" : "false" ?>;

        var errorMessage =
            <?= json_encode(
                (string) ($error_message ?? "")
            ) ?>;


        /**
        * Popup ini hanya muncul setelah SAVE berhasil.
        */
        if (saveSuccess) {

            showRbAlert(
                "Berhasil",
                "Konfigurasi Replication & Backup berhasil disimpan.",
                "success"
            );


            /**
            * Hilangkan ?saved=1 dari URL
            * supaya popup tidak muncul lagi saat refresh.
            */
            if (window.history.replaceState) {

                window.history.replaceState(
                    {},
                    document.title,
                    <?= json_encode(
                        site_url(
                            "replication_backup/edit_vm/"
                            . $vm_detail->id_virtual_machine
                        )
                    ) ?>
                );

            }

        }


        /**
        * Popup error jika proses SAVE gagal.
        */
        if (errorMessage) {

            showRbAlert(
                "Gagal",
                errorMessage,
                "error"
            );

        }

    });
</script>