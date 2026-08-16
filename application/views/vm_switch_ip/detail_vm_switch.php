<?php
/**
 * =============================================================================
 * File Name    : detail_vm_switch.php
 * Modul        : VM Switch IP
 * Purpose      : Menampilkan detail rekam jejak, spesifikasi IP, dan panel aksi.
 * Architecture : Consistent Grid Table UI, Flexbox Inline Sync, Pure SweetAlert2
 * =============================================================================
 */

// ========================================================================
// Intelephense Linter Guard & Defensive Programming
// ========================================================================
$id = $id ?? [];
$user_session = $user_session ?? [];
$detail = $detail ?? [];
$vm_details = $vm_details ?? [];
$ip_warnings = $ip_warnings ?? [];

// INITIALIZE RBAC
$role = isset($id["id_role"]) ? (int) $id["id_role"] : 99;
$can_edit_execute = can_edit_execute($role);
$can_verify_delete = can_verify_delete($role);

$s_eks = trim($detail["status_eksekusi"] ?? "");
$is_closed = $s_eks === "Selesai Verified" || $s_eks === "Cancel by User" || $s_eks === "Ditolak";
$is_waiting_exec = $s_eks === "Menunggu Eksekusi";
$is_waiting_verify = $s_eks === "Telah Dieksekusi";
?>

<div class="right_col" role="main">
    <div class="">
        <div class="row">
            <div class="col-sm-12">

                <!-- TOMBOL KEMBALI & NOTIFIKASI -->
                <div style="margin-bottom: 15px;">
                    <a href="<?= site_url(
                        "vm_switch_ip",
                    ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); font-weight: bold; color: #555;">
                        <i class="fa fa-arrow-left"></i> Kembali ke Daftar Log
                    </a>
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

                <!-- PRA-EKSEKUSI RADAR IP CONFLICT -->
                <?php if (!empty($ip_warnings)): ?>
                    <div class="alert alert-dismissible fade in" role="alert" style="background-color: #fffdf2; border-left: 5px solid #f39c12; color: #333; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 4px; margin-bottom: 20px;">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color: #000; opacity: 0.5;"><span aria-hidden="true">×</span></button>
                        <h4 style="font-weight: bold; font-size: 15px; margin-bottom: 10px; color: #d35400;">
                            <i class="fa fa-exclamation-triangle"></i> Peringatan Pra-Eksekusi (Potensi Konflik IP)
                        </h4>
                        <ul style="margin-bottom: 0; padding-left: 25px; line-height: 1.6; font-size: 13px;">
                            <?php foreach ($ip_warnings as $warn): ?>
                                <li><?= $warn ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <hr style="border-top: 1px dashed #f39c12; opacity: 0.3; margin: 10px 0;">
                        <p style="margin-bottom: 0; font-size: 11px; color: #7f8c8d; font-style: italic;">
                            * Harap Executor melakukan cross-check fisik sebelum eksekusi. Abaikan pesan ini jika yakin status VM di vCenter aman.
                        </p>
                    </div>
                <?php endif; ?>

                <!-- HEADER PANEL -->
                <section class="panel" style="box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 8px;">
                    <header class="panel-heading" style="background-color: #f5f7fa; padding: 18px 20px; border-bottom: 1px solid #e6e9ed; border-top-left-radius: 8px; border-top-right-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-weight: bold; color: #2A3F54; font-size: 18px;">
                            <i class="fa fa-info-circle"></i> Detail Request Switch / Swap IP
                        </h3>

                        <div style="display: flex; gap: 10px;">
                            <?php if ($is_closed): ?>
                                <button type="button" class="btn btn-default btn-sm btn-locked" style="margin: 0; font-weight: bold; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: #999; background-color: #eee; border-color: #ddd;">
                                    <i class="fa fa-lock"></i> Duplikat Request
                                </button>
                            <?php else: ?>
                                <a href="<?= site_url(
                                    "vm_switch_ip/tambah?duplicate_from=" .
                                        ($detail["id_switch"] ?? ""),
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
                                        "vm_switch_ip/edit/" . ($detail["id_switch"] ?? ""),
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
                                            <th style="width: 35%; background-color: #f9f9f9; vertical-align: middle;">No Tiket</th>
                                            <td style="vertical-align: middle;">
                                                <?php if (
                                                    !empty($detail["link_tiket_eksternal"])
                                                ): ?>
                                                    <a href="<?= html_escape(
                                                        $detail["link_tiket_eksternal"],
                                                    ) ?>" target="_blank" class="text-primary font-bold" style="text-decoration:underline;">
                                                        <?= html_escape(
                                                            $detail["no_tiket_eksternal"] ?? "",
                                                        ) ?> <i class="fa fa-external-link"></i>
                                                    </a>
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
                                            <th style="background-color: #f9f9f9; vertical-align: middle;">Jenis Skenario</th>
                                            <td style="vertical-align: middle;">
                                                <?php
                                                $jp = $detail["jenis_switch"] ?? "";
                                                if ($jp == "Ganti IP (Single VM)") {
                                                    $sc_class =
                                                        "background-color: #3498db; color: #fff; font-size: 11.5px; padding: 2px 6px; border-radius: 3px; display: inline-block; font-weight: bold;";
                                                    $sc_icon = "fa-desktop";
                                                } else {
                                                    $sc_class =
                                                        "background-color: #f0ad4e; color: #fff; font-size: 11.5px; padding: 2px 6px; border-radius: 3px; display: inline-block; font-weight: bold;";
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
                                                } elseif (
                                                    $s == "Cancel by User" ||
                                                    $s == "Ditolak"
                                                ) {
                                                    $c = "bg-orange";
                                                } else {
                                                    $c = "bg-black";
                                                }

                                                $s_label =
                                                    $s == "Telah Dieksekusi"
                                                        ? "Menunggu Verifikasi"
                                                        : $s;
                                                echo "<span class='badge {$c}' style='font-size:12px; padding: 6px 10px; letter-spacing: 0.5px;'>{$s_label}</span>";
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
                                            <?= !empty($detail["deskripsi_permintaan"])
                                                ? nl2br(
                                                    html_escape($detail["deskripsi_permintaan"]),
                                                )
                                                : '<span style="color:#cbd5e1; font-style:italic;">Tidak ada keterangan spesifik...</span>' ?>
                                        </td>
                                    </tr>
                                    <?php if (!empty($detail["id_incident"])): ?>
                                    <tr>
                                        <th style="background:#f9f9f9; vertical-align: middle;">Tiket Insiden Terkait</th>
                                        <td style="vertical-align: middle;">
                                            <span class="label label-danger" style="font-size: 10px; padding: 3px 6px;"><i class="fa fa-link"></i> Linked</span>
                                            <?php if (
                                                !empty($detail["no_tiket_insiden_terkait"])
                                            ): ?>
                                                <a href="<?= site_url(
                                                    "vm_incident/detail/" . $detail["id_incident"],
                                                ) ?>" target="_blank" class="text-danger" style="margin-left: 5px; font-size:12px; font-weight: bold; text-decoration: underline;" title="Buka RCA Insiden">
                                                    <?= html_escape(
                                                        $detail["no_tiket_insiden_terkait"],
                                                    ) ?> <i class="fa fa-external-link"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                        <!-- [ENTERPRISE FIX]: DETAIL KONFIGURASI IP VM (Tabular Grid Lines Edition + Inline Flexbox UX) -->
                        <h4 style="font-weight: bold; color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-top: 30px;">
                            <i class="fa fa-exchange text-primary"></i> Detail Perubahan Konfigurasi IP
                        </h4>

                        <div class="table-responsive">
                            <table class="table table-bordered text-center" style="background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <thead>
                                    <tr class="active">
                                        <th class="text-center" style="width: 5%;">No</th>
                                        <th class="text-center" style="width: 25%;">Target Virtual Machine</th>
                                        <th class="text-center" style="width: 35%;">Kondisi Lama (Current)</th>
                                        <th class="text-center" style="width: 35%;">Target Baru (New State)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $v_no = 1;
                                    foreach ($vm_details as $v): ?>
                                        <tr>
                                            <td style="vertical-align: middle; font-weight: bold; font-size: 14px;"><?= $v_no++ ?></td>
                                            <td style="vertical-align: middle; text-align: left;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <i class="fa fa-server text-muted"></i>
                                                    <strong style="color: #2c3e50; font-size: 13px;"><?= html_escape(
                                                        $v["nama_master_aktual"] ?? "-",
                                                    ) ?></strong>
                                                </div>
                                            </td>
                                            <td style="vertical-align: middle; text-align: left; background-color: #fdfdfd; padding: 15px;">
                                                <!-- Inline Flexbox Setup for Label & Value -->
                                                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                                    <span class="text-muted" style="width: 75px; font-size: 12px; font-weight: 600;">IP Address:</span>
                                                    <strong style="font-family: monospace; font-size: 14px; margin-right: 8px; color: #333;"><?= html_escape(
                                                        $v["ip_lama"] ?? "-",
                                                    ) ?></strong>
                                                    <i class="fa fa-copy inline-copy-trigger" data-text="<?= html_escape(
                                                        $v["ip_lama"] ?? "",
                                                    ) ?>" title="Salin IP Lama" style="color:#cbd5e1; cursor:pointer; font-size:13px;"></i>
                                                </div>
                                                <div style="display: flex; align-items: center;">
                                                    <span class="text-muted" style="width: 75px; font-size: 12px; font-weight: 600;">Nama VM:</span>
                                                    <span style="color: #555; font-family: monospace; font-size: 13px; font-weight: 600; margin-right: 8px;"><?= html_escape(
                                                        $v["nama_vm_lama"] ?? "-",
                                                    ) ?></span>
                                                    <i class="fa fa-copy inline-copy-trigger" data-text="<?= html_escape(
                                                        $v["nama_vm_lama"] ?? "",
                                                    ) ?>" title="Salin Nama Lama" style="color:#cbd5e1; cursor:pointer; font-size:13px;"></i>
                                                </div>
                                            </td>
                                            <td style="vertical-align: middle; text-align: left; background-color: #f4fdf7; padding: 15px;">
                                                <!-- Inline Flexbox Setup for Target Baru -->
                                                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                                    <span class="text-success font-bold" style="width: 75px; font-size: 12px;">IP Address:</span>
                                                    <strong class="text-success" style="font-family: monospace; font-size: 15px; margin-right: 8px;"><?= html_escape(
                                                        $v["ip_baru"] ?? "-",
                                                    ) ?></strong>
                                                    <i class="fa fa-copy inline-copy-trigger" data-text="<?= html_escape(
                                                        $v["ip_baru"] ?? "",
                                                    ) ?>" title="Salin IP Baru" style="color:#a3e4d7; cursor:pointer; font-size:13px;"></i>
                                                </div>
                                                <div style="display: flex; align-items: center;">
                                                    <span class="text-success font-bold" style="width: 75px; font-size: 12px;">Nama VM:</span>
                                                    <strong class="text-success" style="font-family: monospace; font-size: 13px; margin-right: 8px;"><?= html_escape(
                                                        $v["nama_vm_baru"] ?? "-",
                                                    ) ?></strong>
                                                    <i class="fa fa-copy inline-copy-trigger" data-text="<?= html_escape(
                                                        $v["nama_vm_baru"] ?? "",
                                                    ) ?>" title="Salin Nama Baru" style="color:#a3e4d7; cursor:pointer; font-size:13px;"></i>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- REKAM JEJAK PELAKSANAAN (Jika Sudah Dieksekusi) -->
                        <?php if (!$is_waiting_exec): ?>
                            <h4 class="font-bold" style="color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-top: 30px;"><i class="fa fa-history"></i> Rekam Jejak Pelaksanaan</h4>
                            <table class="table table-bordered" style="background-color: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <tr>
                                    <th style="background-color: #fcfdf9; width: 20%; color: #3c763d; vertical-align: top;">
                                        <i class="fa fa-wrench"></i> Eksekusi Pekerjaan
                                    </th>
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
                                                    "d M Y, H:i",
                                                    strtotime($detail["tanggal_eksekusi"]),
                                                )
                                                : "-" ?> WIB</strong></div>
                                        </div>
                                        <div style="font-style:italic; color:#333; font-size: 13px; padding: 10px; border-left: 3px solid #d6e9c6; background: #f9fdf5; border-radius: 2px;">
                                            <?= !empty($detail["catatan_eksekusi"])
                                                ? nl2br(html_escape($detail["catatan_eksekusi"]))
                                                : '<span class="text-muted">Tanpa catatan operasional...</span>' ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php if ($detail["status_eksekusi"] == "Selesai Verified"): ?>
                                    <tr>
                                        <th style="background-color: #dff0d8; width: 20%; color: #3c763d; vertical-align: top;">
                                            <i class="fa fa-shield"></i> Verifikasi Akhir
                                        </th>
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
                                                        "d M Y, H:i",
                                                        strtotime($detail["tanggal_verifikasi"]),
                                                    )
                                                    : "-" ?> WIB</strong></div>
                                            </div>
                                            <div style="font-style:italic; color:#333; font-size: 13px; padding: 10px; border-left: 3px solid #c3e6cb; background: #f2f9f4; border-radius: 2px;">
                                                <?= !empty($detail["catatan_verifikasi"])
                                                    ? nl2br(
                                                        html_escape($detail["catatan_verifikasi"]),
                                                    )
                                                    : '<span class="text-muted">Disetujui tanpa catatan tambahan...</span>' ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        <?php endif; ?>

                        <!-- PENGKONDISIAN FORM EKSEKUSI (IMPLEMENTER) -->
                        <?php if ($is_waiting_exec && $can_edit_execute): ?>
                            <?php
                            $catatan_sementara = $detail["catatan_eksekusi"] ?? "";
                            if (!empty($catatan_sementara)): ?>
                                <div style="background-color: #fffdf2; border-left: 4px solid #f1c40f; padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; margin-top: 30px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">
                                    <strong style="color: #b18c00;"><i class="fa fa-info-circle"></i> Info Terkini (Catatan Kendala/Shift Handover):</strong><br>
                                    <div style="margin-top: 4px; color: #333; font-size: 13px; font-style: italic;">
                                        "<?= nl2br(html_escape($catatan_sementara)) ?>"
                                    </div>
                                </div>
                            <?php endif;
                            ?>

                            <form id="formExecuteSwitch" novalidate>
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                <input type="hidden" name="id_switch" value="<?= html_escape(
                                    $detail["id_switch"] ?? "",
                                ) ?>">
                                <input type="hidden" name="action_type" id="input_action_type" value="execute">

                                <div style="margin-top: 10px; background: #fdfdfd; padding: 25px; border-radius: 6px; border-left: 5px solid #337ab7; border: 1px solid #e1e1e1;">
                                    <h4 class="font-bold text-primary" style="margin-top: 0;"><i class="fa fa-wrench"></i> Panel Submit & Eksekusi</h4>

                                    <div class="form-group" style="background-color: #f8fafc; border-left: 4px solid #94a3b8; padding: 15px; margin-top: 15px; margin-bottom: 20px; border-radius: 4px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
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
                                            <i class="fa fa-check-square-o"></i> Selesaikan Pekerjaan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>

                        <!-- PENGKONDISIAN FORM VERIFIKASI -->
                        <?php if ($is_waiting_verify && $can_verify_delete): ?>
                            <form id="formVerifySwitch" novalidate>
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                <input type="hidden" name="id_switch" value="<?= html_escape(
                                    $detail["id_switch"] ?? "",
                                ) ?>">
                                <input type="hidden" name="action_type" value="verify">

                                <div style="margin-top: 40px; background: #fcf8e3; padding: 25px; border-radius: 6px; border-left: 5px solid #5cb85c; border: 1px solid #faebcc;">
                                    <h4 class="font-bold text-success" style="margin-top: 0;"><i class="fa fa-shield"></i> Panel Verifikasi (Peer-Review)</h4>
                                    <small class="text-muted" style="display:block; margin-bottom:15px; font-style:italic;">* Pekerjaan telah diselesaikan oleh implementer. Lakukan validasi akhir, tiket akan ditutup permanen setelah disetujui.</small>

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
    <div class="modal-dialog modal-md" style="margin-top: 10%;">
        <div class="modal-content" style="border-radius: 6px; box-shadow: 0 5px 15px rgba(0,0,0,0.25); border: none;">
            <div class="modal-header" id="mdlConfirmHeader" style="color: white; border-top-left-radius: 6px; border-top-right-radius: 6px; padding: 15px 20px;">
                <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title font-bold"><i class="fa fa-question-circle"></i> Konfirmasi Eksekusi Sistem</h4>
            </div>
            <div class="modal-body" style="padding: 25px 20px;">
                <div id="mdlConfirmText" style="font-size: 14px; color: #333; line-height: 1.6;"></div>
            </div>
            <div class="modal-footer" id="mdlConfirmFooter" style="background-color: #f9fbfd; border-top: 1px solid #edf2f7; padding: 12px 20px; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px; text-align: right;">
                <button type="button" class="btn btn-default btn-sm font-bold" data-dismiss="modal" style="box-shadow: 0 1px 2px rgba(0,0,0,0.05);">Batal</button>
                <button type="button" class="btn btn-sm font-bold" id="btnConfirmSubmit" style="color: white;"></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hapus Jurnal -->
<?php if ($can_verify_delete): ?>
    <div class="modal fade" id="mdlDelDet" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-sm" style="margin-top:15%;">
            <div class="modal-content" style="border-radius: 6px;">
                <form action="<?= site_url("vm_switch_ip/hapus") ?>" method="post">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <input type="hidden" name="id_switch" value="<?= html_escape(
                        $detail["id_switch"] ?? "",
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

    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

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

        $('#btn-trigger-eks').on('click', function(e) { e.preventDefault(); $('#input_action_type').val('execute'); validasiAksi('execute'); });
        $('#btn-trigger-cancel').on('click', function(e) { e.preventDefault(); $('#input_action_type').val('cancel'); validasiAksi('cancel'); });
        $('#btn-trigger-ver').on('click', function(e) { e.preventDefault(); validasiAksi('verify'); });

        function validasiAksi(tipe) {
            currentAction = tipe;
            var isEks = (tipe === 'execute');
            var isCancel = (tipe === 'cancel');

            var inputId = (isEks || isCancel) ? '#input_catatan_eksekusi' : '#input_catatan_verifikasi';
            var formId = (isEks || isCancel) ? '#formExecuteSwitch' : '#formVerifySwitch';
            var $input = $(inputId);

            if ($input.length === 0) return;

            $input.css('border', '');
            $input.siblings('.error-validation').remove();

            var catatan = $input.val().trim();

            if ((isEks || isCancel) && catatan === '') {
                $input.css({'border': '1px solid #ef4444', 'background-color': '#fef2f2'});
                var errMsg = isCancel ? 'Wajib mengisi alasan pembatalan dari user!' : 'Keterangan teknis eksekusi wajib diisi!';
                $input.after('<small class="text-danger font-bold error-validation" style="display:block; margin-top:5px;"><i class="fa fa-warning"></i> ' + errMsg + '</small>');
                $input.focus();
                return;
            }

            if (isEks) {
                var htmlEks = '<div style="font-size:15px; color:#334155; line-height:1.6;">' +
                    'Konfirmasi laporan penyelesaian eksekusi <b>Switch / Swap IP</b>?' +
                    '<div style="margin-top: 20px; background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; font-size: 13px; text-align: left; border-radius: 4px;">' +
                    '<i class="fa fa-info-circle text-primary" style="margin-right: 5px; font-size: 16px; vertical-align: middle;"></i> <strong style="vertical-align: middle; color:#1e293b;">Peringatan Operasional:</strong><br>' +
                    '<span style="display: block; margin-top: 8px; color:#475569;">Pastikan <b>konfigurasi IP aktual sudah benar-benar diubah secara fisik di vCenter / OS</b> sebelum Anda menyimpan log eksekusi ini.</span>' +
                    '</div></div>';

                $('#mdlConfirmHeader').css('background-color', '#3b82f6');
                $('#mdlConfirmText').html(htmlEks);
                $('#btnConfirmSubmit').attr('class', 'btn btn-primary font-bold').css({'background-color': '#3b82f6', 'border-color': '#3b82f6'}).text('Ya, Selesaikan Pekerjaan');

            } else if (isCancel) {
                var htmlCancel = '<div style="font-size:15px; color:#334155; line-height:1.6;">' +
                    'Apakah Anda yakin ingin <b>Membatalkan</b> tiket request ini?' +
                    '<div style="margin-top: 20px; background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; font-size: 13px; text-align: left; border-radius: 4px;">' +
                    '<i class="fa fa-exclamation-triangle" style="color: #d97706; margin-right: 5px; font-size: 16px; vertical-align: middle;"></i> <strong style="vertical-align: middle; color:#92400e;">Tindakan Permanen:</strong><br>' +
                    '<span style="display: block; margin-top: 8px; color:#78350f;">Tiket yang telah dibatalkan akan <b>dikunci secara permanen</b> demi audit log, dan proses eksekusi tidak dapat dilanjutkan kembali.</span>' +
                    '</div></div>';

                $('#mdlConfirmHeader').css('background-color', '#f59e0b');
                $('#mdlConfirmText').html(htmlCancel);
                $('#btnConfirmSubmit').attr('class', 'btn btn-warning font-bold').css({'background-color': '#f59e0b', 'border-color': '#f59e0b'}).text('Ya, Batalkan Request');

            } else {
                var htmlVer = '<div style="font-size:15px; color:#334155; line-height:1.6; text-align: center; padding: 10px 0;">' +
                    '<i class="fa fa-check-circle text-success" style="font-size: 40px; margin-bottom: 15px;"></i><br>' +
                    'Setujui hasil pekerjaan implementer dan <b>kunci tiket secara permanen</b> untuk kelengkapan audit data?' +
                    '</div>';

                $('#mdlConfirmHeader').css('background-color', '#10b981');
                $('#mdlConfirmText').html(htmlVer);
                $('#btnConfirmSubmit').attr('class', 'btn btn-success font-bold').css({'background-color': '#10b981', 'border-color': '#10b981'}).text('Ya, Setujui & Tutup');
            }

            $('#mdlConfirm').modal('show');
        }

        $('#btnConfirmSubmit').off('click').on('click', function() {
            var btn = $(this);
            var formToSubmit = (currentAction === 'execute' || currentAction === 'cancel') ? '#formExecuteSwitch' : '#formVerifySwitch';

            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
            btn.siblings('button').prop('disabled', true);

            $.ajax({
                url: '<?= site_url("vm_switch_ip/ajax_execute_workflow") ?>',
                type: 'POST',
                data: $(formToSubmit).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false}).then(() => {
                                window.location.reload(true);
                            });
                        } else {
                            window.location.reload(true);
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({icon: 'error', title: 'Ditolak', text: res.message});
                        } else {
                            alert('Proses Ditolak: ' + res.message);
                        }
                        var oriText = 'Lanjutkan';
                        if (currentAction === 'execute') oriText = 'Ya, Selesaikan Pekerjaan';
                        if (currentAction === 'cancel') oriText = 'Ya, Batalkan Request';
                        if (currentAction === 'verify') oriText = 'Ya, Setujui & Tutup';

                        btn.prop('disabled', false).text(oriText);
                        btn.siblings('button').prop('disabled', false);
                        $('#mdlConfirm').modal('hide');
                    }
                },
                error: function() {
                    alert('Terjadi gangguan komunikasi jaringan dengan server pusat.');
                    btn.prop('disabled', false).text('Coba Lagi');
                    btn.siblings('button').prop('disabled', false);
                    $('#mdlConfirm').modal('hide');
                }
            });
        });

        // TOAST LOCK BUTTON ANIMATION PURE SWEETALERT
        $(document).on('click', '.btn-locked', function(e) {
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
                    title: 'Akses Terkunci',
                    text: 'Data tidak dapat diedit/diduplikat karena tiket telah ditutup permanen atau dibatalkan.',
                    showConfirmButton: false,
                    timer: 4000
                });
            }
        });

        // CUSTOM COPY TO CLIPBOARD PURE SWEETALERT
        $(document).on('click', '.inline-copy-trigger', function() {
            var $btn = $(this);
            var textToCopy = $btn.data('text');

            if (!textToCopy || textToCopy === '-') return;

            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(textToCopy).select();
            document.execCommand("copy");
            $temp.remove();

            $btn.removeClass('fa-copy').addClass('fa-check').css({'color': '#10b981', 'transform': 'scale(1.2)'});

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Disalin: ' + textToCopy,
                    showConfirmButton: false,
                    timer: 1500
                });
            }

            setTimeout(function() {
                $btn.removeClass('fa-check').addClass('fa-copy').css({'color': '#94a3b8', 'transform': 'scale(1)'});
            }, 1500);
        });
    });
</script>
