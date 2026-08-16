<?php
/**
 * =============================================================================
 * File Name    : form_edit_vm_restart.php
 * Modul        : VM Restart
 * Purpose      : Halaman form untuk mengedit/koreksi data Master Request Restart VM.
 * Architecture : BFCache-Safe DOM Extraction, XSS Safe JSON, Absolute DOM Selectors
 * =============================================================================
 */

// ========================================================================
// [ENTERPRISE FIX]: Intelephense Linter Guard
// ========================================================================
$id = $id ?? [];
$detail = $detail ?? [];
$master_vm = $master_vm ?? [];
$master_team = $master_team ?? [];

$role = isset($id["id_role"]) ? (int) $id["id_role"] : 99;
$can_edit_execute = can_edit_execute($role);
$can_verify_delete = can_verify_delete($role);
?>

<div class="right_col" role="main">
    <div class="">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">

                <div style="margin-bottom: 15px;">
                    <a href="<?= site_url(
                        "vm_restart",
                    ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); font-weight: bold; color: #555;">
                        <i class="fa fa-arrow-left"></i> Kembali ke Daftar Log
                    </a>
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

                <section class="panel" style="box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2a632; border-radius: 8px;">
                    <header class="panel-heading" style="background-color: #fcf8e3; padding: 18px 20px; border-bottom: 1px solid #faebcc; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        <h3 style="margin: 0; font-weight: bold; color: #8a6d3b; font-size: 18px;">
                            <i class="fa fa-edit"></i> Edit Request Restart VM
                        </h3>
                    </header>

                    <div class="panel-body" style="padding: 30px;">
                        <form action="<?= site_url(
                            "vm_restart/update",
                        ) ?>" method="post" id="formRestartVM" novalidate>
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" id="csrf_token">
                            <input type="hidden" name="id_restart" value="<?= html_escape(
                                $detail["id_restart"] ?? "",
                            ) ?>">
                            <input type="hidden" name="resolve_incident_id" id="resolve_incident_id" value="<?= html_escape(
                                $detail["id_incident"] ?? "",
                            ) ?>">

                            <h4 style="font-weight: bold; color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 20px; font-size: 15px;">
                                <i class="fa fa-file-text-o"></i> Informasi Request & Skenario
                            </h4>

                            <div class="row">
                                <div class="col-md-6 col-sm-12" style="border-right: 1px solid #edf2f7; padding-right: 20px;">
                                    <h5 class="font-bold text-primary" style="margin-top: 0; margin-bottom: 15px;"><i class="fa fa-ticket"></i> A. Data Tiket & Skenario</h5>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold">No Tiket IRIS <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control required-field" id="no_tiket_input" name="no_tiket_iris" required placeholder="SCR2026..." value="<?= html_escape(
                                                    $detail["no_tiket_iris"] ?? "",
                                                ) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold">Jenis Downtime <span class="text-danger">*</span></label>
                                                <select class="form-control required-field" name="jenis_downtime" required>
                                                    <option value="Planned" <?= ($detail[
                                                        "jenis_downtime"
                                                    ] ??
                                                        "") ==
                                                    "Planned"
                                                        ? "selected"
                                                        : "" ?>>Planned (Pemeliharaan/Patching)</option>
                                                    <option value="Unplanned" <?= ($detail[
                                                        "jenis_downtime"
                                                    ] ??
                                                        "") ==
                                                    "Unplanned"
                                                        ? "selected"
                                                        : "" ?>>Unplanned (Insiden/Crash)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="font-bold">Link Tiket Eksternal <span class="text-muted" style="font-weight: normal;">(Opsional)</span></label>
                                        <input type="url" class="form-control" name="link_tiket" placeholder="https://iris.bri.co.id/browse/..." value="<?= html_escape(
                                            $detail["link_tiket"] ?? "",
                                        ) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="font-bold">Alasan Restart <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control required-field" name="root_cause" required placeholder="Contoh: Patching, dll..." value="<?= html_escape(
                                            $detail["root_cause"] ?? "",
                                        ) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="font-bold">Deskripsi Permintaan</label>
                                        <textarea class="form-control" name="keterangan_request" rows="3" placeholder="Informasi pendukung..."><?= html_escape(
                                            $detail["keterangan_request"] ?? "",
                                        ) ?></textarea>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-12" style="padding-left: 20px;">
                                    <h5 class="font-bold text-success" style="margin-top: 0; margin-bottom: 15px;"><i class="fa fa-users"></i> B. Informasi Requestor</h5>

                                    <div style="background-color: #f9fbfd; padding: 20px; border: 1px solid #dae1e7; border-radius: 6px;">
                                        <div class="form-group">
                                            <label class="font-bold text-primary">Fungsi / Departemen Peminta <span class="text-danger">*</span></label>
                                            <select class="form-control" id="selectTeamGroup" style="width: 100%;" required>
                                                <option value="">-- Pilih Fungsi / Departemen --</option>
                                            </select>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-7">
                                                <div class="form-group" style="margin-bottom: 10px;">
                                                    <label class="font-bold text-info">PIC Requestor <span class="text-danger">*</span></label>
                                                    <select class="form-control required-field" name="id_team_requestor" id="id_team_requestor" style="width: 100%;" required disabled>
                                                        <option value="">-- Pilih PIC --</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group" style="margin-bottom: 10px;">
                                                    <label class="text-muted font-bold" style="font-size: 12px;">Kontak</label>
                                                    <input type="text" id="info_kontak_pic" class="form-control input-sm" readonly placeholder="-" style="background-color: #eef2f5; font-size: 12px; color: #475569; font-weight: bold; border-color: #d1e0ec;">
                                                </div>
                                            </div>
                                        </div>

                                        <div style="margin-top: 10px; padding-top: 12px; border-top: 1px dashed #cbd5e1;">
                                            <button type="button" class="btn btn-default btn-xs" id="btn-quick-add-team" style="margin:0; font-weight:bold; background-color: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                <i class="fa fa-plus text-success"></i> Tambah Data
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row" style="margin-top: 15px;">
                                <div class="col-md-12">
                                    <div class="form-group" style="background-color: #f8fafc; border-left: 4px solid #94a3b8; padding: 15px; margin-top: 5px; margin-bottom: 20px; border-radius: 4px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                                        <div class="checkbox" style="margin: 0;">
                                            <label style="font-weight: bold; color: #475569;">
                                                <input type="checkbox" id="toggle_backdate" value="1">
                                                <i class="fa fa-sliders text-muted"></i> Opsi Lanjutan: Penyesuaian Waktu Log (Backdate)
                                            </label>
                                            <p style="font-size: 11px; color: #64748b; margin-top: 5px; padding-left: 20px; line-height: 1.4;">
                                                * Fitur administratif. Centang opsi ini jika ingin mengoreksi waktu "Dibuat" secara historis. Jika tidak dicentang, waktu akan menggunakan nilai yang sudah tersimpan sebelumnya.
                                            </p>
                                        </div>
                                        <div id="backdate_container" style="display: none; padding-left: 20px; margin-top: 15px; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
                                            <label class="font-bold" style="color: #334155;">Tanggal Dibuat (Create)</label>
                                            <div class="input-group" style="max-width: 250px;">
                                                <input type="datetime-local" class="form-control" name="created_at" id="input_created_at" style="border-color: #cbd5e1; color: #1e293b; font-weight: 600;" value="<?= !empty(
                                                    $detail["created_at"]
                                                )
                                                    ? date(
                                                        "Y-m-d\TH:i",
                                                        strtotime($detail["created_at"]),
                                                    )
                                                    : "" ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h4 style="font-weight: bold; color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 20px; margin-top: 30px; font-size: 15px;">
                                <i class="fa fa-server"></i> Target Virtual Machine
                            </h4>

                            <div style="background-color: #fdfdfd; padding: 20px; border: 1px solid #eee; border-radius: 6px; margin-bottom: 20px;">
                                <div id="radar_incident_alert" style="display: <?= !empty(
                                    $detail["id_incident"]
                                )
                                    ? "block"
                                    : "none" ?>; background-color: #fffdf2; border-left: 5px solid #f1c40f; color: #333; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 4px; margin-bottom: 20px; padding: 15px;">
                                    <h4 style="font-weight: bold; font-size: 14px; margin-top:0; margin-bottom: 5px; color: #d35400;">
                                        <i class="fa fa-exclamation-triangle animated infinite pulse"></i> Sistem Terintegrasi: Tiket Insiden Terdeteksi Aktif!
                                    </h4>
                                    <p style="margin-bottom: 5px; font-size: 12.5px; color: #555;">
                                        Virtual Machine ini terdeteksi memiliki tiket insiden utilisasi tinggi yang sedang <b>OPEN / ON PROGRESS</b> dengan nomor tiket: <span id="radar_incident_ticket" class="label label-danger" style="font-size:11px;"><?= html_escape(
                                            $detail["no_tiket_insiden_terkait"] ?? "-",
                                        ) ?></span>
                                    </p>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="font-bold text-primary">Target VM <span class="text-danger">*</span> <small>(Filter: Site TBN)</small></label>
                                            <select class="form-control select2-vm required-field" name="id_virtual_machine" id="id_vm" style="width: 100%;" required>
                                                <option value="">-- Cari Nama VM / IP --</option>
                                                <optgroup label="🏢 SITE TBN">
                                                    <?php foreach ($master_vm as $vm):
                                                        if (
                                                            $vm["id_site"] === "TBN" ||
                                                            $vm["id_virtual_machine"] ==
                                                                ($detail["id_virtual_machine"] ??
                                                                    "")
                                                        ): ?>
                                                            <option value="<?= html_escape(
                                                                $vm["id_virtual_machine"],
                                                            ) ?>" <?= ($detail[
    "id_virtual_machine"
] ??
    "") ==
$vm["id_virtual_machine"]
    ? "selected"
    : "" ?>>
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
                                </div>
                            </div>

                            <div class="row" style="margin-top: 40px;">
                                <div class="col-md-12 text-right">
                                    <hr style="border-top: 1px solid #e5e5e5; margin-bottom: 20px;">
                                    <a href="<?= site_url(
                                        "vm_restart",
                                    ) ?>" class="btn btn-default font-bold btn-lg" style="border-radius: 4px; margin-right: 10px;">
                                        <i class="fa fa-arrow-left"></i> Batal Edit
                                    </a>
                                    <!-- [ENTERPRISE FIX]: Penahan Klik Ganda pada Tombol Submit (ID Update) -->
                                    <button type="submit" class="btn btn-primary font-bold btn-lg" id="btnSubmitUpdate" style="border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">
                                        <i class="fa fa-save"></i> Update Data
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

<script>
    // [ENTERPRISE FIX]: Konstanta terdefinisi dengan benar untuk Edit
    const TEAM_DATA_JSON_STRING = '<?= json_encode(
        $master_team ?? [],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
    ) ?>';
    const API_QUICK_ADD         = '<?= site_url("vm_restart/ajax_quick_add_team") ?>';
    const API_CHECK_DUP         = '<?= site_url("vm_restart/ajax_check_duplicate") ?>';
    const API_CHECK_INCIDENT    = '<?= site_url("vm_incident/check_active_incident_json/") ?>';
    const CURRENT_ID            = '<?= html_escape($detail["id_restart"] ?? "0") ?>';
    const CSRF_NAME             = '<?= $this->security->get_csrf_token_name() ?>';
    const CSRF_HASH             = '<?= $this->security->get_csrf_hash() ?>';

    $(document).ready(function() {

        // --- 0. BFCache & SweetAlert Flashdata ---
        var $flashElem = $('#swal-flash-data');
        if ($flashElem.length > 0) {
            var swalType = $flashElem.data('type');
            var swalMessage = $flashElem.data('message');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: swalType, title: swalType === 'error' ? 'Gagal' : 'Informasi', text: swalMessage,
                    timer: 3500, showConfirmButton: false
                });
            }
            if (window.history.replaceState) window.history.replaceState(null, null, window.location.href);
            $flashElem.remove();
        }

        // --- 1. Master Team JSON Grouping ---
        var rawTeamData = [];
        var groupedTeams = {};
        try { rawTeamData = JSON.parse(TEAM_DATA_JSON_STRING); } catch(e) { console.error(e); }

        rawTeamData.forEach(function(item) {
            var keyStr = item.team_code ? item.team_code : item.team_name;
            var labelStr = (item.team_code ? '[' + item.team_code + '] ' : '') + item.team_name;
            if (!groupedTeams[keyStr]) groupedTeams[keyStr] = { label: labelStr, pics: [] };
            groupedTeams[keyStr].pics.push(item);
        });

        var savedIdTeam = "<?= html_escape($detail["id_team_requestor"] ?? "") ?>";
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
                $picSelect.prop('disabled', true).select2({ placeholder: '-- Pilih Requestor / PIC --', width: '100%' });
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

        // --- 2. Quick Add Team Modal (AJAX) ---
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

            var payload = { team_name: teamName, team_code: teamCode, pic_name: picName, pic_contact: picContact };
            payload[CSRF_NAME] = $('#csrf_token').val();

            $.ajax({
                url: API_QUICK_ADD, type: 'POST', data: payload, dataType: 'json',
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

        // --- 3. Select2 VM & Radar Insiden ---
        // [ENTERPRISE FIX]: Inisialisasi yang terlewatkan sekarang dipulihkan 100%
        $('.select2-vm').select2({ placeholder: "-- Cari Nama VM atau IP Address --", allowClear: true });

        // Auto Scan on Load (Edit Mode)
        if ($('#id_vm').val() !== '' && $('#id_vm').val() !== null) {
            var vm_id = $('#id_vm').val();
            $.ajax({
                url: API_CHECK_INCIDENT + vm_id, type: "GET", dataType: "json",
                success: function(incidentRes) {
                    if (incidentRes.has_incident) {
                        $('#resolve_incident_id').val(incidentRes.incident_data.id_incident);
                        $('#radar_incident_ticket').text(incidentRes.incident_data.no_tiket_insiden);
                        $('#radar_incident_alert').slideDown(300);
                    }
                }
            });
        }

        $('#id_vm').on('change', function() {
            var vm_id = $(this).val();
            $(this).next('.select2-container').find('.select2-selection').css({'border': '', 'background-color': ''});
            $('.error-inline').remove();

            if (!vm_id) {
                $('#resolve_incident_id').val('');
                $('#radar_incident_alert').slideUp(200);
                return;
            }

            $.ajax({
                url: API_CHECK_INCIDENT + vm_id, type: "GET", dataType: "json",
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
        });

        // --- 4. Backdate UX ---
        $('#toggle_backdate').on('change', function() {
            if ($(this).is(':checked')) {
                $('#backdate_container').slideDown();
            } else {
                $('#backdate_container').slideUp();
            }
        });

        // --- 5. Form Submission Guard & Duplication Check (Edit Mode) ---
        $('#formRestartVM').on('submit', function(e) {
            var isValid = true;
            var firstInvalidField = null;
            var $form = $(this);
            // [ENTERPRISE FIX]: Absolute Selektor untuk mencegah Double-Submit lock error
            var $btnSubmit = $('#btnSubmitUpdate');

            $('.error-inline').remove();
            $('.required-field').css({'border': '', 'background-color': ''});

            $(this).find('.required-field').each(function() {
                if ($(this).val() === '' || $(this).val() === null) {
                    isValid = false;
                    if (!firstInvalidField) firstInvalidField = $(this);

                    if ($(this).hasClass('select2-vm') || $(this).hasClass('select2-hidden-accessible')) {
                        $(this).next('.select2-container').find('.select2-selection').css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                        $(this).next('.select2-container').after('<small class="text-danger error-inline" style="color:#e74c3c; display:block; margin-top:4px;">Wajib dipilih.</small>');
                    } else {
                        $(this).css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                        $(this).after('<small class="text-danger error-inline" style="color:#e74c3c; display:block; margin-top:4px;">Wajib diisi.</small>');
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Validasi Gagal: Lengkapi kolom merah.', showConfirmButton: false, timer: 3000 });
                }
                if (firstInvalidField) $('html, body').animate({ scrollTop: firstInvalidField.offset().top - 120 }, 400);
            } else {
                e.preventDefault();

                var originalText = $btnSubmit.html();
                $btnSubmit.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memvalidasi Data...');

                var payloadData = {
                    no_tiket: $('input[name="no_tiket_iris"]').val().trim(),
                    id_virtual_machine: $('#id_vm').val(),
                    id_change: CURRENT_ID
                };
                payloadData[CSRF_NAME] = $('#csrf_token').val();

                $.ajax({
                    url: API_CHECK_DUP, type: 'POST', data: payloadData, dataType: 'json',
                    success: function(res) {
                        if(res.csrf_hash) { $('#csrf_token').val(res.csrf_hash); }

                        if (res.status === 'duplicate') {
                            $btnSubmit.html(originalText).prop('disabled', false);
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'warning', title: 'Terdeteksi Duplikasi', html: res.message });
                            }
                            $('input[name="no_tiket_iris"]').css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                            $('#id_vm').next('.select2-container').find('.select2-selection').css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                            $('html, body').animate({ scrollTop: $('input[name="no_tiket_iris"]').offset().top - 120 }, 400);
                        } else {
                            $btnSubmit.html('<i class="fa fa-spinner fa-spin"></i> Menyimpan Pembaruan...');
                            $form[0].submit();
                        }
                    },
                    error: function() {
                        $btnSubmit.html(originalText).prop('disabled', false);
                        if (typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Gangguan Server', text: 'Gagal memverifikasi duplikasi. Koneksi terputus.' }); }
                    }
                });
            }
        });

        $(document).on('input change', '.required-field', function() {
            if ($(this).val() !== '') {
                $(this).css({'border': '', 'background-color': ''});
                $(this).siblings('.error-inline').remove();
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).next('.select2-container').find('.select2-selection').css({'border': '', 'background-color': ''});
                    $(this).next('.select2-container').next('.error-inline').remove();
                }
            }
        });
    });
</script>
