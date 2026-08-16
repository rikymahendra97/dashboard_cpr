<?php
defined("BASEPATH") or exit("No direct script access allowed");

/**
 * ============================================================================
 * File Name    : Vm_restart.php
 * Modul        : VM Restart
 * Purpose      : Controller utama untuk modul pencatatan Log Restart Server.
 * Architecture : Pessimistic Row Locking, RAM-Safe DataTables, Streaming Export
 * ============================================================================
 */
class Vm_restart extends CI_Controller
{
    // ========================================================================
    // SECTION 1: CONSTRUCTOR & SECURITY INITIALIZATION
    // ========================================================================
    public function __construct()
    {
        parent::__construct();

        if (empty($this->session->userdata("user_data"))) {
            if ($this->input->is_ajax_request()) {
                $this->output->set_status_header(401);
                $this->output
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

        $this->load->helper("datetime");
        $this->load->library(["csrf", "form_validation"]);
        $this->load->model("vm_restart_model");
        $this->load->model("Notification_queue_model");

        $this->db->query("SET time_zone = '+07:00'");
    }

    public function index()
    {
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $this->session->userdata("user_data");
        $data["title"] = "Log Restart Server";

        $summary = $this->vm_restart_model->get_kpi_summary();
        $data["kpi"] = [
            "menunggu" => $summary["menunggu"] ?? 0,
            "dieksekusi" => $summary["dieksekusi"] ?? 0,
            "selesai" => $summary["selesai"] ?? 0,
            "kurang_7" => $summary["kurang_7"] ?? 0,
            "lewat_7" => $summary["lewat_7"] ?? 0,
            "lewat_14" => $summary["lewat_14"] ?? 0,
        ];

        $data["list_restart"] = [];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_restart/list_vm_restart", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function create()
    {
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $this->session->userdata("user_data");
        $data["title"] = "Buat Tiket Restart Baru";

        $duplicate_id = $this->input->get("duplicate_from", true);
        $data["duplicate_data"] = null;

        if ($duplicate_id) {
            $data["duplicate_data"] = $this->vm_restart_model->get_ticket_detail(
                (int) $duplicate_id,
            );
            if (!empty($data["duplicate_data"])) {
                $data["title"] =
                    "Duplikat Request - " . html_escape($data["duplicate_data"]["no_tiket_iris"]);
            }
        }

        $data["master_vm"] = $this->vm_restart_model->get_master_vm();
        $data["master_team"] = $this->vm_restart_model->get_master_team();

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_restart/form_add_vm_restart", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function edit(string $id_restart)
    {
        $id_restart = (int) $id_restart;
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $this->session->userdata("user_data");

        if (!can_edit_execute($data["user_session"]["id_role"])) {
            $this->session->set_flashdata("alerts", [
                [
                    "error",
                    "Akses Ditolak! Anda tidak memiliki wewenang untuk mengedit log tiket ini.",
                ],
            ]);
            redirect("vm_restart");
            return;
        }

        $data["detail"] = $this->vm_restart_model->get_ticket_detail($id_restart);
        if (empty($data["detail"])) {
            show_404();
        }

        if (in_array($data["detail"]["status_eksekusi"], ["Selesai Verified", "Cancel by User"])) {
            $this->session->set_flashdata("alerts", [
                [
                    "warning",
                    "Akses Terkunci: Data tidak dapat diedit karena tiket sudah ditutup permanen.",
                ],
            ]);
            redirect("vm_restart");
            return;
        }

        $data["title"] = "Edit Tiket Restart - " . $data["detail"]["no_tiket_iris"];
        $data["master_vm"] = $this->vm_restart_model->get_master_vm();
        $data["master_team"] = $this->vm_restart_model->get_master_team();

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_restart/form_edit_vm_restart", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function detail(string $id_restart)
    {
        $id_restart = (int) $id_restart;
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $this->session->userdata("user_data");
        $data["detail"] = $this->vm_restart_model->get_ticket_detail($id_restart);

        if (empty($data["detail"])) {
            show_404();
        }

        $data["title"] = "Detail Tiket Restart - " . $data["detail"]["no_tiket_iris"];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_restart/detail_vm_restart", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function hapus()
    {
        $id_restart = (int) $this->input->post("id_restart", true);
        $user_session = $this->session->userdata("user_data");

        if (!$id_restart) {
            $this->session->set_flashdata("alerts", [["error", "ID Tiket tidak valid."]]);
            redirect("vm_restart");
            return;
        }

        if (can_verify_delete($user_session["id_role"])) {
            $status = $this->vm_restart_model->hapus_data($id_restart);
            if ($status) {
                $alerts[] = ["success", "Data log restart berhasil dihapus permanen."];
            } else {
                $alerts[] = ["error", "Gagal menghapus data log restart."];
            }
        } else {
            $alerts[] = [
                "error",
                "Akses Ditolak! Privilege Admin/Verifikator diperlukan untuk menghapus data.",
            ];
        }

        $this->session->set_flashdata("alerts", $alerts);
        redirect("vm_restart?_=" . time());
    }

    // ========================================================================
    // SECTION 3: FORM PROCESSING (WRITE) & SYSTEM INTEGRATION
    // ========================================================================
    public function store()
    {
        $this->form_validation->set_rules("no_tiket_iris", "No Tiket IRIS", "required|trim");
        $this->form_validation->set_rules("id_virtual_machine", "Target VM", "required|numeric");
        $this->form_validation->set_rules("id_team_requestor", "Fungsi Requestor", "required");
        $this->form_validation->set_rules(
            "jenis_downtime",
            "Jenis Downtime",
            "required|in_list[Planned,Unplanned]",
        );
        $this->form_validation->set_rules("root_cause", "Root Cause", "required|trim");

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata("alerts", [["error", validation_errors("", "")]]);
            redirect("vm_restart/create");
        } else {
            $no_tiket_iris = $this->input->post("no_tiket_iris", true);
            $id_virtual_machine = (int) $this->input->post("id_virtual_machine", true);

            if (
                $this->vm_restart_model->check_duplicate_restart(
                    $no_tiket_iris,
                    $id_virtual_machine,
                    0,
                )
            ) {
                $this->session->set_flashdata("alerts", [
                    [
                        "error",
                        "Data Ditolak: Virtual Machine tersebut sudah terdaftar pada nomor tiket yang sama!",
                    ],
                ]);
                redirect("vm_restart/create");
                return;
            }

            // [ENTERPRISE FIX]: Menangkap ID Insiden dari Form View untuk disimpan
            $id_incident_req = $this->input->post("resolve_incident_id", true);
            $user_session = $this->session->userdata("user_data");

            $payload = [
                "no_tiket_iris" => $no_tiket_iris,
                "link_tiket" => $this->input->post("link_tiket", false),
                "id_virtual_machine" => $id_virtual_machine,
                "id_team_requestor" => $this->input->post("id_team_requestor", true),
                "jenis_downtime" => $this->input->post("jenis_downtime", true),
                "root_cause" => $this->input->post("root_cause", true),
                "keterangan_request" => $this->input->post("keterangan_request", true),
                "id_pencatat" => $user_session["id_user"],
                "status_eksekusi" => "Menunggu Eksekusi",
                "id_incident" => !empty($id_incident_req) ? (int) $id_incident_req : null, // INJEKSI FIX
            ];

            $insert_id = $this->vm_restart_model->insert_restart_request($payload);

            if ($insert_id) {
                $this->session->set_flashdata("alerts", [
                    ["success", "Tiket Restart Server berhasil dicatat."],
                ]);
                redirect("vm_restart/detail/" . $insert_id . "?_=" . time());
            } else {
                $this->session->set_flashdata("alerts", [
                    ["error", "Terjadi kesalahan sistem saat menyimpan tiket."],
                ]);
                redirect("vm_restart/create");
            }
        }
    }

    public function update()
    {
        $id_restart = (int) $this->input->post("id_restart", true);
        $user_session = $this->session->userdata("user_data");

        if (!can_edit_execute($user_session["id_role"])) {
            $this->session->set_flashdata("alerts", [
                ["error", "Akses Ditolak! Wewenang terbatas, gagal menyimpan pembaruan data."],
            ]);
            redirect("vm_restart/detail/" . $id_restart);
            return;
        }

        $this->form_validation->set_rules("no_tiket_iris", "No Tiket IRIS", "required|trim");
        $this->form_validation->set_rules("id_virtual_machine", "Target VM", "required|numeric");
        $this->form_validation->set_rules("id_team_requestor", "Fungsi Requestor", "required");
        $this->form_validation->set_rules(
            "jenis_downtime",
            "Jenis Downtime",
            "required|in_list[Planned,Unplanned]",
        );
        $this->form_validation->set_rules("root_cause", "Root Cause", "required|trim");

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata("alerts", [["error", validation_errors("", "")]]);
            redirect("vm_restart/edit/" . $id_restart);
        } else {
            $no_tiket_iris = $this->input->post("no_tiket_iris", true);
            $id_virtual_machine = (int) $this->input->post("id_virtual_machine", true);

            if (
                $this->vm_restart_model->check_duplicate_restart(
                    $no_tiket_iris,
                    $id_virtual_machine,
                    $id_restart,
                )
            ) {
                $this->session->set_flashdata("alerts", [
                    [
                        "error",
                        "Data Ditolak: Virtual Machine tersebut sudah terdaftar pada nomor tiket yang sama!",
                    ],
                ]);
                redirect("vm_restart/edit/" . $id_restart);
                return;
            }

            $this->vm_restart_model->update_restart_request($id_restart);
            $this->session->set_flashdata("alerts", [
                ["success", "Perubahan detail tiket berhasil disimpan."],
            ]);
            redirect("vm_restart/detail/" . $id_restart);
        }
    }

    public function ajax_check_duplicate()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $no_tiket = $this->input->post("no_tiket", true);
        $id_vm = (int) $this->input->post("id_virtual_machine", true);
        $id_restart = (int) $this->input->post("id_change", true);

        $is_duplicate = $this->vm_restart_model->check_duplicate_restart(
            $no_tiket,
            $id_vm,
            $id_restart,
        );
        $response = ["csrf_hash" => $this->security->get_csrf_hash()];

        if ($is_duplicate) {
            $response["status"] = "duplicate";
            $response[
                "message"
            ] = "Data Ditolak: Virtual Machine tersebut sudah didaftarkan pada Nomor Tiket <b>{$no_tiket}</b>. Harap batalkan tiket sebelumnya atau pilih VM lain.";
        } else {
            $response["status"] = "safe";
        }
        $this->output->set_content_type("application/json")->set_output(json_encode($response));
    }

    // ========================================================================
    // [ENTERPRISE FIX]: PESSIMISTIC LOCKING WORKFLOW & INTEGRATION
    // ========================================================================
    public function ajax_execute_workflow()
    {
        if (ob_get_length()) {
            ob_clean();
        }

        if (!$this->input->post()) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(json_encode(["status" => false, "message" => "Invalid request."]));
            return;
        }

        $user_session = $this->session->userdata("user_data");
        $id_restart = (int) $this->input->post("id_restart", true);
        $action_type = $this->input->post("action_type", true);

        if (empty($id_restart) || empty($action_type)) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode(["status" => false, "message" => "Parameter tidak lengkap."]),
                );
            return;
        }

        // Pembatasan Akses L1 / L2 Secara Murni
        if (
            in_array($action_type, ["cancel", "execute"]) &&
            !can_edit_execute($user_session["id_role"])
        ) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode([
                        "status" => false,
                        "message" => "Akses Ditolak! Wewenang terbatas untuk eksekusi/batal.",
                    ]),
                );
            return;
        }

        if ($action_type === "verify" && !can_verify_delete($user_session["id_role"])) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode([
                        "status" => false,
                        "message" => "Akses ditolak! Wewenang verifikasi (L2) tidak mencukupi.",
                    ]),
                );
            return;
        }

        $this->db->trans_start(); // MEMULAI TRANSAKSI KUNCI DATABASE

        // ROW-LEVEL LOCKING AGAR TIDAK BENTROK
        $dt_vm = $this->vm_restart_model->get_ticket_detail_for_update($id_restart);
        if (!$dt_vm) {
            $this->db->trans_rollback();
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode([
                        "status" => false,
                        "message" =>
                            "Tiket sedang diproses oleh pengguna lain, atau data tidak ditemukan.",
                    ]),
                );
            return;
        }

        $payload_update = [];

        if ($action_type === "cancel") {
            if ($dt_vm["status_eksekusi"] === "Cancel by User") {
                $this->db->trans_rollback();
                $this->output
                    ->set_content_type("application/json")
                    ->set_output(
                        json_encode([
                            "status" => true,
                            "message" => "Tiket sudah dibatalkan sebelumnya.",
                        ]),
                    );
                return;
            }
            $payload_update = [
                "status_eksekusi" => "Cancel by User",
                "id_executor" => $user_session["id_user"],
                "catatan_eksekusi" => $this->input->post("catatan_eksekusi", true),
                "tanggal_eksekusi" => null,
                "start_downtime" => null,
                "finish_downtime" => null,
                "durasi_downtime_menit" => 0,
            ];
        } elseif ($action_type === "execute") {
            $start_dt = $this->input->post("start_downtime", true);
            $finish_dt = $this->input->post("finish_downtime", true);
            $catatan = $this->input->post("catatan_eksekusi", true);
            $tanggal_eksekusi = $this->input->post("tanggal_eksekusi", true);

            if (empty($start_dt) || empty($finish_dt)) {
                $this->db->trans_rollback();
                $this->output
                    ->set_content_type("application/json")
                    ->set_output(
                        json_encode([
                            "status" => false,
                            "message" => "Waktu Start and Finish Downtime wajib diisi.",
                        ]),
                    );
                return;
            }

            // Normalisasi & Kalkulasi Durasi
            $start_dt_n = normalize_mysql_datetime($start_dt);
            $finish_dt_n = normalize_mysql_datetime($finish_dt);
            $start = strtotime($start_dt_n);
            $finish = strtotime($finish_dt_n);

            if ($start > $finish) {
                $temp = $start;
                $start = $finish;
                $finish = $temp;
                $start_dt_n = date("Y-m-d H:i:s", $start);
                $finish_dt_n = date("Y-m-d H:i:s", $finish);
            }
            $durasi = round(($finish - $start) / 60);

            $payload_update = [
                "status_eksekusi" => "Telah Dieksekusi",
                "id_executor" => $user_session["id_user"],
                "tanggal_eksekusi" => !empty($tanggal_eksekusi)
                    ? date("Y-m-d H:i:s", strtotime(normalize_mysql_datetime($tanggal_eksekusi)))
                    : date("Y-m-d H:i:s"),
                "catatan_eksekusi" => $catatan,
                "start_downtime" => $start_dt_n,
                "finish_downtime" => $finish_dt_n,
                "durasi_downtime_menit" => $durasi > 0 ? $durasi : 0,
            ];
        } elseif ($action_type === "verify") {
            // Validasi Conflict of Interest (Maker != Checker)
            if (
                $dt_vm["id_executor"] == $user_session["id_user"] ||
                $dt_vm["id_pencatat"] == $user_session["id_user"]
            ) {
                $this->db->trans_rollback();
                $this->output
                    ->set_content_type("application/json")
                    ->set_output(
                        json_encode([
                            "status" => false,
                            "message" =>
                                "Pelanggaran Maker-Checker (Conflict of Interest): Anda tidak diizinkan memverifikasi tiket yang telah Anda buat/eksekusi sendiri.",
                        ]),
                    );
                return;
            }
            $payload_update = [
                "status_eksekusi" => "Selesai Verified",
                "id_verifikator" => $user_session["id_user"],
                "catatan_verifikasi" => $this->input->post("catatan_verifikasi", true),
                "tanggal_verifikasi" => date("Y-m-d H:i:s"),
            ];
        }

        $this->vm_restart_model->update_workflow_status($id_restart, $payload_update);

        // [ENTERPRISE FIX]: Auto-Resolve Incident (Secure Logic via DB Lock, Not DOM)
        try {
            // Mengambil id_incident murni dari hasil SELECT Database yang sudah ter-lock (Aman dari Hack UI)
            $resolve_incident_id = $dt_vm["id_incident"] ?? null;

            if (!empty($resolve_incident_id) && $action_type === "execute") {
                $this->load->model("Vm_incident_model");
                $catatan_implementer = $this->input->post("catatan_eksekusi", true);
                $data_fu = [
                    "id_incident" => $resolve_incident_id,
                    "id_user" => $user_session["id_user"],
                    "aksi_tindakan" => "Auto-Resolve via Modul Restart",
                    "catatan_fu" => "Telah diselesaikan otomatis. Catatan: " . $catatan_implementer,
                    "created_at" => date("Y-m-d H:i:s"),
                ];
                $this->Vm_incident_model->insert_fu_and_update_incident(
                    $data_fu,
                    ["status_insiden" => "Done/Close", "id_assignee" => $user_session["id_user"]],
                    $resolve_incident_id,
                );
            }
        } catch (\Throwable $e) {
            log_message("error", "[VM RESTART] Integrasi auto-resolve gagal: " . $e->getMessage());
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === true) {
            $this->session->set_flashdata("alerts", [
                ["success", "Status tiket berhasil diperbarui."],
            ]);
            $this->output
                ->set_content_type("application/json")
                ->set_output(json_encode(["status" => true, "message" => "Berhasil diperbarui."]));
        } else {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode([
                        "status" => false,
                        "message" => "Terjadi kesalahan sistem saat memperbarui database.",
                    ]),
                );
        }
    }

    public function ajax_update_kendala()
    {
        if (!$this->input->post()) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(json_encode(["status" => false, "message" => "Invalid request."]));
            return;
        }

        $id_restart = (int) $this->input->post("id_restart", true);
        $kendala = $this->input->post("kendala", true);

        if (empty($id_restart) || empty($kendala)) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode([
                        "status" => false,
                        "message" => "Catatan kendala tidak boleh kosong.",
                    ]),
                );
            return;
        }

        $process = $this->vm_restart_model->update_kendala($id_restart, $kendala);

        if ($process) {
            $this->session->set_flashdata("alerts", [
                ["success", "Info/Kendala shift berhasil diperbarui."],
            ]);
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode(["status" => true, "message" => "Catatan berhasil diperbarui."]),
                );
        } else {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode([
                        "status" => false,
                        "message" => "Gagal mengupdate catatan ke database.",
                    ]),
                );
        }
    }

    public function ajax_search_vm()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $search = $this->input->get("q", true);
        $page = $this->input->get("page", true) ?: 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $this->db->select(
            "id_virtual_machine as id, virtual_machine_name as text, ip_address, environment",
        );
        $this->db->from("master_virtual_machine");
        $this->db->where("id_site", "TBN");

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like("virtual_machine_name", $search);
            $this->db->or_like("ip_address", $search);
            $this->db->group_end();
        }

        $this->db->order_by("virtual_machine_name", "ASC");
        $this->db->limit($limit, $offset);
        $vms = $this->db->get()->result_array();

        $this->db->from("master_virtual_machine");
        $this->db->where("id_site", "TBN");
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like("virtual_machine_name", $search);
            $this->db->or_like("ip_address", $search);
            $this->db->group_end();
        }
        $total_count = $this->db->count_all_results();

        $this->output
            ->set_content_type("application/json")
            ->set_output(json_encode(["items" => $vms, "total_count" => $total_count]));
    }

    public function ajax_quick_add_team()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $team_name = strtoupper(trim($this->input->post("team_name", true)));
        $team_code = strtoupper(trim($this->input->post("team_code", true)));
        $pic_name = trim($this->input->post("pic_name", true));
        $pic_contact = trim($this->input->post("pic_contact", true));

        if (empty($team_name) || empty($team_code)) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode([
                        "status" => false,
                        "message" => "Nama Team dan Kode Team wajib diisi!",
                    ]),
                );
            return;
        }

        $this->db->where("team_code", $team_code);
        if (empty($pic_name)) {
            $this->db
                ->group_start()
                ->where("pic_name", null)
                ->or_where("pic_name", "")
                ->or_where("pic_name", "-")
                ->group_end();
        } else {
            $this->db->where("pic_name", $pic_name);
        }

        if ($this->db->get("master_team")->num_rows() > 0) {
            $msg = empty($pic_name)
                ? "Fungsi/Departemen tersebut sudah terdaftar sebagai Tim Umum (Tanpa Spesifik PIC)!"
                : "PIC dengan nama '$pic_name' sudah terdaftar di dalam fungsi tersebut!";
            $this->output
                ->set_content_type("application/json")
                ->set_output(json_encode(["status" => false, "message" => $msg]));
            return;
        }

        $this->db->where("team_code", $team_code);
        $existing_team = $this->db->get("master_team")->row();

        if ($existing_team && strtoupper($existing_team->team_name) !== $team_name) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode([
                        "status" => false,
                        "message" => "Kode ($team_code) adalah milik fungsi: {$existing_team->team_name}. Harap samakan ejaan Nama Team!",
                    ]),
                );
            return;
        }

        $data_insert = [
            "team_name" => $team_name,
            "team_code" => $team_code,
            "pic_name" => empty($pic_name) ? null : $pic_name,
            "pic_contact" => empty($pic_contact) ? null : $pic_contact,
        ];

        if ($this->db->insert("master_team", $data_insert)) {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => true,
                    "id_team" => $this->db->insert_id(),
                    "team_code" => $data_insert["team_code"],
                    "team_name" => $data_insert["team_name"],
                    "message" => empty($pic_name)
                        ? "Opsi Tim Umum berhasil ditambahkan!"
                        : "PIC baru berhasil didaftarkan ke dalam fungsi!",
                ]),
            );
        } else {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode([
                        "status" => false,
                        "message" => "Gagal menyimpan data ke database server.",
                    ]),
                );
        }
    }

    public function ajax_list()
    {
        $list = $this->vm_restart_model->get_datatables();
        $data = [];
        $no = (int) $this->input->post("start");
        $user_session = $this->session->userdata("user_data");
        $role = isset($user_session["id_role"]) ? (int) $user_session["id_role"] : 99;

        $can_edit_execute = can_edit_execute($role);
        $can_verify_delete = can_verify_delete($role);
        $now = new DateTime();

        foreach ($list as $row) {
            $no++;
            $row_data = [];

            // 1. Nomor
            $row_data[] = '<div class="text-center">' . $no . "</div>";

            // 2. Tiket (Copy Feature + SLA Badge)
            $s = $row["status_eksekusi"];
            $tiket_html = "";

            if (!empty($row["link_tiket"])) {
                $tiket_html .=
                    '<a href="' .
                    html_escape($row["link_tiket"]) .
                    '" target="_blank" class="text-primary font-bold" title="Buka Tiket Eksternal" style="text-decoration:underline;"><u>' .
                    html_escape($row["no_tiket_iris"]) .
                    '</u> <i class="fa fa-external-link"></i></a>';
            } else {
                $tiket_html .= "<strong>" . html_escape($row["no_tiket_iris"]) . "</strong>";
            }
            $tiket_html .=
                ' <i class="fa fa-copy inline-copy-trigger" data-text="' .
                html_escape($row["no_tiket_iris"]) .
                '" title="Salin Tiket" style="color:#cbd5e1; cursor:pointer; margin-left:5px;"></i>';

            if ($s === "Cancel by User" || $s === "Ditolak") {
                $sla_badge =
                    '<span class="label label-default" style="font-size:9px; padding:2px 5px;"><i class="fa fa-ban"></i> Cancel</span>';
            } else {
                $start_date = new DateTime($row["created_at"]);
                if ($s === "Telah Dieksekusi" || $s === "Selesai Verified") {
                    $end_date = !empty($row["tanggal_eksekusi"])
                        ? new DateTime($row["tanggal_eksekusi"])
                        : clone $now;
                    $diff = $start_date->diff($end_date)->days;
                    $sla_badge =
                        '<span class="label" style="background-color:#16a085; font-size:9px; padding:2px 5px;"><i class="fa fa-check-circle"></i> Closed</span>';
                } else {
                    $end_date = clone $now;
                    $diff = $start_date->diff($end_date)->days;
                    if ($diff >= 14) {
                        $sla_badge =
                            '<span class="label label-danger" style="font-size:9px; padding:2px 5px;"><i class="fa fa-fire"></i> SLA: ' .
                            $diff .
                            " Hari</span>";
                    } elseif ($diff >= 7) {
                        $sla_badge =
                            '<span class="label label-warning" style="font-size:9px; padding:2px 5px;"><i class="fa fa-exclamation-triangle"></i> SLA: ' .
                            $diff .
                            " Hari</span>";
                    } else {
                        $sla_badge =
                            '<span class="label label-success" style="font-size:9px; padding:2px 5px;"><i class="fa fa-clock-o"></i> SLA: ' .
                            $diff .
                            " Hari</span>";
                    }
                }
            }
            $tiket_html .= '<div style="margin-top: 5px;">' . $sla_badge . "</div>";
            $row_data[] = $tiket_html;

            // 3. VM & Copy IP Feature (Inline)
            $nama_vm = html_escape($row["nama_target_vm"]);
            $ip_vm = html_escape($row["ip_target_vm"]);

            $vm_html = "<div style='line-height:1.4;'>
                            <strong class='text-primary'>{$nama_vm}</strong>
                            <i class='fa fa-copy inline-copy-trigger' data-text='{$nama_vm}' title='Salin Nama VM' style='color:#cbd5e1; cursor:pointer; font-size:12px; margin-left:3px;'></i><br>
                            <small class='text-muted'>{$ip_vm}</small>
                            <i class='fa fa-copy inline-copy-trigger' data-text='{$ip_vm}' title='Salin IP' style='color:#cbd5e1; cursor:pointer; font-size:11px; margin-left:3px;'></i>
                        </div>";
            $row_data[] = $vm_html;

            // 4. Jenis
            $row_data[] =
                $row["jenis_downtime"] == "Planned"
                    ? '<div class="text-center"><span class="label label-success"><i class="fa fa-calendar-check-o"></i> Planned</span></div>'
                    : '<div class="text-center"><span class="label label-danger"><i class="fa fa-warning"></i> Unplanned</span></div>';

            // 5. Status
            if ($s == "Menunggu Eksekusi") {
                $c = "bg-red";
                $s_label = "Menunggu Eksekusi";
            } elseif ($s == "Telah Dieksekusi") {
                $c = "bg-blue";
                $s_label = "Menunggu Verifikasi";
            } elseif ($s == "Selesai Verified") {
                $c = "bg-green";
                $s_label = "Selesai Verified";
            } elseif ($s == "Cancel by User") {
                $c = "bg-orange";
                $s_label = "Dibatalkan";
            } else {
                $c = "bg-black";
                $s_label = $s;
            }
            $row_data[] = "<div class='text-center'><span class='badge {$c}' style='font-size:11.5px; padding:5px 8px; letter-spacing:0.3px;'>{$s_label}</span></div>";

            // 6. Implementer
            $row_data[] = !empty($row["nama_executor"])
                ? "<strong>" . html_escape($row["nama_executor"]) . "</strong>"
                : '<span class="text-muted">-</span>';

            // 7. Catatan Progressive Disclosure
            $catatan = $row["catatan_eksekusi"];
            $deskripsi = isset($row["keterangan_request"]) ? $row["keterangan_request"] : "";
            $catatan_html = "";
            $isi_konten = "";

            $raw_catatan = html_escape($catatan);
            $icon_edit = $can_edit_execute
                ? "<i class='fa fa-pencil btn-kendala' data-id='{$row["id_restart"]}' data-notes='{$raw_catatan}' title='Update Catatan' style='color:#3498DB; cursor:pointer; font-size:13px; margin-left:5px;'></i>"
                : "";

            if ($s == "Menunggu Eksekusi") {
                if (!empty($catatan)) {
                    $isi_konten =
                        "<div><strong style='color:#b18c00;'><i class='fa fa-info-circle'></i> Info/Kendala Terkini:</strong> $icon_edit </div><div style='margin-top:4px;'>" .
                        nl2br(html_escape($catatan)) .
                        "</div>";
                } elseif (!empty($deskripsi)) {
                    $isi_konten =
                        "<div><strong class='text-muted'><i class='fa fa-file-text-o'></i> Deskripsi Permintaan:</strong> $icon_edit </div><div style='margin-top:4px; font-style:italic;'>" .
                        nl2br(html_escape($deskripsi)) .
                        "</div>";
                } else {
                    $isi_konten = "<div><strong class='text-muted'><i class='fa fa-file-text-o'></i> Catatan:</strong> $icon_edit </div><div style='margin-top:4px; color:#aaa; font-style:italic;'>Belum ada catatan...</div>";
                }
                $catatan_html =
                    "<div style='background-color: #fffdf2; border-left: 3px solid #f1c40f; padding: 8px 10px; font-size: 12px; white-space: normal; color: #333; border-radius: 2px; box-shadow: 0 1px 1px rgba(0,0,0,0.05); min-width: 250px;'>" .
                    $isi_konten .
                    "</div>";
            } elseif ($s == "Telah Dieksekusi") {
                $isi_konten =
                    "<div><strong class='text-primary'><i class='fa fa-check-square-o'></i> Laporan Eksekusi:</strong> $icon_edit </div><div style='margin-top:4px;'>" .
                    (!empty($catatan)
                        ? nl2br(html_escape($catatan))
                        : '<span style="color:#aaa; font-style:italic;">Tanpa catatan operasional...</span>') .
                    "</div>";
                $catatan_html =
                    "<div style='background-color: #f4f8fa; border-left: 3px solid #3498DB; padding: 8px 10px; font-size: 12px; white-space: normal; color: #333; border-radius: 2px; min-width: 250px;'>" .
                    $isi_konten .
                    "</div>";
            } else {
                $icon_status =
                    $s == "Cancel by User" ? "fa-ban text-danger" : "fa-shield text-success";
                $color_border = $s == "Cancel by User" ? "#e74c3c" : "#2ecc71";
                $bg_color = $s == "Cancel by User" ? "#fadbd8" : "#eafaf1";
                $judul_status = $s == "Cancel by User" ? "Alasan Batal" : "Laporan Akhir";

                $isi_konten =
                    "<div><strong style='color:{$color_border};'><i class='fa {$icon_status}'></i> {$judul_status}:</strong></div><div style='margin-top:4px;'>" .
                    (!empty($catatan)
                        ? nl2br(html_escape($catatan))
                        : '<span style="color:#aaa; font-style:italic;">Tanpa catatan...</span>') .
                    "</div>";
                $catatan_html =
                    "<div style='background-color: {$bg_color}; border-left: 3px solid {$color_border}; padding: 8px 10px; font-size: 12px; white-space: normal; color: #333; border-radius: 2px; min-width: 250px;'>" .
                    $isi_konten .
                    "</div>";
            }
            $row_data[] = $catatan_html;

            // 8. Opsi Aksi
            $is_closed = $s == "Selesai Verified" || $s == "Cancel by User";
            $btn =
                '<div class="text-center" style="display: flex; justify-content: center; align-items: center; gap: 8px;">';
            $btn .=
                '<a href="' .
                site_url("vm_restart/detail/" . $row["id_restart"]) .
                '" class="btn btn-info btn-xs" title="Lihat Detail" style="margin:0;"><i class="fa fa-search"></i></a> ';

            if ($is_closed) {
                $btn .=
                    '<button type="button" class="btn btn-default btn-xs btn-locked" style="color:#aaa; border-color:#e5e5e5; background:#f9f9f9; margin:0;" title="Akses Terkunci (Closed)"><i class="fa fa-lock"></i></button> ';
            } else {
                $btn .=
                    '<a href="' .
                    site_url("vm_restart/create?duplicate_from=" . $row["id_restart"]) .
                    '" class="btn btn-default btn-xs" style="color:#2A3F54; border-color:#2A3F54; margin:0;" title="Duplikat Request"><i class="fa fa-copy"></i></a> ';
            }

            if ($can_edit_execute) {
                if ($is_closed) {
                    $btn .=
                        '<button type="button" class="btn btn-default btn-xs btn-locked" style="color:#aaa; border-color:#e5e5e5; background:#f9f9f9; margin:0;" title="Akses Terkunci"><i class="fa fa-lock"></i></button> ';
                } else {
                    $btn .=
                        '<a href="' .
                        site_url("vm_restart/edit/" . $row["id_restart"]) .
                        '" class="btn btn-default btn-xs" style="color:#d58512; border-color:#d58512; margin:0;" title="Edit Request/Log"><i class="fa fa-edit"></i></a> ';
                }
            }

            if ($can_verify_delete) {
                $btn .=
                    '<button type="button" data-id="' .
                    $row["id_restart"] .
                    '" class="btn btn-default btn-xs btn_del" style="color:#ac2925; border-color:#ac2925; margin:0;" title="Hapus Permanen"><i class="fa fa-trash-o"></i></button>';
            }
            $btn .= "</div>";

            $row_data[] = $btn;
            $data[] = $row_data;
        }

        $output = [
            "draw" => (int) $this->input->post("draw"),
            "recordsTotal" => $this->vm_restart_model->count_all(),
            "recordsFiltered" => $this->vm_restart_model->count_filtered(),
            "data" => $data,
        ];
        $this->output->set_content_type("application/json")->set_output(json_encode($output));
    }

    // ========================================================================
    // [ENTERPRISE FIX]: EXPORT EXCEL DATA (STREAMING UNBUFFERED)
    // ========================================================================
    private function _get_headers_title(array $cols_array): array
    {
        $headers = [];
        $map_title = [
            "no" => "No",
            "nama_server" => "Nama Server",
            "start_restart" => "Start Restart",
            "finish_restart" => "Finish Restart",
            "no_tiket" => "No Tiket",
            "root_cause" => "Root Cause",
            "ip_server" => "IP Address",
            "jenis_downtime" => "Jenis Downtime",
            "durasi" => "Durasi (Menit)",
            "status_eksekusi" => "Status Akhir",
            "nama_executor" => "Implementer",
            "keterangan" => "Keterangan Request",
            "catatan_eksekusi" => "Catatan Eksekusi",
            "catatan_verifikasi" => "Catatan Verifikasi",
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
            "nama_server" =>
                "<td><b>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["nama_server"])
                        : $row["nama_server"] ?? "-",
                ) .
                "</b></td>",
            "start_restart" =>
                '<td align="center">' .
                (!empty($row["start_downtime"])
                    ? date("d-M-Y H:i", strtotime($row["start_downtime"]))
                    : "-") .
                "</td>",
            "finish_restart" =>
                '<td align="center">' .
                (!empty($row["finish_downtime"])
                    ? date("d-M-Y H:i", strtotime($row["finish_downtime"]))
                    : "-") .
                "</td>",
            "no_tiket" =>
                '<td align="center" class="str font-bold" ' .
                ($is_excel ? 'style="mso-number-format:\@;"' : "") .
                ">" .
                html_escape(
                    $is_excel ? $this->_sanitize_excel_formula($row["no_tiket"]) : $row["no_tiket"],
                ) .
                "</td>",
            "root_cause" =>
                '<td style="white-space: normal; min-width:200px;">' .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["root_cause"])
                        : $row["root_cause"] ?? "-",
                ) .
                "</td>",
            "ip_server" =>
                '<td align="center" class="str" ' .
                ($is_excel ? 'style="mso-number-format:\@;"' : "") .
                ">" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["ip_server"])
                        : $row["ip_server"] ?? "-",
                ) .
                "</td>",
            "jenis_downtime" =>
                '<td align="center">' . html_escape($row["jenis_downtime"] ?? "-") . "</td>",
            "durasi" =>
                '<td align="center" class="font-bold" style="color:#d9534f;">' .
                html_escape($row["durasi_downtime_menit"] ?? "0") .
                "</td>",
            "status_eksekusi" =>
                '<td align="center">' . html_escape($row["status_eksekusi"] ?? "-") . "</td>",
            "nama_executor" =>
                "<td>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["nama_executor"])
                        : $row["nama_executor"] ?? "-",
                ) .
                "</td>",
            "keterangan" =>
                "<td>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["keterangan_request"])
                        : $row["keterangan_request"] ?? "-",
                ) .
                "</td>",
            "catatan_eksekusi" =>
                "<td>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["catatan_eksekusi"])
                        : $row["catatan_eksekusi"] ?? "-",
                ) .
                "</td>",
            "catatan_verifikasi" =>
                "<td>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["catatan_verifikasi"])
                        : $row["catatan_verifikasi"] ?? "-",
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

    private function _calculate_executive_summary(array $laporan): array
    {
        $ticket_groups = [];
        $vm_summary = ["sudah" => 0, "cancel" => 0, "menunggu" => 0, "total" => 0];
        $sla = ["kurang_7" => 0, "lewat_7" => 0, "lewat_14" => 0];
        $now = new DateTime();

        foreach ($laporan as $row) {
            $t_no = $row["no_tiket_iris"] ?? ($row["no_tiket"] ?? "UNKNOWN");
            $status = $row["status_eksekusi"];

            if (!isset($ticket_groups[$t_no])) {
                $ticket_groups[$t_no] = [];
            }
            $ticket_groups[$t_no][] = $status;

            if (in_array($status, ["Telah Dieksekusi", "Selesai Verified"])) {
                $vm_summary["sudah"]++;
            } elseif ($status === "Cancel by User" || $status === "Ditolak") {
                $vm_summary["cancel"]++;
            } else {
                $vm_summary["menunggu"]++;
            }
            $vm_summary["total"]++;

            if ($status === "Menunggu Eksekusi") {
                $start_date = new DateTime($row["created_at"]);
                $end_date = clone $now;
                $diff = $start_date->diff($end_date)->days;

                if ($diff <= 7) {
                    $sla["kurang_7"]++;
                } elseif ($diff > 7 && $diff <= 14) {
                    $sla["lewat_7"]++;
                } else {
                    $sla["lewat_14"]++;
                }
            }
        }

        $ticket_summary = ["done" => 0, "pending" => 0, "total" => 0];
        foreach ($ticket_groups as $t_no => $statuses) {
            $is_done = true;
            foreach ($statuses as $st) {
                if ($st === "Menunggu Eksekusi") {
                    $is_done = false;
                    break;
                }
            }
            if ($is_done) {
                $ticket_summary["done"]++;
            } else {
                $ticket_summary["pending"]++;
            }
            $ticket_summary["total"]++;
        }

        return ["ticket" => $ticket_summary, "vm" => $vm_summary, "sla" => $sla];
    }

    private function _aggregate_streaming_summary(
        array &$summary,
        array &$ticket_groups,
        array $row,
    ): void {
        $t_no = $row["no_tiket"] ?? ($row["no_tiket_iris"] ?? "UNKNOWN");
        $status = $row["status_eksekusi"];

        if (!isset($ticket_groups[$t_no])) {
            $ticket_groups[$t_no] = [];
        }
        $ticket_groups[$t_no][] = $status;

        if (in_array($status, ["Telah Dieksekusi", "Selesai Verified"])) {
            $summary["vm"]["sudah"]++;
        } elseif ($status === "Cancel by User" || $status === "Ditolak") {
            $summary["vm"]["cancel"]++;
        } else {
            $summary["vm"]["menunggu"]++;
        }
        $summary["vm"]["total"]++;
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
        $raw_cols = $this->input->post("selected_cols");
        $selected_cols =
            !empty($raw_cols) && is_array($raw_cols)
                ? $raw_cols
                : [
                    "no",
                    "nama_server",
                    "start_restart",
                    "finish_restart",
                    "no_tiket",
                    "root_cause",
                ];

        if ($filter_type == "range" && (empty($start_date) || empty($end_date))) {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => "error",
                    "html_preview" =>
                        '<div class="alert alert-danger text-center"><i class="fa fa-warning"></i> Pilih rentang tanggal terlebih dahulu.</div>',
                    "csrf_hash" => $this->security->get_csrf_hash(),
                ]),
            );
            return;
        }

        $export_query =
            $filter_type == "range"
                ? $this->vm_restart_model->get_data_export_query($start_date, $end_date)
                : $this->vm_restart_model->get_data_export_query();
        $laporan_full = $export_query->result_array();

        if (empty($laporan_full)) {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => "empty",
                    "html_preview" =>
                        '<div class="alert alert-warning text-center" style="margin-top:20px;"><i class="fa fa-info-circle fa-2x"></i><br>Tidak ada data eksekusi pada filter tersebut.</div>',
                    "csrf_hash" => $this->security->get_csrf_hash(),
                ]),
            );
            return;
        }

        $summary_data = $this->_calculate_executive_summary($laporan_full);

        $html =
            '<div style="margin-bottom:15px; background:#f8fafc; padding:12px; border:1px solid #e2e8f0; border-radius:4px;">';
        $html .=
            '<strong style="color:#1e293b; display:block; margin-bottom:8px;"><i class="fa fa-bar-chart"></i> Live Executive Summary Preview</strong>';
        $html .=
            '<table class="table table-condensed table-bordered" style="font-size:11px; margin-bottom:0; background:#fff;">';
        $html .=
            '<tr style="background:#f1f5f9;"><th class="text-center">METRIK UTAMA</th><th class="text-center text-success">SELESAI / DONE</th><th class="text-center text-danger">PARSIAL / PENDING</th><th class="text-center font-bold">TOTAL REQ</th></tr>';
        $html .=
            '<tr><td><strong>Jumlah Tiket IRIS (Kolektif)</strong></td><td class="text-center font-bold text-success">' .
            $summary_data["ticket"]["done"] .
            ' Tiket</td><td class="text-center font-bold text-danger">' .
            $summary_data["ticket"]["pending"] .
            ' Tiket</td><td class="text-center font-bold">' .
            $summary_data["ticket"]["total"] .
            " Tiket</td></tr>";
        $html .= "</table></div>";

        $headers = $this->_get_headers_title($selected_cols);

        $html .=
            '<div style="flex-grow: 1; border: 1px solid #E2E8F0; border-radius: 4px; overflow: hidden; background: #fff; padding: 10px;">';
        $html .=
            '<table id="previewDataTable" class="table table-bordered table-striped" style="font-size: 11px; white-space: nowrap; width:100%;">';
        $html .= '<thead style="background-color: #34495E; color: white;"><tr>';
        foreach ($headers as $head) {
            $html .= '<th class="text-center">' . $head . "</th>";
        }
        $html .= "</tr></thead><tbody>";

        $no = 1;
        $total_data = count($laporan_full);
        foreach ($laporan_full as $row) {
            if ($no <= 100) {
                $html .= "<tr>" . $this->_build_dynamic_row($selected_cols, $no, $row) . "</tr>";
            }
            $no++;
        }
        $html .= "</tbody></table></div>";

        if ($total_data > 100) {
            $html .=
                '<div class="alert alert-info text-center" style="padding:8px; margin-top: 10px;"><i>Tabel rincian dipotong 100 baris. Download Excel untuk melihat seluruh <b>' .
                number_format($total_data) .
                "</b> baris data.</i></div>";
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

        $filter_type =
            $this->input->post("filter_type", true) ?: $this->input->get("filter_type", true);
        $start_date =
            $this->input->post("start_date", true) ?: $this->input->get("start_date", true);
        $end_date = $this->input->post("end_date", true) ?: $this->input->get("end_date", true);
        $raw_cols =
            $this->input->post("export_columns", true) ?? $this->input->get("export_columns", true);

        $selected_cols = !empty($raw_cols)
            ? explode(",", $raw_cols)
            : ["no", "nama_server", "start_restart", "finish_restart", "no_tiket", "root_cause"];

        $export_query =
            $filter_type == "range"
                ? $this->vm_restart_model->get_data_export_query($start_date, $end_date)
                : $this->vm_restart_model->get_data_export_query();

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
            "ticket" => ["done" => 0, "pending" => 0, "total" => 0],
            "vm" => ["sudah" => 0, "cancel" => 0, "menunggu" => 0, "total" => 0],
        ];
        $ticket_groups = [];
        $no = 1;

        while ($row = $export_query->unbuffered_row("array")) {
            $this->_aggregate_streaming_summary($summary, $ticket_groups, $row);
            $html_row =
                "<tr>" . $this->_build_dynamic_row($selected_cols, $no++, $row, true) . "</tr>\n";
            fwrite($temp_fp, $html_row);
        }

        foreach ($ticket_groups as $t_no => $statuses) {
            $is_done = true;
            foreach ($statuses as $st) {
                if ($st === "Menunggu Eksekusi") {
                    $is_done = false;
                    break;
                }
            }
            if ($is_done) {
                $summary["ticket"]["done"]++;
            } else {
                $summary["ticket"]["pending"]++;
            }
            $summary["ticket"]["total"]++;
        }

        $data["filename"] = "Laporan_Restart_Server_" . $filename_date . ".xls";
        $data["summary"] = $summary;
        $data["headers"] = $this->_get_headers_title($selected_cols);
        $data["temp_fp"] = $temp_fp;

        $this->load->view("vm_restart/export_excel_vm_restart", $data);
    }
}
