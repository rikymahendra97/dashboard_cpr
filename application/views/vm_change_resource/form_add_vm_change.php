<?php
/**
 * ============================================================================
 * File Name    : form_add_vm_change.php
 * Modul        : VM Change Resource
 * Tujuan       : Antarmuka pencatatan request penambahan/pengurangan kapasitas.
 * Arsitektur   : - Strict Null Checking pada JSON Team Engine.
 *                - AJAX Radar Interceptor (Pendeteksi Insiden Aktif).
 *                - Double Guard Submit Button (Anti-Spam Click).
 * ============================================================================
 */

$id = $id ?? [];
$user_session = $user_session ?? [];
$list_vm = $list_vm ?? [];
$master_team = $master_team ?? [];
$duplicate_data = $duplicate_data ?? [];
?>
<div class="right_col" role="main">
    <div class="">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">

                <div style="margin-bottom: 15px;">
                    <a href="<?= site_url(
                        "vm_change_resource",
                    ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); font-weight: bold; color: #555;">
                        <i class="fa fa-arrow-left"></i> Kembali ke Daftar Log
                    </a>
                </div>

                <!-- SWEETALERT BFCACHE-SAFE -->
                <div id="alert-container">
                    <?php
                    $alerts = $this->session->flashdata("alerts") ?? [];
                    if (empty($alerts) && $this->session->flashdata("success")) {
                        $alerts = [["success", $this->session->flashdata("success")]];
                    }
                    if (empty($alerts) && $this->session->flashdata("error")) {
                        $alerts = [["error", $this->session->flashdata("error")]];
                    }

                    $this->session->unset_userdata("alerts");
                    $this->session->unset_userdata("success");
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

                <section class="panel" style="box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 8px;">
                    <header class="panel-heading" style="background-color: #f5f7fa; padding: 18px 20px; border-bottom: 1px solid #e6e9ed; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        <h3 style="margin: 0; font-weight: bold; color: #2A3F54; font-size: 18px;">
                            <i class="fa fa-plus-circle"></i> Catat Request Perubahan Resource VM
                        </h3>
                    </header>

                    <div class="panel-body" style="padding: 30px;">
                        <form action="<?php echo site_url(
                            "vm_change_resource/simpan_data",
                        ); ?>" method="post" id="formChangeResource" novalidate>
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" id="csrf_token">
                            <input type="hidden" name="resolve_incident_id" id="resolve_incident_id" value="">

                            <?php if (!empty($duplicate_data)): ?>
                                <div class="alert alert-dismissible fade in" style="background-color: #e8f4f8; color: #1f4e5f; border-left: 5px solid #2980b9; box-shadow: 0 1px 2px rgba(0,0,0,0.05); margin-bottom: 25px; border-radius: 4px;">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="color: #1f4e5f; opacity: 0.8; text-shadow: none;"><span aria-hidden="true">×</span></button>
                                    <i class="fa fa-info-circle" style="font-size: 16px; margin-right: 5px; color: #2980b9;"></i>
                                    <strong>Informasi:</strong> Mode Duplikat Aktif. Silakan pilih konfigurasi Target VM baru. Data informasi tiket telah disalin.
                                </div>
                            <?php endif; ?>

                            <h4 style="font-weight: bold; color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 20px; font-size: 15px;">
                                <i class="fa fa-file-text-o"></i> Informasi Request & Peminta
                            </h4>

                            <div class="row">
                                <div class="col-md-6 col-sm-12" style="border-right: 1px solid #edf2f7; padding-right: 20px;">
                                    <h5 class="font-bold text-primary" style="margin-top: 0; margin-bottom: 15px;"><i class="fa fa-ticket"></i> A. Data Tiket & Skenario</h5>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold">No Tiket <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control required-field" name="no_tiket" required placeholder="SCR20260099" value="<?= html_escape(
                                                    $duplicate_data["no_tiket_eksternal"] ?? "",
                                                ) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold">Jenis Skenario <span class="text-danger">*</span></label>
                                                <select class="form-control required-field" name="jenis_perubahan" required>
                                                    <option value="">-- Pilih Skenario --</option>
                                                    <option value="Upgrade" <?= ($duplicate_data[
                                                        "jenis_perubahan"
                                                    ] ??
                                                        "") ==
                                                    "Upgrade"
                                                        ? "selected"
                                                        : "" ?>>Upgrade Resource</option>
                                                    <option value="Downgrade" <?= ($duplicate_data[
                                                        "jenis_perubahan"
                                                    ] ??
                                                        "") ==
                                                    "Downgrade"
                                                        ? "selected"
                                                        : "" ?>>Downgrade Resource</option>
                                                    <option value="Mixed" <?= ($duplicate_data[
                                                        "jenis_perubahan"
                                                    ] ??
                                                        "") ==
                                                    "Mixed"
                                                        ? "selected"
                                                        : "" ?>>Mixed (Up/Down Bersamaan)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="font-bold">Link Tiket Eksternal <span class="text-muted" style="font-weight: normal;">(Opsional)</span></label>
                                        <input type="url" class="form-control" name="link_tiket" placeholder="https://iris.bri.co.id/browse/..." value="<?= html_escape(
                                            $duplicate_data["link_tiket_eksternal"] ?? "",
                                        ) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="font-bold">Deskripsi Permintaan <span class="text-danger">*</span></label>
                                        <textarea class="form-control required-field" name="deskripsi_permintaan" rows="3" required placeholder="Alasan utama dari tiket..."><?= html_escape(
                                            $duplicate_data["keterangan_request_asli"] ?? "",
                                        ) ?></textarea>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-12" style="padding-left: 20px;">
                                    <h5 class="font-bold text-success" style="margin-top: 0; margin-bottom: 15px;"><i class="fa fa-user"></i> B. Informasi Requestor</h5>

                                    <div style="background-color: #f9fbfd; padding: 20px; border: 1px solid #dae1e7; border-radius: 6px;">
                                        <div class="form-group">
                                            <label class="font-bold text-primary">Fungsi / Departemen <span class="text-danger">*</span></label>
                                            <select class="form-control" id="selectTeamGroup" style="width: 100%;" required>
                                                <option value="">-- Pilih Fungsi / Departemen --</option>
                                            </select>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-7">
                                                <div class="form-group" style="margin-bottom: 10px;">
                                                    <label class="font-bold text-info">Requestor / PIC <span class="text-danger">*</span></label>
                                                    <select class="form-control required-field" name="id_team_requestor" id="id_team_requestor" style="width: 100%;" required disabled>
                                                        <option value="">-- Pilih Requestor / PIC --</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group" style="margin-bottom: 10px;">
                                                    <label class="text-muted font-bold" style="font-size: 12px;">Kontak PIC</label>
                                                    <input type="text" id="info_kontak_pic" class="form-control input-sm" readonly placeholder="-" style="background-color: #eef2f5; font-size: 12px; color: #475569; font-weight: bold; border-color: #d1e0ec;">
                                                </div>
                                            </div>
                                        </div>

                                        <div style="margin-top: 10px; padding-top: 12px; border-top: 1px dashed #cbd5e1;">
                                            <button type="button" class="btn btn-default btn-xs" id="btn-quick-add-team" style="margin:0; font-weight:bold; background-color: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                <i class="fa fa-plus text-success"></i> Tambah Data
                                            </button>
                                            <p class="text-muted" style="font-size: 11px; margin: 6px 0 0 0; line-height: 1.3;">
                                                * Klik tombol ini apabila Fungsi/Departemen atau nama Requestor belum terdaftar.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group" style="background-color: #fcf8e3; border-left: 3px solid #f0ad4e; padding: 15px; margin-top: 5px; margin-bottom: 20px; border-radius: 4px;">
                                        <div class="checkbox" style="margin: 0;">
                                            <label style="font-weight: bold; color: #8a6d3b;">
                                                <input type="checkbox" id="toggle_backdate" value="1">
                                                <i class="fa fa-clock-o"></i> Waktu Mundur (Backdate Log Pembuatan)
                                            </label>
                                            <p style="font-size: 11px; color: #777; margin-top: 5px; padding-left: 20px; font-style: italic;">
                                                * Centang fitur ini jika Anda terlambat melakukan input tiket ke sistem dan perlu menyesuaikan "Tanggal Dibuat" secara historis.
                                            </p>
                                        </div>
                                        <div id="backdate_container" style="display: none; padding-left: 20px; margin-top: 15px;">
                                            <label class="font-bold text-warning">Tanggal Dibuat (Create)</label>
                                            <input type="datetime-local" class="form-control" name="created_at" id="input_created_at" style="max-width: 250px;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h4 style="font-weight: bold; color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 20px; margin-top: 30px; font-size: 15px;">
                                <i class="fa fa-server"></i> Target Virtual Machine
                            </h4>

                            <div style="background-color: #fdfdfd; padding: 20px; border: 1px solid #eee; border-radius: 6px; margin-bottom: 20px;">

                                <div id="radar_incident_alert" style="display: none; background-color: #fffdf2; border-left: 5px solid #f1c40f; color: #333; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 4px; margin-bottom: 20px; padding: 15px;">
                                    <h4 style="font-weight: bold; font-size: 14px; margin-top:0; margin-bottom: 5px; color: #d35400;">
                                        <i class="fa fa-exclamation-triangle animated infinite pulse"></i> Sistem Terintegrasi: Tiket Insiden Terdeteksi Aktif!
                                    </h4>
                                    <p style="margin-bottom: 5px; font-size: 12.5px; color: #555;">
                                        Virtual Machine ini terdeteksi memiliki tiket insiden utilisasi tinggi yang sedang <b>OPEN / ON PROGRESS</b> di sistem dengan nomor tiket: <span id="radar_incident_ticket" class="label label-danger" style="font-size:11px;">-</span>
                                    </p>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="font-bold text-primary">Target VM <span class="text-danger">*</span></label>
                                            <select class="form-control select2-vm required-field" name="id_vm" id="id_vm" style="width: 100%;" required>
                                                <option value="">-- Cari Nama VM / IP --</option>
                                                <optgroup label="🏢 SITE TBN">
                                                    <?php foreach ($list_vm as $vm):
                                                        if ($vm["id_site"] === "TBN"): ?>
                                                            <option value="<?= html_escape(
                                                                $vm["id_virtual_machine"],
                                                            ) ?>">
                                                                <?= html_escape(
                                                                    $vm["virtual_machine_name"],
                                                                ) ?> | IP: <?= html_escape(
     $vm["ip_address"],
 ) ?>
                                                            </option>
                                                        <?php endif;
                                                    endforeach; ?>
                                                </optgroup>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group" style="background-color: #fffdf2; border-left: 3px solid #f1c40f; padding: 10px; margin-top: 5px; margin-bottom: 15px; border-radius: 4px;">
                                            <div class="checkbox" style="margin: 0;">
                                                <label style="font-weight: bold; color: #d35400;">
                                                    <input type="checkbox" name="is_susulan" id="is_susulan" value="1">
                                                    <i class="fa fa-warning"></i> Ini adalah Tiket Susulan (Emergency Change)
                                                </label>
                                                <p style="font-size: 11px; color: #777; margin-top: 5px; padding-left: 20px; font-style: italic;">
                                                    * Centang ini jika eksekusi sudah terlanjur dilakukan di vCenter/Server. Anda dapat membuka kunci kolom di bawah dan mengubah nilai "Current Resource" secara manual.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" style="margin-top: 10px;">
                                    <div class="col-md-3 col-sm-6">
                                        <label class="text-muted">Current vCPU</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="current_cpu" id="curr_cpu_view" readonly placeholder="-">
                                            <span class="input-group-addon font-bold">Core</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <label class="text-muted">Current RAM</label>
                                        <div class="input-group">
                                            <input type="number" step="1" class="form-control" name="current_ram_gb" id="curr_ram_view" readonly placeholder="-">
                                            <span class="input-group-addon font-bold">GB</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <label class="text-muted">Provisioned</label>
                                        <div class="input-group">
                                            <input type="number" step="1" class="form-control" name="current_disk_gb" id="curr_disk_view" readonly placeholder="-">
                                            <span class="input-group-addon font-bold">GB</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <label class="text-muted">Environment</label>
                                        <input type="text" class="form-control" name="snapshot_env" id="curr_env_view" readonly placeholder="-">
                                    </div>
                                </div>
                                <small class="help-block text-info" id="loading_text" style="display:none;"><i class="fa fa-spinner fa-spin"></i> Menarik spek VM & Memindai Insiden Aktif...</small>
                            </div>

                            <h4 style="font-weight: bold; color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 20px; margin-top: 30px; font-size: 15px;">
                                <i class="fa fa-bar-chart"></i> Target Core / RAM Baru
                            </h4>
                            <div class="row">
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group">
                                        <label class="font-bold text-success">Target vCPU (Core) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control required-field" name="target_cpu" id="target_cpu" value="0" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="form-group">
                                        <label class="font-bold text-success">Target RAM (GB) <span class="text-danger">*</span></label>
                                        <input type="number" step="1" class="form-control required-field" name="target_ram_gb" id="target_ram" value="0" min="0" required>
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted" style="font-size: 12px; font-style: italic;">* Biarkan nilai tetap (sama dengan current) jika tidak ada perubahan.</p>

                            <h4 style="font-weight: bold; color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 20px; margin-top: 30px; font-size: 15px;">
                                <i class="fa fa-hdd-o"></i> Manajemen Partisi Disk <small style="font-weight:normal;">(Opsional)</small>
                            </h4>
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="disk-container"></div>
                                    <button type="button" class="btn btn-success btn-sm font-bold" id="btn-add-disk" style="border-radius: 4px;"><i class="fa fa-plus"></i> Tambah Partisi Storage</button>
                                </div>
                            </div>

                            <div class="row" style="margin-top: 40px;">
                                <div class="col-md-12 text-right">
                                    <hr style="border-top: 1px solid #e5e5e5; margin-bottom: 20px;">
                                    <a href="<?= site_url(
                                        "vm_change_resource",
                                    ) ?>" class="btn btn-default font-bold btn-lg" style="border-radius: 4px; margin-right: 10px;">
                                        <i class="fa fa-arrow-left"></i> Batal
                                    </a>
                                    <button type="submit" class="btn btn-primary font-bold btn-lg" id="btnSubmitAdd" style="border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">
                                        <i class="fa fa-save"></i> Simpan Request
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<!-- Modal Quick Add Team -->
<div class="modal fade" id="modalQuickAddTeam" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
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
                                <label style="font-size: 12px; font-weight:bold; color: #777;">Nama Requestor/PIC <small class="text-muted" style="font-weight: normal;">(Opsional)</small></label>
                                <input type="text" class="form-control" id="qa_pic_name" placeholder="Nama lengkap PIC..." style="border-radius: 4px;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-size: 12px; font-weight:bold; color: #777;">Kontak PIC <small class="text-muted" style="font-weight: normal;">(Opsional)</small></label>
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

<!-- Injeksi JS Variabel Linter-Safe -->
<script>
    var TEAM_DATA_JSON_STRING = '<?= json_encode(
        $master_team ?? [],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
    ) ?>';
    var URL_AJAX_DUPLICATE    = '<?= site_url("vm_change_resource/ajax_check_duplicate") ?>';
    var CSRF_NAME_VAL         = '<?= $this->security->get_csrf_token_name() ?>';
</script>

<script>
$(document).ready(function() {

    var $flashElem = $('#swal-flash-data');
    if ($flashElem.length > 0) {
        var swalType = $flashElem.data('type');
        var swalMessage = $flashElem.data('message');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: swalType,
                title: swalType === 'error' ? 'Gagal' : 'Berhasil',
                text: swalMessage,
                timer: 3500,
                showConfirmButton: false
            });
        }
        if (window.history.replaceState) { window.history.replaceState(null, null, window.location.href); }
        $flashElem.remove();
    }

    var rawTeamData = [];
    var groupedTeams = {};
    try { rawTeamData = JSON.parse(TEAM_DATA_JSON_STRING); } catch(e) { console.error(e); }

    rawTeamData.forEach(function(item) {
        var keyStr = item.team_code ? item.team_code : item.team_name;
        var labelStr = (item.team_code ? '[' + item.team_code + '] ' : '') + item.team_name;
        if (!groupedTeams[keyStr]) { groupedTeams[keyStr] = { label: labelStr, pics: [] }; }
        groupedTeams[keyStr].pics.push(item);
    });

    var savedIdTeam = "<?= html_escape($duplicate_data["id_team_requestor"] ?? "") ?>";
    var initialGroupKey = '';

    if (savedIdTeam !== '') {
        rawTeamData.forEach(function(item) {
            if (item.id_team == savedIdTeam) initialGroupKey = item.team_code ? item.team_code : item.team_name;
        });
    }

    var $groupSelect = $('#selectTeamGroup');
    var $picSelect = $('#id_team_requestor');

    $.each(groupedTeams, function(key, groupObj) {
        var isSelected = (key === initialGroupKey) ? 'selected' : '';
        $groupSelect.append('<option value="' + key + '" ' + isSelected + '>' + groupObj.label + '</option>');
    });

    $groupSelect.select2({ placeholder: '-- Pilih Fungsi / Departemen --', width: '100%' });

    $groupSelect.on('change', function() {
        var selectedKey = $(this).val();
        if ($picSelect.hasClass("select2-hidden-accessible")) $picSelect.select2('destroy');
        $picSelect.empty().append('<option value="">-- Pilih Requestor / PIC --</option>');
        $('#info_kontak_pic').val('');

        if (selectedKey && groupedTeams[selectedKey]) {
            var picList = groupedTeams[selectedKey].pics;
            $picSelect.prop('disabled', false);

            picList.forEach(function(p) {
                var picNameDisplay = (p.pic_name && p.pic_name !== '-') ? p.pic_name : 'Tim Umum (Tanpa Spesifik PIC)';
                var isSelected = (p.id_team == savedIdTeam) ? 'selected' : '';
                $picSelect.append('<option value="' + p.id_team + '" data-contact="' + (p.pic_contact || '-') + '" ' + isSelected + '>' + picNameDisplay + '</option>');
            });

            $picSelect.select2({ placeholder: '-- Pilih Requestor / PIC --', width: '100%' });

            if (picList.length === 1 && !savedIdTeam) {
                $picSelect.val(picList[0].id_team).trigger('change');
            } else if (savedIdTeam) {
                $picSelect.val(savedIdTeam).trigger('change');
            }
        } else {
            $picSelect.prop('disabled', true);
            $picSelect.select2({ placeholder: '-- Pilih Requestor / PIC --', width: '100%' });
        }
    });

    $picSelect.on('change', function() {
        var selectedDOM = $(this).find('option:selected')[0];
        if (selectedDOM && $(this).val() !== '') {
            var picContact = selectedDOM.getAttribute('data-contact');
            $('#info_kontak_pic').val(picContact && picContact !== '-' ? picContact : 'Tidak Ada Kontak');
        } else {
            $('#info_kontak_pic').val('');
        }
    });

    if (initialGroupKey !== '') $groupSelect.trigger('change');
    else $picSelect.select2({ placeholder: '-- Pilih Requestor / PIC --', width: '100%' }).prop('disabled', true);

    function showEnterpriseToast(type, title, message) {
        $('.enterprise-floating-toast').remove();
        var borderColors = type === 'success' ? '#26b99a' : '#e74c3c';
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var titleColor = type === 'success' ? '#169f85' : '#c0392b';

        var toastHtml = '<div class="enterprise-floating-toast" style="position: fixed; top: 80px; right: 20px; z-index: 999999; background: #fff; border-left: 4px solid ' + borderColors + '; border-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); padding: 15px 20px; min-width: 280px; max-width: 380px; color: #333;">' +
            '<div style="font-size: 13px; line-height: 1.4;"><i class="fa ' + icon + '" style="color: ' + borderColors + '; font-size: 20px; margin-right: 12px; float: left; margin-top: 2px;"></i>' +
            '<div style="overflow: hidden; Margins: 0;"><strong style="color: ' + titleColor + '; font-size: 14px;">' + title + '</strong><br><span style="color: #555; display: inline-block; margin-top: 4px;">' + message + '</span></div></div></div>';

        $('body').append(toastHtml);
        setTimeout(function() { $('.enterprise-floating-toast').fadeOut(400, function() { $(this).remove(); }); }, 5000);
    }

    $('#btn-quick-add-team').on('click', function(e) {
        e.preventDefault();
        $('#formQuickAddTeam')[0].reset();
        $('#qa_team_name, #qa_team_code').css('border', '');
        $('#qa_error_container').html('');
        $('#modalQuickAddTeam').modal('show');
        setTimeout(function() { $('#qa_team_name').focus(); }, 500);
    });

    $('#qa_team_code').on('input', function() { $(this).val($(this).val().toUpperCase()); });

    $('#formQuickAddTeam').on('submit', function(e) {
        e.preventDefault();
        var teamName = $('#qa_team_name').val().trim();
        var teamCode = $('#qa_team_code').val().trim().toUpperCase();
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

        var payload = { team_name: teamName, team_code: teamCode, pic_name: picName, pic_contact: picContact };
        payload[$('#csrf_token').attr('name')] = $('#csrf_token').val();

        $.ajax({
            url: '<?= site_url("vm_change_resource/ajax_quick_add_team") ?>',
            type: 'POST', data: payload, dataType: 'json',
            success: function(res) {
                if (res.status) {
                    var newItem = { id_team: res.id_team, team_code: res.team_code, team_name: res.team_name, pic_name: picName, pic_contact: picContact };
                    rawTeamData.push(newItem);

                    var keyStr = res.team_code ? res.team_code : res.team_name;
                    var labelStr = (res.team_code ? '[' + res.team_code + '] ' : '') + res.team_name;

                    if (!groupedTeams[keyStr]) {
                        groupedTeams[keyStr] = { label: labelStr, pics: [] };
                        var newOption = new Option(labelStr, keyStr, false, false);
                        $groupSelect.append(newOption).trigger('change');
                    }
                    groupedTeams[keyStr].pics.push(newItem);

                    savedIdTeam = res.id_team;
                    $groupSelect.val(keyStr).trigger('change');

                    $('#modalQuickAddTeam').modal('hide');
                    btnSave.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan ke Master');
                    showEnterpriseToast('success', 'Penyimpanan Berhasil', res.message);
                } else {
                    $('#qa_error_container').html('<div class="alert alert-danger" style="padding:8px; margin:0; font-size:12px;"><i class="fa fa-times-circle"></i> ' + res.message + '</div>');
                    btnSave.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan ke Master');
                    showEnterpriseToast('error', 'Gagal Menyimpan', res.message);
                }
            },
            error: function() {
                $('#qa_error_container').html('<div class="alert alert-danger" style="padding:8px; margin:0; font-size:12px;"><i class="fa fa-wifi"></i> Gagal terhubung ke server.</div>');
                btnSave.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan ke Master');
                showEnterpriseToast('error', 'Gangguan Jaringan', 'Gagal berkomunikasi dengan server pusat.');
            }
        });
    });

    $('.select2-vm').select2({ placeholder: "-- Ketik Nama VM atau IP Address --", allowClear: true });

    function getWibISOString() {
        var date = new Date();
        var wibOffset = 7 * 60;
        var localOffset = date.getTimezoneOffset();
        date.setMinutes(date.getMinutes() + localOffset + wibOffset);
        return date.toISOString().slice(0, 16);
    }

    $('#toggle_backdate').on('change', function() {
        if ($(this).is(':checked')) {
            $('#backdate_container').slideDown();
            if ($('#input_created_at').val() === '') {
                $('#input_created_at').val(getWibISOString());
            }
        } else {
            $('#backdate_container').slideUp();
            $('#input_created_at').val('');
        }
    });

    $('#id_vm').on('change', function() {
        var vm_id = $(this).val();
        $(this).next('.select2-container').find('.select2-selection').css({ 'border': '', 'background-color': '' });
        $('.error-inline').remove();

        if (!vm_id) {
            $('#resolve_incident_id').val('');
            $('#radar_incident_alert').slideUp(200);
            return;
        }

        $('#loading_text').show();
        var formData = { id_virtual_machine: vm_id };
        formData['<?php echo $this->security->get_csrf_token_name(); ?>'] = $('#csrf_token').val();

        $.ajax({
            url: "<?php echo site_url("vm_change_resource/ajax_get_vm_spec"); ?>",
            type: "POST", data: formData, dataType: "json",
            success: function(res) {
                $('#loading_text').hide();
                if (res.status && res.data) {
                    var ramGb = Math.round(res.data.memory_mb / 1024);

                    if (!$('#is_susulan').is(':checked')) {
                        $('#curr_cpu_view').val(res.data.cpu_count);
                        $('#curr_ram_view').val(ramGb);
                        $('#curr_disk_view').val(res.data.provisioned_gb);
                    }
                    $('#curr_env_view').val(res.data.environment);
                    $('#target_cpu').val(res.data.cpu_count);
                    $('#target_ram').val(ramGb);

                    var targetUrl = '<?= site_url(
                        "vm_incident/check_active_incident_json/",
                    ) ?>' + vm_id;
                    $.ajax({
                        url: targetUrl, type: 'GET', dataType: 'json',
                        success: function(incidentRes) {
                            if (incidentRes.has_incident) {
                                $('#resolve_incident_id').val(incidentRes.incident_data.id_incident);
                                $('#radar_incident_ticket').text(incidentRes.incident_data.no_tiket_insiden);
                                $('#radar_incident_alert').slideDown(300);
                            } else {
                                $('#resolve_incident_id').val('');
                                $('#radar_incident_alert').slideUp(200);
                            }
                        },
                        error: function() {
                            $('#resolve_incident_id').val('');
                            $('#radar_incident_alert').slideUp(200);
                        }
                    });
                }
            },
            error: function() { $('#loading_text').hide(); }
        });
    });

    $('#is_susulan').on('change', function() {
        if ($(this).is(':checked')) {
            $('#curr_cpu_view, #curr_ram_view, #curr_disk_view').prop('readonly', false).css({'background-color': '#fff', 'border-color': '#f1c40f'});
        } else {
            $('#curr_cpu_view, #curr_ram_view, #curr_disk_view').prop('readonly', true).css({'background-color': '#eee', 'border-color': '#ccc'});
            $('#id_vm').trigger('change');
        }
    });

    var isSubmitting = false;

    $('#formChangeResource').on('submit', function(e) {
        if (isSubmitting) return false;

        var isValid = true;
        var firstInvalidField = null;
        var $form = $(this);

        $('.error-inline').remove();
        $('.required-field, #disk-container input, #disk-container select').css({'border': '', 'background-color': ''});

        $(this).find('.required-field').each(function() {
            if ($(this).val() === '' || $(this).val() === null) {
                isValid = false;
                if (!firstInvalidField) { firstInvalidField = $(this); }
                if ($(this).hasClass('select2-vm') || $(this).hasClass('select2-hidden-accessible')) {
                    $(this).next('.select2-container').find('.select2-selection').css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                } else {
                    $(this).css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                }
            }
        });

        if (!isValid) {
            e.preventDefault();

            if ($('#custom-validation-toast').length === 0) {
                var toastBox = '<div id="custom-validation-toast" style="position: fixed; top: 80px; right: 20px; z-index: 999999; background: #fff; border-left: 4px solid #e74c3c; border-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); padding: 15px 20px; min-width: 250px; max-width: 350px; display: none; color: #333;"></div>';
                $('body').append(toastBox);
            }
            var msgHtml = "<div style='font-size: 13px; line-height: 1.4;'><i class='fa fa-times-circle' style='color: #e74c3c; font-size: 20px; margin-right: 12px; float: left; margin-top: 2px;'></i><div style='overflow: hidden;'><strong style='color: #c0392b; font-size: 14px;'>Validasi Form Gagal</strong><br><span style='color: #555; display: inline-block; margin-top: 4px;'>Harap lengkapi semua kolom yang ditandai merah sebelum menyimpan update data.</span></div></div>";

            $('#custom-validation-toast').html(msgHtml).stop(true, true).fadeIn(300);
            clearTimeout(window.valToastTimer);
            window.valToastTimer = setTimeout(function() { $('#custom-validation-toast').fadeOut(400); }, 5000);

            if (firstInvalidField) {
                $('html, body').animate({ scrollTop: firstInvalidField.offset().top - 120 }, 400);
            }
        } else {
            e.preventDefault();
            var btnTarget = $(this).find('button[type="submit"]');
            var originalText = btnTarget.html();

            btnTarget.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memvalidasi Data...');
            isSubmitting = true;

            var payloadData = {
                no_tiket: $('input[name="no_tiket"]').val(),
                id_vm: $('#id_vm').val(),
                id_change: 0
            };
            payloadData[CSRF_NAME_VAL] = $('#csrf_token').val();

            $.ajax({
                url: URL_AJAX_DUPLICATE,
                type: 'POST', data: payloadData, dataType: 'json',
                success: function(res) {
                    if(res.csrf_hash) { $('#csrf_token').val(res.csrf_hash); }

                    if (res.status === 'duplicate') {
                        isSubmitting = false;
                        btnTarget.html(originalText).prop('disabled', false);

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error', title: 'Data Ditolak',
                                html: '<span style="font-size: 14px;">' + res.message + '</span>',
                                confirmButtonColor: '#d33', confirmButtonText: '<i class="fa fa-times"></i> Tutup'
                            });
                        } else {
                            alert('Data Ditolak: \n' + res.message.replace(/(<([^>]+)>)/gi, ""));
                        }

                        $('input[name="no_tiket"]').css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                        $('#id_vm').next('.select2-container').find('.select2-selection').css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                        $('html, body').animate({ scrollTop: $('input[name="no_tiket"]').offset().top - 120 }, 400);
                    } else {
                        btnTarget.html('<i class="fa fa-spinner fa-spin"></i> Menyimpan Request...');
                        $form[0].submit();
                    }
                },
                error: function() {
                    if (typeof Swal !== 'undefined') Swal.fire({icon: 'error', title: 'Gangguan Jaringan', text: 'Gagal memvalidasi data ganda. Coba lagi.'});
                    isSubmitting = false;
                    btnTarget.html(originalText).prop('disabled', false);
                }
            });
        }
    });

    $('#btn-add-disk').off('click').on('click', function(e) {
        e.preventDefault();
        var diskHtml = `
        <div class="disk-row row" style="margin-bottom:10px; background-color:#f9f9f9; padding:15px; border-radius:5px; border: 1px solid #e5e5e5;">
            <div class="col-md-3 col-sm-6"><label style="font-size:12px;">Tipe Eksekusi</label>
                <select class="form-control required-field" name="disk_tipe[]" required>
                    <option value="">-- Pilih Tipe --</option><option value="Extend Drive">Extend Drive</option><option value="New Drive">New Drive</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-6"><label style="font-size:12px;">Drive / Label</label><input type="text" class="form-control required-field" name="disk_nama[]" placeholder="D:\\ atau /var" required></div>
            <div class="col-md-3 col-sm-6"><label class="text-success" style="font-size:12px;">Ditambah</label><div class="input-group"><input type="number" step="0.01" class="form-control required-field" name="disk_additional[]" required><span class="input-group-addon font-bold">GB</span></div></div>
            <div class="col-md-3 col-sm-6"><label class="text-success" style="font-size:12px;">End State Target</label><div class="input-group"><input type="number" step="0.01" class="form-control required-field" name="disk_end_state[]" required><span class="input-group-addon font-bold">GB</span></div></div>
            <div class="col-md-12 text-right"><button type="button" class="btn btn-danger btn-xs btn-remove-disk" style="margin-top: 5px;"><i class="fa fa-trash"></i> Hapus Partisi</button></div>
        </div>`;
        $('#disk-container').append(diskHtml);
    });

    $('#disk-container').off('click', '.btn-remove-disk').on('click', '.btn-remove-disk', function(e) {
        e.preventDefault();
        $(this).closest('.disk-row').remove();
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
