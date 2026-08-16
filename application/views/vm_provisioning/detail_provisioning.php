<?php
/**
 * ============================================================================
 * File Name    : detail_provisioning.php
 * Modul        : VM Provisioning
 * Purpose      : Halaman Pusat Eksekusi & Blueprint VM (Read-Only + RBAC Actions)
 * Architecture : Enterprise Data QA (CMDB Tolerant Comparison, Mismatch Logic)
 * ============================================================================
 */

$id = $id ?? [];
$row = $row ?? new stdClass();

// INITIALIZE RBAC
$role = isset($id["id_role"]) ? (int) $id["id_role"] : 99;
$can_edit_execute = can_edit_execute($role);
$can_verify_delete = can_verify_delete($role);

// [QA FIX] LOGIKA TIPE REQUEST (Mencegah Bug Visual "Fresh Install" terbaca "Clone")
$req_type_db = strtolower(trim($row->tipe_request ?? ""));
$is_fresh = $req_type_db === "fresh" || $req_type_db === "fresh install";
$is_clone = $req_type_db === "clone";

// STANDARDISASI STATUS & TIMELINE LOGIC
$raw_status = isset($row->progres_tiket) ? trim($row->progres_tiket) : "";
$status_lower = strtolower($raw_status);

$is_pending = $status_lower === "pending";
$is_inprogress = $status_lower === "in progress";
$is_waiting = $status_lower === "waiting sync";
$is_done = $status_lower === "done" || $status_lower === "cancel";

$badge_class = "label-default";
if ($is_done) {
    $badge_class = "label-success";
} elseif ($is_inprogress) {
    $badge_class = "label-primary";
} elseif ($is_pending) {
    $badge_class = "label-warning";
} elseif ($status_lower === "cancel") {
    $badge_class = "label-default bg-black";
} elseif ($is_waiting) {
    $badge_class = "label-info";
}

// KALKULASI SLA TIMER
$start_date = new DateTime($row->tanggal_masuk_tiket ?? ($row->created_at ?? "now"));
if ($is_done) {
    $end_date = new DateTime($row->tanggal_keluar_tiket ?? "now");
    $sla_status = "Selesai dalam";
} else {
    $end_date = new DateTime();
    $sla_status = "Berjalan";
}

$interval = $start_date->diff($end_date);
$total_days = $interval->days;

if ($is_done) {
    $sla_color = "#10B981";
} else {
    if ($total_days >= 14) {
        $sla_color = "#EF4444";
    } elseif ($total_days >= 7) {
        $sla_color = "#F59E0B";
    } else {
        $sla_color = "#3B82F6";
    }
}

$sla_text = $interval->format("%a Hari, %h Jam");
if ($total_days == 0 && $interval->h == 0) {
    $sla_text = $interval->format("%i Menit");
}

// LOGIKA TIMELINE KELAS
$tl_1_cls = "tl-done";
$tl_2_cls = $is_pending ? "tl-wait" : ($is_inprogress ? "tl-active" : "tl-done");
$tl_3_cls = $is_pending || $is_inprogress ? "tl-wait" : "tl-done";
$tl_4_cls = $is_done ? "tl-done" : ($is_waiting ? "tl-active" : "tl-wait");
?>

<style>
    body { background-color: #F8FAFC; }
    .label-kicker { font-size: 11px; color: #64748B; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 4px; display: block; }
    .val-text { font-size: 14px; color: #1E293B; font-weight: 600; margin-bottom: 0; line-height: 1.4; }
    .val-mono { font-family: 'Courier New', Courier, monospace; font-size: 15px; color: #0F172A; font-weight: 700; background: #F1F5F9; padding: 3px 6px; border-radius: 4px; border: 1px solid #E2E8F0; }
    .ux-panel { background: #FFFFFF; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #E2E8F0; margin-bottom: 24px; }
    .ux-panel-header { padding: 14px 20px; border-bottom: 1px solid #E2E8F0; background-color: #F8FAFC; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
    .ux-panel-title { font-size: 14px; font-weight: 700; color: #334155; margin: 0; display: flex; align-items: center; gap: 8px; }
    .ux-panel-body { padding: 20px; }
    .btn-copy-sm { cursor: pointer; color: #64748B; background: transparent; border: none; font-size: 12px; margin-left: 6px; transition: 0.2s; padding: 0; outline: none; }
    .btn-copy-sm:hover { color: #3B82F6; transform: scale(1.1); }
    .timeline { position: relative; padding-left: 20px; margin-top: 10px; }
    .timeline::before { content: ''; position: absolute; top: 5px; bottom: 5px; left: 5px; width: 2px; background: #E2E8F0; }
    .tl-item { position: relative; padding-left: 25px; margin-bottom: 20px; }
    .tl-item:last-child { margin-bottom: 0; }
    .tl-icon { position: absolute; left: -26px; top: 0; width: 24px; height: 24px; border-radius: 50%; background: #fff; border: 2px solid #E2E8F0; display: flex; align-items: center; justify-content: center; font-size: 10px; z-index: 2; }
    .tl-done .tl-icon { border-color: #10B981; background: #10B981; color: #fff; }
    .tl-active .tl-icon { border-color: #3B82F6; background: #EFF6FF; color: #3B82F6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
    .tl-wait .tl-icon { border-color: #CBD5E1; color: #94A3B8; }
    .tl-title { font-size: 13px; font-weight: 700; color: #1E293B; margin: 0 0 2px 0; }
    .tl-actor { font-size: 11px; color: #64748B; margin: 0; }
    .tl-time { font-size: 10px; color: #94A3B8; font-style: italic; }
    .bp-step { margin-bottom: 24px; }
    .bp-step:last-child { margin-bottom: 0; }
    .bp-step-title { font-size: 14px; font-weight: 700; color: #0F172A; border-bottom: 1px dashed #CBD5E1; padding-bottom: 8px; margin-bottom: 16px; }
    .bp-step-title i { color: #3B82F6; margin-right: 6px; }
    .hw-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .hw-card { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 12px; text-align: center; }
    .hw-val { font-size: 20px; font-weight: 800; color: #0F172A; line-height: 1; margin: 6px 0 2px 0; }
    .hw-unit { font-size: 11px; color: #64748B; font-weight: 600; text-transform: uppercase; }
    .clean-log-box { font-family: monospace; font-size: 12px; white-space: pre-wrap; color: #334155; line-height: 1.5; margin: 0; }

    .select2-container--default .select2-selection--single { border-radius: 4px; border: 1px solid #CBD5E1; height: 38px; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; font-weight: bold; color: #1E293B; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
    .select2-container--open { z-index: 9999999 !important; }

    .compare-table { width: 100%; font-size: 12px; margin-top: 15px; border-collapse: collapse; }
    .compare-table th { background: #1E293B; color: #fff; padding: 8px; text-align: center; font-weight: 600; }
    .compare-table td { padding: 8px; text-align: center; border: 1px solid #E2E8F0; vertical-align: middle; }
    .compare-table tr:nth-child(even) td { background: #F8FAFC; }
</style>

<div class="right_col" role="main">
    <div class="">

        <!-- HEADER CONTROLS -->
        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div style="flex: 1; min-width: 150px; display: flex; justify-content: flex-start;">
                <a href="<?= site_url(
                    "provisioning",
                ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; font-weight: 600; color: #475569; background: #fff; border-color: #cbd5e1; box-shadow: 0 1px 2px rgba(0,0,0,0.05); margin: 0;">
                    <i class="fa fa-arrow-left"></i> Kembali ke Antrean
                </a>
            </div>

            <div style="flex: 1.5; display: flex; justify-content: center; align-items: center; gap: 8px; min-width: 250px;">
                <div style="background: #fff; border: 1px solid #E2E8F0; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <i class="fa fa-clock-o" style="color: <?= $sla_color ?>; font-size: 14px;"></i>
                    <?= $sla_status ?>: <span style="color: #0F172A;"><?= $sla_text ?></span>
                </div>
                <span class="label <?= $badge_class ?>" style="font-size: 13px; padding: 6px 12px; border-radius: 4px; letter-spacing: 0.5px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    STATUS: <?= strtoupper($raw_status) ?>
                </span>
            </div>

            <div style="flex: 1; display: flex; justify-content: flex-end; align-items: center; gap: 8px; min-width: 250px;">
                <a href="<?= site_url(
                    "provisioning/copy_tiket/" . $row->id_tiket,
                ) ?>" class="btn btn-primary btn-sm" style="margin: 0; font-weight: bold; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background-color: #2A3F54; border-color: #2A3F54; color: white;">
                    <i class="fa fa-copy"></i> Duplikat
                </a>

                <?php if ($can_edit_execute): ?>
                    <?php if ($is_done): ?>
                        <button type="button" class="btn btn-default btn-sm btn-locked" style="margin: 0; font-weight: bold; border-radius: 4px; color: #999; background-color: #eee; border-color: #ddd; box-shadow: 0 1px 2px rgba(0,0,0,0.05);" title="Akses Terkunci">
                            <i class="fa fa-lock"></i> Edit Blueprint
                        </button>
                    <?php else: ?>
                        <a href="<?= site_url(
                            "provisioning/edit/" . $row->id_tiket,
                        ) ?>" class="btn btn-default btn-sm" style="margin: 0; font-weight: bold; border-radius: 4px; color: #5A738E; background-color: #F8F9FA; border-color: #E2E2E4; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <i class="fa fa-edit"></i> Edit Blueprint
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($can_verify_delete): ?>
                    <button type="button" id="btn-show-delete-log" class="btn btn-danger btn-sm" style="margin: 0; font-weight: bold; border-radius: 4px; box-shadow: 0 2px 4px rgba(217,83,79,0.2);">
                        <i class="fa fa-trash"></i> Hapus
                    </button>
                <?php endif; ?>
            </div>
        </div>

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
            <!-- KOLOM KIRI -->
            <div class="col-md-4 col-sm-12 col-xs-12">
                <div class="ux-panel">
                    <div class="ux-panel-header">
                        <h2 class="ux-panel-title"><i class="fa fa-info-circle text-primary"></i> Data Administratif</h2>
                    </div>
                    <div class="ux-panel-body" style="padding-bottom: 10px;">
                        <div style="margin-bottom: 14px;">
                            <span class="label-kicker">Nomor iRIS / Request</span>
                            <div class="val-text">
                                <?php if (!empty($row->link_tiket)): ?>
                                    <a href="<?= html_escape(
                                        $row->link_tiket,
                                    ) ?>" target="_blank" style="color: #3B82F6; text-decoration: underline;"><i class="fa fa-external-link"></i> <?= html_escape(
    $row->no_tiket,
) ?></a>
                                <?php else: ?>
                                    <?= html_escape($row->no_tiket) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <span class="label-kicker">Kelompok Aplikasi</span>
                            <div class="val-text"><i class="fa fa-cube" style="color:#94A3B8; width:16px;"></i> <strong style="color: #2563EB;"><?= html_escape(
                                $row->aplikasi ?? "General System",
                            ) ?></strong></div>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <span class="label-kicker">Fungsi / Requestor PIC</span>
                            <div class="val-text">
                                <i class="fa fa-building-o" style="color:#94A3B8; width:16px;"></i> <?= html_escape(
                                    $row->divisi_requestor ?? "",
                                ) ?><br>
                                <i class="fa fa-user" style="color:#94A3B8; width:16px;"></i> <?= html_escape(
                                    $row->nama_requestor ?? "",
                                ) ?> <small style="color:#64748B; font-weight:normal;">(<?= html_escape(
     $row->contact ?? "-",
 ) ?>)</small>
                            </div>
                        </div>

                        <div style="margin-bottom: 10px;">
                            <span class="label-kicker">Kritikalitas & Environment</span>
                            <div>
                                <span class="badge" style="background:#EF4444; font-size:11px; padding:3px 6px;"><?= html_escape(
                                    $row->kritikalitas ?? "",
                                ) ?></span>
                                <span class="badge" style="background:#10B981; font-size:11px; padding:3px 6px;"><?= html_escape(
                                    $row->environment ?? "",
                                ) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- END-TO-END TIMELINE -->
                <div class="ux-panel">
                    <div class="ux-panel-header">
                        <h2 class="ux-panel-title"><i class="fa fa-history text-info"></i> Timeline Operasional</h2>
                    </div>
                    <div class="ux-panel-body">
                        <div class="timeline">
                            <!-- Step 1: Input Data -->
                            <div class="tl-item <?= $tl_1_cls ?>">
                                <div class="tl-icon"><i class="fa fa-check"></i></div>
                                <div class="tl-content">
                                    <h3 class="tl-title">Input Data Request</h3>
                                    <p class="tl-actor">Oleh: <?= html_escape(
                                        $row->created_by ?? "System",
                                    ) ?></p>
                                    <div class="tl-time"><?= date(
                                        "d M Y, H:i",
                                        strtotime(
                                            $row->tanggal_masuk_tiket ??
                                                ($row->created_at ?? date("Y-m-d H:i:s")),
                                        ),
                                    ) ?></div>
                                </div>
                            </div>

                            <!-- Step 2: Eksekusi -->
                            <div class="tl-item <?= $tl_2_cls ?>">
                                <div class="tl-icon"><i class="fa <?= $tl_2_cls === "tl-active"
                                    ? "fa-cog fa-spin"
                                    : ($tl_2_cls === "tl-done"
                                        ? "fa-check"
                                        : "fa-hourglass-o") ?>"></i></div>
                                <div class="tl-content">
                                    <h3 class="tl-title">Eksekusi vCenter</h3>
                                    <?php if (!empty($row->setup_by)): ?>
                                        <p class="tl-actor">Oleh: <?= html_escape(
                                            $row->setup_by,
                                        ) ?></p>
                                        <div class="tl-time"><?= date(
                                            "d M Y, H:i",
                                            strtotime($row->tanggal_masuk_vcenter),
                                        ) ?></div>
                                    <?php else: ?>
                                        <p class="tl-actor">Menunggu tindakan eksekutor</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Step 3: Setup Fisik Selesai -->
                            <div class="tl-item <?= $tl_3_cls ?>">
                                <div class="tl-icon"><i class="fa <?= $tl_3_cls === "tl-done"
                                    ? "fa-check"
                                    : "fa-hourglass-o" ?>"></i></div>
                                <div class="tl-content">
                                    <h3 class="tl-title">Selesai Setup Fisik</h3>
                                    <?php if (
                                        !empty($row->closed_by) ||
                                        $is_waiting ||
                                        $is_done
                                    ): ?>
                                        <p class="tl-actor text-success">Setup Fisik Selesai</p>
                                    <?php else: ?>
                                        <p class="tl-actor">Menunggu setup diselesaikan</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Step 4: CMDB Binding & Closed -->
                            <div class="tl-item <?= $tl_4_cls ?>">
                                <div class="tl-icon"><i class="fa <?= $tl_4_cls === "tl-done"
                                    ? "fa-check"
                                    : ($tl_4_cls === "tl-active"
                                        ? "fa-lock"
                                        : "fa-hourglass-o") ?>"></i></div>
                                <div class="tl-content">
                                    <h3 class="tl-title">Binding CMDB & Closed</h3>
                                    <?php if ($is_done): ?>
                                        <p class="tl-actor text-success">Oleh: <?= html_escape(
                                            $row->closed_by ?? "-",
                                        ) ?></p>
                                        <div class="tl-time"><?= date(
                                            "d M Y, H:i",
                                            strtotime($row->tanggal_keluar_tiket ?? "now"),
                                        ) ?></div>
                                    <?php else: ?>
                                        <p class="tl-actor">Menunggu verifikasi & binding</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($row->keterangan)): ?>
                <div class="ux-panel">
                    <div class="ux-panel-header">
                        <h2 class="ux-panel-title"><i class="fa fa-sticky-note-o text-warning"></i> Log Kendala</h2>
                    </div>
                    <div class="ux-panel-body" style="padding: 12px 15px;">
                        <pre class="clean-log-box" style="color:#d35400; background:#fdf9f9; border-color:#fadbd8;"><?= html_escape(
                            $row->keterangan,
                        ) ?></pre>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- KOLOM KANAN: BLUEPRINT EKSKUSI -->
            <div class="col-md-8 col-sm-12 col-xs-12">
                <div class="ux-panel" style="border-top: 4px solid #2A3F54;">
                    <div class="ux-panel-header" style="background: #fff; padding: 18px 24px;">
                        <h2 class="ux-panel-title" style="font-size: 16px; color: #0F172A;">
                            <i class="fa fa-tasks text-primary"></i> Spesifikasi Teknis Deploy VM
                        </h2>
                        <span class="badge" style="background: #E2E8F0; color: #0F172A; font-size: 12px; letter-spacing: 0.5px; border: 1px solid #CBD5E1; padding: 6px 10px;">
                            <i class="fa <?= $is_fresh ? "fa-star" : "fa-cloud" ?>"></i>
                            Tipe: <?= $is_fresh ? "Fresh Install" : "Clone" ?>
                        </span>
                    </div>

                    <div class="ux-panel-body" style="padding: 24px;">

                        <?php if ($is_clone && ($is_pending || $is_inprogress)): ?>
                            <div style="margin-bottom: 24px; background: #E0F2FE; padding: 12px 15px; border-left: 4px solid #0EA5E9; border-radius: 4px;">
                                <strong style="color: #0369A1; font-size: 13px;"><i class="fa fa-info-circle"></i> Status Replikasi / Progress Clone Terkini:</strong><br>
                                <span style="color: #334155; font-size: 14px; font-weight: bold; display:block; margin-top:4px; white-space: pre-wrap; word-wrap: break-word; line-height: 1.5;">
                                    <?php if (!empty($row->status_clone_recover)): ?>
                                        <?= html_escape($row->status_clone_recover) ?>
                                    <?php else: ?>
                                        <span style="color: #7DD3FC; font-style: italic;">Belum ada update progress.</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- LANGKAH 1 -->
                        <div class="bp-step">
                            <h4 class="bp-step-title"><i class="fa fa-desktop"></i> 1. VM Identity, Storage Placement & Source</h4>
                            <div class="row" style="margin-bottom: 16px;">
                                <div class="col-md-6">
                                    <span class="label-kicker">VM Name (Label vCenter)</span>
                                    <div>
                                        <span class="val-mono text-primary" id="copy_vm_main"><?= html_escape(
                                            $row->nama_server ?? "",
                                        ) ?></span>
                                        <button class="btn-copy-sm" onclick="copyToClipboard('copy_vm_main', this)" title="Copy Label"><i class="fa fa-copy"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <span class="label-kicker">Target Datastore Cluster</span>
                                    <div>
                                        <span class="val-mono" style="color: #059669;" id="copy_ds_main"><?= html_escape(
                                            $row->datastore ?? "",
                                        ) ?></span>
                                        <button class="btn-copy-sm" onclick="copyToClipboard('copy_ds_main', this)" title="Copy Datastore"><i class="fa fa-copy"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <?php if ($is_fresh): ?>
                                        <span class="label-kicker text-success">Golden Template (Deploy From)</span>
                                        <div>
                                            <span class="val-mono" style="color: #059669;" id="copy_src_main"><?= html_escape(
                                                $row->source_clone ?? "Belum dipilih",
                                            ) ?></span>
                                            <button class="btn-copy-sm" onclick="copyToClipboard('copy_src_main', this)"><i class="fa fa-copy"></i></button>
                                        </div>
                                    <?php else: ?>
                                        <span class="label-kicker text-danger">IP Source Clone (Origin)</span>
                                        <div>
                                            <span class="val-mono" style="color: #DC2626;" id="copy_src_main"><?= html_escape(
                                                $row->source_clone ?? "-",
                                            ) ?></span>
                                            <button class="btn-copy-sm" onclick="copyToClipboard('copy_src_main', this)"><i class="fa fa-copy"></i></button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- LANGKAH 2 -->
                        <div class="bp-step">
                            <h4 class="bp-step-title"><i class="fa fa-cubes"></i> 2. Hardware Resource Allocation</h4>
                            <div class="hw-grid">
                                <div class="hw-card">
                                    <i class="fa fa-cogs text-primary" style="font-size: 20px;"></i>
                                    <div class="hw-val" id="val_cpu"><?= html_escape(
                                        $row->cpu ?? 0,
                                    ) ?></div>
                                    <div class="hw-unit">vCPU (Core)</div>
                                </div>
                                <div class="hw-card">
                                    <i class="fa fa-tasks text-success" style="font-size: 20px;"></i>
                                    <div class="hw-val" id="val_ram"><?= html_escape(
                                        $row->ram ?? 0,
                                    ) ?></div>
                                    <div class="hw-unit">Memory (GB)</div>
                                </div>
                                <div class="hw-card">
                                    <i class="fa fa-hdd-o text-warning" style="font-size: 20px;"></i>
                                    <div class="hw-val" id="val_disk"><?= html_escape(
                                        $row->disk ?? 0,
                                    ) ?></div>
                                    <div class="hw-unit">Target Disk (GB)</div>
                                </div>
                            </div>
                        </div>

                        <!-- LANGKAH 3 -->
                        <div class="bp-step" style="margin-bottom: 0;">
                            <h4 class="bp-step-title"><i class="fa fa-globe"></i> 3. Guest OS, Network & Partition Settings</h4>
                            <div class="row" style="margin-bottom: 16px;">
                                <div class="col-md-12">
                                    <span class="label-kicker">Target OS (Guest OS Compatibility)</span>
                                    <div class="val-text"><i class="fa fa-linux text-primary"></i> <?= html_escape(
                                        $row->os ?? "",
                                    ) ?></div>
                                </div>
                            </div>
                            <div class="row" style="margin-bottom: 16px;">
                                <div class="col-md-6">
                                    <span class="label-kicker">Hostname (OS Level)</span>
                                    <div>
                                        <span class="val-mono" style="color: #D97706;" id="copy_host_main"><?= html_escape(
                                            $row->hostname ?? "Belum diset",
                                        ) ?></span>
                                        <?php if (!empty($row->hostname)): ?>
                                            <button class="btn-copy-sm" onclick="copyToClipboard('copy_host_main', this)"><i class="fa fa-copy"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <span class="label-kicker">Static IP Address</span>
                                    <div>
                                        <?php if (!empty($row->ip)): ?>
                                            <span class="val-mono" style="color: #0284C7;" id="copy_ip_main"><?= html_escape(
                                                $row->ip,
                                            ) ?></span>
                                            <button class="btn-copy-sm" onclick="copyToClipboard('copy_ip_main', this)"><i class="fa fa-copy"></i></button>
                                        <?php else: ?>
                                            <span class="val-text" style="color: #94A3B8; font-style: italic;">Belum dialokasikan</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <span class="label-kicker">Detail Mount Point / Partisi Disk</span>
                                <div style="background: #F8FAFC; padding: 12px 16px; border-radius: 4px; font-family: monospace; font-size: 13px; color: #334155; white-space: pre-wrap; min-height: 50px; border: 1px solid #E2E8F0;"><?= html_escape(
                                    $row->detail_disk ?? "Gunakan partisi default OS.",
                                ) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER ACTION PANEL -->
                    <div style="background-color: #F1F5F9; padding: 16px 24px; border-top: 1px solid #E2E8F0; border-radius: 0 0 8px 8px;">
                        <div style="display: flex; justify-content: flex-end; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <?php if (!$is_done): ?>
                                <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalRoadblock" style="margin: 0; font-weight: 600; border-radius: 4px;">
                                    <i class="fa fa-warning"></i> Lapor Kendala
                                </button>
                            <?php endif; ?>

                            <?php if ($is_inprogress && $is_clone): ?>
                                <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#modalUpdateProgress" style="margin: 0; font-weight: 600; border-radius: 4px; box-shadow: 0 2px 4px rgba(6, 182, 212, 0.2);">
                                    <i class="fa fa-refresh"></i> Update Progress Clone
                                </button>
                            <?php endif; ?>

                            <?php if ($is_pending): ?>
                                <form id="formMulaiEksekusi" action="<?= site_url(
                                    "provisioning/action_state_change",
                                ) ?>" method="post" style="margin: 0;">
                                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                    <input type="hidden" name="id_tiket" value="<?= $row->id_tiket ??
                                        "" ?>">
                                    <input type="hidden" name="target_state" value="In Progress">
                                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#mdlMulaiEksekusi" style="margin: 0; border-radius: 4px; font-weight: 600; box-shadow: 0 2px 4px rgba(41, 128, 185, 0.2);">
                                        <i class="fa fa-play-circle" style="margin-right: 4px;"></i> Mulai Eksekusi
                                    </button>
                                </form>
                            <?php elseif ($is_inprogress): ?>
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#mdlSelesaiSetup" style="margin: 0; border-radius: 4px; font-weight: 600; box-shadow: 0 2px 4px rgba(39, 174, 96, 0.2);">
                                    <i class="fa fa-check-square-o" style="margin-right: 4px;"></i> Selesai Setup Fisik
                                </button>
                            <?php elseif ($is_waiting): ?>
                                <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#mdlBindingCMDB" style="margin: 0; border-radius: 4px; font-weight: 600; background-color: #8E44AD; border-color: #8E44AD; box-shadow: 0 2px 4px rgba(142, 68, 173, 0.2);">
                                    <i class="fa fa-link" style="margin-right: 4px;"></i> Bind ke CMDB
                                </button>
                            <?php elseif ($is_done): ?>
                                <button class="btn btn-success btn-sm" disabled style="margin: 0; border-radius: 4px; font-weight: 600; opacity: 1;">
                                    <i class="fa fa-check-circle" style="margin-right: 4px;"></i> Tiket Selesai (Closed)
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- KUMPULAN MODAL EKSEKUSI & BINDING CMDB -->
<!-- ========================================================= -->

<!-- MODAL DEDICATED CMDB BINDING -->
<div class="modal fade" id="mdlBindingCMDB" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md" style="margin-top: 5%;">
        <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <form id="formBindingCMDB" action="<?= site_url(
                "provisioning/bind_cmdb",
            ) ?>" method="post" style="margin: 0;">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="id_tiket" value="<?= $row->id_tiket ?? "" ?>">
                <input type="hidden" name="mismatch_log" id="mismatch_log_input" value="">

                <div class="modal-header" style="background-color: #8E44AD; padding: 15px 20px; border-radius: 8px 8px 0 0; border-bottom: none;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" style="color: white; font-weight: 700; font-size: 16px; margin: 0;">
                        <i class="fa fa-link"></i> CMDB Binding & Penutupan Tiket
                    </h4>
                </div>

                <div class="modal-body" style="padding: 25px 20px;">
                    <div style="background: #F4F0F8; border-left: 4px solid #8E44AD; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                        <strong style="color: #6C3483; font-size: 14px;">Validasi Tiket Eksternal (iRIS)</strong><br>
                        <span style="color: #555; font-size: 13px; display:inline-block; margin-top:4px;">Pastikan Anda telah meng-update status selesai pada tiket iRIS berikut sebelum mengunci CMDB:</span>
                        <div style="margin-top: 8px;">
                            <?php if (!empty($row->link_tiket)): ?>
                                <a href="<?= html_escape(
                                    $row->link_tiket,
                                ) ?>" target="_blank" class="btn btn-default btn-xs" style="font-weight:bold; color:#8E44AD; border-color:#8E44AD; background:#fff;">
                                    Buka Tiket: <?= html_escape(
                                        $row->no_tiket ?? "",
                                    ) ?> <i class="fa fa-external-link"></i>
                                </a>
                            <?php else: ?>
                                <span class="label label-default" style="font-size:12px;"><?= html_escape(
                                    $row->no_tiket ?? "",
                                ) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="background: #e6f7ff; border: 1px solid #bae6fd; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
                        <span style="font-size: 12px; color: #0369a1; display: block; margin-bottom: 5px;"><strong>Target VM yang harus dicari:</strong></span>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="val-mono" id="copy_vm_binding" style="color: #0284c7; font-size: 14px; background: transparent; border: none; padding: 0;"><?= html_escape(
                                $row->nama_server ?? "",
                            ) ?></span>
                            <button type="button" class="btn btn-info btn-xs" onclick="copyToClipboard('copy_vm_binding', this)" style="margin: 0; border-radius: 4px;"><i class="fa fa-copy"></i> Copy</button>
                        </div>
                    </div>

                    <!-- Input VM Select2 -->
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label class="font-bold text-primary">Tautkan ke Master VM (CMDB) <span class="text-danger">*</span></label>
                        <select class="form-control select2-ajax-vm" name="id_virtual_machine" id="select_cmdb_binding" style="width: 100%;" required>
                            <option value="">-- Ketik / Paste nama server untuk mencari... --</option>
                        </select>
                        <small class="text-muted" style="display:block; margin-top:5px; font-style:italic;"><i class="fa fa-info-circle"></i> Sistem akan melakukan verifikasi spesifikasi saat VM dipilih.</small>
                    </div>

                    <!-- CMDB Compare Table -->
                    <div id="cmdb_compare_wrapper" style="display: none; margin-bottom: 20px;">
                        <table class="compare-table">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Metrik</th>
                                    <th style="width: 30%;">Req Tiket</th>
                                    <th style="width: 30%;">Aktual CMDB</th>
                                    <th style="width: 20%;">Validasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>vCPU</strong></td>
                                    <td><?= html_escape($row->cpu ?? 0) ?> Core</td>
                                    <td id="cmdb_val_cpu">-</td>
                                    <td id="cmdb_st_cpu">-</td>
                                </tr>
                                <tr>
                                    <td><strong>RAM</strong></td>
                                    <td><?= html_escape($row->ram ?? 0) ?> GB</td>
                                    <td id="cmdb_val_ram">-</td>
                                    <td id="cmdb_st_ram">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Disk</strong></td>
                                    <td><?= html_escape($row->disk ?? 0) ?> GB</td>
                                    <td id="cmdb_val_disk">-</td>
                                    <td id="cmdb_st_disk">-</td>
                                </tr>
                            </tbody>
                        </table>
                        <div id="cmdb_match_indicator" style="margin-top: 10px; padding: 10px 12px; border-radius: 4px; font-size: 12.5px; font-weight: bold; line-height:1.4; display: none;"></div>
                    </div>

                    <div class="form-group" style="background-color: #fcf8e3; border: 1px solid #faebcc; padding: 15px; border-radius: 6px; margin-bottom:0;">
                        <label class="font-bold text-warning"><i class="fa fa-clock-o"></i> Waktu Tiket Diselesaikan (Closed)</label>
                        <div class="checkbox" style="margin-top: 5px; margin-bottom: 10px;">
                            <label style="font-weight: bold; color: #8a6d3b; font-size: 13px;">
                                <input type="checkbox" id="toggle_backdate_keluar" value="1"> Sesuaikan waktu manual (Backdate)
                            </label>
                        </div>
                        <input type="datetime-local" class="form-control font-bold" name="tanggal_keluar_tiket" id="input_tanggal_keluar_tiket" style="max-width: 250px; color: #2A3F54;" value="<?= date(
                            "Y-m-d\TH:i",
                        ) ?>" readonly required>
                    </div>
                </div>

                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #e5e5e5; padding: 12px 20px; border-radius: 0 0 8px 8px;">
                    <button type="button" class="btn btn-default btn-sm font-bold" data-dismiss="modal" style="border-radius: 4px;">Batal</button>
                    <!-- Tombol Disabled Sampai Sinkron -->
                    <button type="submit" id="btnSubmitBindCMDB" class="btn btn-primary btn-sm font-bold" style="border-radius: 4px; background-color:#8E44AD; border-color:#8E44AD;" disabled onclick="$(this).prop('disabled', true).html('<i class=\'fa fa-spinner fa-spin\'></i> Mengunci...'); $('#formBindingCMDB').submit();"><i class="fa fa-link"></i> Bind & Tutup Tiket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL UPDATE PROGRESS CLONE -->
<div class="modal fade" id="modalUpdateProgress" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 8px; border: none;">
            <form action="<?= site_url("provisioning/update_progress_clone") ?>" method="post">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="id_tiket" value="<?= $row->id_tiket ?? "" ?>">

                <div class="modal-header" style="background-color: #0EA5E9; color: white; padding: 16px 20px; border-radius: 8px 8px 0 0;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" style="font-weight: 700; font-size: 15px;"><i class="fa fa-refresh"></i> Update Progress Clone / Replikasi</h4>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <p class="text-muted" style="font-size: 12px; margin-bottom: 12px;">Informasikan sejauh mana persentase / status proses clone berjalan saat ini.</p>
                    <input type="text" name="status_clone_recover" class="form-control font-bold" placeholder="Misal: Sync vReps 60% (ETA 3 Jam lagi)" required style="border-radius: 4px; border: 1px solid #CBD5E1; padding: 20px 15px; color:#0369A1;">
                </div>
                <div class="modal-footer" style="background-color: #F8FAFC; border-radius: 0 0 8px 8px; padding: 12px 20px;">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal" style="border-radius: 4px;">Batal</button>
                    <button type="submit" class="btn btn-info btn-sm" style="border-radius: 4px; font-weight: 600;" onclick="$(this).prop('disabled', true).html('<i class=\'fa fa-spinner fa-spin\'></i> Menyimpan...'); $(this).closest('form').submit();">Simpan Progress</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL LAPOR KENDALA -->
<div class="modal fade" id="modalRoadblock" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 8px; border: none;">
            <form action="<?= site_url("provisioning/lapor_kendala") ?>" method="post">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="id_tiket" value="<?= $row->id_tiket ?? "" ?>">

                <div class="modal-header" style="background-color: #F59E0B; color: white; padding: 16px 20px; border-radius: 8px 8px 0 0;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" style="font-weight: 700; font-size: 15px;"><i class="fa fa-warning"></i> Lapor Kendala Operasional</h4>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <p class="text-muted" style="font-size: 12px; margin-bottom: 12px;">Catatan kendala akan ditambahkan ke histori tanpa menghapus pesan sebelumnya.</p>
                    <textarea name="kendala_text" class="form-control" rows="5" placeholder="Contoh: Datastore penuh, instalasi gagal karena file ISO corrupt..." required style="border-radius: 4px; border: 1px solid #CBD5E1;"></textarea>
                </div>
                <div class="modal-footer" style="background-color: #F8FAFC; border-radius: 0 0 8px 8px; padding: 12px 20px;">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal" style="border-radius: 4px;">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm" style="border-radius: 4px; font-weight: 600;" onclick="$(this).prop('disabled', true).html('<i class=\'fa fa-spinner fa-spin\'></i> Menyimpan...'); $(this).closest('form').submit();">Simpan Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL HAPUS -->
<?php if ($can_verify_delete): ?>
<div class="modal fade" id="mdlDelDet" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-sm" style="margin-top:15%;">
        <div class="modal-content" style="border-radius: 6px;">
            <div class="modal-header" style="background:#d9534f; color:#fff; border-top-left-radius: 6px; border-top-right-radius: 6px; padding:10px 15px;">
                <h4 class="modal-title font-bold" style="font-size:14px;"><i class="fa fa-warning"></i> Hapus Permanen Tiket</h4>
            </div>
            <div class="modal-body text-center" style="padding:20px 15px;">
                <p style="margin:0; font-size:13px;">Yakin ingin menghapus tiket <b><?= html_escape(
                    $row->no_tiket ?? "",
                ) ?></b>?</p>
            </div>
            <div class="modal-footer" style="background:#f5f5f5; text-align: center; padding:10px;">
                <form action="<?= site_url("provisioning/delete_data") ?>" method="post">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <input type="hidden" name="id_tiket" value="<?= $row->id_tiket ?? "" ?>">
                    <button type="button" class="btn btn-default btn-sm font-bold" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm font-bold" onclick="$(this).prop('disabled', true).html('Menghapus...'); $(this).closest('form').submit();">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL MULAI EKSEKUSI -->
<div class="modal fade" id="mdlMulaiEksekusi" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md" style="margin-top: 10%;">
        <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <form id="formMulaiEksekusiReal" action="<?= site_url(
                "provisioning/action_state_change",
            ) ?>" method="post" style="margin: 0;">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="id_tiket" value="<?= $row->id_tiket ?? "" ?>">
                <input type="hidden" name="target_state" value="In Progress">

                <div class="modal-header" style="background-color: #2980b9; padding: 15px 20px; border-radius: 8px 8px 0 0; border-bottom: none;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" style="color: white; font-weight: 700; font-size: 16px; margin: 0;">
                        <i class="fa fa-play-circle"></i> Konfirmasi Mulai Eksekusi
                    </h4>
                </div>
                <div class="modal-body" style="padding: 25px 20px;">
                    <div style="background: #F0F8FF; border-left: 4px solid #3498db; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                        <strong style="color: #2c3e50; font-size: 14px;">Mulai Proses Provisioning?</strong><br>
                        <span style="color: #555; font-size: 13px;">Status tiket ini akan diubah menjadi <span class="label label-primary">In Progress</span>. Pastikan Anda bersiap mengeksekusi Blueprint ini di vCenter.</span>
                    </div>
                    <div style="background: #fafafa; padding: 12px; border-radius: 4px; border: 1px dashed #ddd; font-size: 13px; color: #555;">
                        <b>Target VM:</b> <span class="text-primary font-bold"><?= html_escape(
                            $row->nama_server ?? "",
                        ) ?></span><br>
                        <!-- [QA FIX] Menggunakan logika $is_fresh -->
                        <b>Skenario:</b> <?= $is_fresh ? "Fresh Install" : "Clone" ?>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #e5e5e5; padding: 12px 20px; border-radius: 0 0 8px 8px;">
                    <button type="button" class="btn btn-default btn-sm font-bold" data-dismiss="modal" style="border-radius: 4px;">Batal</button>
                    <button type="submit" id="btnSubmitMulai" class="btn btn-primary btn-sm font-bold" style="border-radius: 4px;" onclick="$(this).prop('disabled', true).html('<i class=\'fa fa-spinner fa-spin\'></i> Menyimpan...'); $('#formMulaiEksekusiReal').submit();"><i class="fa fa-paper-plane"></i> Ya, Mulai Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL SELESAI SETUP -->
<div class="modal fade" id="mdlSelesaiSetup" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md" style="margin-top: 10%;">
        <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background-color: #27ae60; padding: 15px 20px; border-radius: 8px 8px 0 0; border-bottom: none;">
                <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" style="color: white; font-weight: 700; font-size: 16px; margin: 0;">
                    <i class="fa fa-check-square-o"></i> Konfirmasi Selesai Setup Fisik
                </h4>
            </div>
            <div class="modal-body" style="padding: 25px 20px;">
                <div style="background: #F4FDF7; border-left: 4px solid #27ae60; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <strong style="color: #1e8449; font-size: 14px;">Deklarasi Kehadiran VM di vCenter</strong><br>
                    <span style="color: #555; font-size: 13px;">Dengan melanjutkan, Anda menyatakan bahwa proses (Deploy/Clone) telah rampung, IP diset, dan VM siap digunakan. VM resmi dianggap "Masuk vCenter".</span>
                </div>
                <div class="form-group" style="background-color: #fcf8e3; border: 1px solid #faebcc; padding: 15px; border-radius: 6px; margin-bottom:0;">
                    <label class="font-bold text-warning"><i class="fa fa-clock-o"></i> Waktu VM Selesai Di-deploy (Masuk vCenter)</label>
                    <div class="checkbox" style="margin-top: 5px; margin-bottom: 10px;">
                        <label style="font-weight: bold; color: #8a6d3b; font-size: 13px;">
                            <input type="checkbox" id="toggle_backdate_vcenter" value="1"> Sesuaikan waktu manual (Backdate)
                        </label>
                    </div>
                    <form id="formSelesaiSetupReal" action="<?= site_url(
                        "provisioning/action_state_change",
                    ) ?>" method="post" style="margin: 0;">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                        <input type="hidden" name="id_tiket" value="<?= $row->id_tiket ?? "" ?>">
                        <input type="hidden" name="target_state" value="Waiting Sync">
                        <input type="datetime-local" class="form-control font-bold" name="tanggal_masuk_vcenter" id="input_tanggal_masuk_vcenter" style="max-width: 250px; color: #2A3F54;" value="<?= date(
                            "Y-m-d\TH:i",
                        ) ?>" readonly>
                    </form>
                </div>
            </div>
            <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #e5e5e5; padding: 12px 20px; border-radius: 0 0 8px 8px;">
                <button type="button" class="btn btn-default btn-sm font-bold" data-dismiss="modal" style="border-radius: 4px;">Belum Selesai</button>
                <button type="button" id="btnSubmitSelesai" class="btn btn-success btn-sm font-bold" style="border-radius: 4px;" onclick="$(this).prop('disabled', true).html('<i class=\'fa fa-spinner fa-spin\'></i> Menyimpan...'); $('#formSelesaiSetupReal').submit();"><i class="fa fa-check"></i> Ya, Setup Selesai</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // [QA FIX] XSS SANITIZER UNTUK AJAX & JAVASCRIPT VARS
        function escapeHtml(unsafe) {
            return (unsafe || '').toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // SWEETALERT BFCACHE-SAFE DOM EXTRACTION
        var $flashElem = $('#swal-flash-data');
        if ($flashElem.length > 0) {
            var swalType = $flashElem.data('type');
            var swalMessage = $flashElem.data('message');

            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: swalType, title: swalType === 'error' ? 'Gagal' : 'Informasi', text: swalMessage, timer: 3500, showConfirmButton: false });
            }
            $flashElem.remove();
        }

        // INIT SELECT2 CMDB
        $('.select2-ajax-vm').select2({
            dropdownParent: $('#mdlBindingCMDB'),
            placeholder: '-- Ketik / Paste nama server untuk mencari... --',
            minimumInputLength: 2,
            ajax: {
                url: "<?= site_url("provisioning/search_vm") ?>",
                dataType: 'json', delay: 250, type: "POST",
                data: function(params) {
                    return { keyword: params.term, "<?= $this->security->get_csrf_token_name() ?>": "<?= $this->security->get_csrf_hash() ?>" };
                },
                processResults: function(data) {
                    var results = [];
                    $.each(data, function(index, item) {
                        results.push({
                            id: item.id_virtual_machine,
                            text: escapeHtml(item.virtual_machine_name),
                            cpu_count: item.cpu_count,
                            memory_mb: item.memory_mb,
                            provisioned_gb: item.provisioned_gb
                        });
                    });
                    return { results: results };
                }, cache: true
            }
        });

        // Live Verification CMDB (Poka-Yoke)
        var targetVmName = "<?= strtolower(trim($row->nama_server ?? "")) ?>";
        var reqCpu = parseInt("<?= $row->cpu ?? 0 ?>");
        var reqRam = parseInt("<?= $row->ram ?? 0 ?>");
        var reqDisk = parseInt("<?= $row->disk ?? 0 ?>");

        $('.select2-ajax-vm').on('select2:select', function (e) {
            var cmdb = e.params.data;
            var selectedVmName = cmdb.text.toLowerCase().trim();
            var indicator = $('#cmdb_match_indicator');
            var btnSubmit = $('#btnSubmitBindCMDB');
            var compareWrap = $('#cmdb_compare_wrapper');

            var cmdbCpu = parseInt(cmdb.cpu_count || 0);
            var cmdbRamGB = Math.round(parseInt(cmdb.memory_mb || 0) / 1024);
            var cmdbDiskGB = Math.round(parseFloat(cmdb.provisioned_gb || 0));

            compareWrap.slideDown(200);
            indicator.slideDown(200);

            $('#cmdb_val_cpu').text(cmdbCpu + ' Core');
            $('#cmdb_val_ram').text(cmdbRamGB + ' GB');
            $('#cmdb_val_disk').text(cmdbDiskGB + ' GB');

            var isMismatch = false;
            var mismatchMsg = "";

            var badgeMatch = "<span class='label label-success'><i class='fa fa-check'></i> Match</span>";
            var badgeFail = "<span class='label label-danger'><i class='fa fa-times'></i> Mismatch</span>";

            if(reqCpu !== cmdbCpu) { isMismatch = true; $('#cmdb_st_cpu').html(badgeFail); mismatchMsg += "- vCPU (Req: "+reqCpu+", CMDB: "+cmdbCpu+")\n"; } else { $('#cmdb_st_cpu').html(badgeMatch); }
            if(reqRam !== cmdbRamGB) { isMismatch = true; $('#cmdb_st_ram').html(badgeFail); mismatchMsg += "- RAM (Req: "+reqRam+"GB, CMDB: "+cmdbRamGB+"GB)\n"; } else { $('#cmdb_st_ram').html(badgeMatch); }
            if(reqDisk !== cmdbDiskGB) { isMismatch = true; $('#cmdb_st_disk').html(badgeFail); mismatchMsg += "- Disk (Req: "+reqDisk+"GB, CMDB: "+cmdbDiskGB+"GB)\n"; } else { $('#cmdb_st_disk').html(badgeMatch); }

            $('#mismatch_log_input').val(mismatchMsg);

            if (selectedVmName === targetVmName) {
                if(isMismatch) {
                    indicator.html('<i class="fa fa-warning" style="font-size:14px; vertical-align:middle; margin-right:4px;"></i> <span style="vertical-align:middle;">WARNING: Nama VM sesuai, tetapi ada perbedaan SPESIFIKASI. Tiket tetap bisa ditutup, dan log selisih akan dicatat otomatis.</span>');
                    indicator.css({'background-color': '#FFFBEB', 'color': '#D97706', 'border': '1px solid #F59E0B'});
                } else {
                    indicator.html('<i class="fa fa-check-circle" style="font-size:14px; vertical-align:middle; margin-right:4px;"></i> <span style="vertical-align:middle;">VERIFIED: Nama dan Spesifikasi VM sesuai sempurna (100% Match).</span>');
                    indicator.css({'background-color': '#ECFDF5', 'color': '#047857', 'border': '1px solid #10B981'});
                }
                btnSubmit.prop('disabled', false).removeClass('btn-default').addClass('btn-primary').css({'background-color':'#8E44AD', 'border-color':'#8E44AD'});
            } else {
                indicator.html('<i class="fa fa-times-circle" style="font-size:14px; vertical-align:middle; margin-right:4px;"></i> <span style="vertical-align:middle;">FATAL ERROR: Target salah! Nama yang dipilih: <b>' + escapeHtml(cmdb.text) + '</b></span>');
                indicator.css({'background-color': '#FEF2F2', 'color': '#B91C1C', 'border': '1px solid #EF4444'});
                btnSubmit.prop('disabled', true).removeClass('btn-primary').addClass('btn-default').css({'background-color':'#eee', 'border-color':'#ccc'});
            }
        });

        $('.select2-ajax-vm').on('select2:unselect', function() {
            $('#cmdb_compare_wrapper').slideUp(200);
            $('#cmdb_match_indicator').slideUp(200);
            $('#mismatch_log_input').val('');
            $('#btnSubmitBindCMDB').prop('disabled', true).removeClass('btn-primary').addClass('btn-default').css({'background-color':'#eee', 'border-color':'#ccc'});
        });

        $(document).on('click', '.btn-locked', function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.addClass('animated shake');
            setTimeout(function() { $btn.removeClass('animated shake'); }, 800);

            if ($('#custom-floating-toast').length === 0) {
                $('body').append('<div id="custom-floating-toast" style="position: fixed; top: 80px; right: 20px; z-index: 9999; background: #fff; border-left: 4px solid #f39c12; border-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); padding: 15px 20px; display: none; color: #333;"></div>');
            }
            $('#custom-floating-toast').html("<div style='line-height:1.3;'><strong style='color: #d35400;'><i class='fa fa-lock'></i> Akses Terkunci</strong><br><span style='font-size:12px; color:#555;'>Tiket tidak dapat diedit karena telah selesai (Closed).</span></div>").stop(true, true).fadeIn(300).delay(4000).fadeOut(400);
        });

        function getWibISOString() {
            var date = new Date();
            var wibOffset = 7 * 60;
            var localOffset = date.getTimezoneOffset();
            date.setMinutes(date.getMinutes() + localOffset + wibOffset);
            return date.toISOString().slice(0, 16);
        }

        $('#toggle_backdate_vcenter').on('change', function() {
            if ($(this).is(':checked')) {
                $('#input_tanggal_masuk_vcenter').prop('readonly', false).css('background-color', '#fff');
            } else {
                $('#input_tanggal_masuk_vcenter').prop('readonly', true).css('background-color', '#eee');
                $('#input_tanggal_masuk_vcenter').val(getWibISOString());
            }
        });

        $('#toggle_backdate_keluar').on('change', function() {
            if ($(this).is(':checked')) {
                $('#input_tanggal_keluar_tiket').prop('readonly', false).css('background-color', '#fff');
            } else {
                $('#input_tanggal_keluar_tiket').prop('readonly', true).css('background-color', '#eee');
                $('#input_tanggal_keluar_tiket').val(getWibISOString());
            }
        });
    });

    // MODERN CLIPBOARD API
    function copyToClipboard(elementId, btnElement) {
        var textToCopy = document.getElementById(elementId).textContent.trim();
        var triggerCopySuccess = function() {
            var originalHTML = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fa fa-check text-success"></i>';
            setTimeout(function() { btnElement.innerHTML = originalHTML; }, 1200);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: 'Disalin: ' + textToCopy,
                    showConfirmButton: false, timer: 1500
                });
            }
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(triggerCopySuccess);
        } else {
            var tempInput = document.createElement("textarea");
            tempInput.value = textToCopy;
            tempInput.style.position = "absolute";
            tempInput.style.left = "-9999px";
            tempInput.style.top = "0";
            document.body.appendChild(tempInput);
            tempInput.focus();
            tempInput.select();
            try { document.execCommand("copy"); triggerCopySuccess(); } catch (err) { }
            document.body.removeChild(tempInput);
        }
    }
</script>
