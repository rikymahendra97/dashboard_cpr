<?php
/**
 * ============================================================================
 * File Name    : detail_vm_change.php
 * Modul        : VM Change Resource
 * Purpose      : Halaman Pusat Eksekusi & Blueprint VM (Read-Only + RBAC Actions)
 * Architecture : Clean UI/UX, BFCache-Safe, XSS Guarded, Enterprise Data Sync
 * ============================================================================
 */

// ========================================================================
// Intelephense Linter Guard & Defensive Programming
// ========================================================================
$id = $id ?? [];
$user_session = $user_session ?? [];
$detail = $detail ?? [];
$disks = $disks ?? [];

// INITIALIZE RBAC
$role = isset($id["id_role"]) ? (int) $id["id_role"] : 99;
$can_edit_execute = can_edit_execute($role);
$can_verify_delete = can_verify_delete($role);

$s_eks = trim($detail["status_eksekusi"] ?? "");
$is_closed = $s_eks === "Selesai Verified" || $s_eks === "Cancel by User";
$is_waiting_exec = $s_eks === "Menunggu Eksekusi";
$is_waiting_verify = $s_eks === "Telah Dieksekusi";
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
</style>

<div class="right_col" role="main">
    <div class="">
        <div class="row">
            <div class="col-sm-12">

                <div style="margin-bottom: 15px;">
                    <a href="<?= site_url(
                        "vm_change_resource",
                    ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; font-weight: bold; color: #555;"><i class="fa fa-arrow-left"></i> Kembali ke Daftar Log</a>
                </div>

                <!-- SWEETALERT BFCACHE-SAFE DATA INJECTION -->
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

                <section class="panel" style="box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 8px;">

                    <header class="panel-heading" style="background-color: #f5f7fa; padding: 18px 20px; border-bottom: 1px solid #e6e9ed; border-top-left-radius: 8px; border-top-right-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-weight: bold; color: #2A3F54; font-size: 18px;">
                            <i class="fa fa-info-circle"></i> Detail Request Perubahan Resource
                        </h3>

                        <div style="display: flex; gap: 10px;">
                            <?php if ($is_closed): ?>
                                <button type="button" class="btn btn-default btn-sm btn-locked" style="margin: 0; font-weight: bold; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: #999; background-color: #eee; border-color: #ddd;">
                                    <i class="fa fa-lock"></i> Duplikat Request
                                </button>
                            <?php else: ?>
                                <a href="<?= site_url(
                                    "vm_change_resource/tambah?duplicate_from=" .
                                        ($detail["id_change"] ?? ""),
                                ) ?>" class="btn btn-primary btn-sm" style="margin: 0; font-weight: bold; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background-color: #2A3F54; border-color: #2A3F54; color: white;">
                                    <i class="fa fa-copy"></i> Duplikat Request
                                </a>
                            <?php endif; ?>

                            <?php if ($can_edit_execute): ?>
                                <?php if ($is_closed): ?>
                                    <button type="button" class="btn btn-default btn-sm btn-locked" style="margin: 0; font-weight: bold; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: #999; background-color: #eee; border-color: #ddd;">
                                        <i class="fa fa-lock"></i> Edit Request
                                    </button>
                                <?php else: ?>
                                    <a href="<?= site_url(
                                        "vm_change_resource/edit/" . ($detail["id_change"] ?? ""),
                                    ) ?>" class="btn btn-default btn-sm" style="margin: 0; font-weight: bold; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: #5A738E; background-color: #F8F9FA; border-color: #E2E2E4;">
                                        <i class="fa fa-edit"></i> Edit Request
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($can_verify_delete): ?>
                                <button type="button" id="btn-show-delete-log" class="btn btn-danger btn-sm" style="margin: 0; font-weight: bold; border-radius: 4px; box-shadow: 0 2px 4px rgba(217,83,79,0.2);">
                                    <i class="fa fa-trash"></i> Hapus Log
                                </button>
                            <?php endif; ?>
                        </div>
                    </header>

                    <div class="panel-body" style="padding: 30px;">

                        <div class="row" style="display: flex; flex-wrap: wrap;">
                            <!-- KOLOM KIRI (Administratif) -->
                            <div class="col-md-6 col-sm-12" style="display: flex; flex-direction: column;">
                                <table class="table table-bordered" style="background: #fff; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); flex: 1;">
                                    <tbody>
                                        <tr>
                                            <th style="width: 35%; background:#f9f9f9; vertical-align: middle;">No Tiket</th>
                                            <td style="vertical-align: middle;">
                                                <?php if (
                                                    !empty($detail["link_tiket_eksternal"])
                                                ): ?>
                                                    <a href="<?= html_escape(
                                                        $detail["link_tiket_eksternal"],
                                                    ) ?>" target="_blank" class="text-primary font-bold"><u><?= html_escape(
    $detail["no_tiket_eksternal"] ?? "",
) ?></u> <i class="fa fa-external-link"></i></a>
                                                <?php else: ?>
                                                    <strong><?= html_escape(
                                                        $detail["no_tiket_eksternal"] ?? "",
                                                    ) ?></strong>
                                                <?php endif; ?>
                                                <i class="fa fa-copy inline-copy-trigger" data-text="<?= html_escape(
                                                    $detail["no_tiket_eksternal"] ?? "",
                                                ) ?>" title="Salin No Tiket" style="color:#cbd5e1; cursor:pointer; font-size:13px; margin-left:8px;"></i>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th style="background:#f9f9f9; vertical-align: middle;">VM Target</th>
                                            <td style="vertical-align: middle;">
                                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                                    <div>
                                                        <strong><?= html_escape(
                                                            $detail["snapshot_vm_name"] ?? "",
                                                        ) ?></strong>
                                                        <i class="fa fa-copy inline-copy-trigger" data-text="<?= html_escape(
                                                            $detail["snapshot_vm_name"] ?? "",
                                                        ) ?>" title="Salin Nama VM" style="color:#cbd5e1; cursor:pointer; font-size:12px; margin-left:3px;"></i>
                                                    </div>
                                                    <div>
                                                        <span class="text-muted"><?= html_escape(
                                                            $detail["snapshot_ip_address"] ?? "",
                                                        ) ?></span>
                                                        <i class="fa fa-copy inline-copy-trigger" data-text="<?= html_escape(
                                                            $detail["snapshot_ip_address"] ?? "",
                                                        ) ?>" title="Salin IP Address" style="color:#cbd5e1; cursor:pointer; font-size:11px; margin-left:3px;"></i>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th style="background-color: #f9f9f9; vertical-align: middle;">Jenis Skenario</th>
                                            <td style="vertical-align: middle;">
                                                <?php
                                                $jp = $detail["jenis_perubahan"] ?? "";
                                                if ($jp == "Upgrade") {
                                                    $sc_class =
                                                        "background-color: #27ae60; color: #fff; font-size: 11.5px; padding: 2px 8px; border-radius: 3px; display: inline-block; font-weight: bold;";
                                                    $sc_icon = "fa-arrow-up";
                                                } elseif ($jp == "Downgrade") {
                                                    $sc_class =
                                                        "background-color: #e67e22; color: #fff; font-size: 11.5px; padding: 2px 8px; border-radius: 3px; display: inline-block; font-weight: bold;";
                                                    $sc_icon = "fa-arrow-down";
                                                } else {
                                                    $sc_class =
                                                        "background-color: #2980b9; color: #fff; font-size: 11.5px; padding: 2px 8px; border-radius: 3px; display: inline-block; font-weight: bold;";
                                                    $sc_icon = "fa-exchange";
                                                }
                                                ?>
                                                <span style="<?= $sc_class ?>"><i class="fa <?= $sc_icon ?>"></i> <?= html_escape(
    $jp,
) ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th style="background:#f9f9f9; vertical-align: middle;">Status Eksekusi</th>
                                            <td style="vertical-align: middle;">
                                                <?php
                                                $s = $detail["status_eksekusi"] ?? "";
                                                if ($s == "Menunggu Eksekusi") {
                                                    $c = "bg-red";
                                                } elseif ($s == "Telah Dieksekusi") {
                                                    $c = "bg-blue";
                                                } elseif ($s == "Selesai Verified") {
                                                    $c = "bg-green";
                                                } elseif ($s == "Cancel by User") {
                                                    $c = "bg-orange";
                                                } else {
                                                    $c = "bg-black";
                                                }

                                                $s_label =
                                                    $s == "Telah Dieksekusi"
                                                        ? "Menunggu Verifikasi"
                                                        : $s;
                                                echo "<span class='badge {$c}' style='font-size:11.5px; padding: 4px 8px;'>{$s_label}</span>";
                                                ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- KOLOM KANAN (Organisasi & Info Tambahan) -->
                            <div class="col-md-6 col-sm-12" style="display: flex; flex-direction: column;">
                                <table class="table table-bordered" style="background: #fff; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); flex: 1;">
                                    <tbody>
                                        <tr>
                                            <th style="width: 35%; background:#f9f9f9; vertical-align: middle;">Fungsi Requestor</th>
                                            <td style="vertical-align: middle; color:#2c3e50; font-weight: bold;">
                                                <?php if (!empty($detail["team_name"])): ?>
                                                    <strong class="text-success"><i class="fa fa-users"></i> [<?= html_escape(
                                                        $detail["team_code"] ?? "",
                                                    ) ?>] <?= html_escape(
    $detail["team_name"],
) ?></strong>
                                                <?php else: ?>
                                                    <span class="text-muted font-normal" style="font-style:italic;">Tidak Diketahui / Mandiri</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th style="background-color: #f9f9f9; vertical-align: middle;">PIC & Kontak Tim</th>
                                            <td style="vertical-align: middle;">
                                                <?php if (
                                                    !empty($detail["pic_name"]) &&
                                                    $detail["pic_name"] !== "-"
                                                ): ?>
                                                    <strong class="text-primary"><i class="fa fa-user"></i> <?= html_escape(
                                                        $detail["pic_name"],
                                                    ) ?></strong><br>
                                                    <small class="text-muted" style="display: inline-block; margin-top: 2px;">
                                                        <i class="fa fa-phone"></i> <?= html_escape(
                                                            $detail["pic_contact"] ??
                                                                "Tidak Ada Kontak",
                                                        ) ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-style: italic; font-size:12px;">Tim Umum (Tanpa Spesifik PIC)</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th style="background:#f9f9f9; vertical-align: middle;">Pencatat (Maker)</th>
                                            <td style="vertical-align: middle;">
                                                <strong class="text-primary"><i class="fa fa-user"></i> <?= html_escape(
                                                    $detail["nama_pencatat"] ?? "",
                                                ) ?></strong><br>
                                                <small class="text-muted" style="display: inline-block; margin-top: 3px;">
                                                    <i class="fa fa-clock-o"></i> <?= !empty(
                                                        $detail["created_at"]
                                                    )
                                                        ? date(
                                                            "d-M-Y H:i",
                                                            strtotime($detail["created_at"]),
                                                        )
                                                        : "-" ?> WIB
                                                </small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th style="background:#f9f9f9; vertical-align: middle;">Tiket Insiden Terkait</th>
                                            <td style="vertical-align: middle;">
                                                <?php if (!empty($detail["id_incident"])): ?>
                                                    <span class="label label-danger" style="font-size: 10px; padding: 3px 6px;"><i class="fa fa-link"></i> Linked</span>
                                                    <?php if (
                                                        !empty($detail["no_tiket_insiden_terkait"])
                                                    ): ?>
                                                        <a href="<?= site_url(
                                                            "vm_incident/detail/" .
                                                                $detail["id_incident"],
                                                        ) ?>" target="_blank" class="text-danger" style="margin-left: 5px; font-size:12px; font-weight: bold; text-decoration: underline;" title="Buka RCA Insiden">
                                                            <?= html_escape(
                                                                $detail["no_tiket_insiden_terkait"],
                                                            ) ?> <i class="fa fa-external-link"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-style: italic; font-size: 12px;">Tiket Mandiri (Tidak Terikat)</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- DESKRIPSI REQUEST -->
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="width: 17%; background:#f9f9f9; vertical-align: top; padding-top: 12px;">Deskripsi Request</th>
                                        <td style="color: #444; font-size: 12.5px; line-height: 1.5; padding-top: 12px; background: #fafafa;">
                                            <?= nl2br(
                                                html_escape(
                                                    $detail["keterangan_request_asli"] ?? "-",
                                                ),
                                            ) ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- SPESIFIKASI KOMPARASI -->
                        <?php
                        $cpu_changed =
                            ($detail["target_cpu_count"] ?? 0) !=
                            ($detail["current_cpu_count"] ?? 0);
                        $ram_changed =
                            ($detail["target_memory_mb"] ?? 0) !=
                            ($detail["current_memory_mb"] ?? 0);

                        $curr_ram_gb = round(($detail["current_memory_mb"] ?? 0) / 1024);
                        $tgt_ram_gb = round(($detail["target_memory_mb"] ?? 0) / 1024);
                        ?>
                        <h4 class="font-bold" style="color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-top: 40px;"><i class="fa fa-bar-chart"></i> Perbandingan Core & RAM</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered text-center" style="background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <thead>
                                    <tr class="active">
                                        <th class="text-center">Komponen</th>
                                        <th class="text-center">Current Base</th>
                                        <th class="text-center">Target / Aktual Baru</th>
                                        <th class="text-center">Delta Selisih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr <?= $cpu_changed
                                        ? 'style="background-color: #f4fdf7;"'
                                        : "" ?>>
                                        <td style="vertical-align: middle;"><strong>vCPU (Core)</strong></td>
                                        <td style="vertical-align: middle;"><?= html_escape(
                                            $detail["current_cpu_count"] ?? 0,
                                        ) ?> Core</td>
                                        <td style="vertical-align: middle;"><strong><?= html_escape(
                                            $detail["target_cpu_count"] ?? 0,
                                        ) ?> Core</strong></td>
                                        <td style="vertical-align: middle;">
                                            <?php
                                            $dc =
                                                ($detail["target_cpu_count"] ?? 0) -
                                                ($detail["current_cpu_count"] ?? 0);
                                            if ($dc > 0) {
                                                echo "<span class='label label-success'>+{$dc} Core (Upgrade)</span>";
                                            } elseif ($dc < 0) {
                                                echo "<span class='label label-danger'>{$dc} Core (Downgrade)</span>";
                                            } else {
                                                echo "<span class='text-muted font-italic'>Tetap (Tidak Ada Perubahan)</span>";
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr <?= $ram_changed
                                        ? 'style="background-color: #f4fdf7;"'
                                        : "" ?>>
                                        <td style="vertical-align: middle;"><strong>RAM (Memory)</strong></td>
                                        <td style="vertical-align: middle;"><?= $curr_ram_gb ?> GB</td>
                                        <td style="vertical-align: middle;"><strong><?= $tgt_ram_gb ?> GB</strong></td>
                                        <td style="vertical-align: middle;">
                                            <?php
                                            $dr = $tgt_ram_gb - $curr_ram_gb;
                                            if ($dr > 0) {
                                                echo "<span class='label label-success'>+{$dr} GB (Upgrade)</span>";
                                            } elseif ($dr < 0) {
                                                echo "<span class='label label-danger'>{$dr} GB (Downgrade)</span>";
                                            } else {
                                                echo "<span class='text-muted font-italic'>Tetap (Tidak Ada Perubahan)</span>";
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h4 class="font-bold" style="color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-top: 30px;"><i class="fa fa-hdd-o"></i> Tinjauan Target Partisi Disk</h4>
                        <div class="row" id="panel_disk">
                            <div class="col-md-12">
                                <?php if (!empty($disks)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered text-center" style="background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                            <thead>
                                                <tr class="active">
                                                    <th class="text-center">No</th>
                                                    <th class="text-center">Tipe Eksekusi</th>
                                                    <th class="text-center">Drive / Partisi</th>
                                                    <th class="text-center">Delta Selisih</th>
                                                    <th class="text-center">Kapasitas Akhir</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $dn = 1;
                                                foreach ($disks as $d):

                                                    $delta_disk =
                                                        (float) ($d["additional_gb"] ?? 0);
                                                    $disk_bg =
                                                        $delta_disk > 0
                                                            ? "#f4fdf7"
                                                            : ($delta_disk < 0
                                                                ? "#fdf4f4"
                                                                : "");
                                                    ?>
                                                <tr style="background-color: <?= $disk_bg ?>;">
                                                    <td style="vertical-align: middle;"><?= $dn++ ?></td>
                                                    <td style="vertical-align: middle;"><?= html_escape(
                                                        $d["tipe_eksekusi"] ?? "",
                                                    ) ?></td>
                                                    <td style="vertical-align: middle;"><strong><?= html_escape(
                                                        $d["nama_drive"] ?? "",
                                                    ) ?></strong></td>
                                                    <td style="vertical-align: middle;">
                                                        <?php if ($delta_disk > 0): ?>
                                                            <span class='label label-success'>+<?= html_escape(
                                                                $delta_disk,
                                                            ) ?> GB (Expand)</span>
                                                        <?php elseif ($delta_disk < 0): ?>
                                                            <span class='label label-danger'><?= html_escape(
                                                                $delta_disk,
                                                            ) ?> GB (Reduce)</span>
                                                        <?php else: ?>
                                                            <span class='text-muted font-italic'>0 GB</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="vertical-align: middle;"><strong><?= html_escape(
                                                        $d["end_state_gb"] ?? "",
                                                    ) ?> GB</strong></td>
                                                </tr>
                                                <?php
                                                endforeach;
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info" style="background:#f4f8fa; color:#5bc0de; border-color:#bce8f1;"><i class="fa fa-info-circle"></i> Tidak ada perubahan storage pada tiket ini.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- REKAM JEJAK PELAKSANAAN -->
                        <?php if (!$is_waiting_exec): ?>
                            <h4 class="font-bold" style="color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-top: 30px;"><i class="fa fa-history"></i> Rekam Jejak Pelaksanaan</h4>
                            <table class="table table-bordered" style="background-color: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <tr>
                                    <th style="background:#fcfdf9; width:20%; color:#3c763d; vertical-align:top;"><i class="fa fa-wrench"></i> Eksekusi Pekerjaan</th>
                                    <td>
                                        <div style="font-size: 13px; color: #555; margin-bottom: 8px; display: flex; flex-direction: column; gap: 4px;">
                                            <div><i class="fa fa-user text-primary" style="width: 16px; text-align: center;"></i> Implementer: <strong class="text-primary"><?= !empty(
                                                $detail["nama_executor"]
                                            )
                                                ? html_escape($detail["nama_executor"])
                                                : "-" ?></strong></div>
                                            <div><i class="fa fa-clock-o text-muted" style="width: 16px; text-align: center;"></i> Waktu Eksekusi: <strong><?= !empty(
                                                $detail["tanggal_eksekusi"]
                                            )
                                                ? date(
                                                    "d-M-Y H:i",
                                                    strtotime($detail["tanggal_eksekusi"]),
                                                )
                                                : "-" ?> WIB</strong></div>
                                        </div>
                                        <div style="font-style:italic; color:#333; font-size: 13px; padding: 10px; border-left: 3px solid #d6e9c6; background: #f9fdf5; border-radius: 2px;">
                                            <!-- [QA FIX] escapeHtml applied to user input textarea -->
                                            <?= !empty($detail["catatan_eksekusi"])
                                                ? nl2br(html_escape($detail["catatan_eksekusi"]))
                                                : '<span class="text-muted">Belum ada catatan operasional...</span>' ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php if ($detail["status_eksekusi"] == "Selesai Verified"): ?>
                                    <tr>
                                        <th style="background:#dff0d8; width:20%; color:#3c763d; vertical-align:top;"><i class="fa fa-shield"></i> Verifikasi Akhir</th>
                                        <td>
                                            <div style="font-size: 13px; color: #555; margin-bottom: 8px; display: flex; flex-direction: column; gap: 4px;">
                                                <div><i class="fa fa-user text-success" style="width: 16px; text-align: center;"></i> Verifikator: <strong class="text-success"><?= !empty(
                                                    $detail["nama_verifikator"]
                                                )
                                                    ? html_escape($detail["nama_verifikator"])
                                                    : "-" ?></strong></div>
                                                <div><i class="fa fa-clock-o text-muted" style="width: 16px; text-align: center;"></i> Waktu Selesai: <strong><?= !empty(
                                                    $detail["tanggal_verifikasi"]
                                                )
                                                    ? date(
                                                        "d-M-Y H:i",
                                                        strtotime($detail["tanggal_verifikasi"]),
                                                    )
                                                    : "-" ?> WIB</strong></div>
                                            </div>
                                            <div style="font-style:italic; color:#333; font-size: 13px; padding: 10px; border-left: 3px solid #c3e6cb; background: #f2f9f4; border-radius: 2px;">
                                                <!-- [QA FIX] -->
                                                <?= !empty($detail["catatan_verifikasi"])
                                                    ? nl2br(
                                                        html_escape($detail["catatan_verifikasi"]),
                                                    )
                                                    : '<span class="text-muted">Tanpa catatan tambahan...</span>' ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        <?php endif; ?>

                        <!-- [ACTION PANEL] IMPLEMENTER (EKSEKUSI) -->
                        <?php if ($is_waiting_exec && $can_edit_execute): ?>

                            <form id="formExecuteChange" novalidate>
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                <input type="hidden" name="id_change" value="<?= html_escape(
                                    $detail["id_change"] ?? "",
                                ) ?>">
                                <input type="hidden" name="action_type" id="input_action_type" value="execute">

                                <input type="hidden" name="target_cpu" value="<?= html_escape(
                                    $detail["target_cpu_count"] ?? 0,
                                ) ?>">
                                <input type="hidden" name="target_ram_gb" value="<?= html_escape(
                                    ($detail["target_memory_mb"] ?? 0) / 1024,
                                ) ?>">

                                <div style="margin-top: 30px; background: #fdfdfd; padding: 25px; border-radius: 6px; border-left: 5px solid #337ab7; border: 1px solid #e1e1e1;">
                                    <h4 class="font-bold text-primary"><i class="fa fa-wrench"></i> Panel Submit & Eksekusi</h4>

                                    <div class="form-group" style="background-color: #f8fafc; border-left: 4px solid #94a3b8; padding: 15px; margin-top: 25px; margin-bottom: 20px; border-radius: 4px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                                        <div class="checkbox" style="margin: 0;">
                                            <label style="font-weight: bold; color: #475569;">
                                                <input type="checkbox" id="toggle_backdate_eks" value="1">
                                                <i class="fa fa-sliders text-muted"></i> Opsi Lanjutan: Sesuaikan Waktu Eksekusi (Backdate)
                                            </label>
                                            <p style="font-size: 11px; color: #64748b; margin-top: 5px; padding-left: 20px; line-height: 1.4;">
                                                * Fitur administratif. Centang opsi ini hanya jika Anda sudah selesai melakukan eksekusi di masa lampau dan baru sempat melaporkannya ke sistem hari ini.
                                            </p>
                                        </div>
                                        <div id="backdate_container_eks" style="display: none; padding-left: 20px; margin-top: 15px; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
                                            <div class="row">
                                                <div class="col-md-6 col-sm-12">
                                                    <label class="font-bold" style="color: #334155;">Waktu Aktual Eksekusi / Pembatalan</label>
                                                    <input type="datetime-local" class="form-control" name="tanggal_eksekusi" id="input_tanggal_eksekusi" style="max-width: 250px; border-color: #cbd5e1; color: #1e293b; font-weight: 600;" disabled value="<?= date(
                                                        "Y-m-d\TH:i",
                                                    ) ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <textarea class="form-control" name="catatan_eksekusi" id="input_catatan_eksekusi" rows="3" placeholder="Wajib diisi: Laporan teknis pelaksanaan atau alasan pembatalan dari user..."></textarea>
                                    </div>

                                    <div class="text-right" style="margin-top: 15px; display: flex; justify-content: flex-end; gap: 10px;">
                                        <button type="button" class="btn btn-warning font-bold" id="btn-trigger-cancel" style="background-color: #d35400; border-color: #d35400; color: white;">
                                            <i class="fa fa-ban"></i> Batalkan Request (Cancel)
                                        </button>
                                        <button type="button" class="btn btn-primary font-bold" id="btn-trigger-eks" style="border-radius: 4px;">
                                            <i class="fa fa-check-square-o"></i> Simpan & Selesaikan Pekerjaan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>

                        <!-- [ACTION PANEL] VERIFIKATOR -->
                        <?php if ($is_waiting_verify && $can_verify_delete): ?>
                            <form id="formVerifyChange" novalidate>
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                <input type="hidden" name="id_change" value="<?= html_escape(
                                    $detail["id_change"] ?? "",
                                ) ?>">
                                <input type="hidden" name="action_type" value="verify">

                                <div style="margin-top: 40px; background: #fcf8e3; padding: 25px; border-radius: 6px; border-left: 5px solid #5cb85c; border: 1px solid #faebcc;">
                                    <h4 class="font-bold text-success"><i class="fa fa-shield"></i> Panel Verifikasi (Peer-Review)</h4>
                                    <small class="text-muted" style="display:block; margin-bottom:15px; font-style:italic;">* Pekerjaan telah diselesaikan oleh implementer. Lakukan validasi akhir, tiket akan ditutup setelah disetujui.</small>

                                    <textarea class="form-control" name="catatan_verifikasi" id="input_catatan_verifikasi" rows="3" placeholder="Opsional: Tambahkan catatan jika diperlukan, biarkan kosong jika langsung approve..."></textarea>
                                    <div class="text-right" style="margin-top: 15px;">
                                        <button type="button" class="btn btn-success font-bold" id="btn-trigger-ver"><i class="fa fa-check"></i> Setujui & Tutup Tiket</button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>

                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- MODAL KUMPULAN -->
<!-- ======================================================= -->

<!-- Modal Konfirmasi Universal -->
<div class="modal fade" id="mdlConfirm" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content" style="border-radius: 6px; box-shadow: 0 5px 15px rgba(0,0,0,0.25); border: none;">
            <div class="modal-header" id="mdlConfirmHeader" style="color: white; border-top-left-radius: 6px; border-top-right-radius: 6px; padding: 15px 20px;">
                <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title font-bold"><i class="fa fa-question-circle"></i> Konfirmasi Eksekusi Sistem</h4>
            </div>
            <div class="modal-body" style="padding: 25px 20px;">
                <div id="mdlConfirmText" style="font-size: 14px; color: #333; line-height: 1.6;"></div>
            </div>
            <div class="modal-footer" id="mdlConfirmFooter" style="background-color: #f9fbfd; border-top: 1px solid #edf2f7; padding: 12px 20px; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px; text-align: right;"></div>
        </div>
    </div>
</div>

<!-- Modal Hapus Jurnal -->
<?php if ($can_verify_delete): ?>
    <div class="modal fade" id="mdlDelDet" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-sm" style="margin-top:15%;">
            <div class="modal-content" style="border-radius: 6px;">
                <form action="<?= site_url("vm_change_resource/hapus") ?>" method="post">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <input type="hidden" name="id_change" value="<?= html_escape(
                        $detail["id_change"] ?? "",
                    ) ?>">

                    <div class="modal-header" style="background:#d9534f; color:#fff; border-top-left-radius: 6px; border-top-right-radius: 6px; padding:10px 15px;">
                        <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;"><span aria-hidden="true">×</span></button>
                        <h4 class="modal-title font-bold" style="font-size:14px;"><i class="fa fa-warning"></i> Hapus Jurnal Log</h4>
                    </div>
                    <div class="modal-body text-center" style="padding:20px 15px;">
                        <p style="margin:0; font-size:13px;">Yakin hapus tiket ini secara permanen?</p>
                    </div>
                    <div class="modal-footer" style="background:#f5f5f5; text-align: center; padding:10px;">
                        <button type="button" class="btn btn-default btn-sm font-bold" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-sm font-bold" onclick="$(this).prop('disabled', true).html('<i class=\'fa fa-spinner fa-spin\'></i> Menghapus...'); $(this).closest('form').submit();">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    var currentAction = '';
    $(document).ready(function() {

        // Anti-BFCache SweetAlert Leak
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
            if (window.history.replaceState) window.history.replaceState(null, null, window.location.href);
            $flashElem.remove();
        }

        $('#btn-show-delete-log').on('click', function(e) {
            e.preventDefault();
            $('#mdlDelDet').modal('show');
        });

        $('#toggle_backdate_eks').on('change', function() {
            if ($(this).is(':checked')) {
                $('#backdate_container_eks').slideDown();
                $('#input_tanggal_eksekusi').prop('disabled', false);
            } else {
                $('#backdate_container_eks').slideUp();
                $('#input_tanggal_eksekusi').prop('disabled', true);
            }
        });

        $(document).on('input change', '#input_catatan_eksekusi, #input_catatan_verifikasi', function() {
            $(this).css({ 'border': '', 'background-color': '' });
            $(this).siblings('.error-validation').remove();
        });

        $('#btn-trigger-eks').on('click', function(e) {
            e.preventDefault();
            $('#input_action_type').val('execute');
            validasiAksi('execute');
        });
        $('#btn-trigger-cancel').on('click', function(e) {
            e.preventDefault();
            $('#input_action_type').val('cancel');
            validasiAksi('cancel');
        });
        $('#btn-trigger-ver').on('click', function(e) {
            e.preventDefault();
            validasiAksi('verify');
        });

        // ========================================================================
        // VALIDATION ENGINE & LATE-BINDING CROSS-MODULE HOOK
        // ========================================================================
        function validasiAksi(tipe) {
            currentAction = tipe;
            var isEks = (tipe === 'execute');
            var isCancel = (tipe === 'cancel');

            var inputCatatanId = (isEks || isCancel) ? '#input_catatan_eksekusi' : '#input_catatan_verifikasi';
            var formId = (isEks || isCancel) ? '#formExecuteChange' : '#formVerifyChange';
            var isValid = true;

            if (isEks || isCancel) {
                var $catatan = $(inputCatatanId);
                if ($catatan.val().trim() === '') {
                    $catatan.css({ 'border': '1px solid #e74c3c', 'background-color': '#fadbd8' });
                    if ($catatan.siblings('.error-validation').length === 0) {
                        var errMsg = isCancel ? 'Wajib mengisi alasan pembatalan dari user!' : 'Keterangan teknis eksekusi wajib diisi!';
                        $catatan.after('<small class="text-danger font-bold error-validation" style="color:#e74c3c; display:block; margin-top:5px;"><i class="fa fa-warning"></i> ' + errMsg + '</small>');
                    }
                    isValid = false;
                }
                if (!isValid) return;

                if (isEks) {
                    var btnTrigger = $('#btn-trigger-eks');
                    var idVmTarget = <?= html_escape($detail["id_virtual_machine"] ?? 0) ?>;
                    var namaVmTarget = "<?= html_escape($detail["snapshot_vm_name"] ?? "") ?>";
                    var targetUrl = '<?= site_url(
                        "vm_incident/check_active_incident_json/",
                    ) ?>' + idVmTarget;

                    btnTrigger.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mendeteksi Insiden...');
                    $(formId).find('input[name="force_close_incident"]').remove();

                    $.ajax({
                        url: targetUrl,
                        type: 'GET',
                        dataType: 'json',
                        success: function(res) {
                            btnTrigger.prop('disabled', false).html('<i class="fa fa-check-square-o"></i> Simpan & Selesaikan Pekerjaan');
                            $('#mdlConfirmHeader').css('background-color', '#337ab7');

                            if (res.has_incident) {
                                var idInc = res.incident_data.id_incident;
                                var noTik = res.incident_data.no_tiket_insiden;
                                var typeInc = res.incident_data.tipe_insiden;
                                var metrikArray = typeInc.split(', ');
                                var listMetrikHtml = '<ul style="padding-left: 18px; margin-top: 6px; margin-bottom: 0; font-weight: bold; color: #c0392b; line-height: 1.6;">';
                                metrikArray.forEach(function(item) {
                                    listMetrikHtml += '<li><i class="fa fa-caret-right" style="margin-right:5px;"></i> ' + escapeHtml(item) + '</li>'; // [QA FIX]
                                });
                                listMetrikHtml += '</ul>';

                                var modHtml = '<div style="font-size: 13.5px; color: #333; line-height: 1.5; text-align: left;">' +
                                    '  <div style="text-align: center; margin-bottom: 15px;">Sistem mendeteksi adanya keterikatan tiket insiden aktif pada server berikut:</div>' +
                                    '  <table class="table table-condensed table-bordered" style="margin-top:10px; margin-bottom:15px; background:#fdfdfd; font-size: 13px;">' +
                                    '    <tr><th style="width:35%; background:#f5f5f5; vertical-align: middle;">Nama VM Target</th><td style="vertical-align: middle;"><strong class="text-primary"><i class="fa fa-server"></i> ' + escapeHtml(namaVmTarget) + '</strong></td></tr>' + // [QA FIX]
                                    '    <tr><th style="background:#f5f5f5; vertical-align: middle;">No Tiket Insiden</th><td style="vertical-align: middle;"><strong class="text-danger"><i class="fa fa-ticket"></i> ' + escapeHtml(noTik) + '</strong></td></tr>' + // [QA FIX]
                                    '  </table>' +
                                    '  <div style="background-color: #fffdf0; border-left: 4px solid #f0ad4e; padding: 12px; border-radius: 4px; margin-bottom: 20px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">' +
                                    '     <strong style="color: #b15b10; display: block; margin-bottom: 5px;"><i class="fa fa-warning"></i> Daftar Parameter Kritis (Unresolved Metrics):</strong>' +
                                    listMetrikHtml +
                                    '  </div>' +
                                    '  <div style="text-align: center; margin: 20px 0; font-size: 14.5px; font-weight: bold; color: #2c3e50; border-top: 1px dashed #ddd; border-bottom: 1px dashed #ddd; padding: 15px 10px; background:#fcfcfc; border-radius: 4px;">' +
                                    '     <i class="fa fa-question-circle text-primary" style="font-size: 18px; vertical-align: middle; margin-right: 5px;"></i> <span style="vertical-align: middle;">Pasca perubahan spesifikasi, apakah nilai utilisasi riil pada vCenter/Server sudah kembali normal?</span>' +
                                    '  </div>' +
                                    '  <div style="background: #f8f9fa; padding: 12px 15px; border-radius: 4px; border: 1px solid #edf2f7; font-size: 12.5px; line-height: 1.5; color: #555;">' +
                                    '     <strong style="color: #333;"><i class="fa fa-info-circle text-info"></i> Panduan Konfirmasi BFC:</strong>' +
                                    '     <ul style="margin-bottom: 0; padding-left: 20px; margin-top: 6px;">' +
                                    '       <li>Pilih <b style="color:#d35400;">"BELUM"</b> jika beban (load) server masih tinggi. Tiket insiden akan tetap <b style="color:#d35400;">OPEN</b>.</li>' +
                                    '       <li>Pilih <b style="color:#27ae60;">"YA"</b> jika beban server sudah stabil. Tiket insiden akan otomatis diselesaikan (<b style="color:#27ae60;">CLOSE</b>).</li>' +
                                    '     </ul>' +
                                    '  </div>' +
                                    '</div>';

                                $('#mdlConfirmText').html(modHtml);

                                var footerButtons = '<button type="button" class="btn btn-default btn-sm font-bold btn-batal-modal" data-dismiss="modal" style="float: left; padding: 6px 14px; border-radius:4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">Batal</button>' +
                                    '<button type="button" class="btn btn-warning btn-sm font-bold" id="btn-confirm-keep-open" style="padding: 6px 14px; border-radius:4px; background-color: #d35400; border-color: #d35400; color: white; margin-right: 8px;"><i class="fa fa-envelope-open-o"></i> BELUM (Tetap OPEN)</button>' +
                                    '<button type="button" class="btn btn-success btn-sm font-bold" id="btn-confirm-close-ticket" style="padding: 6px 14px; border-radius:4px; background-color: #27ae60; border-color: #27ae60; color: white;"><i class="fa fa-check-circle"></i> YA, Sudah Normal (CLOSE)</button>';
                                $('#mdlConfirmFooter').html(footerButtons);

                                $('.btn-batal-modal').on('click', function() { $(formId).find('input[name="force_close_incident"]').remove(); });

                                $('#btn-confirm-keep-open').off('click').on('click', function() {
                                    $(formId).find('input[name="force_close_incident"]').remove();
                                    if ($(formId).find('input[name="resolve_incident_id"]').length === 0) {
                                        $(formId).append('<input type="hidden" name="resolve_incident_id" value="' + idInc + '">');
                                    }
                                    executeSubmitForm(formId, $(this));
                                });

                                $('#btn-confirm-close-ticket').off('click').on('click', function() {
                                    if ($(formId).find('input[name="force_close_incident"]').length === 0) {
                                        $(formId).append('<input type="hidden" name="force_close_incident" value="1">');
                                    }
                                    if ($(formId).find('input[name="resolve_incident_id"]').length === 0) {
                                        $(formId).append('<input type="hidden" name="resolve_incident_id" value="' + idInc + '">');
                                    }
                                    executeSubmitForm(formId, $(this));
                                });

                            } else {
                                $('#mdlConfirmHeader').css('background-color', '#337ab7');
                                var normalHtml = '<div style="font-size:14.5px; color:#333; line-height:1.5;">' +
                                    'Simpan aktual resource & laporkan eksekusi sebagai selesai pada server <strong class="text-primary">' + escapeHtml(namaVmTarget) + '</strong>?' + // [QA FIX]
                                    '<div style="margin-top: 15px; background-color: #f4f8fa; border-left: 4px solid #337ab7; padding: 12px 15px; font-size: 13px; text-align: left; border-radius: 3px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">' +
                                    '<i class="fa fa-info-circle text-primary" style="margin-right: 5px; font-size: 15px; vertical-align: middle;"></i> <strong style="vertical-align: middle; color:#2c3e50;">Peringatan Operasional:</strong><br>' +
                                    '<span style="display: block; margin-top: 6px; color:#555;">Pastikan <b>semua spesifikasi aktual (vCPU/RAM/Disk) sudah benar-benar diubah secara fisik di vCenter / OS</b> sebelum Anda menyimpan log eksekusi ini.</span>' +
                                    '</div></div>';
                                $('#mdlConfirmText').html(normalHtml);
                                var normalFooter = '<button type="button" class="btn btn-default btn-sm font-bold btn-batal-modal" data-dismiss="modal" style="padding: 6px 14px; border-radius:4px;">Kembali</button>' +
                                    '<button type="button" class="btn btn-primary btn-sm font-bold" id="btn-confirm-normal-submit" style="padding: 6px 14px; border-radius:4px;">Ya, Selesaikan Pekerjaan</button>';
                                $('#mdlConfirmFooter').html(normalFooter);

                                $('.btn-batal-modal').on('click', function() { $(formId).find('input[name="force_close_incident"]').remove(); });
                                $('#btn-confirm-normal-submit').off('click').on('click', function() { executeSubmitForm(formId, $(this)); });
                            }
                            $('#mdlConfirm').modal('show');
                        },
                        error: function() {
                            alert('Gagal memeriksa status insiden lintas modul. Periksa koneksi jaringan.');
                            btnTrigger.prop('disabled', false).html('<i class="fa fa-check-square-o"></i> Simpan & Selesaikan Pekerjaan');
                        }
                    });
                    return;
                } else {
                    $('#mdlConfirmHeader').css('background-color', '#d35400');
                    var cancelHtml = '<div style="font-size:14.5px; color:#333; line-height:1.5;">Apakah Anda yakin ingin <b>Membatalkan</b> tiket request ini?' +
                        '<div style="margin-top: 15px; background-color: #fffdf2; border-left: 4px solid #d35400; padding: 12px 15px; font-size: 13px; text-align: left; border-radius: 3px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">' +
                        '<i class="fa fa-exclamation-triangle" style="color: #d35400; margin-right: 5px; font-size: 15px; vertical-align: middle;"></i> <strong style="vertical-align: middle; color:#b15b10;">Tindakan Permanen (Irreversibel):</strong><br>' +
                        '<span style="display: block; margin-top: 6px; color:#555;">Tiket yang telah dibatalkan akan <b>dikunci secara permanen</b> demi audit log, dan proses eksekusi tidak dapat dilanjutkan kembali.</span>' +
                        '</div></div>';
                    $('#mdlConfirmText').html(cancelHtml);
                    var cancelFooter = '<button type="button" class="btn btn-default btn-sm font-bold" data-dismiss="modal" style="padding: 6px 14px; border-radius:4px;">Kembali</button>' +
                        '<button type="button" class="btn btn-warning btn-sm font-bold" id="btn-confirm-cancel-submit" style="background-color: #d35400; border-color: #d35400; color: white; padding: 6px 14px; border-radius:4px;">Ya, Batalkan Request</button>';
                    $('#mdlConfirmFooter').html(cancelFooter);

                    $('#btn-confirm-cancel-submit').off('click').on('click', function() { executeSubmitForm(formId, $(this)); });
                    $('#mdlConfirm').modal('show');
                }
            } else {
                $('#mdlConfirmHeader').css('background-color', '#5cb85c');
                $('#mdlConfirmText').html('<div style="font-size:14.5px; color:#333; line-height:1.5;">Setujui hasil rekam jejak pekerjaan implementer dan <b>kunci tiket secara permanen</b> untuk kelengkapan audit data?</div>');
                var verifyFooter = '<button type="button" class="btn btn-default btn-sm font-bold" data-dismiss="modal" style="padding: 6px 14px; border-radius:4px;">Kembali</button>' +
                    '<button type="button" class="btn btn-success btn-sm font-bold" id="btn-confirm-verify-submit" style="padding: 6px 14px; border-radius:4px;">Ya, Setujui & Tutup</button>';
                $('#mdlConfirmFooter').html(verifyFooter);
                $('#btn-confirm-verify-submit').off('click').on('click', function() { executeSubmitForm(formId, $(this)); });
                $('#mdlConfirm').modal('show');
            }
        }

        function executeSubmitForm(formId, btnElement) {
            btnElement.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
            btnElement.siblings('button').prop('disabled', true);

            $.ajax({
                url: '<?= site_url("vm_change_resource/ajax_execute_workflow") ?>',
                type: 'POST',
                data: $(formId).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        window.location.reload(true);
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Proses Ditolak',
                                text: res.message,
                                confirmButtonColor: '#d33',
                                confirmButtonText: '<i class="fa fa-times"></i> Tutup Peringatan'
                            });
                        } else {
                            alert('Proses Ditolak: ' + res.message);
                        }

                        var oriText = 'Lanjutkan';
                        if (currentAction === 'execute') oriText = '<i class="fa fa-check-square-o"></i> Selesaikan Pekerjaan';
                        if (currentAction === 'cancel') oriText = 'Ya, Batalkan Request';
                        if (currentAction === 'verify') oriText = 'Ya, Setujui & Tutup';

                        btnElement.prop('disabled', false).html(oriText);
                        btnElement.siblings('button').prop('disabled', false);
                        $('#mdlConfirm').modal('hide');
                    }
                },
                error: function() {
                    alert('Terjadi gangguan komunikasi jaringan dengan server pusat.');
                    btnElement.prop('disabled', false).html('Coba Lagi');
                    btnElement.siblings('button').prop('disabled', false);
                    $('#mdlConfirm').modal('hide');
                }
            });
        }

        // [QA FIX] DOM-XSS Sanitizer Loader
        function escapeHtml(unsafe) {
            return (unsafe || '').toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    });

    // Custom Copy to Clipboard (Inline Copy API)
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
            try { document.execCommand("copy"); triggerCopySuccess(); } catch (err) {}
            document.body.removeChild(tempInput);
        }
    }
</script>
