<?php
/**
 * ========================================================================
 * File Name    : index.php
 * Modul        : Dashboard Executive & Analytics
 * Architecture : Localized Assets, No CDN Tracker
 * ========================================================================
 */
?>

<!-- [ENTERPRISE FIX]: PEMANGGILAN LOKAL ASET DATERANGEPICKER (Mencegah Tracking Block) -->
<script src="<?= base_url("asset/js/moment/moment.min.js") ?>"></script>
<script src="<?= base_url("asset/js/daterangepicker/daterangepicker.min.js") ?>"></script>
<link rel="stylesheet" type="text/css" href="<?= base_url(
    "asset/css/daterangepicker/daterangepicker.css",
) ?>" />

<div class="right_col" role="main" style="min-height: 100vh;">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>Dashboard Operasional <small>Resource Management</small></h3>
            </div>
        </div>

        <div id="alert-container-dashboard">
            <?php
            // 1. Tarik semua kemungkinan session
            $alerts =
                $this->session->flashdata("alerts") ??
                ($this->session->flashdata("success") ?? $this->session->flashdata("error"));

            // 2. PEMBUNUHAN SESSION: Wajib dilakukan agar tidak menjadi zombie ke halaman lain
            $this->session->unset_userdata("alerts");
            $this->session->unset_userdata("success");
            $this->session->unset_userdata("error");

            if (is_string($alerts)) {
                $type = $this->session->flashdata("error") ? "error" : "success";
                $alerts = [[$type, $alerts]];
            }

            if (is_array($alerts) && count($alerts) > 0):
                foreach ($alerts as $alert):
                    if (
                        is_array($alert) &&
                        isset($alert[0]) &&
                        isset($alert[1]) &&
                        trim($alert[1]) !== ""
                    ):

                        $class =
                            $alert[0] == "error" || $alert[0] == "danger"
                                ? "alert-danger"
                                : ($alert[0] == "warning"
                                    ? "alert-warning"
                                    : "alert-success");
                        $icon =
                            $alert[0] == "warning"
                                ? '<i class="fa fa-exclamation-triangle"></i>'
                                : "";
                        ?>
                        <div class="alert <?= $class ?> alert-dismissible fade in auto-alert" role="alert" style="margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 4px;">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            <strong>Notifikasi:</strong> <?= $icon . " " . $alert[1] ?>
                        </div>
            <?php
                    endif;
                endforeach;
            endif;
            ?>
        </div>

        <div class="clearfix"></div>

        <div class="row" style="margin-bottom: 5px;">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel" style="border-top: 3px solid #2A3F54; border-radius: 8px;">
                    <div class="x_content">
                        <?php $this->load->view("dashboard/components/widget_tiles"); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel" style="border-top: 4px solid #E74C3C; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div class="x_title">
                        <h2 style="font-weight: bold;"><i class="fa fa-list-alt"></i> Daftar Antrean Tugas</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <?php $this->load->view("dashboard/components/tab_antrean"); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel" style="border-top: 4px solid #2A3F54; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">

                    <div class="x_title" style="border-bottom: 2px solid #e6e9ed; padding-bottom: 10px;">
                        <h2 style="font-weight: bold; color: #2A3F54; font-size: 16px; margin-top: 5px;">
                            <i class="fa fa-line-chart"></i> Laporan Analitik Operasional & Kapasitas
                        </h2>

                        <div class="nav navbar-right panel_toolbox" style="min-width: 550px;">
                            <div style="display: flex; justify-content: flex-end; align-items: center;">

                                <span class="text-muted" style="font-size:11px; margin-right:5px; font-weight:bold;">Awal Minggu:</span>
                                <select id="chartCutoff" class="form-control input-sm" style="display:inline-block; width:90px; margin-right:15px; font-weight:bold; border-radius:4px;">
                                    <option value="1">Senin</option>
                                    <option value="2">Selasa</option>
                                    <option value="3">Rabu</option>
                                    <option value="4">Kamis</option>
                                    <option value="5">Jumat</option>
                                    <option value="6">Sabtu</option>
                                    <option value="0">Minggu</option>
                                </select>

                                <select id="chartViewMode" class="form-control input-sm" style="display:inline-block; width:100px; margin-right:8px; font-weight:bold; border-radius:4px;">
                                    <option value="auto">Auto View</option>
                                    <option value="daily">Harian</option>
                                    <option value="weekly">Mingguan</option>
                                    <option value="monthly">Bulanan</option>
                                </select>

                                <i class="fa fa-calendar text-muted" style="margin-right:8px;"></i>
                                <input type="text" id="reportrange" class="form-control input-sm" style="width: 200px; border-radius: 4px; cursor: pointer; background: #fcfcfc; text-align: center; font-weight: bold; color: #2A3F54;" readonly />

                                <button id="btnExport" class="btn btn-sm btn-success" style="margin-bottom: 0; margin-left: 8px;" title="Export Laporan Excel">
                                    <i class="fa fa-file-excel-o"></i>
                                </button>

                                <form id="formExportExcel" action="<?= site_url(
                                    "dashboard/export_excel",
                                ) ?>" method="post" style="display: none;">
                                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                    <input type="hidden" name="start_date" id="export_start_date">
                                    <input type="hidden" name="end_date" id="export_end_date">
                                </form>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </div>

                    <div class="x_content" style="padding-top: 15px;">

                        <h4 style="font-weight:bold; color:#2A3F54; font-size:13px; margin-bottom:15px; border-bottom:1px dashed #ddd; padding-bottom:5px;">
                            Status Penyelesaian Tiket (Selesai vs Antrean)
                        </h4>
                        <div class="row" style="margin-bottom: 20px;">
                            <div class="col-md-3 col-sm-6">
                                <div style="background:#fff; padding:12px; border-radius:6px; text-align:center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 3px solid #3498db;">
                                    <span style="font-weight:bold; color:#73879C; font-size:11px;">PROVISIONING</span>
                                    <div style="margin-top:8px;">
                                        <span id="wdgProvSel" style="font-size:18px; font-weight:bold; color:#16A085;">0</span> <small>Selesai</small>
                                        <span style="margin:0 5px; color:#ddd;">|</span>
                                        <span id="wdgProvAnt" style="font-size:18px; font-weight:bold; color:#E74C3C;">0</span> <small>Antre</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div style="background:#fff; padding:12px; border-radius:6px; text-align:center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 3px solid #9b59b6;">
                                    <span style="font-weight:bold; color:#73879C; font-size:11px;">URR (UP/DOWNGRADE)</span>
                                    <div style="margin-top:8px;">
                                        <span id="wdgUrrSel" style="font-size:18px; font-weight:bold; color:#16A085;">0</span> <small>Selesai</small>
                                        <span style="margin:0 5px; color:#ddd;">|</span>
                                        <span id="wdgUrrAnt" style="font-size:18px; font-weight:bold; color:#E74C3C;">0</span> <small>Antre</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div style="background:#fff; padding:12px; border-radius:6px; text-align:center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 3px solid #e67e22;">
                                    <span style="font-weight:bold; color:#73879C; font-size:11px;">RESTART VM</span>
                                    <div style="margin-top:8px;">
                                        <span id="wdgResSel" style="font-size:18px; font-weight:bold; color:#16A085;">0</span> <small>Selesai</small>
                                        <span style="margin:0 5px; color:#ddd;">|</span>
                                        <span id="wdgResAnt" style="font-size:18px; font-weight:bold; color:#E74C3C;">0</span> <small>Antre</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div style="background:#fff; padding:12px; border-radius:6px; text-align:center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 3px solid #34495E;">
                                    <span style="font-weight:bold; color:#73879C; font-size:11px;">SWITCH IP</span>
                                    <div style="margin-top:8px;">
                                        <span id="wdgSwiSel" style="font-size:18px; font-weight:bold; color:#16A085;">0</span> <small>Selesai</small>
                                        <span style="margin:0 5px; color:#ddd;">|</span>
                                        <span id="wdgSwiAnt" style="font-size:18px; font-weight:bold; color:#E74C3C;">0</span> <small>Antre</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h4 style="font-weight:bold; color:#2A3F54; font-size:13px; margin-bottom:15px; border-bottom:1px dashed #ddd; padding-bottom:5px; margin-top:25px;">
                            Akumulasi Penambahan Kapasitas Fisik
                        </h4>
                        <div class="row" style="margin-bottom: 35px;">
                            <div class="col-md-4 col-sm-4">
                                <div style="background:#fcfcfc; padding:15px; border-radius:6px; text-align:center; border: 1px solid #eee;">
                                    <span style="font-weight:bold; color:#00838F; font-size:13px;">TOTAL PENAMBAHAN CPU</span><br>
                                    <span id="widgetCpu" style="font-size:24px; font-weight:bold; color:#1A252F;">0</span> <small>vCPU</small>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-4">
                                <div style="background:#fcfcfc; padding:15px; border-radius:6px; text-align:center; border: 1px solid #eee;">
                                    <span style="font-weight:bold; color:#7B1FA2; font-size:13px;">TOTAL PENAMBAHAN RAM</span><br>
                                    <span id="widgetRam" style="font-size:24px; font-weight:bold; color:#1A252F;">0</span> <small>GB</small>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-4">
                                <div style="background:#fcfcfc; padding:15px; border-radius:6px; text-align:center; border: 1px solid #eee;">
                                    <span style="font-weight:bold; color:#D84315; font-size:13px;">TOTAL PENAMBAHAN DISK</span><br>
                                    <span id="widgetDisk" style="font-size:24px; font-weight:bold; color:#1A252F;">0</span> <small>GB</small>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin-top: 40px; margin-bottom: 40px; background: #fdfdfd; border: 1px solid #e1e1e1; padding: 20px 15px; border-radius: 8px;">
                            <div class="col-md-12">
                                <h3 style="font-weight:bold; color:#2c3e50; border-bottom:2px solid #eee; padding-bottom:10px; margin-top: 0;">
                                    <i class="fa fa-cubes text-primary"></i> Master Inventory: Pertumbuhan Total Virtual Machine (Kumulatif)
                                </h3>
                                <p style="color: #777; font-size: 13px; margin-bottom: 20px;">
                                    *Grafik ini merepresentasikan jumlah fisik VM secara total di Data Center (Eksisting Baseline + Penambahan - Penghapusan).
                                    Garis merah menunjukkan persentase <b>VM Growth</b> secara aktual.
                                </p>
                            </div>
                            <div class="col-md-12">
                                <div style="position: relative; height: 320px; width: 100%;"><canvas id="chartVmCumulative"></canvas></div>
                            </div>
                        </div>

                        <h4 style="font-weight:bold; color:#2A3F54; font-size:15px; margin-bottom:25px; border-bottom:2px dashed #ddd; padding-bottom:8px; margin-top:25px;">
                            <i class="fa fa-tasks"></i> Rincian Beban Kerja Operasional (Penambahan/Pengurangan)
                        </h4>

                        <div class="row" style="margin-bottom: 30px;">
                            <div class="col-md-12">
                                <h4 class="text-primary" style="font-weight:bold; border-bottom:1px solid #eee; padding-bottom:5px;"><i class="fa fa-ticket"></i> Analitik Berdasarkan Tiket</h4>
                            </div>
                            <div class="col-md-6 col-sm-12" style="border-right: 1px dashed #eee;">
                                <div style="position: relative; height: 250px; width: 100%;"><canvas id="chartTiketProv"></canvas></div>
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <div style="position: relative; height: 250px; width: 100%;"><canvas id="chartTiketURR"></canvas></div>
                            </div>
                        </div>

                        <div class="row" style="margin-bottom: 30px;">
                            <div class="col-md-12">
                                <h4 class="text-success" style="font-weight:bold; border-bottom:1px solid #eee; padding-bottom:5px;"><i class="fa fa-desktop"></i> Analitik Berdasarkan Virtual Machine</h4>
                            </div>
                            <div class="col-md-6 col-sm-12" style="border-right: 1px dashed #eee;">
                                <div style="position: relative; height: 200px; width: 100%;"><canvas id="chartVmProv"></canvas></div>
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <div style="position: relative; height: 200px; width: 100%;"><canvas id="chartVmURR"></canvas></div>
                            </div>
                        </div>

                        <div class="row" style="margin-bottom: 30px;">
                            <div class="col-md-12">
                                <h4 style="font-weight:bold; color:#00838F; border-bottom:1px solid #eee; padding-bottom:5px;"><i class="fa fa-cogs"></i> Analisis Alokasi vCPU</h4>
                            </div>
                            <div class="col-md-6 col-sm-12" style="border-right: 1px dashed #eee;">
                                <div style="position: relative; height: 200px; width: 100%;"><canvas id="chartCpuProv"></canvas></div>
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <div style="position: relative; height: 200px; width: 100%;"><canvas id="chartCpuURR"></canvas></div>
                            </div>
                        </div>

                        <div class="row" style="margin-bottom: 30px;">
                            <div class="col-md-12">
                                <h4 style="font-weight:bold; color:#7B1FA2; border-bottom:1px solid #eee; padding-bottom:5px;"><i class="fa fa-tachometer"></i> Analisis Alokasi Memory (RAM)</h4>
                            </div>
                            <div class="col-md-6 col-sm-12" style="border-right: 1px dashed #eee;">
                                <div style="position: relative; height: 200px; width: 100%;"><canvas id="chartRamProv"></canvas></div>
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <div style="position: relative; height: 200px; width: 100%;"><canvas id="chartRamURR"></canvas></div>
                            </div>
                        </div>

                        <div class="row" style="margin-bottom: 30px;">
                            <div class="col-md-12">
                                <h4 style="font-weight:bold; color:#D84315; border-bottom:1px solid #eee; padding-bottom:5px;"><i class="fa fa-hdd-o"></i> Analisis Alokasi Storage (Disk)</h4>
                            </div>
                            <div class="col-md-6 col-sm-12" style="border-right: 1px dashed #eee;">
                                <div style="position: relative; height: 200px; width: 100%;"><canvas id="chartDiskProv"></canvas></div>
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <div style="position: relative; height: 200px; width: 100%;"><canvas id="chartDiskURR"></canvas></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel" style="border-top: 4px solid #34495E; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div class="x_title">
                        <h2 style="font-weight: bold;"><i class="fa fa-rss"></i> Aktivitas Terbaru Tim Ops</h2>
                        <ul class="nav navbar-right panel_toolbox">
                            <li><a href="javascript:void(0);" onclick="loadTimelineActivity()"><i class="fa fa-refresh text-primary"></i></a></li>
                        </ul>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content" id="panel-activity-timeline" style="padding-top: 15px; background-color: #fcfcfc; border-radius: 0 0 8px 8px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var DashboardConfig = {
        siteUrl: '<?= site_url() ?>',
        urlNotif: '<?= site_url("dashboard/ajax_get_notif") ?>',
        urlProv: '<?= site_url("dashboard/ajax_get_pending_table/provisioning") ?>',
        urlChange: '<?= site_url("dashboard/ajax_get_pending_table/change_vm") ?>',
        urlSwitch: '<?= site_url("dashboard/ajax_get_pending_table/switch_ip") ?>',
        urlRestart: '<?= site_url("dashboard/ajax_get_pending_table/restart_vm") ?>',
        urlSubmitWf: '<?= site_url("dashboard/ajax_submit_workflow") ?>',
        urlRecent: '<?= site_url("dashboard/ajax_get_recent_activity") ?>',
        urlTicketDetail: '<?= site_url("dashboard/ajax_get_ticket_detail") ?>',
        urlSubmitHambatan: '<?= site_url("dashboard/ajax_submit_hambatan") ?>',
        urlChartData: '<?= site_url("dashboard/ajax_get_chart_data") ?>'
    };
</script>

<script>
    $(document).ready(function() {
        $('#btnExport').off('click').on('click', function(e) {
            e.preventDefault();

            var picker = $('#reportrange').data('daterangepicker');

            if (picker) {
                var start = picker.startDate.format('YYYY-MM-DD');
                var end = picker.endDate.format('YYYY-MM-DD');

                if (!start || !end || start === 'Invalid date' || end === 'Invalid date') {
                    alert('Sistem mendeteksi tanggal kosong. Silakan pilih rentang tanggal pada kalender terlebih dahulu!');
                    return false;
                }

                $('#export_start_date').val(start);
                $('#export_end_date').val(end);

                $('#formExportExcel').submit();
            } else {
                alert('Sistem sedang memuat kalender. Silakan coba beberapa saat lagi.');
            }
        });
    });
</script>
