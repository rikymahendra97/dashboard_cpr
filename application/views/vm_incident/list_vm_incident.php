<?php
/**
 * ========================================================================
 * File Name    : list_vm_incident.php
 * Modul        : VM Utilization Incident
 * Purpose      : Daftar Insiden (DataTables View)
 * Architecture : Enterprise Standard CP-05 (Interactive Dashboard, Auto Preview SPA, Linter-Safe)
 * ========================================================================
 */

// ========================================================================
// [ENTERPRISE FIX]: Intelephense Linter Guard
// ========================================================================
$kpi = $kpi ?? [
    "open" => 0,
    "wip" => 0,
    "closed" => 0,
    "kurang_7" => 0,
    "lewat_7" => 0,
    "lewat_14" => 0,
];

$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
?>

<style>
    /* [ENTERPRISE KPI UI]: Interactive Card Styling (Global Wrapper) */
    #kpi-master-wrapper { transition: opacity 0.3s ease; }
    #kpi-master-wrapper.has-active-filter .kpi-card { opacity: 0.5; filter: grayscale(60%); transform: scale(0.98); box-shadow: none; }
    .kpi-card { background: #fff; border-radius: 8px; padding: 12px 15px; border: 1px solid #E2E8F0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; }
    .kpi-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); opacity: 1 !important; filter: none !important; }
    #kpi-master-wrapper.has-active-filter .kpi-card.active-filter { opacity: 1; filter: none; transform: scale(1.02) translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.12); border-color: currentColor; z-index: 10; position: relative; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; transition: transform 0.4s ease; }
    .kpi-card:hover .kpi-icon, .kpi-card.active-filter .kpi-icon { transform: scale(1.1) rotate(5deg); }
    .kpi-details h4 { margin: 0; font-size: 11px; color: #64748B; font-weight: bold; text-transform: uppercase; }
    .kpi-details h2 { margin: 5px 0 0 0; font-size: 22px; font-weight: 800; color: #1E293B; }

    /* Fix DataTables */
    table.dataTable thead .sorting, table.dataTable thead .sorting_asc, table.dataTable thead .sorting_desc { background-image: none !important; }
    table.dataTable tbody td { vertical-align: middle !important; }
    .dt-center { text-align: center; }
    .dt-middle { vertical-align: middle; }

    @keyframes popIn { 0% { opacity: 0; transform: scale(0.8) translateY(-10px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }
    .btn-clear-filter { animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
</style>

<section class="scrollable wrapper">
    <div class="right_col" role="main">
        <div class="">
            <div class="clearfix"></div>

            <!-- [ENTERPRISE FIX]: AUTOHIDE ALERT BFCACHE-SAFE -->
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

            <!-- KPI MASTER WRAPPER (Interactive Drill-down) -->
            <div id="kpi-master-wrapper">
                <!-- ROW 1: STATUS TICKETS -->
                <div class="row animated fadeInDown" style="animation-delay: 0.1s;">
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="pending" data-title="Insiden Baru (Open)" style="border-bottom: 4px solid #EF4444; color: #EF4444;">
                            <div class="kpi-details"><h4>Baru (Open)</h4><h2><?= number_format(
                                $kpi["open"],
                            ) ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Insiden</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #EF4444, #DC2626);"><i class="fa fa-envelope"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="in_progress" data-title="Dalam Investigasi / Setup" style="border-bottom: 4px solid #F59E0B; color: #F59E0B;">
                            <div class="kpi-details"><h4>Investigasi (Progress)</h4><h2><?= number_format(
                                $kpi["wip"],
                            ) ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Insiden</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);"><i class="fa fa-wrench"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="selesai" data-title="Telah Diselesaikan (Closed)" style="border-bottom: 4px solid #10B981; color: #10B981;">
                            <div class="kpi-details"><h4>Selesai (Closed)</h4><h2><?= number_format(
                                $kpi["closed"],
                            ) ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Insiden</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #10B981, #059669);"><i class="fa fa-check-circle"></i></div>
                        </div>
                    </div>
                </div>

                <!-- ROW 2: SLA MONITORING -->
                <div class="row animated fadeInDown" style="animation-delay: 0.2s; margin-bottom: 10px;">
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="kurang_7" data-title="SLA < 7 Hari (Aman)" style="border-bottom: 4px solid #10B981; color: #10B981;">
                            <div class="kpi-details"><h4>SLA < 7 Hari (Aman)</h4><h2><?= number_format(
                                $kpi["kurang_7"],
                            ) ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Insiden</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #10B981, #059669);"><i class="fa fa-shield"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="lewat_7" data-title="SLA > 7 Hari (Warning)" style="border-bottom: 4px solid #F59E0B; color: #F59E0B;">
                            <div class="kpi-details"><h4>SLA > 7 Hari (Warning)</h4><h2><?= number_format(
                                $kpi["lewat_7"],
                            ) ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Insiden</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);"><i class="fa fa-exclamation-triangle"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="lewat_14" data-title="SLA > 14 Hari (Kritis)" style="border-bottom: 4px solid #EF4444; color: #EF4444;">
                            <div class="kpi-details"><h4>SLA > 14 Hari (Kritis)</h4><h2><?= number_format(
                                $kpi["lewat_14"],
                            ) ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Insiden</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #EF4444, #DC2626);"><i class="fa fa-fire"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN TABLE SECTION -->
            <div class="row animated fadeInUp" style="animation-delay: 0.3s;">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel" style="border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">

                        <div class="x_title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                            <h2 style="font-weight: bold; color: #2A3F54; margin: 5px 0;">
                                <i class="fa fa-exclamation-triangle"></i> Tata Kelola Insiden <small style="font-weight: normal;">VM Utilization</small>
                            </h2>
                            <div id="filter-indicator-container"></div>
                            <div class="clearfix"></div>
                        </div>

                        <div class="x_content">

                            <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                                <div style="margin-bottom: 5px;">
                                    <a href="<?php echo site_url(
                                        "vm_incident/create",
                                    ); ?>" class="btn btn-success btn-sm" style="font-weight: bold; border-radius: 4px; padding: 6px 15px;">
                                        <i class="fa fa-plus"></i> Register Insiden Baru
                                    </a>
                                </div>
                                <div style="margin-bottom: 5px;">
                                    <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#modalExportExcel" style="font-weight: bold; color: #1e7145; border-color: #1e7145; border-radius: 4px; padding: 6px 15px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                        <i class="fa fa-file-excel-o"></i> Export Laporan (Excel)
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
                                <table id="datatable-incident" class="table table-striped responsive-utilities jambo_table" style="white-space: nowrap; width: 100%;">
                                    <thead>
                                        <tr class="headings">
                                            <th style="font-weight: bold; width: 5%;" class="text-center">No</th>
                                            <th style="font-weight: bold; width: 10%;">Waktu Input</th>
                                            <th style="font-weight: bold; width: 12%;">No Tiket Jira</th>
                                            <th style="font-weight: bold; width: 18%;">Virtual Machine</th>
                                            <th style="font-weight: bold; width: 10%;" class="text-center">Tipe Insiden</th>
                                            <th style="font-weight: bold; width: 10%;" class="text-center">Kritikalitas</th>
                                            <th style="font-weight: bold; width: 10%;" class="text-center">SLA Tracker</th>
                                            <th style="font-weight: bold; width: 10%;" class="text-center">Status</th>
                                            <th class="no-sort text-center" style="font-weight: bold; width: 15%;">Opsi / Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MODAL EXPORT SPA DENGAN LIVE PREVIEW DINAMIS -->
<div class="modal fade" id="modalExportExcel" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" style="width: 90%;">
        <div class="modal-content" style="border-radius: 6px;">
            <form action="<?= site_url(
                "vm_incident/export_excel",
            ) ?>" method="post" id="formExportReal" target="_blank">
                <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_hash ?>" class="csrf-input">
                <input type="hidden" name="export_columns" id="exportColumns">

                <div class="modal-header" style="background-color: #2A3F54; color: white; border-top-left-radius: 6px; border-top-right-radius: 6px;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" style="font-weight: bold;"><i class="fa fa-file-excel-o"></i> Laporan Tiket SCR Utilisasi VM</h4>
                </div>

                <div class="modal-body" style="padding: 20px; background-color: #f4f6f9;">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="x_panel" style="padding: 10px; border-radius: 4px;">
                                <label style="font-weight: bold; color: #333; font-size: 13px; border-bottom: 2px solid #1ABB9C; padding-bottom: 5px; display: block;">1. Filter Waktu (Created At):</label>
                                <div class="radio"><label><input type="radio" name="filter_type" value="all" checked> Keseluruhan Data</label></div>
                                <div class="radio"><label><input type="radio" name="filter_type" value="range"> Rentang Tanggal</label></div>

                                <div id="dateRangeInputs" style="display: none; background-color: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
                                    <label style="font-size: 11px;">Tanggal Awal <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control input-sm trigger-preview" name="start_date" id="startDate" style="margin-bottom: 5px;">
                                    <label style="font-size: 11px;">Tanggal Akhir <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control input-sm trigger-preview" name="end_date" id="endDate">
                                </div>
                            </div>

                            <div class="x_panel" style="padding: 10px; border-radius: 4px; margin-top: 10px;">
                                <label style="font-weight: bold; color: #333; font-size: 13px; border-bottom: 2px solid #3498DB; padding-bottom: 5px; display: block;">2. Pilih Kolom (Max 18):</label>
                                <div style="max-height: 250px; overflow-y: auto; font-size: 12px; padding-right: 5px;">

                                    <strong style="color: #2A3F54; display:block; margin-bottom:5px;">Kolom Wajib (Default):</strong>
                                    <div class="checkbox" style="margin-top:0;"><label><input type="checkbox" class="col-opt trigger-preview" value="no" checked disabled> No Urut</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="no_tiket_insiden" checked> No Tiket Jira</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="created_at" checked> Waktu Registrasi</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="nama_vm" checked> Nama Target VM</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="ip_vm" checked> IP Address Target</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="tipe_insiden" checked> Kategori Insiden</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="tingkat_urgensi" checked> Level Urgensi</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="sla_deadline" checked> Batas Waktu SLA</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="status_insiden" checked> Status Terkini</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="resolved_at" checked> Waktu Tiket Selesai</label></div>

                                    <hr style="margin: 8px 0; border-top: 1px dashed #ccc;">

                                    <strong style="color: #d9534f; display:block; margin-bottom:5px;">Kolom Tambahan (Opsional):</strong>
                                    <div class="checkbox" style="margin-top:0;"><label><input type="checkbox" class="col-opt trigger-preview" value="metrik_tercatat"> Peak Value (%)</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="nama_pelapor"> Pelapor Awal</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="total_fu"> Total Jurnal FU</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="last_fu_date"> Waktu FU Terakhir</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="catatan_resolusi"> Catatan Resolusi Akhir</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="nama_aplikasi"> Sistem Aplikasi (CMDB)</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="kritikalitas"> Kritikalitas (CMDB)</label></div>
                                    <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="guest_os"> Operating System</label></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-9">
                            <label style="font-weight: bold; color: #334155; font-size: 13px; border-bottom: 2px solid #E74C3C; padding-bottom: 5px; display: block;">3. Live Preview Data Excel:</label>
                            <div id="previewArea" style="background: #fff; border: 1px dashed #ccc; height: 440px; overflow: auto; border-radius: 4px; padding: 10px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
                                <div class="text-center" style="padding-top: 150px; color: #999;">
                                    <i class="fa fa-spinner fa-spin fa-3x" style="margin-bottom: 10px;"></i><br>
                                    <span style="font-size: 14px;">Memuat Data Summary...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="background-color: #f9fbfd; border-top: 1px solid #e5e5e5; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px; text-align: right; padding: 15px 20px;">
                    <button type="button" class="btn btn-default font-bold" data-dismiss="modal" style="margin-right: 8px;"><i class="fa fa-times"></i> Tutup</button>
                    <button type="submit" class="btn btn-success font-bold" id="btnDownloadExcel" disabled style="background-color: #1e7145; border-color: #1e7145;"><i class="fa fa-download"></i> Download Excel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete POST (Enterprise Anti-Spam Fix) -->
<form action="<?= site_url(
    "vm_incident/delete",
) ?>" method="post" id="formDeleteEnterprise" style="display:none;">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" class="csrf-input">
    <input type="hidden" name="id_incident" id="del_id_incident_sa">
</form>

<script src="<?= base_url("asset/js/datatables/js/jquery.dataTables.js") ?>"></script>
<script src="<?= base_url("asset/js/clipboard_engine.js") ?>"></script>

<!-- [ENTERPRISE FIX]: Injeksi JS Variabel Linter-Safe (Constant URL Export) -->
<script>
    const URL_AJAX_LIST    = '<?= site_url("vm_incident/ajax_list") ?>';
    const URL_AJAX_PREVIEW = '<?= site_url("vm_incident/ajax_preview_export") ?>';
    const CSRF_NAME_VAL    = '<?= $this->security->get_csrf_token_name() ?>';
</script>

<script>
    // Variabel Global Filter Dashbaord
    window.currentKpiFilter = "";

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
            if (window.history.replaceState) window.history.replaceState(null, null, window.location.href);
            $flashElem.remove();
        }

        $('[data-toggle="tooltip"]').tooltip();

        // 1. Inisialisasi DataTables Server-Side
        var table = $('#datatable-incident').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": URL_AJAX_LIST,
                "type": "POST",
                "data": function(d) {
                    d[CSRF_NAME_VAL] = $('.csrf-input').first().val();
                    d.filter_kpi = window.currentKpiFilter;
                }
            },
            "oLanguage": {
                "sSearch": "Cari No Tiket / Nama VM:",
                "sProcessing": "Memuat data insiden dari server...",
                "sEmptyTable": "Belum ada insiden utilisasi tercatat.",
                "sZeroRecords": "Pencarian tidak menemukan insiden yang sesuai."
            },
            "iDisplayLength": 25,
            "sPaginationType": "full_numbers",
            "order": [],
            "columnDefs": [
                { "targets": [0, 8], "orderable": false },
                { "targets": [0, 4, 5, 6, 7, 8], "className": "text-center dt-middle" }
            ],
            "drawCallback": function() {
                $('[data-toggle="tooltip"]').tooltip();
            }
        });

        // ========================================================================
        // [ENTERPRISE UX]: INTERACTIVE KPI DASHBOARD (DRILL-DOWN)
        // ========================================================================
        $('.kpi-trigger').on('click', function() {
            var filterValue = $(this).data('filter');
            var filterTitle = $(this).data('title');
            var badgeColor = $(this).css('border-bottom-color');

            if ($(this).hasClass('active-filter')) {
                resetKpiFilter();
                return;
            }

            $('#kpi-master-wrapper').removeClass('has-active-filter');
            $('.kpi-trigger').removeClass('active-filter');

            $('#kpi-master-wrapper').addClass('has-active-filter');
            $(this).addClass('active-filter');

            window.currentKpiFilter = filterValue;
            table.ajax.reload();

            var clearBadge = '<button type="button" id="btn-clear-kpi" class="btn btn-sm btn-clear-filter" style="margin:0; font-weight:bold; background-color:#fff; color:'+badgeColor+'; border: 1px solid '+badgeColor+'; border-radius: 20px; padding: 4px 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">' +
                             '<i class="fa fa-filter"></i> ' + filterTitle + ' <i class="fa fa-times" style="margin-left: 5px; opacity: 0.6;"></i>' +
                             '</button>';
            $('#filter-indicator-container').html(clearBadge);
            $('html, body').animate({ scrollTop: $('#datatable-incident').offset().top - 150 }, 400);
        });

        function resetKpiFilter() {
            $('#kpi-master-wrapper').removeClass('has-active-filter');
            $('.kpi-trigger').removeClass('active-filter');
            $('#filter-indicator-container').empty();
            window.currentKpiFilter = "";
            table.ajax.reload();
        }

        $(document).on('click', '#btn-clear-kpi', function(e) {
            e.preventDefault();
            resetKpiFilter();
        });

        // ========================================================================
        // [ENTERPRISE UX]: SWEETALERT DELETE CONFIRMATION
        // ========================================================================
        $('#datatable-incident tbody').on('click', '.btn-delete-incident', function(e) {
            e.preventDefault();
            var incidentId = $(this).data('id');
            var ticketNumber = $(this).data('ticket');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus Tiket Insiden?',
                    html: "Tiket Jira <b>" + ticketNumber + "</b> beserta seluruh log jurnal resolusinya akan dihapus secara permanen.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#73879C',
                    confirmButtonText: '<i class="fa fa-trash"></i> Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#del_id_incident_sa').val(incidentId);
                        $('#formDeleteEnterprise').submit();
                    }
                });
            } else {
                if (confirm('Yakin ingin menghapus tiket ' + ticketNumber + ' secara permanen?')) {
                    $('#del_id_incident_sa').val(incidentId);
                    $('#formDeleteEnterprise').submit();
                }
            }
        });

        $('#datatable-incident tbody').on('click', '.btn-locked', function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.addClass('animated shake');
            window.setTimeout(function() { $btn.removeClass('animated shake'); }, 800);

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

        // =========================================================================
        // JAVASCRIPT: SPA LIVE EXPORT PREVIEW (DEBOUNCE TIMER)
        // =========================================================================
        var previewTimer;

        function loadPreviewData() {
            var filterType = $('input[name="filter_type"]:checked').val();
            var start = $('#startDate').val();
            var end = $('#endDate').val();

            if (filterType === 'range' && (!start || !end)) {
                $('#previewArea').html('<div class="text-center" style="margin-top:150px; color:#94A3B8;"><i class="fa fa-calendar fa-3x" style="margin-bottom:10px;"></i><br><span style="font-size: 14px;">Silakan isi Tanggal Awal dan Akhir untuk melihat data.</span></div>');
                $('#btnDownloadExcel').prop('disabled', true);
                return;
            }

            var selectedCols = [];
            $('.col-opt:checked').each(function() { selectedCols.push($(this).val()); });
            $('#exportColumns').val(selectedCols.join(','));

            $('#previewArea').html('<div class="text-center" style="padding-top:150px;"><i class="fa fa-spinner fa-spin fa-3x text-primary"></i><br><br>Menganalisis & Memuat Data...</div>');
            $('#btnDownloadExcel').prop('disabled', true);

            var postData = {
                filter_type: filterType,
                start_date: start,
                end_date: end,
                selected_cols: selectedCols
            };
            postData[CSRF_NAME_VAL] = $('.csrf-input').first().val();

            $.ajax({
                url: URL_AJAX_PREVIEW,
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(response) {

                    // 1. Perbarui Token CSRF secara dinamis
                    if (response.csrf_hash) {
                        $('.csrf-input').val(response.csrf_hash);
                    }

                    // 2. Jika Data Kosong atau Error
                    if (response.status === 'empty' || response.status === 'error') {
                        $('#previewArea').html(response.html_preview);
                        $('#btnDownloadExcel').prop('disabled', true);
                        return;
                    }

                    // 3. Jika Data Sukses Dimuat
                    if (response.status === 'success') {
                        $('#previewArea').html(response.html_preview);
                        $('#btnDownloadExcel').prop('disabled', false);

                        // Re-init DataTable di dalam Modal Preview
                        if ($('#previewDataTable').length > 0) {
                            if ($.fn.DataTable.isDataTable('#previewDataTable')) {
                                $('#previewDataTable').DataTable().clear().destroy();
                            }
                            $('#previewDataTable').DataTable({
                                "pageLength": 50,
                                "lengthMenu": [[10, 50, 100, -1], [10, 50, 100, "All"]],
                                "scrollX": true,
                                "scrollY": "300px",
                                "scrollCollapse": true,
                                "autoWidth": false,
                                "bFilter": true,
                                "bInfo": true,
                                "oLanguage": { "sSearch": "Filter:", "sLengthMenu": "_MENU_ Data", "sInfo": "_START_ - _END_ dari total _TOTAL_" },
                                "columnDefs": [ { "className": "dt-center dt-middle", "targets": "_all" } ]
                            });
                        }
                    }
                },
                error: function() {
                    $('#previewArea').html('<div class="alert alert-danger text-center" style="margin-top:20px;">Gagal menghubungi server pusat. (Pastikan URL Endpoint benar).</div>');
                    $('#btnDownloadExcel').prop('disabled', true);
                }
            });
        }

        $('input[name="filter_type"]').on('change', function() {
            if ($(this).val() === 'range') {
                $('#dateRangeInputs').slideDown(200);
                $('#startDate, #endDate').prop('required', true);
            } else {
                $('#dateRangeInputs').slideUp(200);
                $('#startDate, #endDate').prop('required', false).val('');
            }
            clearTimeout(previewTimer);
            previewTimer = setTimeout(loadPreviewData, 500);
        });

        $('.trigger-preview, .col-opt, #startDate, #endDate').on('change input', function() {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(loadPreviewData, 500);
        });

        $('#modalExportExcel').on('shown.bs.modal', function () {
            loadPreviewData();
        });
    });
</script>
