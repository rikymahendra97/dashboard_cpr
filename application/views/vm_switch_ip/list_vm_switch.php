<?php
/**
 * ============================================================================
 * File Name    : list_vm_switch.php
 * Modul        : VM Switch IP
 * Purpose      : Daftar Antrean Tiket Change Resource (DataTables View)
 * Architecture : Dynamic CSRF Sync, DOM-XSS Guard, Interactive KPI Drill-down
 * ============================================================================
 */

$id = $id ?? [];
$kpi = $kpi ?? [
    "menunggu" => 0,
    "dieksekusi" => 0,
    "selesai" => 0,
    "kurang_7" => 0,
    "lewat_7" => 0,
    "lewat_14" => 0,
];

$role = isset($id["id_role"]) ? (int) $id["id_role"] : 99;
$can_edit_execute = can_edit_execute($role);
$can_verify_delete = can_verify_delete($role);

$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
?>

<style>
    #kpi-master-wrapper { transition: opacity 0.3s ease; }
    #kpi-master-wrapper.has-active-filter .kpi-card { opacity: 0.5; filter: grayscale(60%); transform: scale(0.98); box-shadow: none; }
    .kpi-card { background: #fff; border-radius: 8px; padding: 12px 15px; border: 1px solid #E2E8F0; box-shadow: 0 2px 4px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; }
    .kpi-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); opacity: 1 !important; filter: none !important; }
    #kpi-master-wrapper.has-active-filter .kpi-card.active-filter { opacity: 1; filter: none; transform: scale(1.02) translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.12); border-color: currentColor; z-index: 10; position: relative; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; transition: transform 0.4s ease; }
    .kpi-card:hover .kpi-icon, .kpi-card.active-filter .kpi-icon { transform: scale(1.1) rotate(5deg); }
    .kpi-details h4 { margin: 0; font-size: 11px; color: #64748B; font-weight: bold; text-transform: uppercase; }
    .kpi-details h2 { margin: 5px 0 0 0; font-size: 22px; font-weight: 800; color: #1E293B; }

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

            <!-- ======================================================================== -->
            <!-- [ENTERPRISE FIX]: SWEETALERT BFCACHE-SAFE DATA INJECTION                 -->
            <!-- ======================================================================== -->
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

            <!-- KPI MASTER WRAPPER -->
            <div id="kpi-master-wrapper">
                <div class="row animated fadeInDown" style="animation-delay: 0.1s;">
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="menunggu" data-title="Menunggu Eksekusi" style="border-bottom: 4px solid #F59E0B; color: #F59E0B;">
                            <div class="kpi-details"><h4>Menunggu Eksekusi</h4><h2><?= number_format(
                                $kpi["menunggu"] ?? 0,
                            ) ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Tiket</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);"><i class="fa fa-clock-o"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="dieksekusi" data-title="Menunggu Verifikasi" style="border-bottom: 4px solid #3B82F6; color: #3B82F6;">
                            <div class="kpi-details"><h4>Menunggu Verifikasi</h4><h2><?= number_format(
                                $kpi["dieksekusi"] ?? 0,
                            ) ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Tiket</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #3B82F6, #2563EB);"><i class="fa fa-check-square-o"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="selesai" data-title="Selesai / Closed" style="border-bottom: 4px solid #10B981; color: #10B981;">
                            <div class="kpi-details"><h4>Selesai / Closed</h4><h2><?= number_format(
                                $kpi["selesai"] ?? 0,
                            ) ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Tiket</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #10B981, #059669);"><i class="fa fa-check-circle"></i></div>
                        </div>
                    </div>
                </div>

                <div class="row animated fadeInDown" style="animation-delay: 0.2s; margin-bottom: 10px;">
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="kurang_7" data-title="SLA < 7 Hari (Aman)" style="border-bottom: 4px solid #10B981; color: #10B981;">
                            <div class="kpi-details"><h4>SLA < 7 Hari (Aman)</h4><h2><?= number_format(
                                $kpi["kurang_7"] ?? 0,
                            ) ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Tiket</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #10B981, #059669);"><i class="fa fa-shield"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="lewat_7" data-title="SLA > 7 Hari (Warning)" style="border-bottom: 4px solid #F59E0B; color: #F59E0B;">
                            <div class="kpi-details"><h4>SLA > 7 Hari (Warning)</h4><h2><?= number_format(
                                $kpi["lewat_7"] ?? 0,
                            ) ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Tiket</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);"><i class="fa fa-exclamation-triangle"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="lewat_14" data-title="SLA > 14 Hari (Kritis)" style="border-bottom: 4px solid #EF4444; color: #EF4444;">
                            <div class="kpi-details"><h4>SLA > 14 Hari (Kritis)</h4><h2><?= number_format(
                                $kpi["lewat_14"] ?? 0,
                            ) ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Tiket</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #EF4444, #DC2626);"><i class="fa fa-fire"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Transaksi -->
            <div class="row animated fadeInUp" style="animation-delay: 0.3s;">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel" style="border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <div class="x_title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                            <h2 style="font-weight: bold; color: #2A3F54; margin: 5px 0;">
                                <i class="fa fa-exchange"></i> Log Perubahan IP VM <small style="font-weight: normal;">Switch / Swap Management</small>
                            </h2>
                            <div id="filter-indicator-container"></div>
                            <div class="clearfix"></div>
                        </div>

                        <div class="x_content">
                            <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                                <div style="margin-bottom: 5px;">
                                    <a href="<?= site_url(
                                        "vm_switch_ip/tambah",
                                    ) ?>" class="btn btn-success btn-sm" style="font-weight: bold; border-radius: 4px; padding: 6px 15px;">
                                        <i class="fa fa-plus"></i> Tambah Request Baru
                                    </a>
                                </div>
                                <div style="margin-bottom: 5px;">
                                    <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#modalExportExcel" style="font-weight: bold; color: #1e7145; border-color: #1e7145; border-radius: 4px; padding: 6px 15px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                        <i class="fa fa-file-excel-o"></i> Export Laporan (.xls)
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
                                <table id="datatable-switch" class="table table-striped responsive-utilities jambo_table" style="white-space: nowrap; width: 100%;">
                                    <thead>
                                        <tr class="headings">
                                            <!-- TOTAL 8 KOLOM [Index 0 sd 7] -->
                                            <th style="font-weight: bold; width: 5%;" class="text-center">No</th>
                                            <th style="font-weight: bold; width: 15%;">No Tiket</th>
                                            <th style="font-weight: bold; width: 15%;">Virtual Machine</th>
                                            <th style="font-weight: bold; width: 10%;" class="text-center">Jenis</th>
                                            <th style="font-weight: bold; width: 10%;" class="text-center">Status</th>
                                            <th style="font-weight: bold; width: 10%;">Implementer</th>
                                            <th style="font-weight: bold; width: 23%; min-width: 250px;">Catatan / Info</th>
                                            <th class="no-sort text-center" style="font-weight: bold; width: 12%;">Opsi / Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MODAL UPDATE KENDALA -->
<div class="modal fade" id="modalKendala" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius: 6px;">
            <form id="formKendala">
                <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_hash ?>" id="csrf_kendala" class="csrf_dynamic">
                <input type="hidden" name="id_switch" id="k_id_switch">
                <div class="modal-header" style="background-color: #f0ad4e; color: white; border-top-left-radius: 6px; border-top-right-radius: 6px;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title font-bold"><i class="fa fa-commenting"></i> Update Catatan / Info</h4>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight:bold;">Catatan Saat Ini / Baru: <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="kendala" id="k_kendala" rows="4" placeholder="Ketikkan info pending atau kendala eksekusi di sini..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f5f5f5; text-align: center; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;">
                    <button type="button" class="btn btn-default btn-sm font-bold" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm font-bold" id="btnSaveKendala"><i class="fa fa-save"></i> Simpan Info</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DELETE KONFIRMASI -->
<?php if ($can_verify_delete): ?>
<div class="modal fade" id="modalDelete" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-sm" style="margin-top: 15%;">
        <div class="modal-content" style="border-radius: 6px;">
            <form action="<?= site_url("vm_switch_ip/hapus") ?>" method="post">
                <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_hash ?>" class="csrf_dynamic">
                <input type="hidden" name="id_switch" id="del_id_switch" value="">
                <div class="modal-header" style="background-color: #d9534f; color: white; border-top-left-radius: 6px; border-top-right-radius: 6px;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" style="font-weight: bold;"><i class="fa fa-exclamation-triangle"></i> Konfirmasi Hapus Data</h4>
                </div>
                <div class="modal-body text-center" style="padding: 25px 15px;">
                    <p style="font-size: 14px; color: #333; margin-bottom: 0;">Apakah Anda yakin ingin menghapus data tiket beserta riwayat detailnya secara permanen?</p>
                </div>
                <div class="modal-footer" style="background-color: #f5f5f5; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px; text-align: center;">
                    <button type="button" class="btn btn-default btn-sm font-bold" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm font-bold" onclick="$(this).prop('disabled', true).html('<i class=\'fa fa-spinner fa-spin\'></i> Menghapus...'); $(this).closest('form').submit();"><i class="fa fa-trash"></i> Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL EXPORT SPA DENGAN LIVE PREVIEW DINAMIS -->
<div class="modal fade" id="modalExportExcel" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" style="width: 95%;">
        <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <form action="<?= site_url(
                "vm_switch_ip/export_excel",
            ) ?>" method="post" id="formExportReal" target="_blank">
                <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_hash ?>" id="export_csrf" class="csrf_dynamic">
                <input type="hidden" name="export_columns" id="export_columns">

                <div class="modal-header" style="background-color: #34495E; color: white; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" style="font-weight: bold;"><i class="fa fa-file-excel-o"></i> Customizer & Export Laporan Switch/Swap IP</h4>
                </div>

                <div class="modal-body" style="padding: 0; background-color: #f4f6f9;">
                    <div class="row" style="margin: 0; display: flex; flex-wrap: wrap;">
                        <div class="col-md-3" style="background: #FFFFFF; padding: 25px; border-right: 1px solid #E2E8F0;">
                            <h5 style="font-weight: bold; color: #334155; border-bottom: 2px solid #3B82F6; padding-bottom: 8px; margin-top: 0;">1. Pilih Filter Waktu:</h5>
                            <div class="radio"><label><input type="radio" name="filter_type" value="all" checked> Keseluruhan Data</label></div>
                            <div class="radio"><label><input type="radio" name="filter_type" value="range"> Rentang Tanggal</label></div>

                            <div id="date_range_inputs" style="display: none; background-color: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
                                <label style="font-size: 11px;">Tanggal Awal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control input-sm trigger-preview" name="start_date" id="start_date" style="margin-bottom: 5px;">
                                <label style="font-size: 11px;">Tanggal Akhir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control input-sm trigger-preview" name="end_date" id="end_date">
                            </div>

                            <h5 style="font-weight: bold; color: #334155; border-bottom: 2px solid #10B981; padding-bottom: 8px; margin-top: 30px;">2. Pilih Kolom Laporan:</h5>
                            <div style="max-height: 250px; overflow-y: auto; font-size: 12px;">
                                <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="no" checked disabled> No</label></div>
                                <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="nama_vms_awal" checked> Nama VMs Awal</label></div>
                                <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="ip_awal" checked> IP Awal</label></div>
                                <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="nama_vms_baru" checked> Nama VMs Baru</label></div>
                                <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="ip_baru" checked> IP Baru</label></div>
                                <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="no_tiket" checked> No Tiket</label></div>
                                <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="tanggal" checked> Tanggal Eksekusi</label></div>
                                <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="keterangan" checked> Keterangan</label></div>
                                <hr style="margin: 5px 0;">
                                <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="aksi" checked> Skenario Aksi</label></div>
                                <div class="checkbox"><label><input type="checkbox" class="col-opt trigger-preview" value="status" checked> Status Akhir</label></div>
                            </div>
                        </div>

                        <div class="col-md-9" style="padding: 25px; display: flex; flex-direction: column;">
                            <label style="font-weight: bold; color: #334155; font-size: 13px; border-bottom: 2px solid #E74C3C; padding-bottom: 5px; display: block;">3. Live Preview Data & Executive Summary:</label>
                            <div id="previewArea" style="flex-grow: 1; display: flex; flex-direction: column;">
                                <div class="text-center" style="margin-top: 150px; color: #94A3B8;">
                                    <i class="fa fa-spinner fa-spin fa-3x" style="margin-bottom: 10px;"></i><br>
                                    <span style="font-size: 14px;">Memuat Data...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="background-color: #f9fbfd; border-top: 1px solid #e5e5e5; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; text-align: right; padding: 15px 20px;">
                    <button type="button" class="btn btn-default font-bold" data-dismiss="modal" style="margin-right: 8px;"><i class="fa fa-times"></i> Tutup</button>
                    <button type="submit" class="btn btn-success font-bold" id="btnDownloadCSV" style="background-color: #1e7145; border-color: #1e7145;" disabled><i class="fa fa-download"></i> Download Excel (.xls)</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var URL_AJAX_LIST    = "<?= site_url("vm_switch_ip/ajax_list") ?>";
    var URL_AJAX_KENDALA = "<?= site_url("vm_switch_ip/ajax_update_kendala") ?>";
    var URL_AJAX_PREVIEW = "<?= site_url("vm_switch_ip/ajax_preview_export") ?>";
    var CSRF_NAME        = "<?= $csrf_name ?>";
    var CSRF_HASH        = "<?= $csrf_hash ?>";

    window.currentKpiFilter = "";

    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();

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

        var table = $('#datatable-switch').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": URL_AJAX_LIST,
                "type": "POST",
                "data": function(d) {
                    d[CSRF_NAME] = $('.csrf_dynamic').first().val() || CSRF_HASH;
                    d.filter_kpi = window.currentKpiFilter;
                }
            },
            "oLanguage": { "sSearch": "Cari Data:", "sProcessing": "Memuat data dari server...", "sEmptyTable": "Belum ada data log tercatat.", "sZeroRecords": "Pencarian tidak menemukan data log yang sesuai." },
            "iDisplayLength": 25,
            "sPaginationType": "full_numbers",
            "order": [],
            "columnDefs": [
                { "targets": [0, 7], "orderable": false },
                { "targets": [0, 3, 4, 7], "className": "text-center dt-middle" }
            ],
            "drawCallback": function() { $('[data-toggle="tooltip"]').tooltip(); }
        });

        // KPI DASHBOARD FILTER ENGINE
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
                             '<i class="fa fa-filter"></i> ' + filterTitle + ' <i class="fa fa-times" style="margin-left: 5px; opacity: 0.6;"></i></button>';
            $('#filter-indicator-container').html(clearBadge);
            $('html, body').animate({ scrollTop: $('#datatable-switch').offset().top - 150 }, 400);
        });

        function resetKpiFilter() {
            $('#kpi-master-wrapper').removeClass('has-active-filter');
            $('.kpi-trigger').removeClass('active-filter');
            $('#filter-indicator-container').empty();
            window.currentKpiFilter = "";
            table.ajax.reload();
        }

        $(document).on('click', '#btn-clear-kpi', function(e) { e.preventDefault(); resetKpiFilter(); });

        $('#datatable-switch tbody').on('click', '.btn_del', function(e) {
            e.preventDefault();
            $('#del_id_switch').val($(this).data('id'));
            $('#modalDelete').modal('show');
        });

        $('#datatable-switch tbody').on('click', '.btn-kendala', function() {
            $('#k_id_switch').val($(this).data('id'));
            $('#k_kendala').val($(this).data('notes'));
            $('#modalKendala').modal('show');
        });

        // COPY TO CLIPBOARD INLINE
        $('#datatable-switch tbody').on('click', '.inline-copy-trigger', function(e) {
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
            setTimeout(function() { $icon.removeClass('fa-check').addClass('fa-copy').css({'color': '#cbd5e1', 'transform': 'scale(1)'}); }, 1500);

            if (typeof Swal !== 'undefined') {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Disalin: ' + textToCopy, showConfirmButton: false, timer: 1500 });
            }
        });

        $('#datatable-switch tbody').on('click', '.btn-locked', function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.addClass('animated shake');
            setTimeout(function() { $btn.removeClass('animated shake'); }, 800);

            if (typeof Swal !== 'undefined') {
                Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Akses Terkunci', text: 'Aksi ini tidak diizinkan karena tiket telah ditutup permanen (Verified/Cancel).', showConfirmButton: false, timer: 4000 });
            }
        });

        $('#formKendala').on('submit', function(e) {
            e.preventDefault();
            $('#btnSaveKendala').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
            $.ajax({
                url: URL_AJAX_KENDALA,
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.csrf_hash) {
                        $('.csrf_dynamic').val(response.csrf_hash);
                    }

                    if (response.status) {
                        $('#modalKendala').modal('hide');
                        $('#btnSaveKendala').prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Info');
                        if (typeof Swal !== 'undefined') { Swal.fire({toast: true, position: 'top-end', icon: 'success', title: response.message, showConfirmButton: false, timer: 1500}); }
                        table.ajax.reload(null, false);
                    } else {
                        if (typeof Swal !== 'undefined') { Swal.fire({icon: 'error', title: 'Gagal', text: response.message}); }
                        $('#btnSaveKendala').prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Info');
                    }
                },
                error: function() {
                    if (typeof Swal !== 'undefined') { Swal.fire({icon: 'error', title: 'Terputus', text: 'Terjadi kesalahan komunikasi dengan server.'}); }
                    $('#btnSaveKendala').prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Info');
                }
            });
        });

        var previewTimer;
        function loadPreviewData() {
            var filterType = $('input[name="filter_type"]:checked').val();
            var start = $('#start_date').val();
            var end = $('#end_date').val();

            if (filterType === 'range' && (!start || !end)) {
                $('#previewArea').html('<div class="text-center" style="margin-top:150px; color:#94A3B8;"><i class="fa fa-calendar fa-3x" style="margin-bottom:10px;"></i><br><span style="font-size: 14px;">Silakan isi Tanggal Awal dan Akhir untuk melihat data.</span></div>');
                $('#btnDownloadCSV').prop('disabled', true);
                return;
            }

            var selectedCols = [];
            $('.col-opt:checked').each(function() { selectedCols.push($(this).val()); });
            $('#export_columns').val(selectedCols.join(','));

            $('#previewArea').html('<div class="text-center" style="padding-top:150px;"><i class="fa fa-spinner fa-spin fa-3x text-primary"></i><br><br>Menganalisis & Memuat Data...</div>');
            $('#btnDownloadCSV').prop('disabled', true);

            var postData = { filter_type: filterType, start_date: start, end_date: end, selected_cols: selectedCols };
            postData[$('#export_csrf').attr('name')] = $('.csrf_dynamic').first().val() || $('#export_csrf').val();

            $.ajax({
                url: URL_AJAX_PREVIEW,
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(response) {
                    if (response.csrf_hash) {
                        $('.csrf_dynamic').val(response.csrf_hash);
                    }

                    if (response.status === 'empty') {
                        $('#previewArea').html(response.html_preview);
                        $('#btnDownloadCSV').prop('disabled', true);
                        return;
                    }

                    if (response.status === 'success') {
                        $('#previewArea').html(response.html_preview);
                        $('#btnDownloadCSV').prop('disabled', false);

                        if ($('#previewDataTable').length > 0) {
                            if ($.fn.DataTable.isDataTable('#previewDataTable')) { $('#previewDataTable').DataTable().clear().destroy(); }
                            $('#previewDataTable').DataTable({
                                "pageLength": 50, "lengthMenu": [[10, 50, 100, -1], [10, 50, 100, "All"]],
                                "scrollX": true, "scrollY": "300px", "scrollCollapse": true, "autoWidth": false,
                                "bFilter": true, "bInfo": true,
                                "oLanguage": { "sSearch": "Filter Data:", "sLengthMenu": "Tampilkan _MENU_ Baris", "sInfo": "Menampilkan _START_ - _END_ dari total _TOTAL_ Tiket" },
                                "columnDefs": [{ "className": "dt-center dt-middle", "targets": "_all" }]
                            });
                        }
                    }
                },
                error: function() {
                    $('#previewArea').html('<div class="alert alert-danger text-center" style="margin-top:20px;">Gagal menghubungi server pusat. (Pastikan URL Endpoint benar).</div>');
                }
            });
        }

        $('input[name="filter_type"]').on('change', function() {
            if ($(this).val() === 'range') {
                $('#date_range_inputs').slideDown(200);
                $('#start_date, #end_date').prop('required', true);
            } else {
                $('#date_range_inputs').slideUp(200);
                $('#start_date, #end_date').prop('required', false).val('');
            }
            clearTimeout(previewTimer);
            previewTimer = setTimeout(loadPreviewData, 500);
        });

        $('.trigger-preview, .col-opt, #start_date, #end_date').on('change input', function() {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(loadPreviewData, 500);
        });

        $('#modalExportExcel').on('shown.bs.modal', function () { loadPreviewData(); });
    });
</script>
