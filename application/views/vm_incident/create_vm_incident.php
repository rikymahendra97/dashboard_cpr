<?php
/**
 * ========================================================================
 * File Name    : create_vm_incident.php
 * Tujuan       : Antarmuka Form Registrasi Insiden Utilisasi VM.
 * Modul        : VM Utilization Incident
 * Architecture : Enterprise CP-05 (Single-Metric UI, API URL Extractions)
 * ========================================================================
 */
?>

<style>
    .metadata-box {
        background: #fff;
        border-left: 4px solid #2A3F54;
        padding: 15px;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    .btn-action-custom {
        padding: 6px 16px;
        font-weight: bold;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        letter-spacing: 0.5px;
    }
</style>

<div class="right_col" role="main">
    <div class="">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">

                <div style="margin-bottom: 15px;">
                    <a href="<?= site_url(
                        "vm_incident",
                    ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; font-weight: bold; color: #555;">
                        <i class="fa fa-arrow-left"></i> Kembali ke Daftar Log
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

                <section class="panel" style="box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 8px;">
                    <header class="panel-heading" style="background-color: #f5f7fa; padding: 18px 20px; border-bottom: 1px solid #e6e9ed; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        <h3 style="margin: 0; font-weight: bold; color: #2A3F54; font-size: 18px;">
                            <i class="fa fa-plus-circle"></i> Register Insiden Utilisasi VM
                        </h3>
                    </header>

                    <div class="panel-body" style="padding: 30px;">
                        <form action="<?= site_url(
                            "vm_incident/store",
                        ) ?>" method="post" id="formAddIncident" class="form-horizontal" novalidate>
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" id="csrf_token">

                            <div class="row">
                                <div class="col-md-7 col-sm-12" style="border-right: 1px solid #edf2f7; padding-right: 25px;">
                                    <h4 style="font-weight: bold; color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 20px; font-size: 15px;">
                                        <i class="fa fa-file-text-o"></i> Informasi Tiket & Konfigurasi
                                    </h4>

                                    <div class="form-group">
                                        <label class="font-bold">No Tiket Jira <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control required-field" name="no_tiket_insiden" required placeholder="Contoh: SCR-001">
                                    </div>

                                    <div class="form-group">
                                        <label class="font-bold">Link Tiket Jira <span class="text-muted">(Opsional)</span></label>
                                        <input type="url" class="form-control" name="link_tiket" placeholder="https://jira.bri.co.id/browse/...">
                                    </div>

                                    <div class="form-group">
                                        <label class="font-bold text-primary">Cari Server VM <span class="text-danger">*</span></label>
                                        <select class="form-control required-field select2-ajax-vm" name="id_virtual_machine" id="id_virtual_machine" style="width: 100%;" required>
                                            <option value="">-- Ketik Nama Server atau IP --</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-bold text-primary">Tipe Insiden Metrik <span class="text-danger">*</span></label>
                                        <select class="form-control required-field select2-single-incident" name="tipe_insiden" id="tipe_insiden" style="width: 100%;" required>
                                            <option value="">-- Pilih Satu Kategori Insiden --</option>
                                            <option value="VM Tools">VM Tools (VMware Tools Issue)</option>
                                            <option value="CPU">CPU (High CPU Utilization)</option>
                                            <option value="Memory">Memory (High Memory Utilization)</option>
                                            <option value="Disk">Disk (High Disk Space Critical)</option>
                                            <option value="OS">OS (Operating System Obsolete)</option>
                                            <option value="Physical Host">Physical Host (ESXi Core Failure)</option>
                                            <option value="Audit">Audit (Compliance / Zombie VM)</option>
                                        </select>
                                    </div>

                                    <div class="form-group" id="disk_detail_container" style="display: none; background-color: #f7f9fc; padding: 15px; border-left: 4px solid #3498db; border-radius: 4px; margin-top: 15px; margin-bottom: 15px;">
                                        <label class="font-bold text-info"><i class="fa fa-hdd-o"></i> Detail Partisi Drive / Mount Point <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="detail_disk_drive" id="detail_disk_drive" placeholder="Contoh: / atau /data (Linux) | C: atau D: (Windows)">
                                        <small class="text-muted" style="display:block; margin-top:6px; font-size:11.5px; line-height:1.4; color:#4a5568; font-weight:600;">
                                            <i class="fa fa-info-circle text-primary"></i> Aturan Standardisasi Pencatatan Partisi:<br>
                                            <span style="margin-left: 12px; display:inline-block;">• <b>Sistem Linux:</b> Wajib format <i>absolute path</i> diawali garis miring. e.g. <code>/</code>, <code>/var</code>, <code>/data</code>, <code>/u01</code></span><br>
                                            <span style="margin-left: 12px; display:inline-block;">• <b>Sistem Windows:</b> Wajib format huruf kapital penanda drive diakhiri titik dua. e.g. <code>C:</code>, <code>D:</code>, <code>E:</code></span>
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-bold">Nilai Penilaian Metrik (%) <span class="text-danger">*</span></label>
                                        <div id="dynamic_metric_inputs_container" style="background-color: #fafbfc; padding: 15px; border: 1px solid #e2e8f0; border-radius: 4px;">
                                            <div id="metric_placeholder_text" class="text-muted" style="font-style: italic; font-size: 12px;">
                                                Silakan pilih Tipe Insiden Metrik terlebih dahulu untuk memunculkan kotak input nilai.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-bold">Deskripsi / Narasi Kronologi <span class="text-muted">(Opsional)</span></label>
                                        <textarea class="form-control" name="deskripsi_insiden" id="deskripsi_insiden" rows="3" placeholder="Rincian catatan analitik awal tim audit..."></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label class="font-bold">Tingkat Urgensi / Priority <span class="text-danger">*</span></label>
                                        <select class="form-control required-field" name="tingkat_urgensi" id="tingkat_urgensi" required>
                                            <option value="Critical">Critical (Utilisasi >90% / SLA T+5 Hari Kerja)</option>
                                            <option value="Highest">Highest (SLA T+5 Hari Kerja)</option>
                                            <option value="High" selected>High (SLA T+7 Hari Kerja)</option>
                                            <option value="Medium">Medium (SLA T+7 Hari Kerja)</option>
                                            <option value="Low">Low (SLA T+10 Hari Kerja)</option>
                                            <option value="Uncategorized">Uncategorized (Buku Kendali Log Only)</option>
                                        </select>
                                    </div>

                                    <div style="margin-top: 35px; padding-top: 15px; border-top: 1px solid #edf2f7;">
                                        <a href="<?= site_url(
                                            "vm_incident",
                                        ) ?>" class="btn btn-default btn-action-custom"><i class="fa fa-times"></i> Batal</a>
                                        <button type="submit" class="btn btn-primary btn-action-custom"><i class="fa fa-save"></i> Simpan & Mulai SLA</button>
                                    </div>
                                </div>

                                <div class="col-md-5 col-sm-12" style="padding-left: 25px; background-color: #fafbfc; min-height: 400px; border-radius: 4px;">
                                    <div class="metadata-box">
                                        <h4 style="font-weight: bold; color: #73879C; font-size: 13px; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 8px;"><i class="fa fa-database"></i> Live CMDB Metadata Preview</h4>
                                        <div id="meta-empty" class="text-center text-muted" style="padding: 60px 10px;">
                                            <i class="fa fa-search fa-3x" style="color: #ccc; margin-bottom: 15px;"></i><br>Pilih VM untuk validasi CMDB otomatis.
                                        </div>
                                        <div id="meta-content" style="display: none; font-size: 13px;">
                                            <table class="table table-striped table-bordered" style="background:#fff; margin-bottom: 0;">
                                                <tr>
                                                    <th style="width:40%; background:#f9f9f9;">IP Address</th>
                                                    <td id="lbl-ip" class="font-bold text-primary">-</td>
                                                </tr>
                                                <tr>
                                                    <th style="background:#f9f9f9;">Sistem Aplikasi</th>
                                                    <td id="lbl-app" class="font-bold">-</td>
                                                </tr>
                                                <tr>
                                                    <th style="background:#f9f9f9;">Kritikalitas Level</th>
                                                    <td id="lbl-crit">-</td>
                                                </tr>
                                                <tr>
                                                    <th style="background:#f9f9f9;">Operating System</th>
                                                    <td id="lbl-os">-</td>
                                                </tr>
                                                <tr>
                                                    <th style="background:#f9f9f9;">VMware Tools</th>
                                                    <td id="lbl-tools">-</td>
                                                </tr>
                                            </table>
                                        </div>
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

<!-- [ENTERPRISE FIX]: Injeksi Variabel Global JavaScript Linter-Safe (Constant URL Export) -->
<script>
    const URL_AJAX_SEARCH_VM = '<?= site_url("vm_incident/ajax_search_vm") ?>';
    const URL_AJAX_METADATA  = '<?= site_url("vm_incident/ajax_get_vm_metadata/") ?>';
</script>

<script>
    $(document).ready(function() {

        // [ENTERPRISE FIX]: BFCache Alert Sanitizer
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

        // Init Select2 Tunggal
        var $tipeSelect = $('.select2-single-incident');
        $tipeSelect.select2({ placeholder: "-- Pilih Satu Kategori Insiden --" });

        $('.select2-ajax-vm').select2({
            ajax: {
                url: URL_AJAX_SEARCH_VM,
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data.results }; },
                cache: true
            },
            placeholder: 'Ketik Nama Server atau IP...',
            minimumInputLength: 2
        });

        // Engine Render Metrik Form Tunggal
        function renderDynamicMetricInputs() {
            var metric = $tipeSelect.val();
            var container = $('#dynamic_metric_inputs_container');
            var $descField = $('#deskripsi_insiden');

            container.html('');

            if (!metric) {
                container.html('<div class="text-muted" style="font-style: italic; font-size: 12px;">Silakan pilih Tipe Insiden Metrik terlebih dahulu untuk memunculkan kotak input nilai.</div>');
                $('#disk_detail_container').slideUp();
                $('#detail_disk_drive').prop('required', false).removeClass('required-field').val('');
                return;
            }

            if (metric === 'Disk') {
                $('#disk_detail_container').slideDown();
                $('#detail_disk_drive').prop('required', true).addClass('required-field');
            } else {
                $('#disk_detail_container').slideUp();
                $('#detail_disk_drive').prop('required', false).removeClass('required-field').val('');
            }

            var templates = {
                'OS': 'Terdeteksi Operating System berstatus Obsolete / End-of-Support.',
                'Audit': 'Temuan ketidaksesuaian standarisasi komputasi berdasarkan hasil audit internal compliance.',
                'Physical Host': 'Terdeteksi indikasi gangguan atau kegagalan perangkat keras pada level Physical Host / ESXi Core Failure.',
                'VM Tools': 'Terdeteksi status VMware Tools bermasalah (Not Running / Out of Date) pada instance komputasi.'
            };

            var newText = templates[metric] ? templates[metric] : '';
            var currentDesc = $.trim($descField.val());
            var lastGenerated = $descField.data('last-generated') ?? '';

            if (currentDesc === '' || currentDesc === lastGenerated || Object.values(templates).some(t => currentDesc.includes(t))) {
                if (newText !== '') {
                    $descField.val(newText);
                    $descField.data('last-generated', newText);
                }
            }

            if (metric === 'OS' || metric === 'Audit' || metric === 'Physical Host' || metric === 'VM Tools') {
                var noticeText = 'Kategori metrik (<b>' + metric + '</b>) bersifat kualitatif. Kotak isian persentase numerik ditiadakan.';
                container.html('<div class="alert" style="margin:0; font-size:12px; padding:12px; border-left:4px solid #d35400; background-color:#fcf8e3; color:#a04000; font-weight:bold; border-radius:4px; box-shadow:inset 0 1px 1px rgba(0,0,0,0.05);">' +
                    '<i class="fa fa-exclamation-triangle" style="font-size:14px; margin-right:5px;"></i> ' + noticeText + '</div>' +
                    '<input type="hidden" name="metrik_tercatat" id="metrik_tercatat" value="0">');
            } else {
                var inputHtml = '<div class="form-group sub-metric-group" style="margin: 0;">' +
                    '<label style="font-size:12px; font-weight:bold; color:#4a5568;">Nilai Kritis ' + metric + ' (%) <span class="text-danger">*</span></label>' +
                    '<input type="number" step="0.01" class="form-control metric-val-input required-field" id="metrik_tercatat" name="metrik_tercatat" placeholder="90.00" required>' +
                    '</div>';
                container.html(inputHtml);
            }
        }

        $tipeSelect.on('change', function() {
            renderDynamicMetricInputs();
        });

        if ($tipeSelect.val() !== '') {
            renderDynamicMetricInputs();
        }

        $(document).on('input', '.metric-val-input', function() {
            var currentVal = parseFloat($(this).val()) || 0;
            if (currentVal > 90) {
                $('#tingkat_urgensi').val('Critical').trigger('change');
            }
        });

        $('#id_virtual_machine').on('change', function() {
            var idVm = $(this).val();
            if (!idVm) {
                $('#meta-content').hide();
                $('#meta-empty').show();
                return;
            }

            $('#meta-empty').html('<i class="fa fa-spinner fa-spin fa-2x text-primary"></i><br><br>Menghubungkan ke CMDB...').show();
            $('#meta-content').hide();

            $.ajax({
                url: URL_AJAX_METADATA + idVm,
                type: "GET",
                dataType: "json",
                success: function(response) {
                    if (response) {
                        $('#lbl-ip').text(response.ip_address ?? '-');
                        $('#lbl-app').text(response.nama_aplikasi ?? '-');
                        $('#lbl-crit').html('<strong>' + (response.kritikalitas ?? '-') + '</strong>');
                        $('#lbl-os').text(response.guest_os ?? '-');

                        var tools = response.vmware_tools_status ?? '-';
                        var badgeTools = '<span class="label label-default">' + tools + '</span>';
                        if (tools.toLowerCase().includes('running')) {
                            badgeTools = '<span class="label label-success"><i class="fa fa-play"></i> Running</span>';
                        } else if (tools.toLowerCase().includes('not')) {
                            badgeTools = '<span class="label label-danger"><i class="fa fa-stop"></i> Not Running</span>';
                        }
                        $('#lbl-tools').html(badgeTools);

                        $('#meta-empty').hide();
                        $('#meta-content').fadeIn(300);
                    }
                }
            });
        });

        // [ENTERPRISE FIX]: Submit Guard & SweetAlert Validation
        $('#formAddIncident').on('submit', function(e) {
            var isValid = true;
            $('.error-inline').remove();
            $('.required-field').css({
                'border': '',
                'background-color': ''
            });

            $(this).find('.required-field').each(function() {
                if ($(this).val() === '' || $(this).val() === null) {
                    isValid = false;
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).next('.select2-container').find('.select2-selection').css({
                            'border': '1px solid #e74c3c',
                            'background-color': '#fadbd8'
                        });
                    } else {
                        $(this).css({
                            'border': '1px solid #e74c3c',
                            'background-color': '#fadbd8'
                        });
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Validasi Gagal: Silakan lengkapi form yang ditandai merah.', showConfirmButton: false, timer: 3000 });
                }
            } else {
                $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
            }
        });

        // Remove error outline on input
        $(document).on('input change', '.required-field', function() {
            if ($(this).val() !== '') {
                $(this).css({'border': '', 'background-color': ''});
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).next('.select2-container').find('.select2-selection').css({'border': '', 'background-color': ''});
                }
            }
        });
    });
</script>
