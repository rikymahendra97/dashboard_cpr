<?php
/**
 * ============================================================================
 * File Name    : form_edit_provisioning.php
 * Modul        : VM Provisioning
 * Purpose      : Halaman Koreksi Data & CMDB Binding (Tahap Akhir)
 * Architecture : Hostname Regex Strict, CSRF Live Sync, CMDB Verification
 * ============================================================================
 */

// ========================================================================
// Intelephense Linter Guard & Defensive Programming
// ========================================================================
$id = $id ?? [];
$user_session = $user_session ?? [];
$row = $row ?? new stdClass();
$list_os = $list_os ?? [];
$list_template = $list_template ?? [];
$master_team = $master_team ?? [];
$relation_vm = $relation_vm ?? null;

$progres_saat_ini = strtolower(trim($row->progres_tiket ?? ""));
?>

<style>
    .helper-text { font-size: 11px; font-style: italic; display: block; margin-top: 5px; color: #73879C; }
    .panel-cmdb { background-color: #F8FAFC; border: 2px dashed #3B82F6; border-radius: 8px; padding: 20px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.1); }
    .panel-cmdb-title { font-size: 16px; font-weight: 700; color: #1E293B; margin-top: 0; margin-bottom: 15px; }
    .section-title { font-size: 15px; font-weight: bold; color: #2A3F54; border-bottom: 1px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 15px; }
    .datastore-actions { margin-top: 8px; margin-bottom: 5px; }
    .datastore-actions .btn { border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
</style>

<div class="right_col" role="main">
    <div class="">

        <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <a href="<?= site_url(
                "provisioning",
            ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; font-weight: bold; color: #555; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                <i class="fa fa-arrow-left"></i> Kembali ke Antrean
            </a>
            <span class="label label-info" style="font-size: 13px; padding: 6px 12px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">EDIT MODE</span>
        </div>

        <div id="alert-container">
            <?php
            $alerts = $this->session->flashdata("alerts") ?? [];
            if (empty($alerts) && $this->session->flashdata("error")) {
                $alerts = [["error", $this->session->flashdata("error")]];
            }
            $this->session->unset_userdata("alerts");
            $this->session->unset_userdata("error");

            if (!empty($alerts) && is_array($alerts) && isset($alerts[0])):

                $tipe = $alerts[0][0] === "error" ? "error" : "success";
                $pesan = html_escape($alerts[0][1]);
                ?>
                <div id="swal-flash-data" data-type="<?= $tipe ?>" data-message="<?= $pesan ?>" style="display: none;"></div>
            <?php
            endif;
            ?>
        </div>

        <section class="panel" style="box-shadow: 0 2px 6px rgba(0,0,0,0.08); border-radius: 8px; border: 1px solid #E2E8F0;">
            <header class="panel-heading" style="background-color: #f5f7fa; padding: 18px 20px; border-bottom: 1px solid #e6e9ed; border-radius: 8px 8px 0 0;">
                <h3 style="margin: 0; font-weight: bold; color: #2A3F54; font-size: 16px;">
                    <i class="fa fa-pencil-square-o text-primary"></i> Koreksi Request & Validasi Aset
                </h3>
            </header>

            <div class="panel-body" style="padding: 30px;">
                <form id="formEditProvisioning" action="<?= site_url(
                    "provisioning/update_data",
                ) ?>" method="post" novalidate>

                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" id="csrf_token_field" value="<?= $this->security->get_csrf_hash() ?>">
                    <input type="hidden" name="id_tiket" value="<?= html_escape(
                        $row->id_tiket ?? "",
                    ) ?>">

                    <!-- PANEL VERIFIKASI CMDB -->
                    <div class="panel-cmdb" style="<?= $progres_saat_ini !== "waiting sync" &&
                    $progres_saat_ini !== "done"
                        ? "border-color:#E2E8F0; background:#fff;"
                        : "" ?>">
                        <h4 class="panel-cmdb-title">
                            <i class="fa <?= $progres_saat_ini === "waiting sync"
                                ? "fa-exclamation-circle text-primary"
                                : "fa-lock text-success" ?>"></i>
                            Tahap Akhir: Verifikasi Target CMDB & Status Tiket
                        </h4>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="progres_tiket" class="font-bold text-danger">Status Tiket Saat Ini <span class="text-danger">*</span></label>
                                    <select class="form-control" name="progres_tiket" id="progres_tiket" style="font-weight: bold; border-width: 2px;">
                                        <option value="Pending" <?= $progres_saat_ini == "pending"
                                            ? "selected"
                                            : "" ?>>Pending (Menunggu Eksekusi)</option>
                                        <option value="In Progress" <?= $progres_saat_ini ==
                                        "in progress"
                                            ? "selected"
                                            : "" ?>>In Progress (Sedang Setup)</option>
                                        <option value="Waiting Sync" <?= $progres_saat_ini ==
                                        "waiting sync"
                                            ? "selected"
                                            : "" ?>>Waiting Sync (Setup Fisik Selesai)</option>
                                        <option value="Done" <?= $progres_saat_ini == "done"
                                            ? "selected"
                                            : "" ?>>Done (Closed & Terikat CMDB)</option>
                                        <option value="Cancel" <?= $progres_saat_ini == "cancel"
                                            ? "selected"
                                            : "" ?>>Cancel (Dibatalkan)</option>
                                    </select>
                                    <small class="helper-text">Pilih "Done" untuk mengunci tiket ini ke dalam CMDB.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_virtual_machine" class="font-bold text-primary">Tautkan ke Master VM (CMDB) <span class="text-danger">*</span></label>
                                    <select class="form-control select2-ajax-vm" name="id_virtual_machine" id="id_virtual_machine" style="width:100%;">
                                        <?php if (!empty($relation_vm)): ?>
                                            <option value="<?= html_escape(
                                                $relation_vm["id_virtual_machine"],
                                            ) ?>" selected><?= html_escape(
    $relation_vm["virtual_machine_name"],
) ?></option>
                                        <?php else: ?>
                                            <option value="">-- Cari Nama VM di CMDB --</option>
                                        <?php endif; ?>
                                    </select>
                                    <small class="helper-text" id="cmdb-warning" style="color:#e74c3c; display:none;"><i class="fa fa-warning"></i> Wajib diisi jika status tiket "Done". Pastikan nama VM persis sama dengan Nama Server.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- KOLOM KIRI: ADMINISTRATIF -->
                        <div class="col-md-6" style="border-right: 1px solid #edf2f7; padding-right: 25px;">
                            <h4 class="section-title"><i class="fa fa-file-text-o"></i> Informasi Request iRIS</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="no_tiket" class="font-bold">No Tiket iRIS <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control required-field" name="no_tiket" id="no_tiket" value="<?= html_escape(
                                            $row->no_tiket ?? "",
                                        ) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tanggal_masuk_tiket" class="font-bold">Tanggal Masuk Request <span class="text-danger">*</span></label>
                                        <?php $tgl_masuk = !empty($row->tanggal_masuk_tiket)
                                            ? date(
                                                "Y-m-d\TH:i",
                                                strtotime($row->tanggal_masuk_tiket),
                                            )
                                            : ""; ?>
                                        <input type="datetime-local" class="form-control required-field" name="tanggal_masuk_tiket" id="tanggal_masuk_tiket" value="<?= $tgl_masuk ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="link_tiket" class="font-bold">Hyperlink Tiket (URL)</label>
                                <input type="url" class="form-control" name="link_tiket" id="link_tiket" value="<?= html_escape(
                                    $row->link_tiket ?? "",
                                ) ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tipe_request" class="font-bold text-primary">Tipe Request <span class="text-danger">*</span></label>
                                        <select class="form-control required-field" name="tipe_request" id="tipe_request" required>
                                            <?php
                                            $req_type = strtolower(trim($row->tipe_request ?? ""));
                                            $is_fresh =
                                                $req_type === "fresh" ||
                                                $req_type === "fresh install";
                                            ?>
                                            <option value="Fresh Install" <?= $is_fresh
                                                ? "selected"
                                                : "" ?>>Fresh Install</option>
                                            <option value="Clone" <?= $req_type === "clone"
                                                ? "selected"
                                                : "" ?>>Clone</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="aplikasi" class="font-bold">Kelompok Aplikasi</label>
                                        <input type="text" class="form-control" name="aplikasi" id="aplikasi" value="<?= html_escape(
                                            $row->aplikasi ?? "",
                                        ) ?>">
                                    </div>
                                </div>
                            </div>

                            <h4 class="section-title" style="margin-top: 30px;"><i class="fa fa-users"></i> Administratif Peminta</h4>
                            <div class="form-group">
                                <label for="divisi_requestor" class="font-bold">Fungsi / Departemen Peminta <span class="text-danger">*</span></label>
                                <select class="form-control required-field select2-tags-divisi" name="divisi_requestor" id="divisi_requestor" style="width: 100%;" required>
                                    <option value="<?= html_escape(
                                        $row->divisi_requestor ?? "",
                                    ) ?>" selected><?= html_escape(
    $row->divisi_requestor ?? "",
) ?></option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="form-group">
                                        <label for="nama_requestor" class="font-bold text-info">Nama Requestor / PIC <span class="text-danger">*</span></label>
                                        <select class="form-control required-field select2-tags-pic" name="nama_requestor" id="nama_requestor" style="width: 100%;" required>
                                            <option value="<?= html_escape(
                                                $row->nama_requestor ?? "",
                                            ) ?>" selected><?= html_escape(
    $row->nama_requestor ?? "",
) ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="contact" class="font-bold">Kontak PIC</label>
                                        <input type="text" class="form-control" name="contact" id="contact" value="<?= html_escape(
                                            $row->contact ?? "",
                                        ) ?>">
                                    </div>
                                </div>
                            </div>

                            <h4 class="section-title" style="margin-top:25px;"><i class="fa fa-shield"></i> Informasi Lingkungan Sistem</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="kritikalitas" class="font-bold">Kritikalitas Sistem <span class="text-danger">*</span></label>
                                        <select class="form-control required-field" name="kritikalitas" id="kritikalitas" required>
                                            <option value="">-- Pilih --</option>
                                            <?php $k = $row->kritikalitas ?? ""; ?>
                                            <option value="Critical" <?= $k == "Critical"
                                                ? "selected"
                                                : "" ?>>Critical</option>
                                            <option value="Very High" <?= $k == "Very High"
                                                ? "selected"
                                                : "" ?>>Very High</option>
                                            <option value="High" <?= $k == "High"
                                                ? "selected"
                                                : "" ?>>High</option>
                                            <option value="Medium" <?= $k == "Medium"
                                                ? "selected"
                                                : "" ?>>Medium</option>
                                            <option value="Low" <?= $k == "Low"
                                                ? "selected"
                                                : "" ?>>Low</option>
                                            <option value="Non-Prod" <?= $k == "Non-Prod"
                                                ? "selected"
                                                : "" ?>>Non-Prod</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="environment" class="font-bold">Environment <span class="text-danger">*</span></label>
                                        <select class="form-control required-field" name="environment" id="environment" required>
                                            <option value="">-- Pilih --</option>
                                            <?php $e = $row->environment ?? ""; ?>
                                            <option value="Production" <?= $e == "Production"
                                                ? "selected"
                                                : "" ?>>Production</option>
                                            <option value="Development" <?= $e == "Development"
                                                ? "selected"
                                                : "" ?>>Development</option>
                                            <option value="Staging" <?= $e == "Staging"
                                                ? "selected"
                                                : "" ?>>Staging</option>
                                            <option value="Testing" <?= $e == "Testing"
                                                ? "selected"
                                                : "" ?>>Testing</option>
                                            <option value="UAT" <?= $e == "UAT"
                                                ? "selected"
                                                : "" ?>>UAT</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top:10px;">
                                <label for="keterangan" class="font-bold">Catatan / Instruksi / Kendala</label>
                                <textarea class="form-control" name="keterangan" id="keterangan" rows="4"><?= html_escape(
                                    $row->keterangan ?? "",
                                ) ?></textarea>
                            </div>

                            <h4 class="section-title" style="margin-top:25px;"><i class="fa fa-clock-o"></i> Audit Trail Eksekusi</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tanggal_masuk_vcenter" class="font-bold">Tanggal Masuk vCenter</label>
                                        <?php $tgl_vcenter = !empty($row->tanggal_masuk_vcenter)
                                            ? date(
                                                "Y-m-d\TH:i",
                                                strtotime($row->tanggal_masuk_vcenter),
                                            )
                                            : ""; ?>
                                        <input type="datetime-local" class="form-control" name="tanggal_masuk_vcenter" id="tanggal_masuk_vcenter" value="<?= $tgl_vcenter ?>">
                                        <small class="helper-text">Kosongkan jika belum dieksekusi.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tanggal_keluar_tiket" class="font-bold">Tanggal Keluar Tiket (Selesai)</label>
                                        <?php $tgl_keluar = !empty($row->tanggal_keluar_tiket)
                                            ? date(
                                                "Y-m-d\TH:i",
                                                strtotime($row->tanggal_keluar_tiket),
                                            )
                                            : ""; ?>
                                        <input type="datetime-local" class="form-control" name="tanggal_keluar_tiket" id="tanggal_keluar_tiket" value="<?= $tgl_keluar ?>">
                                        <small class="helper-text">Otomatis terisi jika status "Done".</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KOLOM KANAN: SPESIFIKASI TEKNIS & DATASTORE -->
                        <div class="col-md-6" style="padding-left: 25px;">
                            <h4 class="section-title"><i class="fa fa-server"></i> Spesifikasi Target VM</h4>

                            <div class="form-group">
                                <label for="nama_server" class="font-bold text-success">Nama Server (vCenter) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control required-field" name="nama_server" id="nama_server" value="<?= html_escape(
                                    $row->nama_server ?? "",
                                ) ?>" required style="border-color:#27ae60;">
                            </div>

                            <div class="form-group">
                                <label for="hostname" class="font-bold text-info">Hostname (OS Level) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control required-field regex-hostname" name="hostname" id="hostname" value="<?= html_escape(
                                    $row->hostname ?? "",
                                ) ?>" required style="border-color:#3498db;" title="Hanya diizinkan karakter huruf kecil, angka, dan strip (-)">
                                <small class="helper-text text-danger" style="display:none;" id="warn_hostname">Karakter spesial dihapus otomatis untuk menghindari error pada OS.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="os" class="font-bold">Operating System <span class="text-danger">*</span></label>
                                        <select name="os" id="os" class="form-control select2 required-field" required>
                                            <option value="">-- Pilih OS --</option>
                                            <?php foreach ($list_os as $family => $os_array): ?>
                                                <optgroup label="<?= html_escape($family) ?>">
                                                    <?php foreach ($os_array as $os_name): ?>
                                                        <?php $sel =
                                                            ($row->os ?? "") === $os_name
                                                                ? "selected"
                                                                : ""; ?>
                                                        <option value="<?= html_escape(
                                                            $os_name,
                                                        ) ?>" data-family="<?= html_escape(
    $family,
) ?>" <?= $sel ?>><?= html_escape($os_name) ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ip" class="font-bold">IP Address</label>
                                        <input type="text" class="form-control" name="ip" id="ip" value="<?= html_escape(
                                            $row->ip ?? "",
                                        ) ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- [QA FIX]: Source Clone Input Synchronization -->
                            <div class="form-group">
                                <label for="source_input" class="font-bold">Source Clone / Golden Template <span class="text-danger">*</span></label>
                                <div id="container_source">
                                    <input type="text" class="form-control" name="source_clone" id="source_input" value="<?= html_escape(
                                        $row->source_clone ?? "",
                                    ) ?>" placeholder="Isi IP/Nama VM Master Clone">
                                    <select id="source_select" class="form-control select2" style="display:none;"></select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="cpu" class="font-bold">vCPU (Core) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control required-field target-disk-trigger" name="cpu" id="cpu" value="<?= html_escape(
                                            $row->cpu ?? "",
                                        ) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ram" class="font-bold">RAM (GB) <span class="text-danger">*</span></label>
                                        <input type="number" step="1" class="form-control required-field target-disk-trigger" name="ram" id="ram" value="<?= html_escape(
                                            $row->ram ?? "",
                                        ) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="req_disk_val" class="font-bold">Disk (GB) <span class="text-danger">*</span></label>
                                        <input type="number" step="1" class="form-control required-field target-disk-trigger" id="req_disk_val" name="disk" value="<?= html_escape(
                                            $row->disk ?? "",
                                        ) ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="detail_disk" class="font-bold">Detail Partisi Disk</label>
                                <textarea class="form-control" name="detail_disk" id="detail_disk" rows="2"><?= html_escape(
                                    $row->detail_disk ?? "",
                                ) ?></textarea>
                            </div>

                            <h4 class="section-title" style="margin-top: 30px;"><i class="fa fa-database"></i> Alokasi Penyimpanan</h4>
                            <div class="form-group" style="background-color: #fcfdfd; padding: 15px; border: 1px solid #e2e8f0; border-radius: 6px;">
                                <label for="datastore" class="font-bold text-primary">Target Datastore Cluster <span class="text-danger">*</span></label>
                                <select class="form-control select2-ajax-datastore required-field" name="datastore" id="datastore" style="width: 100%;" required>
                                    <?php if (!empty($row->datastore)): ?>
                                        <option value="<?= html_escape(
                                            $row->datastore,
                                        ) ?>" selected><?= html_escape($row->datastore) ?></option>
                                    <?php endif; ?>
                                </select>
                                <small class="helper-text"><i class="fa fa-info-circle"></i> Cari nama Datastore / ketik nama LUN baru.</small>

                                <div class="datastore-actions">
                                    <button type="button" class="btn btn-default btn-xs"><i class="fa fa-refresh text-info"></i> Sync Live Datastore</button>
                                    <button type="button" class="btn btn-default btn-xs"><i class="fa fa-lightbulb-o text-warning"></i> Suggest Placement</button>
                                </div>

                                <div id="datastore_metadata_card" style="display: none; margin-top: 15px; border: 1px solid #e5e5e5; border-radius: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); background-color: #fff; overflow: hidden;">
                                    <div id="ds_header" style="background-color: #f8f9fa; padding: 10px 15px; border-bottom: 1px solid #e5e5e5; font-weight: bold; color: #2A3F54;">
                                        <i class="fa fa-hdd-o"></i> <span id="ds_name_lbl">-</span>
                                        <span id="ds_status_badge" class="pull-right"></span>
                                    </div>
                                    <div style="padding: 15px;">
                                        <table style="width: 100%; font-size: 13px; margin: 0;">
                                            <tr>
                                                <td style="width: 50%; padding-bottom: 8px; border-bottom: 1px dotted #eee;"><strong>Capacity:</strong></td>
                                                <td style="width: 50%; padding-bottom: 8px; border-bottom: 1px dotted #eee; text-align: right;" id="ds_capacity_lbl">-</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px dotted #eee;"><strong>Used:</strong></td>
                                                <td style="padding: 8px 0; border-bottom: 1px dotted #eee; text-align: right;" id="ds_used_lbl">-</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px dotted #eee;"><strong>Free:</strong></td>
                                                <td style="padding: 8px 0; border-bottom: 1px dotted #eee; text-align: right;" id="ds_free_lbl">-</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; border-bottom: 1px dotted #eee;"><strong>Provisioned:</strong></td>
                                                <td style="padding: 8px 0; border-bottom: 1px dotted #eee; text-align: right;" id="ds_prov_lbl">-</td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top: 8px;"><strong>Overprovision:</strong></td>
                                                <td style="padding-top: 8px; text-align: right;" id="ds_over_lbl">-</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-12 text-right">
                            <hr style="border-top: 1px solid #e5e5e5; margin-bottom: 20px;">
                            <a href="<?= site_url(
                                "provisioning/detail/" . ($row->id_tiket ?? ""),
                            ) ?>" class="btn btn-default font-bold btn-lg" style="border-radius: 4px; margin-right: 10px;">
                                Batal (Kembali)
                            </a>
                            <button type="submit" class="btn btn-primary font-bold btn-lg" id="btnSubmitUpdate" style="border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">
                                <i class="fa fa-save"></i> Simpan Perubahan Data
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </section>

    </div>
</div>

<!-- Injeksi JS Variabel Linter-Safe & Sanitized JSON Flags -->
<script>
    var TEAM_DATA_JSON_STRING     = '<?= json_encode(
        $master_team ?? [],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
    ) ?>';
    var TEMPLATE_DATA_JSON_STRING = '<?= json_encode(
        $list_template ?? [],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
    ) ?>';

    var INITIAL_DIVISI = '<?= addslashes($row->divisi_requestor ?? "") ?>';
    var INITIAL_PIC    = '<?= addslashes($row->nama_requestor ?? "") ?>';

    var URL_AJAX_DATASTORE = '<?= site_url("provisioning/ajax_search_datastore") ?>';
    var URL_AJAX_DUPLICATE = '<?= site_url("provisioning/ajax_check_duplicate") ?>';
    var URL_AJAX_SEARCH_VM = '<?= site_url("provisioning/search_vm") ?>';
    var CSRF_NAME_VAL      = '<?= $this->security->get_csrf_token_name() ?>';
</script>

<script>
$(document).ready(function() {

    // [QA FIX] Event Listener untuk sanitasi Hostname
    $('.regex-hostname').on('input', function() {
        var originalValue = this.value;
        var sanitizedValue = originalValue.replace(/[^a-zA-Z0-9-]/g, '').toLowerCase();

        if (originalValue !== sanitizedValue) {
            this.value = sanitizedValue;
            $('#warn_hostname').fadeIn(200);
            clearTimeout(window.warnHostTimer);
            window.warnHostTimer = setTimeout(function() { $('#warn_hostname').fadeOut(200); }, 3000);
        }
    });

    // SWEETALERT BFCACHE-SAFE DOM EXTRACTION
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
        $flashElem.remove();
    }

    var teamData = [];
    var masterTemplates = {};
    try {
        teamData = JSON.parse(TEAM_DATA_JSON_STRING);
        masterTemplates = JSON.parse(TEMPLATE_DATA_JSON_STRING);
    } catch(e) { console.error("JSON Parsing Error", e); }

    var initialSource = "<?= html_escape($row->source_clone ?? "") ?>";

    // [QA FIX]: Sinkronisasi Atribut Name Input Source Clone
    function toggleSource(tipe) {
        var $input = $('#source_input');
        var $select = $('#source_select');

        if (tipe == 'Fresh Install') {
            $select.show().attr('name', 'source_clone').addClass('required-field').prop('required', true);
            $input.hide().removeAttr('name').removeClass('required-field').prop('required', false);
        } else if (tipe == 'Clone') {
            $input.show().attr('name', 'source_clone').addClass('required-field').prop('required', true);
            $select.hide().removeAttr('name').removeClass('required-field').prop('required', false);
        } else {
            $input.show().removeAttr('name').removeClass('required-field').prop('required', false);
            $select.hide().removeAttr('name').removeClass('required-field').prop('required', false);
        }
    }
    $('#tipe_request').on('change', function() { toggleSource($(this).val()); });

    $('#os').on('change', function() {
        var family = $(this).find('option:selected').data('family');
        var $templateDropdown = $('#source_select');

        $templateDropdown.empty().append('<option value="">-- Pilih Template --</option>');

        if (family && masterTemplates[family]) {
            $.each(masterTemplates[family], function(index, tpl_name) {
                $templateDropdown.append('<option value="' + tpl_name + '">' + tpl_name + '</option>');
            });
            $templateDropdown.append('<optgroup label="Alternatif"><option value="Custom ISO">Custom ISO (Instalasi Manual)</option></optgroup>');

            if (initialSource) {
                if($templateDropdown.find('option[value="'+initialSource+'"]').length) {
                    $templateDropdown.val(initialSource);
                }
                initialSource = null;
            }
        }
        $templateDropdown.trigger('change.select2');
    });

    toggleSource($('#tipe_request').val());
    if($('#os').val() !== "") {
        $('#os').trigger('change');
    }

    var $divisiSelect = $('.select2-tags-divisi');
    var $picSelect = $('.select2-tags-pic');

    $divisiSelect.select2({ tags: true, placeholder: '-- Pilih atau Ketik Baru --', allowClear: true });
    $picSelect.select2({ tags: true, placeholder: '-- Pilih atau Ketik Baru --', allowClear: true });

    var uniqueDivisions = [];
    $.each(teamData, function(i, val) {
        if (val.team_name && $.inArray(val.team_name, uniqueDivisions) === -1) {
            uniqueDivisions.push(val.team_name);
            if ($divisiSelect.find('option[value="'+val.team_name+'"]').length === 0) {
                $divisiSelect.append(new Option(val.team_name, val.team_name, false, false));
            }
        }
    });

    $divisiSelect.on('change', function(e, isInitLoad) {
        var selectedDiv = $(this).val();
        var currentPic = isInitLoad ? INITIAL_PIC : $picSelect.val();

        $picSelect.empty().append(new Option('-- Pilih atau Ketik Baru --', ''));
        var filteredTeams = teamData.filter(t => t.team_name === selectedDiv);

        if (filteredTeams.length > 0) {
            var uniquePics = [];
            $.each(filteredTeams, function(i, val) {
                if (val.pic_name && $.inArray(val.pic_name, uniquePics) === -1) {
                    uniquePics.push(val.pic_name);
                    $picSelect.append(new Option(val.pic_name, val.pic_name, false, false));
                }
            });

            if (currentPic && $.inArray(currentPic, uniquePics) !== -1) {
                $picSelect.val(currentPic).trigger('change');
            } else if (uniquePics.length === 1 && !currentPic) {
                $picSelect.val(uniquePics[0]).trigger('change');
            } else if (currentPic) {
                $picSelect.append(new Option(currentPic, currentPic, true, true)).trigger('change');
            }
        } else {
            if (currentPic) {
                $picSelect.append(new Option(currentPic, currentPic, true, true)).trigger('change');
            }
        }
    });

    $picSelect.on('change', function() {
        var selectedDiv = $divisiSelect.val();
        var selectedPic = $(this).val();
        var match = teamData.find(t => t.team_name === selectedDiv && t.pic_name === selectedPic);
        if (match && match.pic_contact) {
            $('#contact').val(match.pic_contact);
        }
    });

    if (INITIAL_DIVISI) {
        $divisiSelect.trigger('change', [true]);
    }

    var $cmdbSelect = $('.select2-ajax-vm');
    $cmdbSelect.select2({
        placeholder: '-- Cari Nama Target VM di Master CMDB --',
        allowClear: true, minimumInputLength: 3,
        ajax: {
            url: URL_AJAX_SEARCH_VM, dataType: 'json', delay: 250, type: "POST",
            data: function(params) {
                var queryParams = { keyword: params.term };
                queryParams[CSRF_NAME_VAL] = $('#csrf_token_field').val();
                return queryParams;
            },
            processResults: function(data) {
                return { results: $.map(data.items, function(item) { return { text: item.text, id: item.id } })};
            }, cache: true
        }
    });

    $('#progres_tiket').on('change', function() {
        if ($(this).val() === 'Done') {
            $('#cmdb-warning').slideDown(200);
            $cmdbSelect.next('.select2-container').find('.select2-selection').css({'border':'2px solid #e74c3c', 'background-color':'#fadbd8'});
        } else {
            $('#cmdb-warning').slideUp(200);
            $cmdbSelect.next('.select2-container').find('.select2-selection').css({'border':'', 'background-color':''});
        }
    });
    if ($('#progres_tiket').val() === 'Done') $('#progres_tiket').trigger('change');

    var $datastoreSelect = $('.select2-ajax-datastore');
    $datastoreSelect.select2({
        tags: true, placeholder: '-- Ketik nama datastore --', minimumInputLength: 2,
        ajax: {
            url: URL_AJAX_DATASTORE, dataType: 'json', delay: 250, type: "POST",
            data: function(params) {
                var queryParams = { keyword: params.term };
                queryParams[CSRF_NAME_VAL] = $('#csrf_token_field').val();
                return queryParams;
            },
            processResults: function(data) { return { results: data }; }, cache: true
        }
    });

    $datastoreSelect.on('select2:select', function (e) { triggerMetadataCard(e.params.data); });

    function triggerMetadataCard(data) {
        var card = $('#datastore_metadata_card');
        if (data.capacity === undefined) { card.slideUp(200); return; }

        var cap = parseFloat(data.capacity) || 0;
        var used = parseFloat(data.used) || 0;
        var free = parseFloat(data.free) || 0;
        var prov = parseFloat(data.provisioned) || 0;
        var reqDisk = parseFloat($('#req_disk_val').val()) || 0;

        var projected_prov = prov + reqDisk;
        var projected_over = cap > 0 ? (projected_prov / cap) * 100 : 0;

        $('#ds_name_lbl').text(data.text);
        $('#ds_capacity_lbl').text(cap.toLocaleString('en-US', {maximumFractionDigits: 0}) + ' GB');
        $('#ds_used_lbl').text(used.toLocaleString('en-US', {maximumFractionDigits: 0}) + ' GB');
        $('#ds_free_lbl').text(free.toLocaleString('en-US', {maximumFractionDigits: 0}) + ' GB');
        $('#ds_prov_lbl').html('<span style="color:#94A3B8;">' + prov.toLocaleString('en-US', {maximumFractionDigits: 0}) + ' GB</span> &rarr; <span style="color: #2980b9; font-weight: bold;">' + projected_prov.toLocaleString('en-US', {maximumFractionDigits: 0}) + ' GB</span>');

        if (projected_over > 100) {
            $('#ds_over_lbl').html('<span style="color: #e74c3c; font-weight: bold;">' + Math.round(projected_over) + '% (TIDAK AMAN)</span>');
            $('#ds_header').css({'background-color': '#fadbd8', 'border-bottom': '1px solid #e74c3c', 'color': '#c0392b'});
            $('#ds_status_badge').html('<span class="label label-danger" style="font-size:11px; letter-spacing:0.5px;"><i class="fa fa-warning"></i> OVERPROVISIONED</span>');
        } else {
            $('#ds_over_lbl').html('<span style="color: #27ae60; font-weight: bold;">' + Math.round(projected_over) + '% (AMAN)</span>');
            $('#ds_header').css({'background-color': '#e8f4f8', 'border-bottom': '1px solid #2980b9', 'color': '#1f4e5f'});
            $('#ds_status_badge').html('<span class="label label-success" style="font-size:11px; letter-spacing:0.5px;"><i class="fa fa-check"></i> AMAN & DIREKOMENDASIKAN</span>');
        }
        card.slideDown(300);
    }

    $datastoreSelect.on('select2:unselect change', function() {
        if(!$(this).val()) $('#datastore_metadata_card').slideUp(200);
    });

    $('.target-disk-trigger').on('input', function() {
        var dsData = $datastoreSelect.select2('data');
        if (dsData.length > 0 && dsData[0].capacity !== undefined) triggerMetadataCard(dsData[0]);
    });

    // ========================================================================
    // [QA FIX] FORM SUBMISSION GUARD & CSRF SYNC
    // ========================================================================
    var isSubmitting = false;

    $('#formEditProvisioning').on('submit', function(e) {
        e.preventDefault();

        if (isSubmitting) {
            return false;
        }

        var isDone = $('#progres_tiket').val() === 'Done';
        var isCMDBEmpty = $('#id_virtual_machine').val() === '' || $('#id_virtual_machine').val() === null;
        var $form = $(this);

        if (isDone && isCMDBEmpty) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Anda memilih status "Done", tetapi belum menautkan CMDB Master VM!', confirmButtonColor: '#d33' });
            } else {
                alert('GAGAL: Anda memilih status "Done", tetapi belum menautkan CMDB Master VM!');
            }
            $('html, body').animate({ scrollTop: 0 }, 400);
            return false;
        }

        if (isDone && !isCMDBEmpty) {
            var cmdbNameText = $('#id_virtual_machine').select2('data')[0].text;
            var serverNameText = $('input[name="nama_server"]').val();
            if (cmdbNameText.toLowerCase().trim() !== serverNameText.toLowerCase().trim()) {
                var msgTolak = 'Nama VM dari CMDB (' + cmdbNameText + ') TIDAK SAMA dengan Nama Server yang diminta (' + serverNameText + ').';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Verifikasi Gagal', text: msgTolak, confirmButtonColor: '#d33' });
                } else {
                    alert('VERIFIKASI GAGAL: ' + msgTolak);
                }
                $('html, body').animate({ scrollTop: 0 }, 400);
                return false;
            }
        }

        var isValid = true;
        var firstInvalidField = null;
        $('.required-field').css({'border': '', 'background-color': ''});

        $form.find('.required-field').each(function() {
            if ($(this).val() === '' || $(this).val() === null) {
                isValid = false;
                if (!firstInvalidField) firstInvalidField = $(this);

                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).next('.select2-container').find('.select2-selection').css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                } else {
                    $(this).css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                }
            }
        });

        if (!isValid) {
            if ($('#custom-validation-toast').length === 0) {
                $('body').append('<div id="custom-validation-toast" style="position: fixed; top: 80px; right: 20px; z-index: 999999; background: #fff; border-left: 4px solid #e74c3c; border-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); padding: 15px 20px; min-width: 250px; max-width: 350px; display: none; color: #333;"></div>');
            }
            $('#custom-validation-toast').html("<div style='font-size: 13px; line-height: 1.4;'><i class='fa fa-times-circle' style='color: #e74c3c; font-size: 20px; margin-right: 12px; float: left; margin-top: 2px;'></i><div style='overflow: hidden;'><strong style='color: #c0392b; font-size: 14px;'>Validasi Form Gagal</strong><br><span style='color: #555; display: inline-block; margin-top: 4px;'>Harap lengkapi semua kolom yang ditandai dengan bintang merah (*).</span></div></div>").stop(true, true).fadeIn(300);
            clearTimeout(window.valToastTimer);
            window.valToastTimer = setTimeout(function() { $('#custom-validation-toast').fadeOut(400); }, 5000);

            if (firstInvalidField) $('html, body').animate({ scrollTop: firstInvalidField.offset().top - 120 }, 400);
            return false;

        } else {
            var btnTarget = $('#btnSubmitUpdate');
            var originalText = btnTarget.html();

            btnTarget.html('<i class="fa fa-spinner fa-spin"></i> Memvalidasi...').prop('disabled', true);
            isSubmitting = true;

            var payloadData = {
                id_tiket: $('input[name="id_tiket"]').val(),
                no_tiket: $('input[name="no_tiket"]').val(),
                nama_server: $('input[name="nama_server"]').val(),
                hostname: $('input[name="hostname"]').val()
            };
            payloadData[CSRF_NAME_VAL] = $('#csrf_token_field').val();

            $.ajax({
                url: URL_AJAX_DUPLICATE,
                type: 'POST',
                data: payloadData,
                dataType: 'json',
                success: function(res) {
                    if(res.csrf_hash) $('#csrf_token_field').val(res.csrf_hash); // Live CSRF Sync

                    if (res.status === 'duplicate') {
                        isSubmitting = false;
                        btnTarget.html(originalText).prop('disabled', false);

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Update Ditolak (Duplikat)',
                                html: '<span style="font-size: 14px;">' + res.message + '</span>',
                                confirmButtonColor: '#d33',
                                confirmButtonText: '<i class="fa fa-times"></i> Tutup Peringatan'
                            });
                        } else {
                            alert('Data Duplikat: \n' + res.message.replace(/(<([^>]+)>)/gi, ""));
                        }

                        $('input[name="nama_server"], input[name="hostname"]').css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                        $('html, body').animate({ scrollTop: $('input[name="nama_server"]').offset().top - 120 }, 400);

                    } else {
                        btnTarget.html('<i class="fa fa-spinner fa-spin"></i> Menyimpan Perubahan...');
                        $form[0].submit();
                    }
                },
                error: function() {
                    alert("Terjadi kesalahan jaringan saat memvalidasi data.");
                    isSubmitting = false;
                    btnTarget.html(originalText).prop('disabled', false);
                }
            });
        }
    });

    $(document).on('input change', '.required-field', function() {
        if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).next('.select2-container').find('.select2-selection').css({'border': '', 'background-color': ''});
        } else {
            $(this).css({'border': '', 'background-color': ''});
        }
    });
});
</script>
