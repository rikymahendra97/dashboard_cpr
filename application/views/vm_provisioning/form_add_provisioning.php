<?php
/**
 * ============================================================================
 * File Name    : form_add_provisioning.php
 * Modul        : VM Provisioning
 * Deskripsi    : Halaman Dedicated untuk penambahan tiket Provisioning baru
 * Arsitektur   : Double-Submit Guard, Hostname Strict Rule, Live CSRF Sync
 * ============================================================================
 */

// ========================================================================
// Intelephense Linter Guard & Defensive Programming
// ========================================================================
$list_os = $list_os ?? [];
$list_template = $list_template ?? [];
$master_team = $master_team ?? [];
?>

<style>
    .datastore-actions { margin-top: 8px; margin-bottom: 5px; }
    .datastore-actions .btn { border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .helper-text { font-size: 11px; font-style: italic; display: block; margin-top: 5px; color: #73879C; }
    .section-title { font-size: 15px; font-weight: bold; color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 20px; }
</style>

<div class="right_col" role="main">
    <div class="">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">

                <div style="margin-bottom: 15px;">
                    <a href="<?= site_url(
                        "provisioning",
                    ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); font-weight: bold; color: #555;">
                        <i class="fa fa-arrow-left"></i> Kembali ke Antrean
                    </a>
                </div>

                <!-- ======================================================================== -->
                <!-- SWEETALERT BFCACHE-SAFE DATA INJECTION                                   -->
                <!-- ======================================================================== -->
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

                <section class="panel" style="box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 8px;">
                    <header class="panel-heading" style="background-color: #f5f7fa; padding: 18px 20px; border-bottom: 1px solid #e6e9ed; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        <h3 style="margin: 0; font-weight: bold; color: #2A3F54; font-size: 18px;">
                            <i class="fa fa-plus-circle"></i> Catat Request Provisioning VM Baru
                        </h3>
                    </header>

                    <div class="panel-body" style="padding: 30px;">
                        <form id="formAddProvisioning" action="<?= site_url(
                            "provisioning/simpan_data",
                        ) ?>" method="post" novalidate>

                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" id="csrf_token_field" value="<?= $this->security->get_csrf_hash() ?>">

                            <div class="row">
                                <!-- KOLOM KIRI: ADMINISTRATIF -->
                                <div class="col-md-6 col-sm-12" style="border-right: 1px solid #edf2f7; padding-right: 25px;">
                                    <h4 class="section-title"><i class="fa fa-file-text-o"></i> Informasi Request iRIS</h4>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold text-primary">No Tiket iRIS <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control required-field" id="no_tiket_input" name="no_tiket" placeholder="Contoh: SCR-2026-..." required>

                                                <div style="margin-top: 5px;">
                                                    <label style="font-weight: normal; cursor: pointer; color: #555; font-size: 12px;">
                                                        <input type="checkbox" id="chk_draft_tiket" style="margin-right: 5px; position: relative; top: 2px;"> Tiket menyusul (Draft)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold">Tgl Masuk Request <span class="text-danger">*</span></label>
                                                <input type="datetime-local" class="form-control required-field" name="tanggal_masuk_tiket" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-bold">Hyperlink Tiket (URL) <span class="text-muted" style="font-weight: normal;">(Opsional)</span></label>
                                        <input type="url" class="form-control" name="link_tiket" placeholder="https://iris.bri.co.id/HEAT/...">
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold text-primary">Tipe Request <span class="text-danger">*</span></label>
                                                <select class="form-control required-field" name="tipe_request" id="tipe_request" required>
                                                    <option value="">-- Pilih Tipe --</option>
                                                    <option value="Fresh Install">Fresh Install</option>
                                                    <option value="Clone">Clone</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold">Kelompok Aplikasi</label>
                                                <input type="text" class="form-control" name="aplikasi" placeholder="Misal: Brimo / Brilink / Qlola">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group" style="background-color: #f9fbfd; padding: 15px; border: 1px solid #dae1e7; border-radius: 6px;">
                                        <label class="font-bold text-warning">Status Progres Awal</label>
                                        <input type="text" class="form-control" value="Pending" readonly style="background-color: #f5f5f5; color: #e67e22; font-weight: bold; cursor: not-allowed; border-color: #f1c40f;">
                                        <input type="hidden" name="progres_tiket" value="Pending">
                                    </div>

                                    <h4 class="section-title" style="margin-top: 30px;"><i class="fa fa-users"></i> Administratif Tim Requestor</h4>
                                    <div class="form-group">
                                        <label class="font-bold">Fungsi / Departemen Requestor <span class="text-danger">*</span></label>
                                        <select class="form-control required-field select2-tags-divisi" name="divisi_requestor" id="divisi_requestor" style="width: 100%;" required>
                                            <option value="">-- Pilih atau Ketik Baru --</option>
                                        </select>
                                        <small class="helper-text"><i class="fa fa-info-circle"></i> Pilih dari daftar Dropdown atau ketik manual jika belum terdaftar.</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label class="font-bold text-info">Nama Requestor / PIC <span class="text-danger">*</span></label>
                                                <select class="form-control required-field select2-tags-pic" name="nama_requestor" id="nama_requestor" style="width: 100%;" required>
                                                    <option value="">-- Pilih atau Ketik Baru --</option>
                                                </select>
                                                <small class="helper-text"><i class="fa fa-info-circle"></i> Bisa pilih nama yang sudah ada atau ketik nama PIC baru.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label class="font-bold">Kontak PIC</label>
                                                <input type="text" class="form-control" name="contact" id="contact" placeholder="No HP/Email">
                                            </div>
                                        </div>
                                    </div>

                                    <h4 class="section-title" style="margin-top: 30px;"><i class="fa fa-shield"></i> Informasi Lingkungan Sistem</h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold">Kritikalitas Sistem <span class="text-danger">*</span></label>
                                                <select class="form-control required-field" name="kritikalitas" required>
                                                    <option value="">-- Pilih Kritikalitas --</option>
                                                    <option value="Critical">Critical</option>
                                                    <option value="Very High">Very High</option>
                                                    <option value="High">High</option>
                                                    <option value="Medium">Medium</option>
                                                    <option value="Low">Low</option>
                                                    <option value="Non-Prod">Non-Prod</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold">Environment <span class="text-danger">*</span></label>
                                                <select class="form-control required-field" name="environment" required>
                                                    <option value="">-- Pilih Environment --</option>
                                                    <option value="Production">Production</option>
                                                    <option value="Development">Development</option>
                                                    <option value="Staging">Staging</option>
                                                    <option value="Testing">Testing</option>
                                                    <option value="UAT">UAT</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-bold">Deskripsi Permintaan / Instruksi Khusus</label>
                                        <textarea class="form-control" name="keterangan" rows="3" placeholder="Instruksi tambahan instalasi jika ada..."></textarea>
                                    </div>
                                </div>

                                <!-- KOLOM KANAN: SPESIFIKASI -->
                                <div class="col-md-6 col-sm-12" style="padding-left: 25px;">
                                    <h4 class="section-title"><i class="fa fa-desktop"></i> Spesifikasi Target VM</h4>

                                    <div class="form-group">
                                        <label class="font-bold text-success">Nama Server (vCenter) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control required-field" name="nama_server" placeholder="prd-tbn-vm..." required style="border-color: #27ae60;">
                                    </div>

                                    <div class="form-group">
                                        <label class="font-bold text-info">Hostname (OS Level) <span class="text-danger">*</span></label>
                                        <!-- [QA FIX] Menggunakan class regex-hostname untuk event handler Javascript -->
                                        <input type="text" class="form-control required-field regex-hostname" name="hostname" placeholder="Misal: prdtbnvm" title="Hanya diizinkan karakter huruf kecil, angka, dan strip (-)" required style="border-color: #3498db;">
                                        <small class="helper-text text-danger" style="display:none;" id="warn_hostname">Karakter spesial dihapus otomatis untuk menghindari error pada OS.</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold">Operating System <span class="text-danger">*</span></label>
                                                <select name="os" id="os" class="form-control select2 required-field" required>
                                                    <option value="">-- Pilih OS --</option>
                                                    <?php foreach (
                                                        $list_os
                                                        as $family => $os_array
                                                    ): ?>
                                                        <optgroup label="<?= html_escape(
                                                            $family,
                                                        ) ?>">
                                                            <?php foreach (
                                                                $os_array
                                                                as $os_name
                                                            ): ?>
                                                                <option value="<?= html_escape(
                                                                    $os_name,
                                                                ) ?>" data-family="<?= html_escape(
    $family,
) ?>"><?= html_escape($os_name) ?></option>
                                                            <?php endforeach; ?>
                                                        </optgroup>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold">IP Address</label>
                                                <input type="text" class="form-control" name="ip" placeholder="IP Address">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-bold">Source Clone / Golden Template <span class="text-danger">*</span></label>
                                        <div id="container_source">
                                            <input type="text" class="form-control" name="source_clone" id="source_input" placeholder="IP/Nama Source Clone">
                                            <select class="form-control" id="source_select" style="display:none;">
                                                <option value="">-- Pilih OS Terlebih Dahulu --</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 col-sm-4">
                                            <div class="form-group">
                                                <label class="font-bold">vCPU (Core) <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control required-field target-disk-trigger" name="cpu" placeholder="0" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-4">
                                            <div class="form-group">
                                                <label class="font-bold">RAM (GB) <span class="text-danger">*</span></label>
                                                <input type="number" step="1" class="form-control required-field target-disk-trigger" name="ram" placeholder="0" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-4">
                                            <div class="form-group">
                                                <label class="font-bold">Disk (GB) <span class="text-danger">*</span></label>
                                                <input type="number" step="1" class="form-control required-field target-disk-trigger" id="req_disk_val" name="disk" placeholder="0" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-bold">Detail Partisi Disk</label>
                                        <textarea class="form-control" name="detail_disk" rows="2" placeholder="Gunakan partisi default OS..."></textarea>
                                    </div>

                                    <h4 class="section-title" style="margin-top:30px;"><i class="fa fa-database"></i> Alokasi Penyimpanan</h4>
                                    <div class="form-group" style="background-color: #fcfdfd; padding: 15px; border: 1px solid #e2e8f0; border-radius: 6px;">
                                        <label class="font-bold text-primary">Target Datastore Cluster <span class="text-danger">*</span></label>
                                        <select class="form-control select2-ajax-datastore required-field" name="datastore" id="datastore" style="width: 100%;" required>
                                            <option value="">-- Ketik 2 huruf --</option>
                                        </select>
                                        <small class="helper-text"><i class="fa fa-info-circle"></i> Wajib diisi setelah besaran Disk ditentukan di atas.</small>

                                        <div class="datastore-actions">
                                            <button type="button" class="btn btn-default btn-xs" title="Sinkronisasi ke vROps"><i class="fa fa-refresh text-info"></i> Sync Live Datastore</button>
                                            <button type="button" class="btn btn-default btn-xs" title="Mesin Rekomendasi"><i class="fa fa-lightbulb-o text-warning"></i> Suggest Placement</button>
                                        </div>

                                        <div id="datastore_metadata_card" style="display: none; margin-top: 15px; border: 1px solid #e5e5e5; border-radius: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); background-color: #fff; overflow: hidden;">
                                            <div id="ds_header" style="background-color: #f8f9fa; padding: 10px 15px; border-bottom: 1px solid #e5e5e5; font-weight: bold; color: #2A3F54;">
                                                <i class="fa fa-hdd-o"></i> <span id="ds_name_lbl">-</span>
                                                <span id="ds_status_badge" class="pull-right"></span>
                                            </div>
                                            <div style="padding: 15px;">
                                                <table style="width: 100%; font-size: 13px; margin: 0;">
                                                    <tr>
                                                        <td style="width: 50%; padding-bottom: 8px; border-bottom: 1px dotted #eee; color:#555;"><strong>Capacity:</strong></td>
                                                        <td style="width: 50%; padding-bottom: 8px; border-bottom: 1px dotted #eee; text-align: right;" id="ds_capacity_lbl">-</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; border-bottom: 1px dotted #eee; color:#555;"><strong>Used:</strong></td>
                                                        <td style="padding: 8px 0; border-bottom: 1px dotted #eee; text-align: right;" id="ds_used_lbl">-</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; border-bottom: 1px dotted #eee; color:#555;"><strong>Free:</strong></td>
                                                        <td style="padding: 8px 0; border-bottom: 1px dotted #eee; text-align: right;" id="ds_free_lbl">-</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; border-bottom: 1px dotted #eee; color:#555;"><strong>Provisioned:</strong></td>
                                                        <td style="padding: 8px 0; border-bottom: 1px dotted #eee; text-align: right;" id="ds_prov_lbl">-</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding-top: 8px; color:#555;"><strong>Overprovision:</strong></td>
                                                        <td style="padding-top: 8px; text-align: right;" id="ds_over_lbl">-</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row" style="margin-top: 40px;">
                                <div class="col-md-12">
                                    <hr style="border-top: 1px solid #e5e5e5; margin-bottom: 20px;">
                                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                                        <a href="<?= site_url(
                                            "provisioning",
                                        ) ?>" class="btn btn-default font-bold" style="border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); padding: 8px 20px;">
                                            <i class="fa fa-arrow-left"></i> Batal
                                        </a>
                                        <button type="submit" class="btn btn-primary font-bold" id="btnSubmitAdd" style="border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.15); background-color: #2A3F54; border-color: #2A3F54; padding: 8px 20px;">
                                            <i class="fa fa-save"></i> Simpan Request
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </section>

            </div>
        </div>
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

    var URL_AJAX_DATASTORE = '<?= site_url("provisioning/ajax_search_datastore") ?>';
    var URL_AJAX_DUPLICATE = '<?= site_url("provisioning/ajax_check_duplicate") ?>';
    var CSRF_NAME_VAL      = '<?= $this->security->get_csrf_token_name() ?>';
</script>

<script>
$(document).ready(function() {

    // [QA FIX] Event Listener untuk sanitasi Hostname POSIX Standards (No Spaces, No Special Chars)
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

    // AUTO GENERATE DRAFT TICKET
    $('#chk_draft_tiket').on('change', function() {
        var inputTiket = $('#no_tiket_input');
        if ($(this).is(':checked')) {
            var d = new Date();
            var dateStr = d.getFullYear().toString().slice(-2) +
                          ('0' + (d.getMonth()+1)).slice(-2) +
                          ('0' + d.getDate()).slice(-2);
            var randomStr = Math.random().toString(36).substring(2, 5).toUpperCase();
            var draftId = 'DRAFT-' + dateStr + '-' + randomStr;

            inputTiket.val(draftId).prop('readonly', true).css({'background-color': '#f8f9fa', 'border-color': '#e2e8f0'})
                      .parent().find('.fa-times-circle').remove();
        } else {
            inputTiket.val('').prop('readonly', false).css({'background-color': '#fff', 'border-color': ''});
        }
    });

    var $divisiSelect = $('.select2-tags-divisi');
    var $picSelect = $('.select2-tags-pic');

    $divisiSelect.select2({ tags: true, placeholder: '-- Pilih atau Ketik Baru --', allowClear: true });
    $picSelect.select2({ tags: true, placeholder: '-- Pilih atau Ketik Baru --', allowClear: true });

    var uniqueDivisions = [];
    $.each(teamData, function(i, val) {
        if (val.team_name && $.inArray(val.team_name, uniqueDivisions) === -1) {
            uniqueDivisions.push(val.team_name);
            $divisiSelect.append(new Option(val.team_name, val.team_name, false, false));
        }
    });

    $divisiSelect.on('change', function() {
        var selectedDiv = $(this).val();
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
            if (uniquePics.length === 1) {
                $picSelect.val(uniquePics[0]).trigger('change');
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

    $('#os').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var family = selectedOption.data('family');
        var $templateDropdown = $('#source_select');

        $templateDropdown.empty().append('<option value="">-- Pilih Template --</option>');

        if (family && masterTemplates[family]) {
            $.each(masterTemplates[family], function(index, tpl_name) {
                $templateDropdown.append('<option value="' + tpl_name + '">' + tpl_name + '</option>');
            });
            $templateDropdown.append('<optgroup label="Alternatif"><option value="Custom ISO">Custom ISO (Instalasi Manual)</option></optgroup>');
        } else {
            $templateDropdown.append('<option value="">-- Pilih OS Terlebih Dahulu --</option>');
        }
    });

    function toggleSource(tipe) {
        var sourceInput = $('#source_input');
        var sourceSelect = $('#source_select');

        if (tipe == 'Fresh Install') {
            sourceSelect.show().attr('name', 'source_clone').addClass('required-field').prop('required', true);
            sourceInput.hide().removeAttr('name').removeClass('required-field').prop('required', false);
        } else if (tipe == 'Clone') {
            sourceInput.show().attr('name', 'source_clone').addClass('required-field').prop('required', true);
            sourceSelect.hide().removeAttr('name').removeClass('required-field').prop('required', false);
        } else {
            sourceInput.show().removeAttr('name').removeClass('required-field').prop('required', false);
            sourceSelect.hide().removeAttr('name').removeClass('required-field').prop('required', false);
        }
    }

    $('#tipe_request').on('change', function() { toggleSource($(this).val()); });
    toggleSource($('#tipe_request').val());

    // DATASTORE AJAX SELECT2
    var $datastoreSelect = $('.select2-ajax-datastore');
    $datastoreSelect.select2({
        tags: true, placeholder: '-- Ketik 2 huruf --', minimumInputLength: 2,
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
    // [QA FIX] FORM SUBMISSION, DOUBLE-SUBMIT GUARD & CSRF SYNC
    // ========================================================================
    var isSubmitting = false;

    $('#formAddProvisioning').on('submit', function(e) {
        e.preventDefault();

        if (isSubmitting) {
            return false;
        }

        var isValid = true;
        var firstInvalidField = null;
        var formObj = $(this);

        $('.required-field').css({'border': '', 'background-color': ''});

        formObj.find('.required-field').each(function() {
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
        } else {
            var btnTarget = $('#btnSubmitAdd');
            var originalText = btnTarget.html();

            // Penguncian Tombol
            btnTarget.html('<i class="fa fa-spinner fa-spin"></i> Memvalidasi Data...').prop('disabled', true);
            isSubmitting = true;

            var payloadData = {
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
                    // Inject New CSRF agar aman dari 403
                    if(res.csrf_hash) $('#csrf_token_field').val(res.csrf_hash);

                    if (res.status === 'duplicate') {
                        isSubmitting = false; // Buka kuncian form
                        btnTarget.html(originalText).prop('disabled', false);

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Data Duplikat Ditemukan!',
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
                        btnTarget.html('<i class="fa fa-spinner fa-spin"></i> Menyimpan Request...');
                        formObj[0].submit(); // Bypass jQuery trigger submit
                    }
                },
                error: function() {
                    alert("Terjadi kesalahan jaringan saat memvalidasi data duplikat.");
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
