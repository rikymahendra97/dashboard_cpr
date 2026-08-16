<?php
/**
 * ========================================================================
 * File Name    : detail_vm_incident.php
 * Modul        : VM Utilization Incident
 * Purpose      : Antarmuka Detail Manajemen Siklus Hidup & Jurnal Follow-Up Insiden
 * Architecture : Inline Flex, Linter-Safe JS, SA2
 * ========================================================================
 */

// ========================================================================
// Intelephense Linter Guard & Defensive Programming
// ========================================================================
$id = $id ?? [];
$detail = $detail ?? [];
$fu_history = $fu_history ?? [];
$master_team = $master_team ?? [];

$role = isset($id["id_role"]) ? (int) $id["id_role"] : 99;
$can_edit_execute = can_edit_execute($role);
$can_verify_delete = can_verify_delete($role);

$s_eks = trim($detail["status_insiden"] ?? "");
$is_closed = $s_eks === "Done/Close";
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* TIMELINE & HIGHLIGHT CSS */
    .timeline-wrapper { max-height: 700px; overflow-y: auto; padding-right: 15px; }
    .timeline-wrapper::-webkit-scrollbar { width: 6px; }
    .timeline-wrapper::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
    .timeline-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .timeline-wrapper::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    .timeline { position: relative; padding: 20px 0; list-style: none; }
    .timeline:before { content: ''; position: absolute; top: 0; bottom: 0; left: 20px; width: 3px; background: #edf2f7; border-radius: 3px; }
    .timeline-item { position: relative; margin-bottom: 25px; }
    .timeline-badge { position: absolute; top: 0; left: 8px; width: 27px; height: 27px; border-radius: 50%; text-align: center; color: white; line-height: 27px; font-size: 12px; z-index: 100; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
    .timeline-panel { margin-left: 55px; background: #fff; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); position: relative; }
    .timeline-panel:before { content: " "; position: absolute; top: 10px; left: -8px; border-top: 8px solid transparent; border-right: 8px solid #e2e8f0; border-bottom: 8px solid transparent; }
    .timeline-panel:after { content: " "; position: absolute; top: 11px; left: -7px; border-top: 7px solid transparent; border-right: 7px solid #fff; border-bottom: 7px solid transparent; }

    .btn-fu-highlight { background: linear-gradient(135deg, #3498DB 0%, #2980B9 100%); color: #ffffff !important; border: none; box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3); transition: all 0.3s ease; animation: pulse-soft 2s infinite; }
    .btn-fu-highlight:hover { background: linear-gradient(135deg, #2980B9 0%, #2471A3 100%); box-shadow: 0 6px 12px rgba(52, 152, 219, 0.4); transform: translateY(-1px); color: #ffffff; }

    @keyframes pulse-soft {
        0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.5); }
        70% { box-shadow: 0 0 0 8px rgba(52, 152, 219, 0); }
        100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0); }
    }

    .select2-container--default .select2-selection--single { border: 1px solid #ccc !important; height: 34px !important; border-radius: 4px !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 32px !important; }
    .select2-container--open { z-index: 9999999 !important; }
</style>

<div class="right_col" role="main">
    <div class="">
        <div class="row">
            <div class="col-sm-12">

                <div style="margin-bottom: 15px;">
                    <a href="<?= site_url(
                        "vm_incident",
                    ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; font-weight: bold; color: #555;">
                        <i class="fa fa-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>

                <!-- [ENTERPRISE FIX]: SWEETALERT BFCACHE-SAFE DATA INJECTION -->
                <div id="alert-container">
                    <?php
                    $alerts = $this->session->flashdata("alerts") ?? [];
                    if (empty($alerts) && $this->session->flashdata("success")) {
                        $alerts = [["success", $this->session->flashdata("success")]];
                    }
                    if (empty($alerts) && $this->session->flashdata("error")) {
                        $alerts = [["error", $this->session->flashdata("error")]];
                    }

                    $this->session->unset_userdata(["alerts", "success", "error"]);

                    if (!empty($alerts) && is_array($alerts) && isset($alerts[0])):

                        $tipe = $alerts[0][0] === "error" ? "error" : "success";
                        $pesan = html_escape($alerts[0][1]);
                        ?>
                        <div id="swal-flash-data" data-type="<?= $tipe ?>" data-message="<?= $pesan ?>" style="display: none;"></div>
                    <?php
                    endif;
                    ?>
                </div>

                <div class="row">
                    <!-- KOLOM KIRI: METADATA TIKET -->
                    <div class="col-md-7 col-sm-12">
                        <section class="panel" style="border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                            <div class="panel-heading" style="background: #2A3F54; color: white; border-top-left-radius: 6px; border-top-right-radius: 6px; display: flex; justify-content: space-between; align-items: center; padding: 12px 15px;">
                                <h4 class="font-bold" style="margin:0; font-size: 15px;"><i class="fa fa-ticket"></i> Informasi Tiket</h4>

                                <div style="display: flex; gap: 8px;">
                                    <?php if ($is_closed && !$can_verify_delete): ?>
                                        <button type="button" class="btn btn-default btn-xs btn-locked" style="margin: 0; font-weight: bold; border-radius: 3px; color: #999; background-color: #eee; border-color: #ddd;">
                                            <i class="fa fa-lock"></i> Edit
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= site_url(
                                            "vm_incident/edit/" . ($detail["id_incident"] ?? ""),
                                        ) ?>" class="btn btn-default btn-xs" style="margin: 0; font-weight: bold; border-radius: 3px; color: #2A3F54; background-color: #fff; border-color: #ccc;">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($can_verify_delete): ?>
                                        <button type="button" id="btnTriggerDeleteDetail" class="btn btn-danger btn-xs" style="margin: 0; font-weight: bold; border-radius: 3px; box-shadow: 0 1px 2px rgba(217,83,79,0.2);">
                                            <i class="fa fa-trash"></i> Hapus
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="panel-body" style="background: #fdfdfd;">
                                <table class="table table-bordered" style="background: #fff; margin-bottom: 20px;">

                                    <!-- LEVEL 1: IDENTITAS & STATUS -->
                                    <tr>
                                        <th style="background:#f9f9f9; width:28%; vertical-align:middle;">No Tiket Jira</th>
                                        <td style="vertical-align:middle;">
                                            <!-- [ENTERPRISE FIX]: Flexbox Presisi -->
                                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                <?php if (!empty($detail["link_tiket"])): ?>
                                                    <a href="<?= html_escape(
                                                        $detail["link_tiket"],
                                                    ) ?>" target="_blank" class="font-bold text-primary" title="Buka di Jira"><u><?= html_escape(
    $detail["no_tiket_insiden"] ?? "-",
) ?></u> <i class="fa fa-external-link"></i></a>
                                                <?php else: ?>
                                                    <strong><?= html_escape(
                                                        $detail["no_tiket_insiden"] ?? "-",
                                                    ) ?></strong>
                                                <?php endif; ?>
                                                <i class="fa fa-copy inline-copy-trigger" data-text="<?= html_escape(
                                                    $detail["no_tiket_insiden"] ?? "-",
                                                ) ?>" title="Salin No Tiket" style="color:#cbd5e1; cursor:pointer; font-size:13px;"></i>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="background:#f9f9f9; vertical-align:middle;">Status Tiket</th>
                                        <td style="vertical-align:middle;">
                                            <?php
                                            $s = $detail["status_insiden"] ?? "Open Tiket";
                                            $c =
                                                $s == "Open Tiket"
                                                    ? "bg-red"
                                                    : ($s == "Review by Owner" ||
                                                    $s == "Apply Solution by Owner"
                                                        ? "bg-orange"
                                                        : "bg-green");
                                            ?>
                                            <span class="badge <?= $c ?>"><?= $s ?></span>
                                        </td>
                                    </tr>

                                    <!-- LEVEL 2: OBJEK & KONTEKS VM -->
                                    <?php $is_snapshot_vm = empty($detail["id_virtual_machine"]); ?>
                                    <tr>
                                        <th style="background:#f9f9f9; vertical-align:middle;">Target VM</th>
                                        <td style="vertical-align:middle;">
                                            <!-- [ENTERPRISE FIX]: Flexbox Presisi -->
                                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">
                                                <strong><?= html_escape(
                                                    $detail["nama_vm"] ?? "-",
                                                ) ?></strong>
                                                <i class="fa fa-copy inline-copy-trigger" data-text="<?= html_escape(
                                                    $detail["nama_vm"] ?? "-",
                                                ) ?>" title="Salin Nama VM" style="color:#cbd5e1; cursor:pointer; font-size:12px;"></i>
                                                <?php if ($is_snapshot_vm): ?>
                                                    <span class="label" style="background-color: #8e44ad; font-size: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);" title="Data diekstrak dari Excel, VM belum terdaftar di CMDB"><i class="fa fa-history"></i> Imported Snapshot</span>
                                                <?php endif; ?>
                                            </div>

                                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                <small class="text-muted">IP Address: <span style="font-family: monospace; font-size: 12px; color: #d35400; font-weight: bold;"><?= html_escape(
                                                    $detail["ip_vm"] ?? "-",
                                                ) ?></span></small>
                                                <button type="button" class="btn btn-copy-ip inline-copy-trigger" data-text="<?= html_escape(
                                                    $detail["ip_vm"] ?? "-",
                                                ) ?>" title="Salin IP Alamat" style="padding: 0; border: none; background: transparent; box-shadow: none; outline: none;">
                                                    <i class="fa fa-copy" style="color: #3498db; font-size: 13px; cursor: pointer;"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="background:#f9f9f9; vertical-align:middle;">Spesifikasi VM & Env</th>
                                        <td style="vertical-align:middle; font-size: 12.5px;">
                                            <?php
                                            $kritikalitas = $detail["kritikalitas"] ?? "Unrated";
                                            $crit_badge_style =
                                                "background-color: #7F8C8D; color: white;"; // Default Abu-abu

                                            if (
                                                stripos($kritikalitas, "Critical") !== false ||
                                                stripos($kritikalitas, "Very High") !== false
                                            ) {
                                                $crit_badge_style =
                                                    "background-color: #C0392B; color: white;";
                                            } elseif (stripos($kritikalitas, "High") !== false) {
                                                $crit_badge_style =
                                                    "background-color: #E67E22; color: white;";
                                            } elseif (stripos($kritikalitas, "Medium") !== false) {
                                                $crit_badge_style =
                                                    "background-color: #F1C40F; color: #333;";
                                            } elseif (stripos($kritikalitas, "Low") !== false) {
                                                $crit_badge_style =
                                                    "background-color: #1ABC9C; color: white;";
                                            }
                                            ?>

                                            <span class="label" style="<?= $crit_badge_style ?> padding: 4px 8px; font-size: 11px; box-shadow: inset 0 1px 1px rgba(0,0,0,0.1); margin-right: 5px;" title="Kritikalitas Server"><i class="fa fa-shield"></i> <?= html_escape(
     $kritikalitas,
 ) ?></span>

                                            <?php if ($is_snapshot_vm): ?>
                                                <span class="text-muted" style="font-size: 11.5px; font-style: italic; border-left: 2px solid #ccc; padding-left: 8px;">
                                                    <i class="fa fa-info-circle"></i> Info spesifikasi tidak tersedia (VM belum terdaftar).
                                                </span>
                                            <?php else: ?>
                                                <?php
                                                $cpu = $detail["cpu_count"] ?? "-";
                                                $ram = isset($detail["memory_mb"])
                                                    ? intval($detail["memory_mb"]) / 1024
                                                    : "-";
                                                $disk = $detail["provisioned_gb"] ?? "-";
                                                $env = $detail["environment"] ?? "N/A";
                                                ?>
                                                <span class="label label-default" style="margin-right: 3px;"><i class="fa fa-server"></i> <?= html_escape(
                                                    $env,
                                                ) ?></span>
                                                <span class="label label-info" style="margin-right: 3px;"><?= html_escape(
                                                    $cpu,
                                                ) ?> vCPU</span>
                                                <span class="label label-info" style="margin-right: 3px;"><?= html_escape(
                                                    $ram,
                                                ) ?> GB RAM</span>
                                                <span class="label label-info"><?= html_escape(
                                                    $disk,
                                                ) ?> GB Disk</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- LEVEL 3: INTI MASALAH & URGENSI -->
                                    <tr>
                                        <th style="background:#f9f9f9; vertical-align:middle;">Jenis Insiden & Metrik</th>
                                        <td style="vertical-align:middle;">
                                            <?php
                                            $base_tipe = html_escape($detail["tipe_insiden"] ?? "");
                                            $metric_color = "#73879C";
                                            $icon = "fa-tag";
                                            $guest_os = strtolower($detail["guest_os"] ?? "");
                                            $os_icon =
                                                strpos($guest_os, "win") !== false
                                                    ? "fa-windows"
                                                    : "fa-linux";

                                            if (stripos($base_tipe, "CPU") !== false) {
                                                $metric_color = "#D9534F";
                                                $icon = "fa-area-chart";
                                            } elseif (stripos($base_tipe, "Memory") !== false) {
                                                $metric_color = "#F0AD4E";
                                                $icon = "fa-bolt";
                                            } elseif (stripos($base_tipe, "Disk") !== false) {
                                                $metric_color = "#9B59B6";
                                                $icon = "fa-hdd-o";
                                            } elseif (stripos($base_tipe, "OS") !== false) {
                                                $metric_color = "#546E7A";
                                                $icon = $os_icon;
                                            } elseif (
                                                stripos($base_tipe, "Physical Host") !== false
                                            ) {
                                                $metric_color = "#E74C3C";
                                                $icon = "fa-server";
                                            } elseif (stripos($base_tipe, "Audit") !== false) {
                                                $metric_color = "#1ABB9C";
                                                $icon = "fa-shield";
                                            }

                                            $disk_label = !empty($detail["disk_drive_detail"])
                                                ? ' <span class="text-muted" style="font-weight:normal;">(' .
                                                    html_escape($detail["disk_drive_detail"]) .
                                                    ")</span>"
                                                : "";
                                            $pct_badge = "";

                                            if (
                                                !in_array($base_tipe, [
                                                    "OS",
                                                    "Audit",
                                                    "Physical Host",
                                                    "VM Tools",
                                                ])
                                            ) {
                                                $pct_badge =
                                                    '<span style="background-color: ' .
                                                    $metric_color .
                                                    '; color: white; padding: 2px 8px; border-radius: 12px; margin-left: 8px; font-weight: 800; font-size: 11px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">' .
                                                    html_escape($detail["metrik_tercatat"] ?? "0") .
                                                    "%</span>";
                                            }
                                            ?>
                                            <div style="font-size: 14px; font-weight: bold; color: #2C3E50; display: flex; align-items: center;">
                                                <i class="fa <?= $icon ?>" style="color: <?= $metric_color ?>; margin-right: 8px; font-size: 16px;"></i>
                                                <?= $base_tipe . $disk_label . $pct_badge ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="background:#f9f9f9; vertical-align:middle;">SLA Tracker</th>
                                        <td style="vertical-align:middle;">
                                            <?= get_sla_status_badge(
                                                $detail["sla_deadline"] ?? "",
                                                $detail["status_insiden"] ?? "",
                                            ) ?>
                                        </td>
                                    </tr>

                                    <!-- LEVEL 4: DETAIL NARASI & AUDIT IDENTITAS -->
                                    <tr>
                                        <th style="background:#f9f9f9; vertical-align:middle;">Deskripsi Insiden</th>
                                        <td style="white-space: normal; word-break: break-word; line-height: 1.5; font-size: 12.5px; color:#444; vertical-align:middle;">
                                            <?= !empty($detail["deskripsi_insiden"])
                                                ? nl2br(html_escape($detail["deskripsi_insiden"]))
                                                : '<em class="text-muted" style="font-size:11.5px;">Tidak ada deskripsi kronologi awal yang dicatat.</em>' ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="background:#f9f9f9; vertical-align:middle;">Didaftarkan Oleh</th>
                                        <td style="vertical-align:middle;">
                                            <strong class="text-primary"><i class="fa fa-user"></i> <?= !empty(
                                                $detail["nama_pelapor"]
                                            )
                                                ? html_escape($detail["nama_pelapor"])
                                                : "Sistem / Auto-Generated" ?></strong><br>
                                            <small class="text-muted"><i class="fa fa-clock-o"></i> <?= date(
                                                "d-M-Y H:i",
                                                strtotime($detail["created_at"] ?? ""),
                                            ) ?> WIB</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="background:#f9f9f9; vertical-align:middle;">Statistik FU</th>
                                        <td style="vertical-align:middle;">
                                            <?php
                                            $total_fu = 0;
                                            $last_fu_date = "-";
                                            if (!empty($fu_history)) {
                                                $total_fu = count($fu_history);
                                                $last_fu_date =
                                                    date(
                                                        "d-M-Y H:i",
                                                        strtotime(
                                                            $fu_history[$total_fu - 1][
                                                                "created_at"
                                                            ],
                                                        ),
                                                    ) . " WIB";
                                            }
                                            if ($total_fu > 0): ?>
                                                <span class="badge" style="background-color: #3498DB; font-size: 11px; padding: 3px 7px;"><i class="fa fa-reply"></i> <?= $total_fu ?>x Di-FU</span>
                                                <div style="font-size: 11px; color: #777; margin-top: 5px; font-weight: 600;"><i class="fa fa-clock-o"></i> Terakhir: <?= $last_fu_date ?></div>
                                            <?php else: ?>
                                                <span class="badge" style="background-color: #BDC3C7; font-size: 11px; padding: 3px 7px;"><i class="fa fa-clock-o"></i> Belum ada FU</span>
                                            <?php endif;
                                            ?>
                                        </td>
                                    </tr>
                                </table>

                                <?php if ($is_closed): ?>
                                    <div class="alert alert-success text-center font-bold" style="margin:0; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        <i class="fa fa-check-circle"></i> TIKET TELAH DISELESAIKAN
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>

                    <!-- KOLOM KANAN: TIMELINE REKAM JEJAK -->
                    <div class="col-md-5 col-sm-12">
                        <section class="panel" style="border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); background: #fcfcfc;">
                            <div class="panel-heading" style="background: #f5f7fa; border-bottom: 1px solid #eee; border-top-left-radius: 6px; border-top-right-radius: 6px; display: flex; justify-content: space-between; align-items: center; padding: 12px 15px;">
                                <h4 class="font-bold text-primary" style="margin:0; font-size: 14px;"><i class="fa fa-history"></i> Rekam Jejak (Timeline)</h4>

                                <?php if (!$is_closed): ?>
                                    <button type="button" class="btn btn-fu-highlight btn-sm font-bold" data-toggle="modal" data-target="#modalFollowUp" style="margin:0; border-radius: 4px; padding: 5px 12px;">
                                        <i class="fa fa-reply" style="margin-right:4px;"></i> Tindak Lanjut (FU)
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-green" style="font-size: 11px; padding: 5px 8px;"><i class="fa fa-lock"></i> Closed</span>
                                <?php endif; ?>
                            </div>

                            <div class="panel-body">
                                <div class="timeline-wrapper">
                                    <ul class="timeline">
                                        <li class="timeline-item">
                                            <div class="timeline-badge bg-green"><i class="fa fa-asterisk"></i></div>
                                            <div class="timeline-panel">
                                                <h5 class="font-bold text-success" style="margin-top:0;">Insiden Didaftarkan</h5>
                                                <div style="font-size: 12px; color: #777; margin-bottom: 8px;"><i class="fa fa-clock-o"></i> <?= date(
                                                    "d-M-Y H:i",
                                                    strtotime($detail["created_at"] ?? ""),
                                                ) ?> WIB oleh <b><?= !empty($detail["nama_pelapor"])
     ? html_escape($detail["nama_pelapor"])
     : "Sistem" ?></b></div>
                                                <p style="font-size: 13px; color: #444; margin:0; line-height: 1.5;">Tiket <?= html_escape(
                                                    $detail["no_tiket_insiden"] ?? "",
                                                ) ?> diregistrasi dengan urgensi: <b><?= html_escape(
     $detail["tingkat_urgensi"] ?? "",
 ) ?></b>.</p>
                                            </div>
                                        </li>

                                        <?php if (!empty($fu_history)):
                                            foreach ($fu_history as $fu): ?>
                                                <li class="timeline-item">
                                                    <div class="timeline-badge bg-blue"><i class="fa fa-reply"></i></div>
                                                    <div class="timeline-panel">
                                                        <h5 class="font-bold text-primary" style="margin-top:0;">Tindakan: <?= html_escape(
                                                            $fu["aksi_tindakan"] ?? "-",
                                                        ) ?></h5>
                                                        <div style="font-size: 12px; color: #777; margin-bottom: 8px;">
                                                            <i class="fa fa-clock-o"></i> <?= date(
                                                                "d-M-Y H:i",
                                                                strtotime($fu["created_at"] ?? ""),
                                                            ) ?> WIB oleh <b><?= html_escape(
     $fu["nama_engineer"] ?? "Operator Intern",
 ) ?></b>
                                                        </div>
                                                        <div style="background: #fdfdfd; padding: 10px; border-left: 3px solid #3498DB; font-size: 12.5px; margin-bottom: 5px; border-radius: 2px; border: 1px solid #e1e1e1;">
                                                            <div style="color: #2c3e50; font-weight: bold; margin-bottom: 3px;"><i class="fa fa-address-book-o"></i> Pihak Dihubungi:</div>
                                                            <?php if (
                                                                !empty($fu["id_team_tujuan"])
                                                            ): ?>
                                                                <div style="padding-left: 14px; margin-bottom: 2px;">Tim / Fungsi: <strong class="text-info"><?= html_escape(
                                                                    $fu["team_code"] ??
                                                                        $fu["team_name"],
                                                                ) ?> <?= !empty($fu["team_name"])
     ? "- " . html_escape($fu["team_name"])
     : "" ?></strong></div>
                                                                <div style="padding-left: 14px;">Kontak PIC: <strong><?= !empty(
                                                                    $fu["pic_name"]
                                                                ) && $fu["pic_name"] !== "-"
                                                                    ? html_escape($fu["pic_name"])
                                                                    : "Tim Umum (Tanpa PIC)" ?></strong>
                                                                <?= !empty($fu["pic_contact"]) &&
                                                                $fu["pic_contact"] !== "-"
                                                                    ? ' <span class="text-muted"><i class="fa fa-phone" style="margin-left:5px;"></i> ' .
                                                                        html_escape(
                                                                            $fu["pic_contact"],
                                                                        ) .
                                                                        "</span>"
                                                                    : "" ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="text-muted" style="padding-left: 14px;"><i>Tindakan/Log Internal (Tidak ada pihak eksternal dihubungi)</i></div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div style="background: #f4f8fa; padding: 12px 15px; border-left: 3px solid #3498DB; font-size: 13px; margin-top: 10px; border-radius: 0 4px 4px 0; color: #444; font-style: italic; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); line-height: 1.5;">
                                                            "<?= !empty($fu["catatan_fu"])
                                                                ? nl2br(
                                                                    html_escape($fu["catatan_fu"]),
                                                                )
                                                                : "-" ?>"
                                                        </div>
                                                    </div>
                                                </li>
                                        <?php endforeach;
                                        endif; ?>

                                        <?php if ($is_closed):

                                            $closed_date = isset($detail["resolved_at"])
                                                ? date(
                                                    "d-M-Y H:i",
                                                    strtotime($detail["resolved_at"]),
                                                )
                                                : date(
                                                    "d-M-Y H:i",
                                                    strtotime($detail["created_at"] ?? ""),
                                                );
                                            $closed_by = "Operator Intern (Sistem)";
                                            $resolution_code = "Tiket Ditutup Manual";
                                            $code_icon = "fa-lock";

                                            if (!empty($fu_history)) {
                                                $last_index = count($fu_history) - 1;
                                                $closed_by = html_escape(
                                                    $fu_history[$last_index]["nama_engineer"] ??
                                                        "Sistem",
                                                );
                                                $last_action =
                                                    $fu_history[$last_index]["aksi_tindakan"] ?? "";
                                                if (
                                                    stripos($last_action, "Apply Solution") !==
                                                    false
                                                ) {
                                                    $resolution_code = "Apply Solution by Owner";
                                                    $code_icon = "fa-wrench";
                                                }
                                            }
                                            ?>
                                            <li class="timeline-item">
                                                <div class="timeline-badge bg-red"><i class="fa fa-lock"></i></div>
                                                <div class="timeline-panel" style="background:#fffcf5; border-color:#faebcc;">
                                                    <h5 class="font-bold text-danger" style="margin-top:0; margin-bottom: 5px;">Tiket Selesai (Closed)</h5>
                                                    <div style="font-size: 12px; color: #777; margin-bottom: 10px; display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                                                        <span><i class="fa fa-clock-o"></i> <?= html_escape(
                                                            $closed_date,
                                                        ) ?> WIB oleh <b><?= html_escape(
     $closed_by,
 ) ?></b></span>
                                                        <span class="label label-success" style="font-size: 10px; padding: 3px 6px; box-shadow: inset 0 1px 1px rgba(0,0,0,0.1);"><i class="fa <?= $code_icon ?>"></i> <?= html_escape(
    $resolution_code,
) ?></span>
                                                    </div>
                                                    <p style="font-size: 13px; color: #8a6d3b; margin-bottom: 6px;"><b>Catatan Resolusi Akhir:</b></p>
                                                    <div style="background: #fef9f9; padding: 12px 15px; border-left: 3px solid #e74c3c; font-size: 13px; border-radius: 0 4px 4px 0; color: #444; font-style: italic; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); line-height: 1.5;">
                                                        "<?= !empty($detail["catatan_resolusi"])
                                                            ? nl2br(
                                                                html_escape(
                                                                    $detail["catatan_resolusi"],
                                                                ),
                                                            )
                                                            : "-" ?>"
                                                    </div>
                                                </div>
                                            </li>
                                        <?php
                                        endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Quick Add & Formulir Lainnya Dipertahankan -->
<div class="modal fade" id="modalFollowUp" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content" style="border-radius: 6px; box-shadow: 0 5px 15px rgba(0,0,0,0.25); border:none;">
            <form action="<?= site_url(
                "vm_incident/store_fu",
            ) ?>" method="post" id="formInputFollowUp">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="id_incident" value="<?= html_escape(
                    $detail["id_incident"] ?? "",
                ) ?>">

                <div class="modal-header" style="background-color: #3498DB; color: white; border-top-left-radius: 6px; border-top-right-radius: 6px; padding: 15px 20px;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title font-bold"><i class="fa fa-envelope-o"></i> Input Log Progres & Follow Up</h4>
                </div>

                <div class="modal-body" style="padding: 25px 20px;">
                    <div class="form-group">
                        <label class="font-bold text-primary">Fungsi / Tim Tujuan <span class="text-danger">*</span></label>
                        <select class="form-control select2-team-group" name="id_team_tujuan" id="selectTeamGroup" style="width: 100%;" required>
                            <option value="">-- Ketik Nama Tim atau Kode --</option>
                        </select>
                        <div style="margin-top: 8px;">
                            <button type="button" class="btn btn-default btn-xs" id="btn-quick-add-team" style="margin:0; font-weight:bold; background-color: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <i class="fa fa-plus text-success"></i> Tambah Data Master
                            </button>
                            <p style="font-size: 11px; color: #777; margin-top: 6px; margin-bottom: 0; font-style: italic; line-height: 1.3;">
                                * Klik tombol ini apabila Fungsi/Departemen atau nama Requestor belum terdaftar.
                            </p>
                        </div>
                    </div>

                    <div id="containerPicSelect" style="display: none; margin-top: 15px; background: #f4f8fa; padding: 15px; border-left: 3px solid #3498DB; border-radius: 4px;">
                        <label class="font-bold text-info"><i class="fa fa-user"></i> Pilih PIC Spesifik <span class="text-danger">*</span></label>
                        <select class="form-control select2-team-pic" id="selectTeamPic" style="width: 100%;" required>
                            <option value="">-- Pilih Nama PIC --</option>
                        </select>
                        <div style="margin-top: 10px;">
                            <label class="text-muted" style="font-size: 11.5px; font-weight: 600;">Kontak Terdaftar (Telepon/Email)</label>
                            <input type="text" class="form-control input-sm" id="infoPicContact" disabled style="background-color: #eaf1f6; border-color: #d1e0ec; color: #475569; font-weight: bold;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <label class="font-bold text-primary">Aksi Tindakan / Log Progress <span class="text-danger">*</span></label>
                        <select class="form-control" name="aksi_tindakan" id="selectAksiTindakan" required style="border-radius: 4px; box-shadow: inset 0 1px 1px rgba(0,0,0,0.05);">
                            <optgroup label="Tindakan Pemicu (Push Action)">
                                <option value="Analisa Awal (Tanya User)">Analisa Awal (Tanya User via Kontak)</option>
                                <option value="Reminder FU (Peringatan Ulang)">Reminder FU / Peringatan Ulang</option>
                                <option value="Eskalasi Lanjut (Manajemen)">Eskalasi Lanjut (Manajemen)</option>
                            </optgroup>
                            <optgroup label="Respon & Progress (Update Log)">
                                <option value="User Reply: On Check (Sedang Dicek)">Balasan User: Sedang Dicek (On Check)</option>
                                <option value="Progress Delay (Penundaan Jadwal)">Progress Delay (Penundaan Eksekusi Terjadwal)</option>
                                <option value="Solution Approved (Solusi Disetujui)">Solusi Disetujui User (Siap Dieksekusi)</option>
                                <option value="Observasi Sistem (Pemantauan Zabbix)">Observasi Sistem Pasca Tindakan</option>
                                <option value="Lainnya (Other Update)">Catatan Kustom Lainnya</option>
                            </optgroup>
                            <optgroup label="Finalisasi / Resolusi">
                                <option value="Apply Solution by Owner (Tuning & Resolusi)" style="font-weight:bold; color:#d35400;">Apply Solution by Owner (Tuning & Resolusi)</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label class="font-bold text-primary">Keterangan / Pesan Riil Lapangan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="catatan_fu" rows="3" placeholder="Tulis rincian balasan atau status tindak lanjut secara detail..." required style="border-radius: 4px;"></textarea>
                    </div>

                    <div style="background-color: #fcf8e3; border-left: 4px solid #f0ad4e; padding: 12px 15px; border-radius: 4px; margin-top: 20px;" id="boxStatusTiket">
                        <label class="font-bold" style="color: #d35400; margin-bottom: 5px;"><i class="fa fa-refresh"></i> Ubah Status Tiket Insiden</label>
                        <select class="form-control" name="update_status_insiden" id="selectStatusInsiden" style="border-color: #f0ad4e;">
                            <option value="No Change" selected>-- Biarkan Status Saat Ini (<?= html_escape(
                                $detail["status_insiden"] ?? "Open",
                            ) ?>) --</option>
                            <?php if (($detail["status_insiden"] ?? "") !== "Review by Owner"): ?>
                                <option value="Review by Owner">Geser ke: Review by Owner (Sedang Dicek User)</option>
                            <?php endif; ?>
                            <option value="Done/Close">TUTUP TIKET SECARA MANUAL: Done/Close</option>
                        </select>
                        <small class="text-muted" style="display:block; margin-top:8px; font-size:11px; line-height:1.4; border-top:1px dashed #e2e8f0; padding-top:6px;" id="hintStatusTiket">
                            * Gunakan status <b>Done/Close</b> apabila beban utilisasi telah kembali berangsur normal.
                        </small>
                    </div>
                </div>

                <div class="modal-footer" style="background-color: #f9fbfd; border-top: 1px solid #edf2f7; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px; padding: 12px 20px;">
                    <button type="button" class="btn btn-default font-bold" data-dismiss="modal" style="border-radius: 4px; padding: 6px 14px;">Batal</button>
                    <!-- [ENTERPRISE FIX]: Anti-Spam Check di ID -->
                    <button type="submit" class="btn btn-info font-bold" id="btnSubmitFU" style="border-radius: 4px; padding: 6px 14px;"><i class="fa fa-save"></i> Simpan Histori FU</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDeleteDetail" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-sm" style="margin-top: 15%;">
        <div class="modal-content" style="border-radius: 6px; border: none;">
            <div class="modal-header" style="background: #d9534f; color: #fff; border-top-left-radius: 6px; border-top-right-radius: 6px; padding: 12px 15px;">
                <h4 class="modal-title font-bold" style="font-size: 14px;"><i class="fa fa-warning"></i> Hapus Tiket Permanen</h4>
            </div>
            <div class="modal-body text-center" style="padding: 20px 15px;">
                <p style="font-size: 13px; color: #333; margin: 0 0 10px 0;">Yakin menghapus dokumen insiden berikut beserta kronologi timeline-nya?</p>
                <strong class="text-danger" style="font-size: 15px; letter-spacing: 0.5px;"><?= html_escape(
                    $detail["no_tiket_insiden"] ?? "",
                ) ?></strong>
            </div>
            <div class="modal-footer" style="background: #f8f9fa; text-align: center; padding: 10px 15px; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;">
                <button type="button" class="btn btn-default btn-sm font-bold" data-dismiss="modal">Batal</button>
                <!-- [ENTERPRISE FIX]: Style pointer-events none Anti-Spam Click (Disabled Before Request) -->
                <a href="<?= site_url(
                    "vm_incident/delete/" . ($detail["id_incident"] ?? ""),
                ) ?>" class="btn btn-danger btn-sm font-bold" onclick="$(this).css('pointer-events', 'none').html('<i class=\'fa fa-spinner fa-spin\'></i> Menghapus...');"><i class="fa fa-trash"></i> Ya, Hapus</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalQuickAddTeam" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" style="z-index: 999999;">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
            <form id="formQuickAddTeam">
                <div class="modal-header" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef; border-top-left-radius: 8px; border-top-right-radius: 8px; padding: 18px 25px;">
                    <button type="button" class="close" data-dismiss="modal" style="color: #666; opacity: 0.7; margin-top: 2px;"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title font-bold" style="color: #2c3e50; font-size: 16px;">
                        <i class="fa fa-plus-square text-primary" style="margin-right: 8px;"></i> Tambah Master Fungsi/Tim
                    </h4>
                </div>
                <div class="modal-body" style="padding: 25px 30px; background-color: #fff;">
                    <div style="background-color: #fdfdfd; border: 1px solid #e6e9ed; padding: 15px 15px 5px 15px; border-radius: 6px; margin-bottom: 20px;">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label style="font-size: 12px; font-weight:bold; color: #444;">Nama Team / Fungsi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="qa_team_name" placeholder="Contoh: DIVISI IT APP" required style="border-radius: 4px; text-transform: uppercase;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label style="font-size: 12px; font-weight:bold; color: #444;">Kode <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="qa_team_code" placeholder="Maks 4" required maxlength="4" style="border-radius: 4px; text-transform: uppercase; text-align: center; font-weight: bold; letter-spacing: 1px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-size: 12px; font-weight:bold; color: #777;">Nama Requestor/PIC</label>
                                <input type="text" class="form-control" id="qa_pic_name" placeholder="Nama lengkap PIC..." style="border-radius: 4px;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-size: 12px; font-weight:bold; color: #777;">Kontak PIC</label>
                                <input type="text" class="form-control" id="qa_pic_contact" placeholder="Telepon / Email..." style="border-radius: 4px;">
                            </div>
                        </div>
                    </div>
                    <div id="qa_error_container" style="margin-top: 5px;"></div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef; padding: 15px 25px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                    <button type="button" class="btn btn-default font-bold" data-dismiss="modal" style="border-radius: 4px;">Batal</button>
                    <button type="submit" class="btn btn-primary font-bold" id="btnSaveQuickAdd" style="border-radius: 4px;"><i class="fa fa-save"></i> Simpan ke Master</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= base_url("asset/js/clipboard_engine.js") ?>"></script>

<!-- [ENTERPRISE FIX]: Injeksi JS Variabel Linter-Safe (Cegah P1008 / JSON Parse Error) -->
<script>
    const TEAM_DATA_JSON_STRING = '<?= json_encode(
        $master_team ?? [],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
    ) ?>';
    const URL_AJAX_QUICK_ADD    = '<?= site_url("vm_incident/ajax_quick_add_team") ?>';
    const CSRF_NAME_VAL         = '<?= $this->security->get_csrf_token_name() ?>';
    const CSRF_HASH_VAL         = '<?= $this->security->get_csrf_hash() ?>';
</script>

<script>
    $(document).ready(function() {

        // [ENTERPRISE FIX]: Penanganan Flashdata SweetAlert Murni
        var $flashElem = $('#swal-flash-data');
        if ($flashElem.length > 0) {
            var swalType = $flashElem.data('type');
            var swalMessage = $flashElem.data('message');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: swalType,
                    title: swalType === 'error' ? 'Gagal' : 'Informasi',
                    text: swalMessage,
                    timer: 3500,
                    showConfirmButton: false
                });
            }
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            $flashElem.remove();
        }

        // Bugfix Select2 di dalam Modal Bootstrap
        if (typeof $.fn.modal !== 'undefined' && $.fn.modal.Constructor) {
            $.fn.modal.Constructor.prototype.enforceFocus = function() {};
        }

        // Dekode Variabel Global
        var rawTeamData = [];
        try {
            rawTeamData = JSON.parse(TEAM_DATA_JSON_STRING);
        } catch (e) {
            console.error("JSON Parsing Error pada Master Team", e);
        }

        var groupedTeams = {};
        rawTeamData.forEach(function(item) {
            var keyStr = item.team_code ? item.team_code : item.team_name;
            var labelStr = (item.team_code ? '[' + item.team_code + '] ' : '') + item.team_name;

            if (!groupedTeams[keyStr]) groupedTeams[keyStr] = {
                label: labelStr,
                pics: [],
                id_team_induk: item.id_team // ID pertama sebagai perwakilan Parent
            };
            groupedTeams[keyStr].pics.push(item);
        });

        var $groupSelect = $('#selectTeamGroup');
        $.each(groupedTeams, function(key, groupObj) {
            $groupSelect.append('<option value="' + key + '" data-id_team="' + groupObj.id_team_induk + '">' + groupObj.label + '</option>');
        });

        $groupSelect.select2({
            dropdownParent: $('#modalFollowUp'),
            placeholder: '-- Ketik Nama Tim atau Kode --',
            width: '100%'
        });

        $groupSelect.on('change', function() {
            var selectedKey = $(this).val();
            var $picSelect = $('#selectTeamPic');

            $picSelect.empty().append('<option value="">-- Pilih Nama PIC Spesifik --</option>');
            $('#infoPicContact').val('');

            if (selectedKey && groupedTeams[selectedKey]) {
                // Saat grup (Parent) dipilih, ganti name atribut target ke Parent ID
                var parentIdTeam = $(this).find(':selected').data('id_team');
                $(this).attr('name', 'id_team_tujuan'); // Jadikan ini sebagai sumber ID Team
                $picSelect.removeAttr('name');          // Lepas name dari anak

                var picList = groupedTeams[selectedKey].pics;
                picList.forEach(function(p) {
                    var picNameDisplay = (p.pic_name && p.pic_name !== '-') ? p.pic_name : 'Tim Umum (Tanpa PIC)';
                    $picSelect.append('<option value="' + p.id_team + '" data-contact="' + (p.pic_contact || '-') + '">' + picNameDisplay + '</option>');
                });
                $('#containerPicSelect').slideDown(300);

                if ($picSelect.hasClass("select2-hidden-accessible")) $picSelect.select2('destroy');
                $picSelect.select2({
                    dropdownParent: $('#modalFollowUp'),
                    width: '100%'
                });

                // Auto-select jika cuma ada 1 PIC
                if (picList.length === 1) $picSelect.val(picList[0].id_team).trigger('change');
            } else {
                $('#containerPicSelect').slideUp(300);
            }
        });

        $('#selectTeamPic').on('change', function() {
            var selectedDOM = $(this).find('option:selected')[0];
            if (selectedDOM && $(this).val() !== '') {
                // Jika user memilih PIC spesifik, lempar "id_team_tujuan" ke Select Anak ini
                $groupSelect.removeAttr('name');
                $(this).attr('name', 'id_team_tujuan');

                var picContact = selectedDOM.getAttribute('data-contact');
                $('#infoPicContact').val(picContact && picContact !== '-' ? picContact : 'Belum Ada Kontak Tercatat');
            } else {
                // Jika PIC dikosongkan kembali, kembalikan "id_team_tujuan" ke Select Induk
                $(this).removeAttr('name');
                $groupSelect.attr('name', 'id_team_tujuan');
                $('#infoPicContact').val('');
            }
        });

        $('.btn-locked').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.addClass('animated shake');
            window.setTimeout(function() {
                $btn.removeClass('animated shake');
            }, 800);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Akses Ditolak',
                    text: 'Hanya Role IT-PO/Admin yang diizinkan untuk mengedit parameter tiket historis (Closed).',
                    showConfirmButton: false,
                    timer: 4000
                });
            }
        });

        // ========================================================================
        // COPY INLINE CLIPBOARD (DARI ENTERPRISE FIX)
        // ========================================================================
        $('.inline-copy-trigger').on('click', function(e) {
            e.preventDefault();
            var $icon = $(this);
            var textToCopy = $icon.data('text');

            if (!textToCopy || textToCopy === '-') return;

            var tempInput = $("<input>");
            $("body").append(tempInput);
            tempInput.val(textToCopy).select();
            document.execCommand("copy");
            tempInput.remove();

            $icon.removeClass('fa-copy').addClass('fa-check').css({'color': '#2ecc71', 'transform': 'scale(1.2)'});
            setTimeout(function() {
                $icon.removeClass('fa-check').addClass('fa-copy').css({'color': '#cbd5e1', 'transform': 'scale(1)'});
            }, 1500);

            if (typeof Swal !== 'undefined') {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Disalin: ' + textToCopy, showConfirmButton: false, timer: 1500 });
            }
        });

        // ========================================================================
        // MODAL QUICK ADD TEAM HANDLING
        // ========================================================================
        $('#btn-quick-add-team').on('click', function(e) {
            e.preventDefault();
            $('#formQuickAddTeam')[0].reset();
            $('#qa_team_name, #qa_team_code').css('border', '');
            $('#qa_error_container').html('');
            $('#modalQuickAddTeam').modal('show');
            setTimeout(function() {
                $('#qa_team_name').focus();
            }, 500);
        });

        $('#qa_team_code').on('input', function() {
            $(this).val($(this).val().toUpperCase());
        });

        $('#formQuickAddTeam').on('submit', function(e) {
            e.preventDefault();
            var teamName = $('#qa_team_name').val().trim();
            var teamCode = $('#qa_team_code').val().trim();
            var picName = $('#qa_pic_name').val().trim();
            var picContact = $('#qa_pic_contact').val().trim();

            $('#qa_team_name, #qa_team_code').css('border', '');
            $('#qa_error_container').html('');

            if (teamName === '' || teamCode === '') {
                if (teamName === '') $('#qa_team_name').css('border', '1px solid #e74c3c');
                if (teamCode === '') $('#qa_team_code').css('border', '1px solid #e74c3c');
                return;
            }

            var btnSave = $('#btnSaveQuickAdd');
            btnSave.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');

            var payload = {
                team_name: teamName,
                team_code: teamCode,
                pic_name: picName,
                pic_contact: picContact
            };
            payload[CSRF_NAME_VAL] = $('input[name="' + CSRF_NAME_VAL + '"]').first().val(); // Get latest token

            $.ajax({
                url: URL_AJAX_QUICK_ADD,
                type: 'POST',
                data: payload,
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        var newItem = {
                            id_team: res.id_team,
                            team_code: res.team_code,
                            team_name: res.team_name,
                            pic_name: picName,
                            pic_contact: picContact
                        };
                        rawTeamData.push(newItem);

                        var keyStr = res.team_code ? res.team_code : res.team_name;
                        var labelStr = (res.team_code ? '[' + res.team_code + '] ' : '') + res.team_name;

                        if (!groupedTeams[keyStr]) {
                            groupedTeams[keyStr] = {
                                label: labelStr,
                                pics: [],
                                id_team_induk: res.id_team
                            };
                            $('#selectTeamGroup').append('<option value="' + keyStr + '" data-id_team="'+res.id_team+'">' + labelStr + '</option>');
                        }
                        groupedTeams[keyStr].pics.push(newItem);
                        $('#selectTeamGroup').val(keyStr).trigger('change');
                        $('#modalQuickAddTeam').modal('hide');
                        btnSave.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan ke Master');

                        if (typeof Swal !== 'undefined') { Swal.fire({toast: true, position: 'top-end', icon: 'success', title: 'Berhasil: ' + res.message, showConfirmButton: false, timer: 2000}); }
                    } else {
                        $('#qa_error_container').html('<div class="alert alert-danger" style="padding:8px; margin:0; font-size:12px;"><i class="fa fa-times-circle"></i> ' + res.message + '</div>');
                        btnSave.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan ke Master');
                    }
                },
                error: function() {
                    $('#qa_error_container').html('<div class="alert alert-danger" style="padding:8px; margin:0; font-size:12px;"><i class="fa fa-wifi"></i> Gagal terhubung ke server.</div>');
                    btnSave.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan ke Master');
                }
            });
        });

        $('#btnTriggerDeleteDetail').on('click', function(e) {
            e.preventDefault();
            $('#modalDeleteDetail').modal('show');
        });

        $('#formInputFollowUp').on('submit', function(e) {
            var btn = $('#btnSubmitFU');
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
        });

        // ========================================================================
        // [PATCH UI]: SMART AUTO-CLOSE TRIGGER (Jika aksi "Apply Solution" dipilih)
        // ========================================================================
        $('#selectAksiTindakan').on('change', function() {
            var selectedAksi = $(this).val();
            var $statusDropdown = $('#selectStatusInsiden');
            var $boxStatus = $('#boxStatusTiket');
            var $hintStatus = $('#hintStatusTiket');

            if (selectedAksi === 'Apply Solution by Owner (Tuning & Resolusi)') {
                $statusDropdown.val('Done/Close');
                $statusDropdown.css({
                    'background-color': '#e8f5e9',
                    'border-color': '#2ecc71',
                    'pointer-events': 'none',
                    'opacity': '0.8'
                });

                $boxStatus.css({
                    'background-color': '#f0f9f4',
                    'border-left-color': '#2ecc71'
                });
                $boxStatus.find('label').css('color', '#27ae60');

                $hintStatus.html('<strong class="text-success"><i class="fa fa-check"></i> Mode Resolusi Aktif:</strong> Status tiket akan otomatis diubah menjadi <b>Done/Close</b> karena VM telah di-tuning.');
            } else {
                if ($statusDropdown.val() === 'Done/Close' && $statusDropdown.css('pointer-events') === 'none') {
                    $statusDropdown.val('No Change');
                }
                $statusDropdown.css({
                    'background-color': '',
                    'border-color': '#f0ad4e',
                    'pointer-events': 'auto',
                    'opacity': '1'
                });
                $boxStatus.css({
                    'background-color': '#fcf8e3',
                    'border-left-color': '#f0ad4e'
                });
                $boxStatus.find('label').css('color', '#d35400');
                $hintStatus.html('* Gunakan status <b>Done/Close</b> apabila beban utilisasi telah kembali berangsur normal.');
            }
        });
    });
</script>
