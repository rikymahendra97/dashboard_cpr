<?php
defined("BASEPATH") or exit("No direct script access allowed");

/**
 * ========================================================================
 * File Name    : Dashboard.php
 * Modul        : Dashboard Executive & Analytics
 * Architecture : Strict Typing, HTML Response Builder
 * ========================================================================
 */
class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (empty($this->session->userdata("user_data"))) {
            redirect("auth/login");
        }

        // Anti-Cache Header
        $this->output->set_header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        $this->output->set_header("Cache-Control: post-check=0, pre-check=0", false);
        $this->output->set_header("Pragma: no-cache");

        $this->load->model(["dashboard_model", "user_model"]);
        $this->db->query("SET time_zone = '+07:00'");
    }

    public function index()
    {
        $user_session = $this->session->userdata("user_data");
        $data["id"] = $user_session;
        $data["user_session"] = $this->user_model->get((int) $user_session["id_user"]);
        $data["title"] = "Dashboard Operasional";

        // Injeksi Dynamic JS Loader
        $data["custom_js"] = ["dashboard_core.js"];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("dashboard/index", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("dashboard/components/modals", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function ajax_get_notif()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }
        $id_role = (int) $this->session->userdata("user_data")["id_role"];
        $counts = $this->dashboard_model->get_notification_counts($id_role);
        $this->output->set_content_type("application/json")->set_output(json_encode($counts));
    }

    public function ajax_get_pending_table(string $tipe_modul)
    {
        // Kembalikan HTTP 403 JSON jika diakses langsung lewat URL Browser (Bukan AJAX)
        if (!$this->input->is_ajax_request()) {
            $this->output->set_status_header(403)->set_content_type("application/json")->set_output(
                json_encode([
                    "data" => [],
                    "error" => "Akses ditolak: Permintaan harus melalui AJAX (DataTables).",
                ]),
            );
            return;
        }

        $user_session = $this->session->userdata("user_data");
        $id_role = isset($user_session["id_role"]) ? (int) $user_session["id_role"] : null;

        if ($id_role === null) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(json_encode(["data" => []]));
            return;
        }

        $tasks = $this->dashboard_model->get_pending_tasks($id_role, $tipe_modul);
        $this->output
            ->set_content_type("application/json")
            ->set_output(json_encode(["data" => $tasks]));
    }

    public function ajax_get_recent_activity()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }
        $activities = $this->dashboard_model->get_recent_activities(8);
        $this->output
            ->set_content_type("application/json")
            ->set_output(json_encode(["data" => $activities]));
    }

    public function ajax_get_ticket_detail()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }
        $id_trx = $this->input->get("id_trx", true);
        $modul = $this->input->get("modul", true);
        $detail = $this->dashboard_model->get_ticket_detail($id_trx, $modul);

        if (!$detail) {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "html" => '<div class="alert alert-danger">Tiket tidak ditemukan.</div>',
                    "status_tiket" => "",
                ]),
            );
            return;
        }

        $status_tiket = $detail["status_eksekusi"] ?? ($detail["progres_tiket"] ?? "Unknown");

        $html =
            '<div class="table-responsive"><table class="table table-bordered table-striped" style="font-size:12px; margin-bottom:0;">';

        if ($modul === "change_vm") {
            $html .=
                '<tr class="info"><th colspan="2"><i class="fa fa-sliders"></i> Informasi Spesifikasi Target VM</th></tr>';
            $html .=
                '<tr><td width="35%" class="font-bold">Target VM</td><td><strong class="text-primary">' .
                html_escape($detail["virtual_machine_name"]) .
                "</strong></td></tr>";
            $html .=
                '<tr><td class="font-bold">Skenario</td><td><span class="label label-warning">' .
                html_escape($detail["jenis_perubahan"]) .
                "</span></td></tr>";

            $cpu_old = $detail["current_cpu_count"];
            $cpu_new = $detail["target_cpu_count"];
            $cpu_text =
                $cpu_old == $cpu_new
                    ? $cpu_old .
                        ' Core <span class="text-muted" style="font-style:italic;">(Tetap)</span>'
                    : $cpu_old .
                        ' Core <i class="fa fa-long-arrow-right text-muted" style="margin: 0 8px;"></i> <strong class="text-success">' .
                        $cpu_new .
                        " Core</strong>";

            $ram_old = $detail["current_memory_mb"] / 1024;
            $ram_new = $detail["target_memory_mb"] / 1024;
            $ram_text =
                $ram_old == $ram_new
                    ? $ram_old .
                        ' GB <span class="text-muted" style="font-style:italic;">(Tetap)</span>'
                    : $ram_old .
                        ' GB <i class="fa fa-long-arrow-right text-muted" style="margin: 0 8px;"></i> <strong class="text-success">' .
                        $ram_new .
                        " GB</strong>";

            $html .= '<tr><td class="font-bold">Perubahan CPU</td><td>' . $cpu_text . "</td></tr>";
            $html .= '<tr><td class="font-bold">Perubahan RAM</td><td>' . $ram_text . "</td></tr>";

            $disks = $this->dashboard_model->get_change_disk_details((int) $detail["id_change"]);
            if (!empty($disks)) {
                $disk_html = '<ul style="padding-left:0; margin-bottom:0; list-style-type:none;">';
                foreach ($disks as $d) {
                    $raw_drive = trim($d["nama_drive"]);
                    $clean_drive = rtrim($raw_drive, ":\\/");
                    $display_drive =
                        strlen($clean_drive) === 1 && ctype_alpha($clean_drive)
                            ? "Drive " . strtoupper($clean_drive) . ":"
                            : $raw_drive;
                    $tipe_eksekusi_db = strtoupper(trim($d["tipe_eksekusi"]));

                    $tipe_badge =
                        strpos($tipe_eksekusi_db, "NEW") !== false
                            ? '<span class="label label-info" style="font-size: 10px; margin-right: 6px; padding: 3px 5px;"><i class="fa fa-plus-circle"></i> New Disk</span>'
                            : '<span class="label label-primary" style="font-size: 10px; margin-right: 6px; padding: 3px 5px;"><i class="fa fa-arrows-h"></i> Extend</span>';

                    $disk_html .=
                        '<li style="margin-bottom: 8px; display: flex; align-items: center;">';
                    $disk_html .=
                        $tipe_badge .
                        '<span class="label label-default" style="font-size: 11px; letter-spacing: 0.5px; padding: 4px 7px;">' .
                        html_escape($display_drive) .
                        "</span> ";
                    $disk_html .=
                        '<span class="text-success font-bold" style="margin-left: 10px;">+' .
                        $d["additional_gb"] .
                        " GB</span> ";
                    $disk_html .=
                        '<i class="fa fa-long-arrow-right text-muted" style="margin: 0 10px; font-size: 14px;"></i> ';
                    $disk_html .=
                        '<span class="text-primary font-bold" style="font-size: 13px;">' .
                        $d["end_state_gb"] .
                        " GB</span></li>";
                }
                $disk_html .= "</ul>";
                $html .=
                    '<tr><td class="font-bold" style="vertical-align: middle;">Penambahan Disk</td><td>' .
                    $disk_html .
                    "</td></tr>";
            }
            $html .=
                '<tr><td class="font-bold">Keterangan User</td><td style="white-space:pre-wrap; color:#555;">' .
                html_escape($detail["keterangan_request_asli"]) .
                "</td></tr>";

            if ($status_tiket === "Telah Dieksekusi") {
                $html .=
                    '<tr class="warning"><th colspan="2" style="color:#8a6d3b;"><i class="fa fa-wrench"></i> Hasil Tindakan Eksekutor</th></tr>';
                $html .=
                    '<tr><td class="font-bold">Waktu Eksekusi</td><td><strong>' .
                    date("d-M-Y H:i", strtotime($detail["tanggal_eksekusi"])) .
                    " WIB</strong></td></tr>";
                $html .=
                    '<tr><td class="font-bold">Catatan Eksekutor</td><td style="font-style:italic; color:#c0392b;">"' .
                    nl2br(html_escape($detail["catatan_eksekusi"])) .
                    '"</td></tr>';
            }
        } elseif ($modul === "restart_vm") {
            $html .=
                '<tr class="info"><th colspan="2"><i class="fa fa-refresh"></i> Informasi Request Restart VM</th></tr>';
            $html .=
                '<tr><td width="35%" class="font-bold">Target VM</td><td><strong class="text-primary">' .
                html_escape($detail["virtual_machine_name"]) .
                "</strong></td></tr>";
            $cls_dw = $detail["jenis_downtime"] == "Planned" ? "label-success" : "label-danger";
            $html .=
                '<tr><td class="font-bold">Klasifikasi</td><td><span class="label ' .
                $cls_dw .
                '">' .
                html_escape($detail["jenis_downtime"]) .
                "</span></td></tr>";
            $html .=
                '<tr><td class="font-bold">Alasan Restart</td><td>' .
                html_escape($detail["root_cause"]) .
                "</td></tr>";
            $html .=
                '<tr><td class="font-bold">Keterangan User</td><td style="white-space:pre-wrap; color:#555;">' .
                html_escape($detail["keterangan_request"]) .
                "</td></tr>";

            if ($status_tiket === "Telah Dieksekusi") {
                $html .=
                    '<tr class="warning"><th colspan="2" style="color:#8a6d3b;"><i class="fa fa-wrench"></i> Hasil Tindakan Eksekutor</th></tr>';
                $html .=
                    '<tr><td class="font-bold">Waktu Start</td><td><strong class="text-danger">' .
                    date("d-M-Y H:i", strtotime($detail["start_downtime"])) .
                    " WIB</strong></td></tr>";
                $html .=
                    '<tr><td class="font-bold">Waktu Finish</td><td><strong class="text-danger">' .
                    date("d-M-Y H:i", strtotime($detail["finish_downtime"])) .
                    " WIB</strong></td></tr>";
                $html .=
                    '<tr><td class="font-bold">Total Downtime</td><td><strong>' .
                    $detail["durasi_downtime_menit"] .
                    " Menit</strong></td></tr>";
                $html .=
                    '<tr><td class="font-bold">Catatan Eksekutor</td><td style="font-style:italic; color:#c0392b;">"' .
                    nl2br(html_escape($detail["catatan_eksekusi"])) .
                    '"</td></tr>';
            }
        } elseif ($modul === "switch_ip") {
            $jenis = html_escape($detail["jenis_switch"]);
            $label_jenis = $jenis === "Swap" ? "label-warning" : "label-primary";

            $html .=
                '<tr class="info"><th colspan="2"><i class="fa fa-exchange"></i> Informasi Pertukaran Network (IP)</th></tr>';
            $html .=
                '<tr><td width="35%" class="font-bold">Skenario Switch</td><td><span class="label ' .
                $label_jenis .
                '">' .
                $jenis .
                "</span></td></tr>";

            $details_ip = $this->db
                ->get_where("trx_vm_switch_ip_detail", ["id_switch" => $detail["id_switch"]])
                ->result_array();

            $ip_html = "";
            if ($jenis === "Swap") {
                $ip_html .=
                    '<div style="margin-bottom:8px; font-weight:bold; color:#d35400;"><i class="fa fa-refresh"></i> Pertukaran Silang (Swap) antar 2 VM:</div>';
            } else {
                $ip_html .=
                    '<div style="margin-bottom:8px; font-weight:bold; color:#2980b9;"><i class="fa fa-arrow-right"></i> Perubahan IP VM (Standard):</div>';
            }

            $ip_html .=
                '<table class="table table-condensed table-bordered" style="margin-bottom:0; background-color:#fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">';
            $ip_html .=
                '<tr class="active"><th style="font-size:11px;">Nama VM Target</th><th style="font-size:11px;" class="text-center">Kondisi Lama</th><th style="font-size:11px;" class="text-center">Target IP Baru</th></tr>';

            foreach ($details_ip as $ip) {
                $ip_html .= "<tr>";
                $ip_html .=
                    '<td style="font-size:11px;"><strong>' .
                    html_escape($ip["nama_vm_lama"]) .
                    "</strong></td>";
                $ip_html .=
                    '<td style="font-size:11px;" class="text-center text-muted">' .
                    html_escape($ip["ip_lama"]) .
                    "</td>";
                $ip_html .=
                    '<td style="font-size:11px;" class="text-center"><strong class="text-success">' .
                    html_escape($ip["ip_baru"]) .
                    "</strong></td>";
                $ip_html .= "</tr>";
            }
            $ip_html .= "</table>";

            $html .=
                '<tr><td colspan="2" style="background-color:#fcfcfc; padding: 15px;">' .
                $ip_html .
                "</td></tr>";
            $html .=
                '<tr><td class="font-bold">Deskripsi Request</td><td style="white-space:pre-wrap; color:#555;">' .
                html_escape($detail["deskripsi_permintaan"]) .
                "</td></tr>";

            if ($status_tiket === "Telah Dieksekusi") {
                $html .=
                    '<tr class="warning"><th colspan="2" style="color:#8a6d3b;"><i class="fa fa-wrench"></i> Hasil Tindakan Eksekutor</th></tr>';
                $html .=
                    '<tr><td class="font-bold">Waktu Eksekusi</td><td><strong>' .
                    date("d-M-Y H:i", strtotime($detail["tanggal_eksekusi"])) .
                    " WIB</strong></td></tr>";
                $html .=
                    '<tr><td class="font-bold">Catatan Eksekutor</td><td style="font-style:italic; color:#c0392b;">"' .
                    nl2br(html_escape($detail["catatan_eksekusi"])) .
                    '"</td></tr>';
            }
        } elseif ($modul === "provisioning") {
            $html .=
                '<tr class="info"><th colspan="2"><i class="fa fa-laptop"></i> Detail Server Target</th></tr>';
            $html .=
                '<tr><td width="35%" class="font-bold">Target Server</td><td><strong class="text-primary">' .
                html_escape($detail["nama_server"]) .
                "</strong></td></tr>";
            $html .=
                '<tr><td class="font-bold">Environment / App</td><td>' .
                html_escape($detail["environment"]) .
                " / " .
                html_escape($detail["aplikasi"]) .
                "</td></tr>";
            $html .=
                '<tr><td class="font-bold">Spesifikasi Server</td><td>' .
                html_escape($detail["cpu"]) .
                " vCPU | " .
                html_escape($detail["ram"]) .
                " GB RAM | " .
                html_escape($detail["disk"]) .
                " GB Disk</td></tr>";
        }

        $html .= "</table></div>";

        $this->output->set_content_type("application/json")->set_output(
            json_encode([
                "html" => $html,
                "status_tiket" => $status_tiket,
            ]),
        );
    }

    public function ajax_get_chart_data()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $start_date = $this->input->get("start_date", true) ?: date("Y-m-d", strtotime("-29 days"));
        $end_date = $this->input->get("end_date", true) ?: date("Y-m-d");
        $view_mode = $this->input->get("view_mode", true) ?: "auto";
        $dow_cutoff = (int) ($this->input->get("dow", true) ?: 1);

        $prov_raw = $this->dashboard_model->get_strict_executed_tickets(
            $start_date,
            $end_date,
            "tiket_provisioning",
            "tanggal_masuk_tiket",
            "id_tiket",
            "progres_tiket",
            ["done", "in progress"],
        );
        $urr_raw = $this->dashboard_model->get_strict_executed_tickets(
            $start_date,
            $end_date,
            "trx_vm_change_resource",
            "created_at",
            "id_change",
            "status_eksekusi",
            ["Telah Dieksekusi", "Selesai Verified"],
        );
        $resource_raw = $this->dashboard_model->get_resource_growth_stats($start_date, $end_date);
        $ticket_summary = $this->dashboard_model->get_ticket_status_summary($start_date, $end_date);

        $mapped_prov = [];
        foreach ($prov_raw as $p) {
            $mapped_prov[$p["tgl"]][] = $p;
        }
        $mapped_urr = [];
        foreach ($urr_raw as $u) {
            $mapped_urr[$u["tgl"]][] = $u;
        }
        $mapped_res_p = [];
        foreach ($resource_raw["prov"] as $r) {
            $mapped_res_p[$r["tgl"]] = $r;
        }
        $mapped_res_u = [];
        foreach ($resource_raw["urr_res"] as $r) {
            $mapped_res_u[$r["tgl"]] = $r;
        }
        $mapped_res_ud = [];
        foreach ($resource_raw["urr_disk"] as $r) {
            $mapped_res_ud[$r["tgl"]] = $r;
        }

        $sum_cpu =
            array_sum(array_column($resource_raw["prov"], "cpu")) +
            array_sum(array_column($resource_raw["urr_res"], "cpu"));
        $sum_ram =
            array_sum(array_column($resource_raw["prov"], "ram")) +
            array_sum(array_column($resource_raw["urr_res"], "ram"));
        $sum_disk =
            array_sum(array_column($resource_raw["prov"], "disk")) +
            array_sum(array_column($resource_raw["urr_disk"], "disk"));

        if ($view_mode == "auto") {
            $diff_days = round((strtotime($end_date) - strtotime($start_date)) / 86400);
            if ($diff_days > 90) {
                $view_mode = "monthly";
            } elseif ($diff_days > 31) {
                $view_mode = "weekly";
            } else {
                $view_mode = "daily";
            }
        }

        $buckets = [];
        $current = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify("+1 day");

        while ($current < $end) {
            $date_str = $current->format("Y-m-d");

            if ($view_mode == "weekly") {
                $week_start = clone $current;
                while ($week_start->format("w") != $dow_cutoff) {
                    $week_start->modify("-1 day");
                }
                $b_key = $week_start->format("Y-m-d");
                $b_label = "Wk " . $week_start->format("d M");
            } elseif ($view_mode == "monthly") {
                $b_key = $current->format("Y-m");
                $b_label = $current->format("M Y");
            } else {
                $b_key = $date_str;
                $b_label = $current->format("d M");
            }

            if (!isset($buckets[$b_key])) {
                $buckets[$b_key] = [
                    "label" => $b_label,
                    "dates" => [],
                    "prov_t" => 0,
                    "urr_t" => 0,
                    "prov_v" => [],
                    "urr_v" => [],
                    "cpu_p" => 0,
                    "ram_p" => 0,
                    "disk_p" => 0,
                    "cpu_u" => 0,
                    "ram_u" => 0,
                    "disk_u" => 0,
                ];
            }

            if (!in_array($date_str, $buckets[$b_key]["dates"])) {
                $buckets[$b_key]["dates"][] = $date_str;
            }

            if (isset($mapped_prov[$date_str])) {
                foreach ($mapped_prov[$date_str] as $p) {
                    $buckets[$b_key]["prov_t"]++;
                    if ($p["id_virtual_machine"]) {
                        $buckets[$b_key]["prov_v"][] = $p["id_virtual_machine"];
                    }
                }
            }
            if (isset($mapped_urr[$date_str])) {
                foreach ($mapped_urr[$date_str] as $u) {
                    $buckets[$b_key]["urr_t"]++;
                    if ($u["id_virtual_machine"]) {
                        $buckets[$b_key]["urr_v"][] = $u["id_virtual_machine"];
                    }
                }
            }
            if (isset($mapped_res_p[$date_str])) {
                $buckets[$b_key]["cpu_p"] += $mapped_res_p[$date_str]["cpu"];
                $buckets[$b_key]["ram_p"] += $mapped_res_p[$date_str]["ram"];
                $buckets[$b_key]["disk_p"] += $mapped_res_p[$date_str]["disk"];
            }
            if (isset($mapped_res_u[$date_str])) {
                $buckets[$b_key]["cpu_u"] += $mapped_res_u[$date_str]["cpu"];
                $buckets[$b_key]["ram_u"] += $mapped_res_u[$date_str]["ram"];
            }
            if (isset($mapped_res_ud[$date_str])) {
                $buckets[$b_key]["disk_u"] += $mapped_res_ud[$date_str]["disk"];
            }
            $current->modify("+1 day");
        }

        $anchor_total = $this->dashboard_model->get_anchor_active_vms();
        $creations = $this->dashboard_model->get_daily_creations($start_date);
        $deletions = $this->dashboard_model->get_daily_deletions($start_date);

        $change_map = [];
        $total_net_change_since_start = 0;

        foreach ($creations as $c) {
            if (!isset($change_map[$c["tgl"]])) {
                $change_map[$c["tgl"]] = 0;
            }
            $change_map[$c["tgl"]] += $c["qty"];
            $total_net_change_since_start += $c["qty"];
        }

        foreach ($deletions as $d) {
            if (!isset($change_map[$d["tgl"]])) {
                $change_map[$d["tgl"]] = 0;
            }
            $change_map[$d["tgl"]] -= $d["qty"];
            $total_net_change_since_start -= $d["qty"];
        }

        $running_inventory = $anchor_total - $total_net_change_since_start;
        $final = ["labels" => [], "tiket" => [], "vm" => [], "res" => []];
        $today_str = date("Y-m-d");

        foreach ($buckets as $b) {
            $final["labels"][] = $b["label"];
            $bucket_start_date = isset($b["dates"][0]) ? $b["dates"][0] : "2099-12-31";

            if ($bucket_start_date > $today_str) {
                $final["tiket"]["prov"][] = null;
                $final["tiket"]["urr"][] = null;
                $final["vm"]["prov"][] = null;
                $final["vm"]["urr"][] = null;
                $final["vm"]["cumulative"][] = null;
                $final["res"]["cpu_p"][] = null;
                $final["res"]["cpu_u"][] = null;
                $final["res"]["ram_p"][] = null;
                $final["res"]["ram_u"][] = null;
                $final["res"]["disk_p"][] = null;
                $final["res"]["disk_u"][] = null;
            } else {
                $final["tiket"]["prov"][] = $b["prov_t"];
                $final["tiket"]["urr"][] = $b["urr_t"];
                $final["vm"]["prov"][] = count(array_unique($b["prov_v"]));
                $final["vm"]["urr"][] = count(array_unique($b["urr_v"]));

                $net_change_in_bucket = 0;
                foreach ($b["dates"] as $date_in_bucket) {
                    if ($date_in_bucket <= $today_str && isset($change_map[$date_in_bucket])) {
                        $net_change_in_bucket += $change_map[$date_in_bucket];
                    }
                }
                $running_inventory += $net_change_in_bucket;
                $final["vm"]["cumulative"][] = $running_inventory;

                $final["res"]["cpu_p"][] = $b["cpu_p"];
                $final["res"]["cpu_u"][] = $b["cpu_u"];
                $final["res"]["ram_p"][] = $b["ram_p"];
                $final["res"]["ram_u"][] = $b["ram_u"];
                $final["res"]["disk_p"][] = $b["disk_p"];
                $final["res"]["disk_u"][] = $b["disk_u"];
            }
        }

        $this->output->set_content_type("application/json")->set_output(
            json_encode([
                "labels" => $final["labels"],
                "tiket" => $final["tiket"],
                "vm" => $final["vm"],
                "res" => $final["res"],
                "summary" => [
                    "total_cpu" => $sum_cpu,
                    "total_ram" => round($sum_ram, 2),
                    "total_disk" => round($sum_disk, 2),
                    "tickets" => $ticket_summary,
                ],
            ]),
        );
    }

    public function ajax_submit_workflow()
    {
        if (!$this->input->post()) {
            return;
        }

        $user_session = $this->session->userdata("user_data");
        $session_id = (int) $user_session["id_user"];
        $session_username = $user_session["username"];

        $id_trx = $this->input->post("id_trx", true);
        $modul = $this->input->post("modul", true);
        $action_type = $this->input->post("action_type", true);
        $catatan = $this->input->post("catatan", true);

        $new_csrf = $this->security->get_csrf_hash();

        if ($action_type === "verify" && (int) $user_session["id_role"] > 4) {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => false,
                    "message" =>
                        "Akses ditolak! Wewenang verifikasi tiket hanya untuk Atasan / Peer-Reviewer (Role 0 - 4).",
                    "csrf_hash" => $new_csrf,
                ]),
            );
            return;
        }

        if ($action_type === "execute" && trim($catatan) == "") {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => false,
                    "message" => "Catatan hasil eksekusi tidak boleh kosong.",
                    "csrf_hash" => $new_csrf,
                ]),
            );
            return;
        }

        // ========================================================================
        // [ENTERPRISE FIX]: MAKER-CHECKER (SEGREGATION OF DUTIES) GUARD
        // Mencegah Eksekutor melakukan verifikasi terhadap pekerjaannya sendiri
        // ========================================================================
        if ($action_type === "verify") {
            $tiket = $this->dashboard_model->get_ticket_detail((int) $id_trx, $modul);

            if ($tiket) {
                if ($modul === "provisioning") {
                    // Modul Provisioning menyimpan nama executor di kolom 'setup_by'
                    if (
                        isset($tiket["setup_by"]) &&
                        strtolower($tiket["setup_by"]) === strtolower($session_username)
                    ) {
                        $this->output->set_content_type("application/json")->set_output(
                            json_encode([
                                "status" => false,
                                "message" =>
                                    "Maker-Checker Violation: Anda adalah Eksekutor tiket ini. Verifikasi harus dilakukan oleh anggota tim lain.",
                                "csrf_hash" => $new_csrf,
                            ]),
                        );
                        return;
                    }
                } else {
                    // Modul Change Resource, Switch IP, & Restart menyimpan ID executor
                    if (
                        isset($tiket["id_executor"]) &&
                        (int) $tiket["id_executor"] === $session_id
                    ) {
                        $this->output->set_content_type("application/json")->set_output(
                            json_encode([
                                "status" => false,
                                "message" =>
                                    "Maker-Checker Violation: Anda dilarang melakukan verifikasi (Approval) terhadap pekerjaan yang Anda eksekusi sendiri.",
                                "csrf_hash" => $new_csrf,
                            ]),
                        );
                        return;
                    }
                }
            }
        }
        // ================= END OF MAKER-CHECKER GUARD =========================

        $process = $this->dashboard_model->update_ticket_status(
            (int) $id_trx,
            $modul,
            $action_type,
            $catatan,
            (int) $user_session["id_user"],
            $user_session["nama_lengkap"],
        );

        if ($process) {
            $msg =
                $action_type === "verify"
                    ? "Tiket berhasil diverifikasi."
                    : "Tiket berhasil dieksekusi.";
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode(["status" => true, "message" => $msg, "csrf_hash" => $new_csrf]),
                );
        } else {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => false,
                    "message" => "Terjadi kegagalan sinkronisasi database.",
                    "csrf_hash" => $new_csrf,
                ]),
            );
        }
    }

    public function ajax_submit_hambatan()
    {
        if (!$this->input->post()) {
            return;
        }

        $process = $this->dashboard_model->update_hambatan(
            $this->input->post("id_trx", true),
            $this->input->post("modul", true),
            $this->input->post("hambatan", true),
        );

        $this->output->set_content_type("application/json")->set_output(
            json_encode([
                "status" => (bool) $process,
                "message" => $process
                    ? "Informasi kendala berhasil dicatat."
                    : "Gagal mencatat log.",
                "csrf_hash" => $this->security->get_csrf_hash(),
            ]),
        );
    }

    public function export_excel()
    {
        $start_date = $this->input->post("start_date", true);
        $end_date = $this->input->post("end_date", true);

        if (empty($start_date) || empty($end_date)) {
            $this->session->set_flashdata("alerts", [
                ["error", "Tanggal awal dan akhir wajib diisi untuk Export!"],
            ]);
            redirect("dashboard");
            return;
        }

        if (strtotime($start_date) > strtotime($end_date)) {
            $this->session->set_flashdata("alerts", [
                ["warning", "Tanggal awal tidak boleh lebih besar dari tanggal akhir!"],
            ]);
            redirect("dashboard");
            return;
        }

        $this->load->model("Vm_restart_model", "vm_restart_model");
        $this->load->model("Vm_change_resource_model", "vm_change_resource_model");
        // $this->load->model('Provisioning_model', 'vm_provisioning_model');

        $data["data_restart"] = $this->vm_restart_model->get_data_export_dashboard(
            $start_date,
            $end_date,
        );
        $data["data_urr"] = $this->vm_change_resource_model->get_data_export_dashboard(
            $start_date,
            $end_date,
        );
        $data["data_prov"] = []; // Placeholder until Prov is integrated

        $data["periode"] =
            date("d F Y", strtotime($start_date)) . " s/d " . date("d F Y", strtotime($end_date));
        $data["filename"] = "Laporan_Dashboard_DC_" . $start_date . "_sd_" . $end_date . ".xls";

        $this->load->view("dashboard/export_excel_dashboard", $data);
    }
}
