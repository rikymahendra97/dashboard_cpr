<?php
/**
 * ============================================================================
 * File Name    : list_provisioning.php
 * Modul        : VM Provisioning
 * Purpose      : Daftar Antrean Tiket Provisioning (DataTables View)
 * Architecture : DOM-XSS Guarded, Symmetrical KPI Grid, Auto-Sync CSRF
 * ============================================================================
 */

// ========================================================================
// Intelephense Linter Guard & Defensive Programming
// ========================================================================
$id = $id ?? [];
$kpi = $kpi ?? [
    "pending" => 0,
    "in_progress" => 0,
    "waiting_sync" => 0,
    "kurang_7" => 0,
    "lewat_7" => 0,
    "lewat_14" => 0,
];

$role = isset($id["id_role"]) ? (int) $id["id_role"] : 99;
$can_edit_execute = can_edit_execute($role);
$can_verify_delete = can_verify_delete($role);
?>

<style>
    #kpi-master-wrapper { transition: opacity 0.3s ease; }
    #kpi-master-wrapper.has-active-filter .kpi-card { opacity: 0.5; filter: grayscale(60%); transform: scale(0.98); box-shadow: none; }
    .kpi-card { background: #fff; border-radius: 8px; padding: 12px 15px; border: 1px solid #E2E8F0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; }
    .kpi-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); opacity: 1 !important; filter: none !important; }
    #kpi-master-wrapper.has-active-filter .kpi-card.active-filter { opacity: 1; filter: none; transform: scale(1.02) translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.12); border-color: currentColor; z-index: 10; position: relative; }
    .kpi-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; transition: transform 0.4s ease; }
    .kpi-card:hover .kpi-icon, .kpi-card.active-filter .kpi-icon { transform: scale(1.1) rotate(5deg); }
    .kpi-details h4 { margin: 0; font-size: 11px; color: #64748B; font-weight: bold; text-transform: uppercase; }
    .kpi-details h2 { margin: 5px 0 0 0; font-size: 22px; font-weight: 800; color: #1E293B; }
    .table-provisioning { font-size: 13px; color: #2A3F54; margin-bottom: 0 !important; width: 100% !important; }
    .table-provisioning thead tr th { color: #ECF0F1 !important; font-size: 12px; font-weight: bold; text-transform: uppercase; padding: 12px 10px; vertical-align: middle !important; text-align: center !important; }
    .table-provisioning tbody tr td { padding: 4px 8px !important; vertical-align: middle !important; text-align: center !important; border-top: 1px solid #E2E8F0; }
    .table-provisioning tbody tr:hover { background-color: #F1F5F9; }
    table.dataTable thead .sorting::after, table.dataTable thead .sorting_asc::after, table.dataTable thead .sorting_desc::after,
    table.dataTable thead .sorting::before, table.dataTable thead .sorting_asc::before, table.dataTable thead .sorting_desc::before { display: none !important; content: "" !important; }
    .btn-copy-tbl { transition: transform 0.2s; font-size: 13px; margin-left: 4px; cursor: pointer; color: #cbd5e1; }
    .btn-copy-tbl:hover { transform: scale(1.3); color: #3498DB; }
    @keyframes popIn { 0% { opacity: 0; transform: scale(0.8) translateY(-10px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }
    .btn-clear-filter { animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
</style>

<section class="scrollable wrapper">
    <div class="right_col" role="main">
        <div class="">
            <div class="clearfix"></div>

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

            <!-- KPI MASTER WRAPPER -->
            <div id="kpi-master-wrapper">
                <div class="row animated fadeInDown" style="animation-delay: 0.1s;">
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="pending" data-title="Tiket Pending" style="border-bottom: 4px solid #F59E0B; color: #F59E0B;">
                            <div class="kpi-details"><h4>Tiket Pending</h4><h2><?= $kpi[
                                "pending"
                            ] ??
                                0 ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Tiket</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);"><i class="fa fa-clock-o"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="in_progress" data-title="In Progress" style="border-bottom: 4px solid #3B82F6; color: #3B82F6;">
                            <div class="kpi-details"><h4>In Progress</h4><h2><?= $kpi[
                                "in_progress"
                            ] ??
                                0 ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Tiket</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #3B82F6, #2563EB);"><i class="fa fa-cogs"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="waiting_sync" data-title="Waiting Sync" style="border-bottom: 4px solid #8B5CF6; color: #8B5CF6;">
                            <div class="kpi-details"><h4>Waiting Sync</h4><h2><?= $kpi[
                                "waiting_sync"
                            ] ??
                                0 ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Tiket</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #8B5CF6, #7C3AED);"><i class="fa fa-refresh"></i></div>
                        </div>
                    </div>
                </div>

                <div class="row animated fadeInDown" style="animation-delay: 0.2s; margin-bottom: 10px;">
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="kurang_7" data-title="SLA < 7 Hari (Aman)" style="border-bottom: 4px solid #10B981; color: #10B981;">
                            <div class="kpi-details"><h4>SLA < 7 Hari (Aman)</h4><h2><?= $kpi[
                                "kurang_7"
                            ] ??
                                0 ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Tiket</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #10B981, #059669);"><i class="fa fa-shield"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="lewat_7" data-title="SLA > 7 Hari (Warning)" style="border-bottom: 4px solid #F59E0B; color: #F59E0B;">
                            <div class="kpi-details"><h4>SLA > 7 Hari (Warning)</h4><h2><?= $kpi[
                                "lewat_7"
                            ] ??
                                0 ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Tiket</small></h2></div>
                            <div class="kpi-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);"><i class="fa fa-exclamation-triangle"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="kpi-card kpi-trigger" data-filter="lewat_14" data-title="SLA > 14 Hari (Kritis)" style="border-bottom: 4px solid #EF4444; color: #EF4444;">
                            <div class="kpi-details"><h4>SLA > 14 Hari (Kritis)</h4><h2><?= $kpi[
                                "lewat_14"
                            ] ??
                                0 ?> <small style="font-size: 12px; font-weight: normal; color: #94A3B8;">Tiket</small></h2></div>
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
                                <i class="fa fa-server"></i> Log Provisioning VM <small style="font-weight: normal;">Deploy & Clone Requests</small>
                            </h2>
                            <div id="filter-indicator-container"></div>
                            <div class="clearfix"></div>
                        </div>

                        <div class="x_content">
                            <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                                <div style="margin-bottom: 5px;">
                                    <a href="<?= site_url(
                                        "provisioning/tambah",
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
                                <table id="table-provisioning" class="table table-striped responsive-utilities jambo_table table-provisioning" style="white-space: nowrap;">
                                    <thead>
                                        <tr class="headings">
                                            <th class="text-center" style="width: 3%;">No</th>
                                            <th>Tgl Masuk</th>
                                            <th>No Tiket</th>
                                            <th>Virtual Machine</th>
                                            <th title="Kritikalitas">Kritikalitas</th>
                                            <th title="Environment">Env</th>
                                            <th class="text-center" title="Skenario Deployment">Tipe</th>
                                            <th class="text-center">Spesifikasi</th>
                                            <th style="display:none;">Detail Disk</th>
                                            <th style="display:none;">Keterangan</th>
                                            <th>Template / Source</th>
                                            <th>Aktor (Log)</th>
                                            <th style="display:none;">Setup By</th>
                                            <th style="display:none;">Closed By</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Aksi</th>
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

<!-- MODAL EXPORT EXCEL (ENTERPRISE DYNAMIC & LIVE PREVIEW) -->
<div class="modal fade" id="modalExportExcel" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg" style="width: 95%;">
        <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <form action="<?= site_url(
                "provisioning/export_excel",
            ) ?>" method="get" id="formExportReal" target="_blank">
                <!-- CSRF Token (Diupdate secara dinamis oleh JS) -->
                <input type="hidden" id="export_csrf" class="csrf_dynamic" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                <div class="modal-header" style="background-color: #1E293B; color: white; border-top-left-radius: 8px; border-top-right-radius: 8px; padding: 18px 25px;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title" style="font-weight: bold; font-size: 16px;"><i class="fa fa-file-excel-o text-success"></i> Customizer & Export Laporan Provisioning</h4>
                </div>

                <div class="modal-body" style="padding: 0; background-color: #F8FAFC;">
                    <div class="row" style="margin: 0; display: flex; flex-wrap: wrap;">
                        <div class="col-md-3" style="background: #FFFFFF; padding: 25px; border-right: 1px solid #E2E8F0;">
                            <h5 style="font-weight: bold; color: #334155; border-bottom: 2px solid #3B82F6; padding-bottom: 8px; margin-top: 0;">1. Pilih Filter Waktu:</h5>
                            <div class="radio"><label style="font-size: 13px;"><input type="radio" name="filter_type" value="all" checked> Keseluruhan Data</label></div>
                            <div class="radio"><label style="font-size: 13px;"><input type="radio" name="filter_type" value="range"> Rentang Tanggal</label></div>

                            <div id="date_range_inputs" style="display: none; background-color: #F1F5F9; padding: 12px; border: 1px solid #E2E8F0; border-radius: 6px; margin-top: 10px;">
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <label style="font-size: 12px; color: #64748B;">Tanggal Awal <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control input-sm trigger-preview" name="tgl_mulai" id="start_date">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 12px; color: #64748B;">Tanggal Akhir <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control input-sm trigger-preview" name="tgl_selesai" id="end_date">
                                </div>
                            </div>

                            <h5 style="font-weight: bold; color: #334155; border-bottom: 2px solid #10B981; padding-bottom: 8px; margin-top: 30px;">2. Pilih Kolom Laporan:</h5>
                            <p style="font-size: 11px; color: #94A3B8; margin-bottom: 10px;">12 Kolom standar selalu disertakan. Centang tambahan di bawah jika diperlukan.</p>

                            <div style="background: #F1F5F9; padding: 12px; border-radius: 6px; border: 1px solid #E2E8F0; max-height: 200px; overflow-y: auto;">
                                <div class="checkbox" style="margin-top: 0;"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="os"> Operating System</label></div>
                                <div class="checkbox"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="ip"> IP Address</label></div>
                                <div class="checkbox"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="hostname"> Hostname</label></div>
                                <div class="checkbox"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="aplikasi"> Kelompok Aplikasi</label></div>
                                <div class="checkbox"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="divisi_requestor"> Fungsi Peminta</label></div>
                                <div class="checkbox"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="nama_requestor"> Nama PIC</label></div>
                                <div class="checkbox"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="contact"> Kontak PIC</label></div>
                                <div class="checkbox"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="source_clone"> Source / Template</label></div>
                                <div class="checkbox"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="progres_tiket"> Status Tiket Akhir</label></div>
                                <div class="checkbox"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="tanggal_masuk_tiket"> Tgl Input Tiket</label></div>
                                <div class="checkbox"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="tanggal_keluar_tiket"> Tgl Selesai (Closed)</label></div>
                                <div class="checkbox"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="created_by"> Diinput Oleh</label></div>
                                <div class="checkbox"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="setup_by"> Eksekutor</label></div>
                                <div class="checkbox" style="margin-bottom: 0;"><label style="font-size: 13px;"><input type="checkbox" class="col-opt trigger-preview" name="opt_cols[]" value="keterangan"> Catatan Kendala</label></div>
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

                <div class="modal-footer" style="background-color: #F8FAFC; border-top: 1px solid #E2E8F0; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; text-align: right; padding: 15px 25px;">
                    <button type="button" class="btn btn-default font-bold" data-dismiss="modal" style="margin-right: 8px;"><i class="fa fa-times"></i> Tutup</button>
                    <button type="submit" id="btnDownloadCSV" class="btn btn-success font-bold" style="background-color: #10B981; border-color: #10B981; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);" disabled>
                        <i class="fa fa-download"></i> Download Excel (.xls)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DELETE (CSRF PROTECTED & LIVE SYNCED) -->
<div class="modal fade" id="mdlDeleteEnterprise" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-sm" style="margin-top: 15%;">
        <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <form action="<?= site_url("provisioning/delete_data") ?>" method="post">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" class="csrf_dynamic" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="id_tiket" id="del_id_tiket" value="">

                <div class="modal-header" style="background-color: #EF4444; padding: 15px 20px; border-radius: 8px 8px 0 0; border-bottom: none;">
                    <h4 class="modal-title" style="color: white; font-weight: 700; font-size: 15px; margin: 0;">
                        <i class="fa fa-exclamation-triangle"></i> Konfirmasi Penghapusan
                    </h4>
                </div>
                <div class="modal-body text-center" style="padding: 25px 20px;">
                    <div style="background: #FEF2F2; color: #991B1B; padding: 12px; border-radius: 6px; border-left: 4px solid #EF4444; margin-bottom: 15px; font-size: 13px; text-align: left;">
                        <strong>Peringatan!</strong><br>
                        Tindakan ini akan menghapus tiket <b><span id="del_tiket_text"></span></b> (<span id="del_vm_text" class="text-primary font-bold"></span>) secara permanen.
                    </div>
                    <p style="font-size: 13px; color: #475569; margin: 0;">Apakah Anda yakin ingin melanjutkan?</p>
                </div>
                <div class="modal-footer" style="background-color: #F8FAFC; border-top: 1px solid #E2E8F0; padding: 12px 20px; border-radius: 0 0 8px 8px; display: flex; justify-content: center; gap: 10px;">
                    <button type="button" class="btn btn-default btn-sm font-bold" data-dismiss="modal" style="margin: 0; box-shadow: 0 1px 2px rgba(0,0,0,0.05); border-radius: 4px;">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm font-bold" style="margin: 0; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2); border-radius: 4px;">Ya, Hapus Permanen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.currentKpiFilter = "";

    // ========================================================================
    // [QA FIX] XSS Sanitizer untuk DataTables DOM Injection
    // ========================================================================
    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    $(document).ready(function() {
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

        var table = $('#table-provisioning').DataTable({
            "processing": true,
            "serverSide": true,
            "autoWidth": false,
            "ajax": {
                "url": "<?= site_url("provisioning/ajax_list") ?>",
                "type": "POST",
                "data": function(d) {
                    d.<?= $this->security->get_csrf_token_name() ?> = $('.csrf_dynamic').first().val() || $('#export_csrf').val();
                    d.filter_kpi = window.currentKpiFilter;
                }
            },
            "oLanguage": {
                "sSearch": "Cari Data:",
                "sProcessing": "Memuat data dari server...",
                "sEmptyTable": "Belum ada tiket provisioning tercatat.",
                "sZeroRecords": "Pencarian tidak menemukan tiket yang sesuai."
            },
            "iDisplayLength": 25,
            "sPaginationType": "full_numbers",
            "order": [],
            "columnDefs": [
                { "targets": [0, 3, 7, 10, 11, 15], "orderable": false },
                { "targets": [8, 9, 12, 13], "visible": false },
                {
                    "targets": 6,
                    "render": function(data, type, row) {
                        var tipe = row[6] ? row[6].toString().toLowerCase() : '';
                        if(tipe === 'fresh' || tipe === 'fresh install') return '<span class="label label-primary">Fresh Install</span>';
                        if(tipe === 'clone') return '<span class="label label-warning">Clone</span>';
                        return escapeHtml(row[6]); // [QA FIX]
                    }
                },
                {
                    "targets": 11,
                    "render": function(data, type, row, meta) {
                        // [QA FIX] Sanitasi XSS
                        var creator = row[11] ? escapeHtml(row[11]) : '-';
                        var setup   = row[12] ? escapeHtml(row[12]) : '-';
                        var closed  = row[13] ? escapeHtml(row[13]) : '-';

                        return '<div style="font-size:11px; line-height:1.1; text-align:left; display:inline-block;">' +
                               '<div style="margin-bottom:2px;"><span style="color:#64748B;"><b>Add:</b> ' + creator + '</span></div>' +
                               '<div style="margin-bottom:2px;"><span class="text-primary"><b>Exe:</b> ' + setup + '</span></div>' +
                               '<div style="margin-bottom:0;"><span class="text-success"><b>Cls:</b> ' + closed + '</span></div>' +
                               '</div>';
                    }
                }
            ],
            "drawCallback": function() { $('[data-toggle="tooltip"]').tooltip(); }
        });

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
            $('html, body').animate({ scrollTop: $('#table-provisioning').offset().top - 150 }, 400);
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

        $('#table-provisioning tbody').on('click', '.btn-copy-tbl', function() {
            var textToCopy = $(this).data('text');
            var $icon = $(this);
            var originalClass = $icon.attr('class');

            var triggerCopySuccess = function() {
                $icon.removeClass('fa-copy text-primary text-danger').addClass('fa-check text-success');
                setTimeout(function() { $icon.attr('class', originalClass); }, 1000);

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
                var tempInput = $("<input>");
                $("body").append(tempInput);
                tempInput.val(textToCopy).select();
                try { document.execCommand("copy"); triggerCopySuccess(); } catch (e) {}
                tempInput.remove();
            }
        });

        $(document).on('click', '.btn-trigger-delete', function() {
            $('#del_tiket_text').text($(this).data('tiket'));
            $('#del_vm_text').text($(this).data('vm'));
            $('#del_id_tiket').val($(this).data('id'));
            $('#mdlDeleteEnterprise').modal('show');
        });

        $(document).on('click', '.btn-locked', function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.addClass('animated shake');
            setTimeout(function() { $btn.removeClass('animated shake'); }, 800);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'warning',
                    title: 'Akses Terkunci', text: 'Data telah disinkronisasi (Closed).',
                    showConfirmButton: false, timer: 4000
                });
            }
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
            $('input[name="opt_cols[]"]:checked').each(function() { selectedCols.push($(this).val()); });

            $('#previewArea').html('<div class="text-center" style="padding-top:150px;"><i class="fa fa-spinner fa-spin fa-3x text-primary"></i><br><br>Menganalisis & Memuat Data...</div>');
            $('#btnDownloadCSV').prop('disabled', true);

            $.ajax({
                url: "<?= site_url("provisioning/ajax_preview_export") ?>",
                type: "POST",
                dataType: "json",
                data: {
                    filter_type: filterType,
                    tgl_mulai: start,
                    tgl_selesai: end,
                    opt_cols: selectedCols,
                    "<?= $this->security->get_csrf_token_name() ?>": $('#export_csrf').val()
                },
                success: function(res) {
                    // [QA FIX] Global CSRF Sync (Mencegah Stale Token 403)
                    if (res.csrf_hash) {
                        $('#export_csrf').val(res.csrf_hash);
                        $('.csrf_dynamic').val(res.csrf_hash);
                    }

                    if(res.status === 'success') {
                        if (res.total_rows === 0) {
                            $('#previewArea').html('<div class="text-center" style="padding-top:150px; color:#EF4444;"><i class="fa fa-warning fa-3x" style="margin-bottom:10px;"></i><br><span style="font-size: 14px;">Tidak ada tiket pada filter waktu tersebut.</span></div>');
                            $('#btnDownloadCSV').prop('disabled', true);
                            return;
                        }

                        $('#btnDownloadCSV').prop('disabled', false);

                        var sum = res.summary;
                        var html = '<div style="margin-bottom:15px; background:#F1F5F9; padding:12px; border:1px solid #E2E8F0; border-radius:6px; flex-shrink: 0;">';
                        html += '<strong style="color:#1E293B; display:block; margin-bottom:8px; font-size:13px;"><i class="fa fa-bar-chart"></i> Executive Summary</strong>';
                        html += '<table class="table table-bordered" style="font-size:11px; margin-bottom:0; background:#fff;">';
                        html += '<tr style="background:#E2E8F0;"><th class="text-center">Total Tiket</th><th class="text-center">Total VM</th><th class="text-center">vCPU (Core)</th><th class="text-center">RAM (GB)</th><th class="text-center">Disk (GB)</th><th class="text-center">Production</th><th class="text-center">Non-Prod</th><th class="text-center">Fresh</th><th class="text-center">Clone</th></tr>';
                        html += '<tr><td align="center" style="font-weight:bold;">'+sum.total_tiket+'</td><td align="center" style="font-weight:bold;">'+sum.total_vm+'</td><td align="center" style="color:#D9534F; font-weight:bold;">'+sum.total_cpu+'</td><td align="center" style="color:#D9534F; font-weight:bold;">'+sum.total_ram+'</td><td align="center" style="color:#D9534F; font-weight:bold;">'+sum.total_disk+'</td><td align="center">'+sum.total_prod+'</td><td align="center">'+sum.total_dev+'</td><td align="center">'+sum.total_fresh+'</td><td align="center">'+sum.total_clone+'</td></tr>';
                        html += '</table></div>';

                        html += '<div style="flex-grow: 1; border: 1px solid #E2E8F0; border-radius: 4px; overflow: hidden; background: #fff; padding: 10px;">';
                        html += '<table id="previewDataTable" class="table table-striped table-bordered" style="width:100%; font-size: 11px; white-space: nowrap;">';
                        html += '<thead style="background-color: #34495E; color: white;"><tr>';
                        $.each(res.headers, function(i, h) { html += '<th style="text-align:center;">' + escapeHtml(h) + '</th>'; }); // [QA FIX] Sanitize Table Head
                        html += '</tr></thead><tbody>';

                        $.each(res.data, function(row_idx, row_obj) {
                            html += '<tr>';
                            $.each(res.columns, function(i, col_key) { html += '<td style="text-align:center;">' + escapeHtml(row_obj[col_key]) + '</td>'; }); // [QA FIX] Sanitize Data
                            html += '</tr>';
                        });
                        html += '</tbody></table></div>';

                        if ($.fn.DataTable.isDataTable('#previewDataTable')) $('#previewDataTable').DataTable().clear().destroy();
                        $('#previewArea').html(html);
                        $('#previewDataTable').DataTable({
                            "pageLength": 50, "lengthMenu": [[10, 50, 100, -1], [10, 50, 100, "All"]],
                            "scrollX": true, "scrollY": "300px", "scrollCollapse": true, "autoWidth": false,
                            "bFilter": true, "bInfo": true,
                            "oLanguage": { "sSearch": "Filter:", "sLengthMenu": "_MENU_ Data", "sInfo": "_START_ - _END_ dari _TOTAL_" }
                        });
                    }
                },
                error: function() {
                    $('#previewArea').html('<div class="alert alert-danger text-center" style="margin-top:20px;">Gagal menghubungi server.</div>');
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

        $('.trigger-preview, input[name="opt_cols[]"], #start_date, #end_date').on('change input', function() {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(loadPreviewData, 500);
        });

        $('#modalExportExcel').on('shown.bs.modal', function () { loadPreviewData(); });
    });
</script>
