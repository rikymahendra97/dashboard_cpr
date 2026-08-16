<?php
defined("BASEPATH") or exit("No direct script access allowed");

/**
 * ========================================================================
 * File Name    : Vm_incident.php
 * Modul        : VM Utilization Incident
 * Purpose      : Controller utama manajemen Tata Kelola Insiden Utilisasi VM.
 * Architecture : Enterprise CP-05 (Linter Safe, Strict Type-Hinted, JSON Clean)
 * ========================================================================
 */
class Vm_incident extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (empty($this->session->userdata("user_data"))) {
            if ($this->input->is_ajax_request()) {
                $this->output
                    ->set_status_header(401)
                    ->set_content_type("application/json")
                    ->set_output(json_encode(["status" => false, "message" => "Sesi berakhir."]));
                exit();
            }
            redirect("auth/login");
        }

        $this->output->set_header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        $this->output->set_header("Cache-Control: post-check=0, pre-check=0", false);
        $this->output->set_header("Pragma: no-cache");

        $this->load->library("form_validation");
        $this->load->model("Vm_incident_model");
        $this->load->helper("sla");

        $this->load->model("Notification_queue_model");
        $this->db->query("SET time_zone = '+07:00'");
    }

    public function index()
    {
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $this->session->userdata("user_data");
        $data["title"] = "Tata Kelola Insiden Utilisasi VM";
        $data["kpi"] = $this->Vm_incident_model->get_kpi_summary();

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_incident/list_vm_incident", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function create()
    {
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $this->session->userdata("user_data");
        $data["title"] = "Register Insiden Baru";

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_incident/create_vm_incident", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function store()
    {
        $this->form_validation->set_rules(
            "no_tiket_insiden",
            "No Tiket",
            "required|trim|is_unique[trx_vm_utilization_incident.no_tiket_insiden]",
        );
        $this->form_validation->set_rules(
            "id_virtual_machine",
            "Virtual Machine",
            "required|numeric",
        );
        $this->form_validation->set_rules("tingkat_urgensi", "Urgensi", "required");
        $this->form_validation->set_rules("tipe_insiden", "Tipe Insiden", "required|trim");

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata("alerts", [["error", validation_errors("", "")]]);
            redirect("vm_incident/create");
        } else {
            $user_session = $this->session->userdata("user_data");
            $id_vm = $this->input->post("id_virtual_machine", true);

            $vm_info = $this->db
                ->select("virtual_machine_name, ip_address")
                ->get_where("master_virtual_machine", ["id_virtual_machine" => $id_vm])
                ->row_array();

            $type = $this->input->post("tipe_insiden", true);
            $metric_val = $this->input->post("metrik_tercatat", true);

            $disk_target = null;
            if ($type === "Disk") {
                $raw_input_disk = trim($this->input->post("detail_disk_drive", true) ?? "");
                if (!empty($raw_input_disk)) {
                    if (strpos($raw_input_disk, "/") === 0) {
                        $disk_target = strtolower($raw_input_disk);
                    } else {
                        $clean_windows_drive = preg_replace("/[^a-zA-Z]/", "", $raw_input_disk);
                        $first_letter = substr(
                            str_replace("DRIVE", "", strtoupper($clean_windows_drive)),
                            0,
                            1,
                        );
                        if (!empty($first_letter)) {
                            $disk_target = $first_letter . ":";
                        } else {
                            $disk_target = strtoupper($raw_input_disk);
                        }
                    }
                }
            }

            if (in_array($type, ["OS", "Audit", "Physical Host", "VM Tools"])) {
                $metric_val = 0;
            }

            $payload = [
                "no_tiket_insiden" => $this->input->post("no_tiket_insiden", true),
                "link_tiket" => $this->input->post("link_tiket", true),
                "id_virtual_machine" => $id_vm,
                "snapshot_vm_name" => $vm_info["virtual_machine_name"] ?? null,
                "snapshot_ip_address" => $vm_info["ip_address"] ?? null,
                "tipe_insiden" => $type,
                "disk_drive_detail" => $disk_target,
                "deskripsi_insiden" => $this->input->post("deskripsi_insiden", true),
                "metrik_tercatat" => $metric_val,
                "tingkat_urgensi" => $this->input->post("tingkat_urgensi", true),
                "status_insiden" => "Open Tiket",
                "id_pelapor" => $user_session["id_user"],
            ];

            $insert_id = $this->Vm_incident_model->insert_incident($payload);

            if ($insert_id) {
                $tele_kategori = $type . (!empty($disk_target) ? " (" . $disk_target . ")" : "");
                try {
                    $payload_json = json_encode([
                        "event_type" => "NEW_INCIDENT_TICKET",
                        "no_tiket" => $payload["no_tiket_insiden"],
                        "server" => $vm_info["virtual_machine_name"] ?? "Unknown VM",
                        "ip_address" => $vm_info["ip_address"] ?? "-",
                        "kategori" => $tele_kategori,
                        "urgensi" => $payload["tingkat_urgensi"],
                        "peak_value" => $payload["metrik_tercatat"],
                    ]);
                    $this->Notification_queue_model->push_to_queue("VM INCIDENT", $payload_json);
                } catch (\Throwable $e) {
                    log_message(
                        "error",
                        "[VM INCIDENT] Integrasi bot gagal di fungsi store: " . $e->getMessage(),
                    );
                }

                $this->session->set_flashdata("alerts", [
                    ["success", "Registrasi insiden mitigasi berhasil disimpan."],
                ]);
                redirect("vm_incident/detail/" . $insert_id . "?_=" . time());
            } else {
                $this->session->set_flashdata("alerts", [
                    ["error", "Gagal memproses transaksi database."],
                ]);
                redirect("vm_incident/create");
            }
        }
    }

    public function ajax_search_vm()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $search = $this->input->get("q", true);
        $this->db->select("id_virtual_machine as id, virtual_machine_name as text, ip_address");
        $this->db->from("master_virtual_machine");
        $this->db->where("is_active", 1);
        $this->db->where("id_site", "TBN");

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like("virtual_machine_name", $search);
            $this->db->or_like("ip_address", $search);
            $this->db->group_end();
        }

        $this->db->limit(50);
        $query = $this->db->get()->result_array();

        $this->output
            ->set_content_type("application/json")
            ->set_output(json_encode(["results" => $query]));
    }

    public function ajax_search_team()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $search = $this->input->get("q", true);
        $this->db->select(
            'id_team as id, CONCAT(IFNULL(team_code, "[NO-CODE]"), " - ", team_name) as text',
        );
        $this->db->from("master_team");

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like("team_name", $search);
            $this->db->or_like("team_code", $search);
            $this->db->group_end();
        }

        $this->db->limit(50);
        $result = $this->db->get()->result_array();

        $this->output
            ->set_content_type("application/json")
            ->set_output(json_encode(["results" => $result]));
    }

    public function ajax_get_vm_metadata(string $id_vm)
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $this->db->select("
            vm.ip_address, vm.guest_os, vm.vmware_tools_status, vm.vmware_tools_version,
            COALESCE(app.application_system_name, 'No Application Bound') as nama_aplikasi,
            COALESCE(crit.criticality_name, 'Unrated') as kritikalitas
        ");
        $this->db->from("master_virtual_machine vm");
        $this->db->join(
            "relation_table rel",
            "rel.id_virtual_machine = vm.id_virtual_machine",
            "left",
        );
        $this->db->join(
            "master_application_system app",
            "app.id_application_system = rel.id_application_system",
            "left",
        );
        $this->db->join(
            "master_criticality crit",
            "crit.id_criticality = app.id_criticality",
            "left",
        );
        $this->db->where("vm.id_virtual_machine", (int) $id_vm);

        $data = $this->db->get()->row_array();

        $this->output->set_content_type("application/json")->set_output(json_encode($data));
    }

    public function check_active_incident_json(string $id_vm)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $this->db->select("id_incident, no_tiket_insiden, tipe_insiden");
        $this->db->from("trx_vm_utilization_incident");
        $this->db->where("id_virtual_machine", (int) $id_vm);
        $this->db->where_in("status_insiden", [
            "Open Tiket",
            "Review by Owner",
            "Apply Solution by Owner",
        ]);
        $this->db->order_by("created_at", "DESC");
        $this->db->limit(1);

        $active_incident = $this->db->get()->row_array();

        if (!empty($active_incident)) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode(["has_incident" => true, "incident_data" => $active_incident]),
                );
        } else {
            $this->output
                ->set_content_type("application/json")
                ->set_output(json_encode(["has_incident" => false]));
        }
    }

    public function ajax_list()
    {
        $list = $this->Vm_incident_model->get_datatables();
        $data = [];
        $no = $_POST["start"] ?? 0;

        $user_session = $this->session->userdata("user_data");
        $role_id = isset($user_session["id_role"]) ? (int) $user_session["id_role"] : 99;
        $can_manage_historical = can_verify_delete($role_id);

        foreach ($list as $incident) {
            $no++;
            $row = [];

            $badge_sla = get_sla_status_badge(
                $incident["sla_deadline"],
                $incident["status_insiden"],
            );
            $badge_status = $this->_format_status_badge($incident["status_insiden"]);

            $row[] = '<div class="text-center">' . $no . "</div>";
            $row[] = date("d-m-Y H:i", strtotime($incident["created_at"]));

            $fu_count = isset($incident["total_fu"]) ? (int) $incident["total_fu"] : 0;
            $last_fu = !empty($incident["last_fu_date"])
                ? date("d-M-Y H:i", strtotime($incident["last_fu_date"]))
                : "-";

            if ($fu_count > 0) {
                $fu_badge =
                    '<div style="margin-top: 6px;"><span class="badge" style="background-color: #3498DB; font-size: 10px; padding: 2px 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);"><i class="fa fa-reply"></i> ' .
                    $fu_count .
                    'x FU</span> <small class="text-muted" style="font-size: 10.5px; margin-left: 5px; font-weight:600;"><i class="fa fa-clock-o"></i> ' .
                    $last_fu .
                    "</small></div>";
            } else {
                $fu_badge =
                    '<div style="margin-top: 6px;"><span class="badge" style="background-color: #BDC3C7; font-size: 10px; padding: 2px 6px;"><i class="fa fa-clock-o"></i> Belum di-FU</span></div>';
            }

            if (!empty($incident["link_tiket"])) {
                $tiket_link =
                    '<a href="' .
                    html_escape($incident["link_tiket"]) .
                    '" target="_blank" class="text-primary font-bold" title="Buka Tiket Jira Eksternal"><u>' .
                    html_escape($incident["no_tiket_insiden"]) .
                    '</u> <i class="fa fa-external-link"></i></a>';
            } else {
                $tiket_link = "<strong>" . html_escape($incident["no_tiket_insiden"]) . "</strong>";
            }
            $row[] = $tiket_link . $fu_badge;

            $nama_vm = html_escape($incident["nama_vm"]);
            $ip_vm = html_escape($incident["ip_vm"]);
            $row[] =
                "<strong>" .
                $nama_vm .
                '</strong><br><small class="text-muted">' .
                $ip_vm .
                '</small> <a href="#" class="btn-copy-ip" data-ip="' .
                $ip_vm .
                '" title="Copy IP Alamat" style="color: #3498db; margin-left: 5px; font-size: 13px;"><i class="fa fa-copy"></i></a>';

            $guest_os = strtolower($incident["guest_os"] ?? "");
            $os_icon = "fa-cogs";

            if (strpos($guest_os, "win") !== false) {
                $os_icon = "fa-windows";
            } elseif (
                strpos($guest_os, "linux") !== false ||
                strpos($guest_os, "centos") !== false ||
                strpos($guest_os, "ubuntu") !== false ||
                strpos($guest_os, "redhat") !== false
            ) {
                $os_icon = "fa-linux";
            }

            $arr_tipe = explode(", ", $incident["tipe_insiden"]);
            $list_tipe_html =
                '<div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 6px; min-width: 140px;">';

            foreach ($arr_tipe as $tipe_item) {
                $base_tipe = trim(preg_replace("/\s*\([^)]*\)/", "", $tipe_item));
                $metric_color = "#73879C";
                $icon = "fa-tag";

                if (stripos($base_tipe, "CPU") !== false) {
                    $metric_color = "#D9534F";
                    $icon = "fa-area-chart";
                } elseif (stripos($base_tipe, "Memory") !== false) {
                    $metric_color = "#F0AD4E";
                    $icon = "fa-bolt";
                } elseif (stripos($base_tipe, "Disk") !== false) {
                    $metric_color = "#9B59B6";
                    $icon = "fa-hdd-o";
                } elseif (stripos($base_tipe, "OS") !== false) {
                    $metric_color = "#546E7A";
                    $icon = $os_icon;
                } elseif (stripos($base_tipe, "Physical Host") !== false) {
                    $metric_color = "#E74C3C";
                    $icon = "fa-server";
                } elseif (stripos($base_tipe, "Audit") !== false) {
                    $metric_color = "#1ABB9C";
                    $icon = "fa-shield";
                }

                $disk_label = "";
                $pct_badge = "";

                if (
                    !empty($incident["disk_drive_detail"]) &&
                    stripos($base_tipe, "Disk") !== false
                ) {
                    $disk_label = " (" . html_escape($incident["disk_drive_detail"]) . ")";
                }

                if (
                    count($arr_tipe) === 1 &&
                    !in_array($base_tipe, ["OS", "Audit", "Physical Host"])
                ) {
                    $pct_badge =
                        '<span style="background-color: ' .
                        $metric_color .
                        '; color: white; padding: 1px 6px; border-radius: 10px; margin-left: 5px; font-weight: 800; font-size: 10px; box-shadow: inset 0 1px 1px rgba(0,0,0,0.1);">' .
                        html_escape($incident["metrik_tercatat"]) .
                        "%</span>";
                } elseif (preg_match('/\((\d+([.,]\d+)?)%\)$/', $tipe_item, $val_matches)) {
                    $pct_badge =
                        '<span style="background-color: ' .
                        $metric_color .
                        '; color: white; padding: 1px 6px; border-radius: 10px; margin-left: 5px; font-weight: 800; font-size: 10px; box-shadow: inset 0 1px 1px rgba(0,0,0,0.1);">' .
                        $val_matches[1] .
                        "%</span>";
                }

                $list_tipe_html .=
                    '<div style="display: inline-flex; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 3px 8px; font-size: 11px; color: #2C3E50; font-weight: bold; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"><i class="fa ' .
                    $icon .
                    '" style="color: ' .
                    $metric_color .
                    '; margin-right: 6px; font-size: 12px; text-align: center;"></i>' .
                    $base_tipe .
                    $disk_label .
                    $pct_badge .
                    "</div>";
            }
            $list_tipe_html .= "</div>";
            $row[] = $list_tipe_html;

            $kritikalitas = $incident["kritikalitas"] ?? "Unrated";
            $crit_badge_style = "background-color: #7F8C8D; color: white;";

            if (
                stripos($kritikalitas, "Critical") !== false ||
                stripos($kritikalitas, "Very High") !== false
            ) {
                $crit_badge_style = "background-color: #C0392B; color: white;";
            } elseif (stripos($kritikalitas, "High") !== false) {
                $crit_badge_style = "background-color: #E67E22; color: white;";
            } elseif (stripos($kritikalitas, "Medium") !== false) {
                $crit_badge_style = "background-color: #F1C40F; color: #333;";
            } elseif (stripos($kritikalitas, "Low") !== false) {
                $crit_badge_style = "background-color: #1ABC9C; color: white;";
            }

            $row[] =
                '<div class="text-center"><span class="label" style="' .
                $crit_badge_style .
                ' padding: 4px 8px; font-size: 11px; box-shadow: inset 0 1px 1px rgba(0,0,0,0.1);"><i class="fa fa-shield"></i> ' .
                html_escape($kritikalitas) .
                "</span></div>";
            $row[] = '<div class="text-center">' . $badge_sla . "</div>";
            $row[] = '<div class="text-center">' . $badge_status . "</div>";

            $btn_detail =
                '<a href="' .
                site_url("vm_incident/detail/" . $incident["id_incident"]) .
                '" class="btn btn-xs btn-info" style="border-radius:3px; margin:0; padding:3px 7px;" data-toggle="tooltip" data-placement="top" title="Lihat Detail"><i class="fa fa-eye"></i></a>';

            if ($incident["status_insiden"] === "Done/Close" && !$can_manage_historical) {
                $btn_edit =
                    '<button type="button" class="btn btn-xs btn-default btn-locked" style="border-radius:3px; margin:0; padding:3px 7px; color:#999; background-color:#eee; border-color:#ddd;" data-toggle="tooltip" data-placement="top" title="Terkunci: Akses Terbatas (Administrator)"><i class="fa fa-lock"></i></button>';
            } else {
                $btn_edit =
                    '<a href="' .
                    site_url("vm_incident/edit/" . $incident["id_incident"]) .
                    '" class="btn btn-xs btn-default" style="border-radius:3px; margin:0; padding:3px 7px; color:#5A738E;" data-toggle="tooltip" data-placement="top" title="Edit Tiket"><i class="fa fa-edit"></i></a>';
            }

            $btn_delete = "";
            if ($can_manage_historical) {
                $btn_delete =
                    '<button type="button" class="btn btn-xs btn-danger btn-delete-incident" data-id="' .
                    $incident["id_incident"] .
                    '" data-ticket="' .
                    html_escape($incident["no_tiket_insiden"]) .
                    '" style="border-radius:3px; margin:0; padding:3px 7px;" data-toggle="tooltip" data-placement="top" title="Hapus Permanen"><i class="fa fa-trash"></i></button>';
            }

            $row[] =
                '<div class="text-center" style="display:flex; flex-direction:row; align-items:center; justify-content:center; gap:6px;">' .
                $btn_detail .
                $btn_edit .
                $btn_delete .
                "</div>";
            $data[] = $row;
        }

        $output = [
            "draw" => $_POST["draw"] ?? 1,
            "recordsTotal" => $this->Vm_incident_model->count_all(),
            "recordsFiltered" => $this->Vm_incident_model->count_filtered(),
            "data" => $data,
        ];

        $this->output->set_content_type("application/json")->set_output(json_encode($output));
    }

    private function _format_status_badge(string $status)
    {
        switch ($status) {
            case "Open Tiket":
                return '<span class="badge bg-red" style="font-size:11px; padding:4px 8px;"><i class="fa fa-envelope"></i> Open</span>';
            case "Review by Owner":
                return '<span class="badge bg-orange" style="font-size:11px; padding:4px 8px;"><i class="fa fa-search"></i> Review</span>';
            case "Apply Solution by Owner":
                return '<span class="badge bg-blue" style="font-size:11px; padding:4px 8px;"><i class="fa fa-wrench"></i> Applying</span>';
            case "Done/Close":
                return '<span class="badge bg-green" style="font-size:11px; padding:4px 8px;"><i class="fa fa-check"></i> Done</span>';
            default:
                return '<span class="badge bg-black">' . html_escape($status) . "</span>";
        }
    }

    public function detail(string $id_incident)
    {
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $this->session->userdata("user_data");

        $data["detail"] = $this->Vm_incident_model->get_detail_incident($id_incident);
        if (empty($data["detail"])) {
            show_404();
        }

        $data["title"] = "Detail Insiden - " . $data["detail"]["no_tiket_insiden"];
        $data["fu_history"] = $this->Vm_incident_model->get_fu_history($id_incident);
        $data["master_team"] = $this->Vm_incident_model->get_master_team_list();

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_incident/detail_vm_incident", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function store_fu()
    {
        $id_incident = $this->input->post("id_incident", true);
        if (empty($id_incident)) {
            show_404();
            return;
        }

        $this->form_validation->set_rules("id_team_tujuan", "Kontak Fungsi", "required|numeric");
        $this->form_validation->set_rules("aksi_tindakan", "Tindakan", "required|trim");
        $this->form_validation->set_rules("catatan_fu", "Keterangan FU", "required|trim");
        $this->form_validation->set_rules("update_status_insiden", "Status Tiket", "required|trim");

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata("alerts", [["error", validation_errors("", "")]]);
        } else {
            $user_session = $this->session->userdata("user_data");

            $date = new DateTime();
            $daysToAdd = 2;
            $whileSafe = 0;
            while ($daysToAdd > 0 && $whileSafe < 10) {
                $date->modify("+1 day");
                if ($date->format("N") < 6) {
                    $daysToAdd--;
                }
                $whileSafe++;
            }
            $next_deadline = $date->format("Y-m-d H:i:s");

            $aksi_tindakan = $this->input->post("aksi_tindakan", true);
            $catatan_fu = $this->input->post("catatan_fu", true);
            $status_baru = $this->input->post("update_status_insiden", true);

            $payload_fu = [
                "id_incident" => $id_incident,
                "id_user" => $user_session["id_user"],
                "id_team_tujuan" => $this->input->post("id_team_tujuan", true),
                "aksi_tindakan" => $aksi_tindakan,
                "catatan_fu" => $catatan_fu,
                "next_fu_deadline" => $next_deadline,
            ];

            $payload_incident = [
                "status_insiden" => $status_baru,
                "id_assignee" => $user_session["id_user"],
            ];

            if ($status_baru === "Done/Close") {
                $payload_incident["resolved_at"] = date("Y-m-d H:i:s");
                $payload_incident["catatan_resolusi"] = $catatan_fu;
            }

            $this->db->trans_start();
            $this->db->insert("trx_vm_incident_fu", $payload_fu);
            if ($status_baru !== "No Change") {
                $this->db->where("id_incident", $id_incident);
                $this->db->update("trx_vm_utilization_incident", $payload_incident);
            }
            $this->db->trans_complete();

            if ($this->db->trans_status()) {
                $incident_info = $this->db
                    ->select("i.no_tiket_insiden, v.virtual_machine_name")
                    ->from("trx_vm_utilization_incident i")
                    ->join(
                        "master_virtual_machine v",
                        "i.id_virtual_machine = v.id_virtual_machine",
                        "left",
                    )
                    ->where("i.id_incident", $id_incident)
                    ->get()
                    ->row_array();

                if ($incident_info) {
                    $payload_json = json_encode([
                        "event_type" => "UPDATE_INCIDENT_TICKET",
                        "no_tiket" => $incident_info["no_tiket_insiden"],
                        "server" => $incident_info["virtual_machine_name"] ?? "Unknown VM",
                        "action_type" => "FOLLOW UP / STATUS UPDATE",
                        "status_akhir" => $status_baru,
                    ]);
                    $this->Notification_queue_model->push_to_queue("VM INCIDENT", $payload_json);
                }
                $this->session->set_flashdata("alerts", [
                    ["success", "Jejak rekam FU dan Status Tiket berhasil diperbarui."],
                ]);
            } else {
                $this->session->set_flashdata("alerts", [
                    ["error", "Terjadi kesalahan sistem saat menyimpan log database."],
                ]);
            }
        }
        redirect("vm_incident/detail/" . $id_incident);
    }

    public function edit(string $id_incident)
    {
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $this->session->userdata("user_data");

        $data["detail"] = $this->Vm_incident_model->get_incident_detail($id_incident);
        if (empty($data["detail"])) {
            show_404();
        }

        $role_id = (int) $data["user_session"]["id_role"];
        $can_manage_historical = can_verify_delete($role_id);

        if ($data["detail"]["status_insiden"] === "Done/Close" && !$can_manage_historical) {
            $this->session->set_flashdata("alerts", [
                [
                    "error",
                    "Akses Ditolak: Tiket yang telah selesai (Closed) hanya dapat diedit oleh pengguna dengan otorisasi Verifikator/Admin.",
                ],
            ]);
            redirect("vm_incident/detail/" . $id_incident);
        }

        $raw_types_string = $data["detail"]["tipe_insiden"];
        $arr_types = explode(", ", $raw_types_string);

        $bind_metrics = [];
        $bind_disk_detail = "";

        foreach ($arr_types as $item) {
            $base_type = "";
            $val = "";

            if (stripos($item, "CPU") !== false) {
                $base_type = "CPU";
            } elseif (stripos($item, "Memory") !== false) {
                $base_type = "Memory";
            } elseif (stripos($item, "Physical Host") !== false) {
                $base_type = "Physical Host";
            } elseif (stripos($item, "Audit") !== false) {
                $base_type = "Audit";
            } elseif (stripos($item, "OS") !== false) {
                $base_type = "OS";
            } elseif (stripos($item, "Disk") !== false) {
                $base_type = "Disk";
                if (preg_match("/Disk\s*\((.*?)\)/i", $item, $matches)) {
                    $bind_disk_detail = $matches[1];
                }
            }

            if (preg_match('/\((\d+([.,]\d+)?)%\)$/', $item, $val_matches)) {
                $val = str_replace(",", ".", $val_matches[1]);
            }
            if (!empty($base_type)) {
                $bind_metrics[$base_type] = $val;
            }
        }

        $data["bind_metrics_json"] = json_encode($bind_metrics);
        $data["bind_disk_detail"] = $bind_disk_detail;
        $data["title"] = "Edit Insiden - " . $data["detail"]["no_tiket_insiden"];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_incident/edit_vm_incident", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function delete(string $incident_id)
    {
        if (empty($incident_id)) {
            show_404();
            return;
        }

        $user_session = $this->session->userdata("user_data");
        $role_id = isset($user_session["id_role"]) ? (int) $user_session["id_role"] : 99;
        $is_authorized = can_verify_delete($role_id);

        if (!$is_authorized) {
            $this->session->set_flashdata("alerts", [
                [
                    "error",
                    "Pelanggaran Hak Akses: Peran akun Anda tidak diberikan otoritas untuk membuang data dari sistem.",
                ],
            ]);
            redirect("vm_incident");
            return;
        }

        $is_deleted = $this->Vm_incident_model->delete_incident($incident_id);

        if ($is_deleted) {
            $this->session->set_flashdata("alerts", [
                [
                    "success",
                    "Sukses: Tiket insiden beserta seluruh riwayat jurnal follow-up terkait telah dihapus secara permanen.",
                ],
            ]);
        } else {
            $this->session->set_flashdata("alerts", [
                [
                    "error",
                    "Kegagalan Sistem: Terjadi kendala transaksional pada internal database engine.",
                ],
            ]);
        }
        redirect("vm_incident");
    }

    public function update()
    {
        $id_incident = $this->input->post("id_incident", true);
        if (empty($id_incident)) {
            show_404();
            return;
        }

        $detail_awal = $this->Vm_incident_model->get_incident_detail($id_incident);
        $user_session = $this->session->userdata("user_data");
        $can_manage_historical = can_verify_delete((int) $user_session["id_role"]);

        if ($detail_awal["status_insiden"] === "Done/Close" && !$can_manage_historical) {
            $this->session->set_flashdata("alerts", [
                ["error", "Akses Ditolak: Anda tidak memiliki izin untuk mengedit tiket historis."],
            ]);
            redirect("vm_incident/detail/" . $id_incident);
            return;
        }

        $is_unique_rule =
            $this->input->post("no_tiket_insiden") !== $detail_awal["no_tiket_insiden"]
                ? "|is_unique[trx_vm_utilization_incident.no_tiket_insiden]"
                : "";
        $this->form_validation->set_rules(
            "no_tiket_insiden",
            "No Tiket",
            "required|trim" . $is_unique_rule,
        );
        $this->form_validation->set_rules(
            "id_virtual_machine",
            "Virtual Machine",
            "required|numeric",
        );
        $this->form_validation->set_rules("tingkat_urgensi", "Urgensi", "required");
        $this->form_validation->set_rules("tipe_insiden", "Tipe Insiden", "required|trim");

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata("alerts", [["error", validation_errors("", "")]]);
            redirect("vm_incident/edit/" . $id_incident);
        } else {
            $id_vm = $this->input->post("id_virtual_machine", true);
            $vm_info = $this->db
                ->select("virtual_machine_name, ip_address")
                ->get_where("master_virtual_machine", ["id_virtual_machine" => $id_vm])
                ->row_array();

            $type = $this->input->post("tipe_insiden", true);
            $metric_val = $this->input->post("metrik_tercatat", true);

            $disk_target = null;
            if ($type === "Disk") {
                $raw_input_disk = trim($this->input->post("detail_disk_drive", true) ?? "");
                if (!empty($raw_input_disk)) {
                    if (strpos($raw_input_disk, "/") === 0) {
                        $disk_target = strtolower($raw_input_disk);
                    } else {
                        $clean_windows_drive = preg_replace("/[^a-zA-Z]/", "", $raw_input_disk);
                        $first_letter = substr(
                            str_replace("DRIVE", "", strtoupper($clean_windows_drive)),
                            0,
                            1,
                        );
                        if (!empty($first_letter)) {
                            $disk_target = $first_letter . ":";
                        } else {
                            $disk_target = strtoupper($raw_input_disk);
                        }
                    }
                }
            }

            if (in_array($type, ["OS", "Audit", "Physical Host", "VM Tools"])) {
                $metric_val = 0;
            }

            $payload = [
                "no_tiket_insiden" => $this->input->post("no_tiket_insiden", true),
                "link_tiket" => $this->input->post("link_tiket", true),
                "id_virtual_machine" => $id_vm,
                "snapshot_vm_name" => $vm_info["virtual_machine_name"] ?? null,
                "snapshot_ip_address" => $vm_info["ip_address"] ?? null,
                "tipe_insiden" => $type,
                "disk_drive_detail" => $disk_target,
                "deskripsi_insiden" => $this->input->post("deskripsi_insiden", true),
                "metrik_tercatat" => $metric_val,
                "tingkat_urgensi" => $this->input->post("tingkat_urgensi", true),
            ];

            if ($payload["tingkat_urgensi"] !== $detail_awal["tingkat_urgensi"]) {
                $payload["sla_deadline"] = calculate_sla_deadline(
                    $detail_awal["created_at"],
                    $payload["tingkat_urgensi"],
                );
            }

            if ($this->Vm_incident_model->update_incident($id_incident, $payload)) {
                $this->session->set_flashdata("alerts", [
                    ["success", "Perubahan tiket berhasil disimpan."],
                ]);
                redirect("vm_incident/detail/" . $id_incident);
            } else {
                $this->session->set_flashdata("alerts", [["error", "Gagal menyimpan perubahan."]]);
                redirect("vm_incident/edit/" . $id_incident);
            }
        }
    }

    // ========================================================================
    // SECTION: EXPORT REPORTING ENGINE (Unbuffered Streaming Architecture)
    // ========================================================================
    private function _get_headers_title(array $cols_array)
    {
        $headers = [];
        $map_title = [
            "no" => "No Urut",
            "no_tiket_insiden" => "No Tiket Jira",
            "created_at" => "Waktu Registrasi",
            "nama_vm" => "Target Virtual Machine",
            "ip_vm" => "IP Address",
            "tipe_insiden" => "Kategori Insiden",
            "tingkat_urgensi" => "Level Urgensi",
            "sla_deadline" => "Batas Waktu SLA",
            "status_insiden" => "Status Terkini",
            "resolved_at" => "Waktu Selesai (Closed)",
            "metrik_tercatat" => "Peak Value (%)",
            "nama_pelapor" => "Dilaporkan Oleh",
            "total_fu" => "Total Jurnal FU",
            "last_fu_date" => "Waktu FU Terakhir",
            "catatan_resolusi" => "Catatan Resolusi Akhir",
            "nama_aplikasi" => "Sistem Aplikasi (CMDB)",
            "kritikalitas" => "Kritikalitas (CMDB)",
            "guest_os" => "Operating System",
        ];
        foreach ($cols_array as $col) {
            if (isset($map_title[$col])) {
                $headers[$col] = $map_title[$col];
            }
        }
        return $headers;
    }

    private function _sanitize_excel_formula(?string $str): string
    {
        if (empty($str)) {
            return "";
        }
        $str = strip_tags($str);
        if (preg_match("/^[\=\+\-@]/", $str)) {
            return "'" . $str;
        }
        return $str;
    }

    private function _build_dynamic_row(
        array $cols_array,
        int $no,
        array $row,
        bool $is_excel = false,
    ): string {
        $html = "";
        $map_data = [
            "no" => '<td align="center">' . $no . "</td>",
            "no_tiket_insiden" =>
                '<td align="center" ' .
                ($is_excel ? 'style="mso-number-format:\@;"' : "") .
                "><strong>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["no_tiket_insiden"])
                        : $row["no_tiket_insiden"],
                ) .
                "</strong></td>",
            "created_at" =>
                '<td align="center">' . date("d-M-Y H:i", strtotime($row["created_at"])) . "</td>",
            "nama_vm" =>
                "<td><strong>" .
                html_escape(
                    $is_excel ? $this->_sanitize_excel_formula($row["nama_vm"]) : $row["nama_vm"],
                ) .
                "</strong></td>",
            "ip_vm" =>
                '<td align="center" ' .
                ($is_excel ? 'style="mso-number-format:\@;"' : "") .
                ">" .
                html_escape(
                    $is_excel ? $this->_sanitize_excel_formula($row["ip_vm"]) : $row["ip_vm"],
                ) .
                "</td>",
            "tipe_insiden" =>
                "<td>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["tipe_insiden"])
                        : $row["tipe_insiden"],
                ) .
                (!empty($row["disk_drive_detail"])
                    ? " (" .
                        html_escape(
                            $is_excel
                                ? $this->_sanitize_excel_formula($row["disk_drive_detail"])
                                : $row["disk_drive_detail"],
                        ) .
                        ")"
                    : "") .
                "</td>",
            "tingkat_urgensi" =>
                '<td align="center">' .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["tingkat_urgensi"])
                        : $row["tingkat_urgensi"],
                ) .
                "</td>",
            "sla_deadline" =>
                '<td align="center" style="color:#b91d47;"><strong>' .
                date("d-M-Y H:i", strtotime($row["sla_deadline"])) .
                "</strong></td>",
            "status_insiden" =>
                '<td align="center"><strong>' .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["status_insiden"])
                        : $row["status_insiden"],
                ) .
                "</strong></td>",
            "resolved_at" =>
                '<td align="center">' .
                (!empty($row["resolved_at"])
                    ? date("d-M-Y H:i", strtotime($row["resolved_at"]))
                    : "-") .
                "</td>",
            "metrik_tercatat" =>
                '<td align="center" class="font-bold" style="color:#b91d47;">' .
                html_escape($row["metrik_tercatat"]) .
                "%</td>",
            "nama_pelapor" =>
                "<td>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["nama_pelapor"])
                        : $row["nama_pelapor"],
                ) .
                "</td>",
            "deskripsi_insiden" =>
                '<td style="white-space:normal; min-width:180px;">' .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["deskripsi_insiden"] ?? "-")
                        : $row["deskripsi_insiden"] ?? "-",
                ) .
                "</td>",
            "total_fu" => '<td align="center">' . (int) $row["total_fu"] . "x</td>",
            "last_fu_date" =>
                '<td align="center">' .
                (!empty($row["last_fu_date"])
                    ? date("d-M-Y H:i", strtotime($row["last_fu_date"]))
                    : "-") .
                "</td>",
            "catatan_resolusi" =>
                '<td style="white-space:normal; min-width:180px;">' .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["catatan_resolusi"] ?? "-")
                        : $row["catatan_resolusi"] ?? "-",
                ) .
                "</td>",
            "nama_aplikasi" =>
                "<td>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["nama_aplikasi"])
                        : $row["nama_aplikasi"],
                ) .
                "</td>",
            "kritikalitas" =>
                '<td align="center">' .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["kritikalitas"])
                        : $row["kritikalitas"],
                ) .
                "</td>",
            "guest_os" =>
                '<td align="center">' .
                html_escape(
                    $is_excel ? $this->_sanitize_excel_formula($row["guest_os"]) : $row["guest_os"],
                ) .
                "</td>",
        ];
        foreach ($cols_array as $col) {
            if (isset($map_data[$col])) {
                $html .= $map_data[$col];
            }
        }
        return $html;
    }

    private function _aggregate_summary_row(array &$summary, array $row): void
    {
        $status = $row["status_insiden"];
        $action = !empty($row["last_action"]) ? $row["last_action"] : "None";

        if (!isset($summary["status_action"][$status][$action])) {
            $summary["status_action"][$status][$action] = 0;
        }
        $summary["status_action"][$status][$action]++;

        $tipe = trim($row["tipe_insiden"]);
        if (stripos($tipe, "vm tool") !== false || stripos($tipe, "vmware tool") !== false) {
            $tipe = "VM Tools";
        } elseif (stripos($tipe, "mem") !== false) {
            $tipe = "Memory";
        } elseif (stripos($tipe, "cpu") !== false) {
            $tipe = "CPU";
        } elseif (stripos($tipe, "disk") !== false) {
            $tipe = "Disk";
        }

        $kritis = $row["kritikalitas"];
        if (stripos($kritis, "Critical") !== false) {
            $kritis_grp = "Critical";
        } elseif (stripos($kritis, "Very High") !== false) {
            $kritis_grp = "Very High";
        } elseif (stripos($kritis, "High") !== false) {
            $kritis_grp = "High";
        } else {
            $kritis_grp = "Other";
        }

        $default_kritis = [
            "Open Tiket" => 0,
            "Review by Owner" => 0,
            "Apply Solution by Owner" => 0,
            "Done/Close" => 0,
            "Total" => 0,
        ];

        if (!isset($summary["tipe_kritis"][$tipe])) {
            $summary["tipe_kritis"][$tipe] = [
                "Critical" => $default_kritis,
                "Very High" => $default_kritis,
                "High" => $default_kritis,
                "Other" => $default_kritis,
            ];
        }

        $summary["tipe_kritis"][$tipe][$kritis_grp][$status]++;
        $summary["tipe_kritis"][$tipe][$kritis_grp]["Total"]++;
        $summary["grand_total"]++;
    }

    public function ajax_preview_export()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        set_time_limit(0);

        $filter_type = $this->input->post("filter_type", true);
        $start_date = $this->input->post("start_date", true);
        $end_date = $this->input->post("end_date", true);
        $selected_cols = $this->input->post("selected_cols") ?? [
            "no",
            "no_tiket_insiden",
            "created_at",
            "nama_vm",
            "ip_vm",
            "tipe_insiden",
            "tingkat_urgensi",
            "sla_deadline",
            "status_insiden",
        ];

        $export_query =
            $filter_type == "range"
                ? $this->Vm_incident_model->get_data_export_query($start_date, $end_date)
                : $this->Vm_incident_model->get_data_export_query();

        $summary = [
            "status_action" => [
                "Apply Solution by Owner" => [],
                "Done/Close" => [],
                "Open Tiket" => [],
                "Review by Owner" => [],
            ],
            "tipe_kritis" => [],
            "grand_total" => 0,
        ];

        $html_rows = "";
        $no = 1;
        $total_data = 0;

        while ($row = $export_query->unbuffered_row("array")) {
            $this->_aggregate_summary_row($summary, $row);

            if ($no <= 100) {
                $html_rows .=
                    "<tr>" . $this->_build_dynamic_row($selected_cols, $no, $row, false) . "</tr>";
            }
            $no++;
            $total_data++;
        }

        if ($total_data == 0) {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => "empty",
                    "html_preview" =>
                        '<div class="alert alert-warning text-center" style="margin-top:20px;"><i class="fa fa-info-circle"></i> Data kosong pada filter tersebut.</div>',
                    "csrf_hash" => $this->security->get_csrf_hash(),
                ]),
            );
            return;
        }

        foreach ($summary["status_action"] as $s => &$actions) {
            ksort($actions);
        }

        $html =
            '<div style="margin-bottom:20px;"><h4 style="color:#2A3F54; font-weight:bold; border-bottom:2px solid #ccc; padding-bottom:5px;"><i class="fa fa-pie-chart"></i> PIVOT 1: Status Tiket vs Action Log</h4>';
        $html .=
            '<table class="table table-bordered table-condensed" style="background:#fff; font-size:11px; width:100%; max-width:600px;">';
        $html .=
            '<thead style="background:#f1f5f9;"><tr><th class="text-center">Status Tiket</th><th class="text-center">Action</th><th class="text-center">Total</th></tr></thead><tbody>';

        $grand_total_1 = 0;
        foreach (
            ["Apply Solution by Owner", "Done/Close", "Open Tiket", "Review by Owner"]
            as $status_key
        ) {
            $actions = $summary["status_action"][$status_key] ?? [];
            if (empty($actions)) {
                $actions = ["None" => 0];
            }

            $rowspan = count($actions);
            $first = true;
            foreach ($actions as $act => $qty) {
                $html .= "<tr>";
                if ($first) {
                    $display_status = $status_key == "Done/Close" ? "Done" : $status_key;
                    $html .=
                        '<td rowspan="' .
                        $rowspan .
                        '" style="vertical-align:middle; font-weight:bold;">' .
                        $display_status .
                        "</td>";
                    $first = false;
                }
                $html .= "<td>" . $act . '</td><td class="text-center">' . $qty . "</td></tr>";
                $grand_total_1 += $qty;
            }
        }
        $html .=
            '<tr style="background:#f8fafc;"><th colspan="2" class="text-right">Grand Total</th><th class="text-center">' .
            $grand_total_1 .
            "</th></tr></tbody></table></div>";

        $html .=
            '<div style="margin-bottom:20px;"><h4 style="color:#2A3F54; font-weight:bold; border-bottom:2px solid #ccc; padding-bottom:5px;"><i class="fa fa-bar-chart"></i> PIVOT 2: Utilisasi per Kategori & Kritikalitas</h4>';
        $order_kritis = ["Critical", "Very High", "High", "Other"];
        foreach ($summary["tipe_kritis"] as $tipe => $kritis_data) {
            $html .=
                '<table class="table table-bordered table-condensed" style="background:#fff; font-size:11px; width:100%; margin-bottom:15px;">';
            $html .=
                '<thead style="background:#f1f5f9;"><tr><th colspan="6" class="text-center" style="font-size:12px;">Tiket Utilisasi<br>' .
                $tipe .
                "</th></tr>";
            $html .=
                '<tr><th rowspan="2" style="vertical-align:middle; text-align:center;">Kritikalitas VM</th><th colspan="4" class="text-center">Status Tiket</th><th rowspan="2" style="vertical-align:middle; text-align:center;">Grand Total</th></tr>';
            $html .=
                '<tr><th class="text-center">Open Tiket</th><th class="text-center">Review by Owner</th><th class="text-center">Apply Solution by Owner</th><th class="text-center">Done</th></tr></thead><tbody>';

            $sub_totals = [
                "Open Tiket" => 0,
                "Review by Owner" => 0,
                "Apply Solution by Owner" => 0,
                "Done/Close" => 0,
                "Total" => 0,
            ];

            foreach ($order_kritis as $kr) {
                $data = $kritis_data[$kr] ?? [
                    "Open Tiket" => 0,
                    "Review by Owner" => 0,
                    "Apply Solution by Owner" => 0,
                    "Done/Close" => 0,
                    "Total" => 0,
                ];
                $html .= "<tr><td><i>" . $kr . "</i></td>";
                $html .= '<td class="text-center">' . $data["Open Tiket"] . "</td>";
                $html .= '<td class="text-center">' . $data["Review by Owner"] . "</td>";
                $html .= '<td class="text-center">' . $data["Apply Solution by Owner"] . "</td>";
                $html .= '<td class="text-center">' . $data["Done/Close"] . "</td>";
                $html .= '<td class="text-center font-bold">' . $data["Total"] . "</td></tr>";

                $sub_totals["Open Tiket"] += $data["Open Tiket"];
                $sub_totals["Review by Owner"] += $data["Review by Owner"];
                $sub_totals["Apply Solution by Owner"] += $data["Apply Solution by Owner"];
                $sub_totals["Done/Close"] += $data["Done/Close"];
                $sub_totals["Total"] += $data["Total"];
            }
            $html .=
                '<tr style="background:#f8fafc;"><th class="text-right">Grand Total</th><th class="text-center">' .
                $sub_totals["Open Tiket"] .
                '</th><th class="text-center">' .
                $sub_totals["Review by Owner"] .
                '</th><th class="text-center">' .
                $sub_totals["Apply Solution by Owner"] .
                '</th><th class="text-center">' .
                $sub_totals["Done/Close"] .
                '</th><th class="text-center font-bold" style="color:#d9534f;">' .
                $sub_totals["Total"] .
                "</th></tr></tbody></table>";
        }
        $html .= "</div>";

        $html .=
            '<h4 style="color:#2A3F54; font-weight:bold; border-bottom:2px solid #ccc; padding-bottom:5px; margin-top:30px;"><i class="fa fa-list-alt"></i> RINCIAN DATA TIKET EXCEL</h4>';
        $headers = $this->_get_headers_title($selected_cols);
        $html .=
            '<table id="previewDataTable" class="table table-bordered table-striped" style="font-size: 11px; white-space: nowrap; width: 100%;">';
        $html .= '<thead style="background-color: #2A3F54; color: white;"><tr>';
        foreach ($headers as $head) {
            $html .= '<th class="text-center">' . $head . "</th>";
        }
        $html .= "</tr></thead><tbody>";

        $html .= $html_rows;

        $html .= "</tbody></table>";

        if ($total_data > 100) {
            $html .=
                '<div class="alert alert-info text-center" style="padding:8px; margin-top: 10px;"><i>Tabel rincian dipotong 100 baris untuk performa Preview. Download Excel untuk melihat seluruh <b>' .
                number_format($total_data) .
                "</b> baris data lengkap.</i></div>";
        }

        $this->output->set_content_type("application/json")->set_output(
            json_encode([
                "status" => "success",
                "html_preview" => $html,
                "csrf_hash" => $this->security->get_csrf_hash(),
            ]),
        );
    }

    public function export_excel()
    {
        set_time_limit(0);
        ini_set("memory_limit", "512M");

        $filter_type = $this->input->post("filter_type", true);
        $start_date = $this->input->post("start_date", true);
        $end_date = $this->input->post("end_date", true);
        $raw_cols = $this->input->post("export_columns", true);

        $selected_cols = !empty($raw_cols)
            ? explode(",", $raw_cols)
            : [
                "no",
                "no_tiket_insiden",
                "created_at",
                "nama_vm",
                "ip_vm",
                "tipe_insiden",
                "tingkat_urgensi",
                "sla_deadline",
                "status_insiden",
            ];

        $export_query =
            $filter_type == "range"
                ? $this->Vm_incident_model->get_data_export_query($start_date, $end_date)
                : $this->Vm_incident_model->get_data_export_query();

        if ($filter_type == "range") {
            $data["periode"] =
                date("d M Y", strtotime($start_date)) .
                " s/d " .
                date("d M Y", strtotime($end_date));
            $filename_date = $start_date . "_sd_" . $end_date;
        } else {
            $data["periode"] = "Semua Waktu (Keseluruhan Data)";
            $filename_date = "All_Data";
        }

        $temp_fp = fopen("php://temp", "r+");

        $summary = [
            "status_action" => [
                "Apply Solution by Owner" => [],
                "Done/Close" => [],
                "Open Tiket" => [],
                "Review by Owner" => [],
            ],
            "tipe_kritis" => [],
            "grand_total" => 0,
        ];

        $no = 1;

        while ($row = $export_query->unbuffered_row("array")) {
            $this->_aggregate_summary_row($summary, $row);

            $html_row =
                "<tr>" . $this->_build_dynamic_row($selected_cols, $no, $row, true) . "</tr>\n";
            fwrite($temp_fp, $html_row);
            $no++;
        }

        foreach ($summary["status_action"] as $s => &$actions) {
            ksort($actions);
        }

        $data["filename"] = "Tiket_SCR_Utilisasi_VM_" . $filename_date . ".xls";
        $data["summary"] = $summary;
        $data["headers"] = $this->_get_headers_title($selected_cols);
        $data["temp_fp"] = $temp_fp;

        $this->load->view("vm_incident/export_excel_vm_incident", $data);
    }

    public function insert_staging_batch(array $data_batch)
    {
        return $this->db->insert_batch("staging_vm_utilization_incident", $data_batch);
    }

    public function run_staging_validation(string $batch_id)
    {
        $staging_data = $this->db
            ->get_where("staging_vm_utilization_incident", ["batch_id" => $batch_id])
            ->result_array();
        if (empty($staging_data)) {
            return;
        }

        $this->db->select("id_virtual_machine, virtual_machine_name");
        $vm_results = $this->db->get("master_virtual_machine")->result_array();
        $master_vms = [];
        foreach ($vm_results as $vm) {
            $master_vms[strtolower(trim($vm["virtual_machine_name"]))] = $vm["id_virtual_machine"];
        }

        $extracted_tickets = [];
        foreach ($staging_data as $row) {
            if (preg_match("/\/browse\/([A-Z0-9\-]+)/i", $row["raw_link_tiket"], $matches)) {
                $extracted_tickets[] = $matches[1];
            }
        }

        $existing_tickets = [];
        if (!empty($extracted_tickets)) {
            $this->db->select("no_tiket_insiden");
            $this->db->where_in("no_tiket_insiden", array_unique($extracted_tickets));
            $ticket_results = $this->db->get("trx_vm_utilization_incident")->result_array();
            foreach ($ticket_results as $t) {
                $existing_tickets[$t["no_tiket_insiden"]] = true;
            }
        }

        $update_batch_payload = [];

        foreach ($staging_data as $row) {
            $is_valid = true;
            $reject_reasons = [];
            $update_data = ["id_staging" => $row["id_staging"]];

            $vm_name_clean = strtolower(trim($row["raw_vm_name"]));
            $vm_no_ip = preg_replace('/_[0-9]{1,3}(\.[0-9]{1,3}){3}$/', "", $vm_name_clean);
            $vm_no_ip = trim($vm_no_ip, " _,-");

            if (isset($master_vms[$vm_name_clean])) {
                $update_data["extracted_id_virtual_machine"] = $master_vms[$vm_name_clean];
                $reject_reasons[] =
                    "<span class='text-success d-block mb-1'><i class='fa fa-check-circle'></i> <b>Master VM:</b> Terdaftar (ID: " .
                    $master_vms[$vm_name_clean] .
                    ")</span>";
            } elseif (isset($master_vms[$vm_no_ip])) {
                $update_data["extracted_id_virtual_machine"] = $master_vms[$vm_no_ip];
                $reject_reasons[] =
                    "<span class='text-success d-block mb-1'><i class='fa fa-check-circle'></i> <b>Master VM:</b> Terdaftar via Regex IP (ID: " .
                    $master_vms[$vm_no_ip] .
                    ")</span>";
            } else {
                $update_data["extracted_id_virtual_machine"] = null;
                $reject_reasons[] =
                    "<span class='text-warning d-block mb-1'><i class='fa fa-exclamation-triangle'></i> <b>Master VM:</b> Belum Diregistrasi. (Akan direkam sebagai Snapshot)</span>";
            }

            if (preg_match("/\/browse\/([A-Z0-9\-]+)/i", $row["raw_link_tiket"], $matches)) {
                $ticket_no = $matches[1];
                $update_data["extracted_no_tiket_insiden"] = $ticket_no;
                if (isset($existing_tickets[$ticket_no])) {
                    $is_valid = false;
                    $reject_reasons[] = "<span class='text-danger'>- Tiket [{$ticket_no}] Duplikat (Sudah ada di sistem).</span>";
                }
            } else {
                $is_valid = false;
                $reject_reasons[] =
                    "<span class='text-danger'>- Link Tiket tidak mengandung format nomor Jira.</span>";
            }

            $text_to_search = $row["raw_kategori"] . " " . $row["raw_keterangan_mixed"];
            if (preg_match("/(\d+(?:[.,]\d+)?)\s*%/i", $text_to_search, $matches)) {
                $update_data["extracted_metrik_tercatat"] = str_replace(",", ".", $matches[1]);
            } else {
                $urgensi_cek = strtolower(trim($row["raw_kritikalitas"]));
                if ($urgensi_cek === "critical" || $urgensi_cek === "very high") {
                    $update_data["extracted_metrik_tercatat"] = "98.00";
                } elseif ($urgensi_cek === "high") {
                    $update_data["extracted_metrik_tercatat"] = "95.00";
                } else {
                    $update_data["extracted_metrik_tercatat"] = "90.00";
                }
            }

            if (!empty($row["raw_tanggal_created"])) {
                $created_time = strtotime($row["raw_tanggal_created"]);
                if ($created_time !== false) {
                    $update_data["extracted_sla_deadline"] = date(
                        "Y-m-d H:i:s",
                        strtotime("+3 days", $created_time),
                    );
                } else {
                    $is_valid = false;
                    $reject_reasons[] =
                        "<span class='text-danger'>- Format Tanggal Created rusak.</span>";
                }
            } else {
                $is_valid = false;
                $reject_reasons[] = "<span class='text-danger'>- Tanggal Created Kosong.</span>";
            }

            $update_data["status_row"] = $is_valid ? "VALID" : "INVALID";
            $update_data["reject_reason"] = implode("", $reject_reasons);

            $update_batch_payload[] = $update_data;
        }

        if (!empty($update_batch_payload)) {
            $this->db->update_batch(
                "staging_vm_utilization_incident",
                $update_batch_payload,
                "id_staging",
                500,
            );
        }
    }

    public function commit_staging_to_production(string $batch_id, string $id_user_pelapor)
    {
        $valid_data = $this->db
            ->get_where("staging_vm_utilization_incident", [
                "batch_id" => $batch_id,
                "status_row" => "VALID",
            ])
            ->result_array();
        if (empty($valid_data)) {
            return false;
        }

        $teams = $this->db->get("master_team")->result_array();
        $master_teams = [];
        foreach ($teams as $t) {
            $master_teams[strtolower(trim($t["team_name"]))] = $t["id_team"];
        }

        $this->db->select("vm.id_virtual_machine, crit.criticality_name");
        $this->db->from("master_virtual_machine vm");
        $this->db->join(
            "relation_table rel",
            "rel.id_virtual_machine = vm.id_virtual_machine",
            "left",
        );
        $this->db->join(
            "master_application_system app",
            "app.id_application_system = rel.id_application_system",
            "left",
        );
        $this->db->join(
            "master_criticality crit",
            "crit.id_criticality = app.id_criticality",
            "left",
        );
        $cmdb_results = $this->db->get()->result_array();

        $cmdb_crit_map = [];
        foreach ($cmdb_results as $c) {
            $cmdb_crit_map[$c["id_virtual_machine"]] = $c["criticality_name"];
        }

        $this->db->trans_start();

        foreach ($valid_data as $row) {
            $created_date = date("Y-m-d H:i:s", strtotime($row["raw_tanggal_created"]));
            $resolved_date = !empty($row["raw_tanggal_done"])
                ? date("Y-m-d H:i:s", strtotime($row["raw_tanggal_done"]))
                : null;

            $status_enum = "Open Tiket";
            if (
                stripos($row["raw_status_tiket"], "Done") !== false ||
                stripos($row["raw_status_tiket"], "Close") !== false
            ) {
                $status_enum = "Done/Close";
            } elseif (
                stripos($row["raw_status_tiket"], "Progress") !== false ||
                stripos($row["raw_status_tiket"], "Review") !== false
            ) {
                $status_enum = "Review by Owner";
            }

            $id_vm_resolved = $row["extracted_id_virtual_machine"];

            if (!empty($id_vm_resolved) && !empty($cmdb_crit_map[$id_vm_resolved])) {
                $urgensi = $cmdb_crit_map[$id_vm_resolved];
            } else {
                $urgensi = trim($row["raw_kritikalitas"]);
            }

            $valid_urgensi = ["Low", "Medium", "High", "Critical"];
            if (!in_array($urgensi, $valid_urgensi)) {
                if (stripos($urgensi, "very high") !== false) {
                    $urgensi = "High";
                } else {
                    $urgensi = "Medium";
                }
            }

            $parsed_ip = null;
            $raw_vm_text = trim($row["raw_vm_name"]);
            if (preg_match("/(?:[0-9]{1,3}\.){3}[0-9]{1,3}/", $raw_vm_text, $matches_ip)) {
                $parsed_ip = $matches_ip[0];
            }

            $raw_kategori = trim($row["raw_kategori"]);
            $keterangan = trim($row["raw_keterangan_mixed"]);
            $metrik_val = $row["extracted_metrik_tercatat"];
            $disk_drive_detail = null;

            $full_text = strtolower($raw_kategori . " " . $keterangan);
            $kategori = "Other/Unknown";

            if (
                strpbrk($full_text, "cpu") !== false ||
                strpbrk($full_text, "processor") !== false
            ) {
                $kategori = "CPU";
            } elseif (
                stripos($full_text, "mem") !== false ||
                stripos($full_text, "ram") !== false ||
                stripos($full_text, "memory") !== false
            ) {
                $kategori = "Memory";
            } elseif (
                stripos($full_text, "disk") !== false ||
                stripos($full_text, "drive") !== false ||
                stripos($full_text, "partisi") !== false ||
                stripos($full_text, "backup") !== false ||
                stripos($full_text, "data") !== false ||
                stripos($full_text, "database") !== false ||
                stripos($full_text, "storage") !== false ||
                stripos($full_text, "volume") !== false ||
                stripos($full_text, "mnt") !== false
            ) {
                $kategori = "Disk";
            } elseif (
                stripos($full_text, "os") !== false ||
                stripos($full_text, "operating system") !== false ||
                stripos($full_text, "obsolete") !== false
            ) {
                $kategori = "OS";
            } elseif (
                stripos($full_text, "host") !== false ||
                stripos($full_text, "esxi") !== false
            ) {
                $kategori = "Physical Host";
            } elseif (
                stripos($full_text, "audit") !== false ||
                stripos($full_text, "compliance") !== false ||
                stripos($full_text, "zombie") !== false
            ) {
                $kategori = "Audit";
            }

            if ($kategori === "Disk") {
                if (
                    preg_match(
                        '/(?:drive|disk|partisi|volume)\s+([a-zA-Z])(?:[:\s><=]|$)/i',
                        $keterangan,
                        $matches,
                    )
                ) {
                    $disk_drive_detail = strtoupper(trim($matches[1])) . ":";
                } elseif (preg_match("/\b([a-zA-Z]):/i", $keterangan, $matches)) {
                    $disk_drive_detail = strtoupper(trim($matches[1])) . ":";
                } elseif (preg_match("/(\/[a-zA-Z0-9_\-\.]*)/", $keterangan, $matches)) {
                    $candidate = trim($matches[1]);
                    if (!preg_match('/^\/[\d\/\.]+$/', $candidate)) {
                        $disk_drive_detail = strtolower($candidate);
                    }
                }
                if ($disk_drive_detail !== null && strpbrk($disk_drive_detail, "%><=") !== false) {
                    $disk_drive_detail = null;
                }
            }

            if (in_array($kategori, ["OS", "Audit", "Physical Host"])) {
                $metrik_val = 0;
            }

            $deskripsi = $keterangan;
            if (empty($deskripsi) || $deskripsi === "-") {
                $deskripsi = "Dibuat otomatis via Mass Importer. (Keterangan kosong)";
            }

            $catatan_resolusi = null;
            if ($status_enum === "Done/Close") {
                $catatan_resolusi = trim($row["raw_action"]);
                if (empty($catatan_resolusi)) {
                    $catatan_resolusi = "Diselesaikan otomatis (Auto-Closed by Import)";
                }
            }

            $incident_payload = [
                "id_virtual_machine" => $id_vm_resolved,
                "snapshot_vm_name" => $raw_vm_text,
                "snapshot_ip_address" => $parsed_ip,
                "no_tiket_insiden" => $row["extracted_no_tiket_insiden"],
                "link_tiket" => trim($row["raw_link_tiket"]) ?: null,
                "tipe_insiden" => $kategori,
                "disk_drive_detail" => $disk_drive_detail,
                "deskripsi_insiden" => $deskripsi,
                "metrik_tercatat" => $metrik_val,
                "tingkat_urgensi" => $urgensi,
                "status_insiden" => $status_enum,
                "id_pelapor" => $id_user_pelapor,
                "id_assignee" => $status_enum !== "Open Tiket" ? $id_user_pelapor : null,
                "catatan_resolusi" => $catatan_resolusi,
                "created_at" => $created_date,
                "sla_deadline" => $row["extracted_sla_deadline"],
                "resolved_at" => $resolved_date,
            ];

            $this->db->insert("trx_vm_utilization_incident", $incident_payload);
            $new_incident_id = $this->db->insert_id();

            $is_fu = strtoupper(trim($row["raw_fu_user_checklist"] ?? ""));
            if (in_array($is_fu, ["V", "Y", "YES", "1", "TRUE"])) {
                $id_team_tujuan = null;
                $req_team_excel = strtolower(trim($row["raw_fungsi_requestor"] ?? ""));

                if (!empty($req_team_excel)) {
                    foreach ($master_teams as $team_name => $team_id) {
                        if (
                            strpos($team_name, $req_team_excel) !== false ||
                            strpos($req_team_excel, $team_name) !== false
                        ) {
                            $id_team_tujuan = $team_id;
                            break;
                        }
                    }
                }

                $fu_date = !empty($row["raw_tanggal_fu"])
                    ? date("Y-m-d H:i:s", strtotime($row["raw_tanggal_fu"]))
                    : date("Y-m-d H:i:s");
                $fu_action = trim($row["raw_action"] ?? "");
                if (empty($fu_action)) {
                    $fu_action = "Follow Up System (Import)";
                }

                $next_deadline = null;
                if ($status_enum !== "Done/Close") {
                    $next_deadline = date("Y-m-d H:i:s", strtotime("+2 days", strtotime($fu_date)));
                }

                $fu_payload = [
                    "id_incident" => $new_incident_id,
                    "id_user" => $id_user_pelapor,
                    "id_team_tujuan" => $id_team_tujuan,
                    "aksi_tindakan" => $fu_action,
                    "catatan_fu" => $deskripsi,
                    "next_fu_deadline" => $next_deadline,
                    "created_at" => $fu_date,
                ];
                $this->db->insert("trx_vm_incident_fu", $fu_payload);
            }

            $this->db
                ->where("id_staging", $row["id_staging"])
                ->update("staging_vm_utilization_incident", ["status_row" => "IMPORTED"]);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
