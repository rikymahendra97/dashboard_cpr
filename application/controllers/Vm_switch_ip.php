<?php if (!defined("BASEPATH")) {
    exit("No direct script access allowed");
}

/**
 * =============================================================================
 * File Name    : Vm_switch_ip.php
 * Modul        : VM Switch IP
 * Purpose      : Controller utama untuk modul manajemen pertukaran IP (Switch/Swap).
 * Architecture : Backend Hard-Guard RBAC, Row-Level Lock, Unbuffered Streaming Export
 * =============================================================================
 */

class Vm_switch_ip extends CI_Controller
{
    // ========================================================================
    // SECTION 1: CONSTRUCTOR & SECURITY GUARDS
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
        $this->load->model("vm_switch_ip_model", "switch_model");
        $this->load->model("user_model");

        $this->db->query("SET time_zone = '+07:00'");
    }

    public function index()
    {
        redirect("vm_switch_ip/get_list_switches");
    }

    // ========================================================================
    // SECTION 2: VIEW RENDERERS (LIST, ADD, DETAIL, EDIT)
    // ========================================================================
    public function get_list_switches()
    {
        $user_session = $this->session->userdata("user_data");
        $data["id"] = $user_session;
        $data["user_session"] = $this->user_model->get($user_session["id_user"]);

        $data["kpi"] = $this->switch_model->get_kpi_summary();
        $data["list_switches"] = [];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_switch_ip/list_vm_switch", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function tambah()
    {
        $user_session = $this->session->userdata("user_data");
        $data["id"] = $user_session;
        $data["user_session"] = $this->user_model->get($user_session["id_user"]);

        $duplicate_id = $this->input->get("duplicate_from", true);
        $data["duplicate_data"] = null;

        if ($duplicate_id) {
            $data["duplicate_data"] = $this->switch_model->get_switch_detail((int) $duplicate_id);
            if (!empty($data["duplicate_data"])) {
                $no_tiket =
                    $data["duplicate_data"]["no_tiket_eksternal"] ??
                    $data["duplicate_data"]["no_tiket"];
                $data["title"] = "Duplikat Request - " . html_escape($no_tiket);
            }
        }

        $data["list_vm"] = $this->switch_model->get_active_vms();
        $data["master_team"] = $this->switch_model->get_master_team();

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_switch_ip/form_add_vm_switch", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function detail(string $id_switch)
    {
        $id_switch = (int) $id_switch;
        $user_session = $this->session->userdata("user_data");

        $data["id"] = $user_session;
        $data["user_session"] = $this->user_model->get($user_session["id_user"]);
        $data["detail"] = $this->switch_model->get_switch_detail($id_switch);
        $data["vm_details"] = $this->switch_model->get_switch_details_vm($id_switch);

        if (empty($data["detail"])) {
            redirect("vm_switch_ip");
        }

        $data["ip_warnings"] = [];
        if (
            $data["detail"]["status_eksekusi"] === "Menunggu Eksekusi" &&
            !empty($data["vm_details"])
        ) {
            $jenis_switch = $data["detail"]["jenis_switch"];
            $valid_swap_ids = array_column($data["vm_details"], "id_virtual_machine");

            foreach ($data["vm_details"] as $det) {
                $ip_baru = trim($det["ip_baru"]);
                if (empty($ip_baru)) {
                    continue;
                }

                $used_by_vms = $this->switch_model->check_ip_usage($ip_baru);

                foreach ($used_by_vms as $used_vm) {
                    $is_conflict = true;
                    if ($used_vm["id_virtual_machine"] == $det["id_virtual_machine"]) {
                        $is_conflict = false;
                    } elseif (
                        $jenis_switch == "Tukar Silang (Dual VM)" &&
                        in_array($used_vm["id_virtual_machine"], $valid_swap_ids)
                    ) {
                        $is_conflict = false;
                    }

                    if ($is_conflict) {
                        $data[
                            "ip_warnings"
                        ][] = "IP <strong>{$ip_baru}</strong> saat ini terdeteksi masih terkait dengan VM aktif: <strong>{$used_vm["virtual_machine_name"]}</strong>";
                    }
                }
            }
            $data["ip_warnings"] = array_unique($data["ip_warnings"]);
        }

        $data["authorized_verifier_roles"] = [1, 2, 3, 4, 5];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_switch_ip/detail_vm_switch", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function edit(string $id_switch)
    {
        $id_switch = (int) $id_switch;
        $user_session = $this->session->userdata("user_data");

        if (!can_edit_execute($user_session["id_role"])) {
            $this->session->set_flashdata("alerts", [
                ["error", "Akses Ditolak! Anda tidak memiliki wewenang edit data."],
            ]);
            redirect("vm_switch_ip");
            return;
        }

        $data["id"] = $user_session;
        $data["user_session"] = $this->user_model->get($user_session["id_user"]);
        $data["detail"] = $this->switch_model->get_switch_detail($id_switch);
        $data["vm_details"] = $this->switch_model->get_switch_details_vm($id_switch);
        $data["list_vm"] = $this->switch_model->get_active_vms();

        if (empty($data["detail"])) {
            redirect("vm_switch_ip");
        }

        if (
            $data["detail"]["status_eksekusi"] == "Selesai Verified" ||
            $data["detail"]["status_eksekusi"] == "Cancel by User"
        ) {
            $this->session->set_flashdata("alerts", [
                [
                    "warning",
                    "Akses Terkunci: Data tidak dapat diedit karena tiket sudah ditutup permanen.",
                ],
            ]);
            redirect("vm_switch_ip");
            return;
        }

        $data["master_team"] = $this->switch_model->get_master_team();

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_switch_ip/form_edit_vm_switch", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    // ========================================================================
    // SECTION 3: OPERATIONAL WORKFLOW (SIMPAN, EKSEKUSI, VERIFIKASI)
    // ========================================================================
    public function simpan_data()
    {
        $user_session = $this->session->userdata("user_data");

        if ($this->input->post()) {
            $jenis_switch = $this->input->post("jenis_switch", true);
            $this->form_validation->set_rules("no_tiket", "No Tiket", "required|trim");
            $this->form_validation->set_rules(
                "id_team_requestor",
                "Fungsi Requestor",
                "required|numeric",
            );
            $this->form_validation->set_rules("id_vm_1", "Target VM 1", "required|numeric");
            $this->form_validation->set_rules(
                "ip_baru_1",
                "IP Baru 1",
                "required|trim|callback__valid_ip_or_dash",
            );

            if ($jenis_switch == "Tukar Silang (Dual VM)") {
                $this->form_validation->set_rules(
                    "id_vm_2",
                    "Target VM 2",
                    "required|numeric|differs[id_vm_1]",
                );
                $this->form_validation->set_rules(
                    "ip_baru_2",
                    "IP Baru 2",
                    "required|trim|callback__valid_ip_or_dash",
                );
            }

            if ($this->form_validation->run() == false) {
                $error_msg = validation_errors("", "");
                $this->session->set_flashdata("alerts", [
                    ["error", "Validasi Gagal: " . strip_tags($error_msg)],
                ]);
                redirect("vm_switch_ip/tambah");
                return;
            }

            $no_tiket = $this->input->post("no_tiket", true);
            $id_vm_1 = (int) $this->input->post("id_vm_1", true);
            $id_vm_2 = (int) $this->input->post("id_vm_2", true);

            if ($this->switch_model->check_duplicate_switch($no_tiket, $id_vm_1, $id_vm_2, 0)) {
                $this->session->set_flashdata("alerts", [
                    [
                        "error",
                        "Data Ditolak: Virtual Machine tersebut sudah terdaftar pada nomor tiket yang sama!",
                    ],
                ]);
                redirect("vm_switch_ip/tambah");
                return;
            }

            $insert_id = $this->switch_model->simpan_data_awal($user_session["id_user"]);

            if ($insert_id) {
                $this->session->set_flashdata("alerts", [
                    ["success", "Request Switch/Swap IP berhasil dicatat."],
                ]);
                redirect("vm_switch_ip/detail/" . $insert_id . "?_=" . time());
            } else {
                $this->session->set_flashdata("alerts", [["error", "Gagal mencatat request."]]);
                redirect("vm_switch_ip/tambah?_=" . time());
            }
            return;
        }
        redirect("vm_switch_ip?_=" . time());
    }

    public function update_data()
    {
        $user_session = $this->session->userdata("user_data");
        $id_switch = (int) $this->input->post("id_switch");

        if (!can_edit_execute($user_session["id_role"])) {
            $this->session->set_flashdata("alerts", [
                ["error", "Akses Ditolak! Anda tidak memiliki wewenang edit data."],
            ]);
            redirect("vm_switch_ip/detail/" . $id_switch);
            return;
        }

        if ($this->input->post()) {
            $jenis_switch = $this->input->post("jenis_switch", true);
            $this->form_validation->set_rules("no_tiket", "No Tiket", "required|trim");
            $this->form_validation->set_rules(
                "id_team_requestor",
                "Fungsi Requestor",
                "required|numeric",
            );
            $this->form_validation->set_rules("id_vm_1", "Target VM 1", "required|numeric");
            $this->form_validation->set_rules(
                "ip_baru_1",
                "IP Baru 1",
                "required|trim|callback__valid_ip_or_dash",
            );

            if ($jenis_switch == "Tukar Silang (Dual VM)") {
                $this->form_validation->set_rules(
                    "id_vm_2",
                    "Target VM 2",
                    "required|numeric|differs[id_vm_1]",
                );
                $this->form_validation->set_rules(
                    "ip_baru_2",
                    "IP Baru 2",
                    "required|trim|callback__valid_ip_or_dash",
                );
            }

            if ($this->form_validation->run() == false) {
                $error_msg = validation_errors("", "");
                $this->session->set_flashdata("alerts", [
                    ["error", "Validasi Gagal: " . strip_tags($error_msg)],
                ]);
                redirect("vm_switch_ip/edit/" . $id_switch);
                return;
            }

            $no_tiket = $this->input->post("no_tiket", true);
            $id_vm_1 = (int) $this->input->post("id_vm_1", true);
            $id_vm_2 = (int) $this->input->post("id_vm_2", true);

            if (
                $this->switch_model->check_duplicate_switch(
                    $no_tiket,
                    $id_vm_1,
                    $id_vm_2,
                    $id_switch,
                )
            ) {
                $this->session->set_flashdata("alerts", [
                    [
                        "error",
                        "Data Ditolak: Virtual Machine tersebut sudah terdaftar pada nomor tiket yang sama!",
                    ],
                ]);
                redirect("vm_switch_ip/edit/" . $id_switch);
                return;
            }

            $status = $this->switch_model->update_data_awal($id_switch);
            if ($status) {
                $this->session->set_flashdata("alerts", [
                    ["success", "Master Log Switch IP diperbarui."],
                ]);
            } else {
                $this->session->set_flashdata("alerts", [["error", "Gagal memperbarui data."]]);
            }
        }
        redirect("vm_switch_ip/detail/" . $id_switch . "?_=" . time());
    }

    public function hapus()
    {
        $id_switch = (int) $this->input->post("id_switch", true);
        $user_session = $this->session->userdata("user_data");

        if (!$id_switch) {
            $this->session->set_flashdata("alerts", [["error", "ID Tiket tidak valid."]]);
            redirect("vm_switch_ip");
            return;
        }

        if (can_verify_delete($user_session["id_role"])) {
            $status = $this->switch_model->hapus_data($id_switch);
            if ($status) {
                $this->session->set_flashdata("alerts", [["success", "Data dihapus permanen."]]);
            } else {
                $this->session->set_flashdata("alerts", [["error", "Gagal menghapus data."]]);
            }
        } else {
            $this->session->set_flashdata("alerts", [
                ["error", "Akses Ditolak! Privilege Admin diperlukan."],
            ]);
        }
        redirect("vm_switch_ip?_=" . time());
    }

    public function ajax_execute_workflow()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $user_session = $this->session->userdata("user_data");
        // [ENTERPRISE FIX] Normalisasi variabel menjadi $id_switch agar sinkron
        $id_switch = (int) $this->input->post("id_switch", true);
        $action_type = $this->input->post("action_type", true);

        if (empty($id_switch) || empty($action_type)) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode(["status" => false, "message" => "Parameter tidak lengkap."]),
                );
            return;
        }

        // Otorisasi L1 (Eksekusi & Cancel)
        if (
            in_array($action_type, ["cancel", "execute"]) &&
            !can_edit_execute($user_session["id_role"])
        ) {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => false,
                    "message" =>
                        "Akses Ditolak! Anda tidak memiliki otorisasi (L1) untuk mengeksekusi/membatalkan tiket.",
                ]),
            );
            return;
        }

        // Otorisasi L2 (Verifikasi)
        if ($action_type === "verify" && !can_verify_delete($user_session["id_role"])) {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => false,
                    "message" => "Akses ditolak! Wewenang verifikasi (L2) tidak mencukupi.",
                ]),
            );
            return;
        }

        // ====================================================================
        // [ENTERPRISE FIX] ROW-LEVEL LOCKING & DATABASE TRANSACTION
        // Melindungi data dari bentrokan (Race Condition) saat Verify/Execute
        // ====================================================================
        $this->db->trans_start();

        // 1. Ambil data dengan For Update Lock
        $dt_vm = $this->switch_model->get_change_detail_for_update($id_switch);

        if (!$dt_vm) {
            $this->db->trans_rollback();
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => false,
                    "message" => "Data tidak ditemukan atau sedang diubah oleh pihak lain.",
                ]),
            );
            return;
        }

        $payload_update = [];

        if ($action_type === "cancel") {
            // Idempotency check
            if ($dt_vm["status_eksekusi"] === "Cancel by User") {
                $this->db->trans_rollback();
                $this->output->set_content_type("application/json")->set_output(
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
            ];
        } elseif ($action_type === "execute") {
            $tgl_eks = $this->input->post("tanggal_eksekusi", true);
            $payload_update = [
                "status_eksekusi" => "Telah Dieksekusi",
                "id_executor" => $user_session["id_user"],
                "tanggal_eksekusi" => normalize_mysql_datetime($tgl_eks) ?? date("Y-m-d H:i:s"),
                "catatan_eksekusi" => $this->input->post("catatan_eksekusi", true),
            ];
        } elseif ($action_type === "verify") {
            // Validasi Conflict of Interest: Pembuat tiket & Eksekutor tidak boleh Verify
            if (
                $dt_vm["id_executor"] == $user_session["id_user"] ||
                $dt_vm["id_pencatat"] == $user_session["id_user"]
            ) {
                $this->db->trans_rollback();
                $this->output->set_content_type("application/json")->set_output(
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
                "tanggal_verifikasi" => date("Y-m-d H:i:s"),
                "catatan_verifikasi" => $this->input->post("catatan_verifikasi", true),
            ];
        }

        $this->switch_model->update_workflow_status($id_switch, $payload_update);
        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->session->set_flashdata("alerts", [
                ["success", "Status eksekusi dan relasi tiket berhasil diperbarui."],
            ]);
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode(["status" => true, "message" => "Sistem berhasil disinkronisasi."]),
                );
        } else {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode([
                        "status" => false,
                        "message" => "Transaksi basis data gagal. Sistem di-Rollback otomatis.",
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

        $id_switch = (int) $this->input->post("id_switch", true);
        $kendala = $this->input->post("kendala", true);

        if (empty($id_switch) || empty($kendala)) {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => false,
                    "message" => "Catatan kendala tidak boleh kosong.",
                ]),
            );
            return;
        }

        $process = $this->switch_model->update_kendala($id_switch, $kendala);

        $this->output->set_content_type("application/json");
        if ($process) {
            $this->session->set_flashdata("alerts", [
                ["success", "Info/Kendala shift berhasil diperbarui."],
            ]);
            $this->output->set_output(
                json_encode(["status" => true, "message" => "Catatan berhasil diperbarui."]),
            );
        } else {
            $this->output->set_output(
                json_encode([
                    "status" => false,
                    "message" => "Gagal mengupdate catatan ke database.",
                ]),
            );
        }
    }

    // ========================================================================
    // SECTION 4: DATATABLES & API SERVICES
    // ========================================================================
    public function ajax_search_vm()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $search = $this->input->get("q", true);
        $page = (int) $this->input->get("page", true) ?: 1;
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

    public function ajax_check_duplicate()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $no_tiket = $this->input->post("no_tiket", true);
        $id_vm_1 = (int) $this->input->post("id_vm_1", true);
        $id_vm_2 = (int) $this->input->post("id_vm_2", true);
        $id_switch = (int) $this->input->post("id_change", true); // Bawaan form FE

        $is_duplicate = $this->switch_model->check_duplicate_switch(
            $no_tiket,
            $id_vm_1,
            $id_vm_2,
            $id_switch,
        );
        $response = ["csrf_hash" => $this->security->get_csrf_hash()];

        if ($is_duplicate) {
            $response["status"] = "duplicate";
            $response["message"] =
                "Virtual Machine tersebut sudah didaftarkan pada Nomor Tiket <b>" .
                html_escape($no_tiket) .
                "</b>. Harap batalkan tiket sebelumnya atau pilih VM lain.";
        } else {
            $response["status"] = "safe";
        }
        $this->output->set_content_type("application/json")->set_output(json_encode($response));
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
        $user_session = $this->session->userdata("user_data");
        $role = isset($user_session["id_role"]) ? (int) $user_session["id_role"] : 99;
        $can_edit_execute = can_edit_execute($role);
        $can_verify_delete = can_verify_delete($role);

        $list = $this->switch_model->get_datatables();
        $data = [];
        $no = (int) $this->input->post("start");
        $now = new DateTime();

        foreach ($list as $row) {
            $no++;
            $tbody = [];

            $tbody[] = '<div class="text-center">' . $no . "</div>";

            $status = trim($row["status_eksekusi"] ?? "");
            $is_closed =
                $status === "Selesai Verified" ||
                $status === "Cancel by User" ||
                $status === "Ditolak";

            $tiket_html = "";
            if (!empty($row["link_tiket_eksternal"])) {
                $tiket_html .=
                    '<a href="' .
                    html_escape($row["link_tiket_eksternal"]) .
                    '" target="_blank" class="text-primary font-bold" title="Buka Tiket Eksternal" style="text-decoration:underline;"><u>' .
                    html_escape($row["no_tiket_eksternal"]) .
                    '</u> <i class="fa fa-external-link"></i></a>';
            } else {
                $tiket_html .= "<strong>" . html_escape($row["no_tiket_eksternal"]) . "</strong>";
            }
            $tiket_html .=
                ' <i class="fa fa-copy inline-copy-trigger" data-text="' .
                html_escape($row["no_tiket_eksternal"]) .
                '" title="Salin Tiket" style="color:#cbd5e1; cursor:pointer; margin-left:5px;"></i>';

            if ($status === "Cancel by User" || $status === "Ditolak") {
                $sla_badge =
                    '<span class="label label-default" style="font-size:9px; padding:2px 5px;"><i class="fa fa-ban"></i> Cancel</span>';
            } else {
                $start_date = !empty($row["created_at"])
                    ? new DateTime($row["created_at"])
                    : clone $now;
                if ($status === "Telah Dieksekusi" || $status === "Selesai Verified") {
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
            $tbody[] = $tiket_html;

            // 3. Gabungan VM Awal dan Baru
            $details = $this->switch_model->get_switch_details_vm((int) $row["id_switch"]);
            $vm_html = "";

            foreach ($details as $idx => $d) {
                if ($idx > 0) {
                    $vm_html .= '<hr style="margin:6px 0; border-top: 1px dashed #ddd;">';
                }
                $nm_awal = html_escape($d["nama_master_aktual"] ?? "-");
                $ip_awal = html_escape($d["ip_lama"] ?? "-");
                $ip_baru = html_escape($d["ip_baru"] ?? "-");

                $vm_html .= "<div style='line-height:1.4;'>
                                <strong class='text-primary'>{$nm_awal}</strong>
                                <i class='fa fa-copy inline-copy-trigger' data-text='{$nm_awal}' title='Salin Nama VM' style='color:#cbd5e1; cursor:pointer; font-size:12px; margin-left:3px;'></i><br>
                                <small class='text-muted'>{$ip_awal} <i class='fa fa-arrow-right text-success'></i> <strong class='text-success'>{$ip_baru}</strong></small>
                             </div>";
            }
            $tbody[] = $vm_html;

            // 4. Skenario
            if ($row["jenis_switch"] == "Ganti IP (Single VM)") {
                $tbody[] =
                    '<div class="text-center"><span class="label label-primary"><i class="fa fa-desktop"></i> Ganti IP</span></div>';
            } else {
                $tbody[] =
                    '<div class="text-center"><span class="label label-warning"><i class="fa fa-exchange"></i> Tukar Silang</span></div>';
            }

            // 5. Status
            $c =
                $status == "Menunggu Eksekusi"
                    ? "bg-red"
                    : ($status == "Telah Dieksekusi"
                        ? "bg-blue"
                        : ($status == "Selesai Verified"
                            ? "bg-green"
                            : ($status == "Cancel by User"
                                ? "bg-orange"
                                : "bg-black")));
            $s_label = $status == "Telah Dieksekusi" ? "Menunggu Verifikasi" : $status;
            $tbody[] =
                '<div class="text-center"><span class="badge ' .
                $c .
                '" style="font-size:11.5px; padding:5px 8px; letter-spacing:0.3px;">' .
                $s_label .
                "</span></div>";

            // 6. Implementer
            $tbody[] = !empty($row["nama_executor"])
                ? "<strong>" . html_escape($row["nama_executor"]) . "</strong>"
                : '<span class="text-muted">-</span>';

            // 7. Catatan Progressive Disclosure
            $catatan = $row["catatan_eksekusi"];
            $deskripsi = isset($row["deskripsi_permintaan"]) ? $row["deskripsi_permintaan"] : "";
            $catatan_html = "";
            $isi_konten = "";

            $raw_catatan = html_escape($catatan);
            $icon_edit = $can_edit_execute
                ? "<i class='fa fa-pencil btn-kendala' data-id='{$row["id_switch"]}' data-notes='{$raw_catatan}' title='Update Catatan' style='color:#3498DB; cursor:pointer; font-size:13px; margin-left:5px;'></i>"
                : "";

            if ($status == "Menunggu Eksekusi") {
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
            } elseif ($status == "Telah Dieksekusi") {
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
                    $status == "Cancel by User" ? "fa-ban text-danger" : "fa-shield text-success";
                $color_border = $status == "Cancel by User" ? "#e74c3c" : "#2ecc71";
                $bg_color = $status == "Cancel by User" ? "#fadbd8" : "#eafaf1";
                $judul_status = $status == "Cancel by User" ? "Alasan Batal" : "Laporan Akhir";

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
            $tbody[] = $catatan_html;

            // 8. Opsi / Aksi
            $aksi =
                '<div class="action-btn" style="display: flex; justify-content: center; align-items: center; gap: 8px;">';
            $aksi .=
                '<a href="' .
                site_url("vm_switch_ip/detail/" . $row["id_switch"]) .
                '" class="btn btn-info btn-xs" title="Detail" style="margin:0;"><i class="fa fa-search"></i></a> ';

            if ($is_closed) {
                $aksi .=
                    '<button type="button" class="btn btn-default btn-xs btn-locked" style="color:#aaa; border-color:#e5e5e5; background:#f9f9f9; margin:0;" title="Akses Terkunci (Closed)"><i class="fa fa-lock"></i></button> ';
            } else {
                $aksi .=
                    '<a href="' .
                    site_url("vm_switch_ip/tambah?duplicate_from=" . $row["id_switch"]) .
                    '" class="btn btn-default btn-xs" style="color:#2A3F54; border-color:#2A3F54; margin:0;" title="Duplikat Request"><i class="fa fa-copy"></i></a> ';
            }

            if ($can_edit_execute) {
                if ($is_closed) {
                    $aksi .=
                        '<button type="button" class="btn btn-default btn-xs btn-locked" style="color:#aaa; border-color:#e5e5e5; background:#f9f9f9; margin:0;" title="Data Terkunci"><i class="fa fa-lock"></i></button> ';
                } else {
                    $aksi .=
                        '<a href="' .
                        site_url("vm_switch_ip/edit/" . $row["id_switch"]) .
                        '" class="btn btn-default btn-xs" style="color:#d58512; border-color:#d58512; margin:0;" title="Edit Data"><i class="fa fa-edit"></i></a> ';
                }
            }

            if ($can_verify_delete) {
                $aksi .=
                    '<button type="button" class="btn btn-default btn-xs btn_del" data-id="' .
                    $row["id_switch"] .
                    '" style="color:#ac2925; border-color:#ac2925; margin:0;" title="Hapus Permanen"><i class="fa fa-trash-o"></i></button>';
            }
            $aksi .= "</div>";

            $tbody[] = $aksi;
            $data[] = $tbody;
        }

        $output = [
            "draw" => (int) $this->input->post("draw"),
            "recordsTotal" => $this->switch_model->count_all(),
            "recordsFiltered" => $this->switch_model->count_filtered(),
            "data" => $data,
        ];
        $this->output->set_content_type("application/json")->set_output(json_encode($output));
    }

    public function _valid_ip_or_dash(?string $str): bool
    {
        $clean_str = trim($str);
        if ($clean_str === "-") {
            return true;
        }
        if ($this->form_validation->valid_ip($clean_str)) {
            return true;
        }

        $this->form_validation->set_message(
            "_valid_ip_or_dash",
            "Kolom {field} harus berisi IP Address yang sah atau karakter tanda hubung (-).",
        );
        return false;
    }

    // ========================================================================
    // SECTION 6: ENTERPRISE EXPORT ENGINE (Unbuffered Streaming)
    // ========================================================================
    private function _get_headers_title(array $cols_array): array
    {
        $headers = [];
        $map_title = [
            "no" => "No",
            "nama_vms_awal" => "Nama VMs Awal",
            "ip_awal" => "IP Awal",
            "nama_vms_baru" => "Nama VMs Baru",
            "ip_baru" => "IP Baru",
            "no_tiket" => "No Tiket",
            "tanggal" => "Tanggal Eksekusi",
            "keterangan" => "Keterangan",
            "aksi" => "Aksi",
            "status" => "Status Akhir",
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

    private function _calculate_executive_summary(
        array &$summary,
        array &$processed_tickets,
        array $row,
    ): void {
        if (in_array($row["id_switch"], $processed_tickets)) {
            return;
        }
        $processed_tickets[] = $row["id_switch"];

        $summary["total_tiket"]++;

        if ($row["jenis_aksi"] == "Ganti IP (Single VM)") {
            $summary["ganti_ip"]++;
        } else {
            $summary["tukar_silang"]++;
        }

        if (in_array($row["status_akhir"], ["Selesai Verified", "Cancel by User", "Ditolak"])) {
            $summary["done"]++;
        } else {
            $summary["pending"]++;
        }
    }

    private function _build_dynamic_row(
        array $cols_array,
        int $no,
        array $row,
        array $det,
        bool $is_excel = false,
    ): string {
        $html = "";
        $map_data = [
            "no" => '<td class="text-center">' . $no . "</td>",
            "nama_vms_awal" =>
                "<td>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($det["nama_master_aktual"] ?? "-")
                        : $det["nama_master_aktual"] ?? "-",
                ) .
                "</td>",
            "ip_awal" =>
                '<td class="text-center ' .
                ($is_excel ? "str" : "") .
                '" ' .
                ($is_excel ? 'style="mso-number-format:\@;"' : "") .
                ">" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($det["ip_lama"] ?? "-")
                        : $det["ip_lama"] ?? "-",
                ) .
                "</td>",
            "nama_vms_baru" =>
                "<td>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($det["nama_vm_baru"] ?? "-")
                        : $det["nama_vm_baru"] ?? "-",
                ) .
                "</td>",
            "ip_baru" =>
                '<td class="text-center font-bold text-success ' .
                ($is_excel ? "str" : "") .
                '" ' .
                ($is_excel ? 'style="mso-number-format:\@;"' : "") .
                ">" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($det["ip_baru"] ?? "-")
                        : $det["ip_baru"] ?? "-",
                ) .
                "</td>",
            "no_tiket" =>
                "<td><strong " .
                ($is_excel ? 'class="str" style="mso-number-format:\@;"' : "") .
                ">" .
                html_escape(
                    $is_excel ? $this->_sanitize_excel_formula($row["no_tiket"]) : $row["no_tiket"],
                ) .
                "</strong></td>",
            "tanggal" =>
                '<td class="text-center">' .
                (!empty($row["tanggal_eksekusi"])
                    ? date("d-M-Y", strtotime($row["tanggal_eksekusi"]))
                    : "-") .
                "</td>",
            "keterangan" =>
                '<td style="white-space: normal; min-width:200px;">' .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["deskripsi_permintaan"] ?? "-")
                        : $row["deskripsi_permintaan"] ?? "-",
                ) .
                "</td>",
            "aksi" => '<td class="text-center">' . html_escape($row["jenis_aksi"] ?? "-") . "</td>",
            "status" =>
                '<td class="text-center">' . html_escape($row["status_akhir"] ?? "-") . "</td>",
        ];

        foreach ($cols_array as $col) {
            if (isset($map_data[$col])) {
                $html .= $map_data[$col];
            }
        }
        return $html;
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
                    "nama_vms_awal",
                    "ip_awal",
                    "nama_vms_baru",
                    "ip_baru",
                    "no_tiket",
                    "tanggal",
                    "keterangan",
                    "aksi",
                    "status",
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
                ? $this->switch_model->get_data_export_query($start_date, $end_date)
                : $this->switch_model->get_data_export_query();
        $laporan_full = $export_query->result_array();

        if (empty($laporan_full)) {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => "empty",
                    "html_preview" =>
                        '<div class="alert alert-warning text-center" style="margin-top:20px;"><i class="fa fa-info-circle fa-2x"></i><br>Tidak ada data tiket eksekusi pada filter tersebut.</div>',
                    "csrf_hash" => $this->security->get_csrf_hash(),
                ]),
            );
            return;
        }

        $summary = [
            "total_tiket" => 0,
            "ganti_ip" => 0,
            "tukar_silang" => 0,
            "done" => 0,
            "pending" => 0,
        ];
        $processed_tickets = [];
        $html_rows = "";
        $no = 1;
        $total_data = 0;

        foreach ($laporan_full as $row) {
            $this->_calculate_executive_summary($summary, $processed_tickets, $row);

            if (!empty($row["vms_json"])) {
                $detail_vms = json_decode($row["vms_json"], true);
                if (is_array($detail_vms)) {
                    foreach ($detail_vms as $det) {
                        if ($total_data < 100) {
                            $html_rows .=
                                "<tr>" .
                                $this->_build_dynamic_row(
                                    $selected_cols,
                                    $no++,
                                    $row,
                                    $det,
                                    false,
                                ) .
                                "</tr>";
                        } else {
                            $no++;
                        }
                        $total_data++;
                    }
                }
            }
        }

        $headers = $this->_get_headers_title($selected_cols);

        $html =
            '<div style="margin-bottom:15px; background:#F1F5F9; padding:12px; border:1px solid #E2E8F0; border-radius:6px; flex-shrink: 0; display: flex; gap: 10px; flex-wrap: wrap;">';
        $html .=
            '<table class="table table-bordered" style="font-size:11px; margin-bottom:0; background:#fff; flex: 1; min-width: 250px;">';
        $html .=
            '<tr style="background:#E2E8F0;"><th class="text-center" colspan="3">METRIK PENYELESAIAN TIKET</th></tr>';
        $html .=
            '<tr><td>Selesai (Closed)</td><td class="text-center text-success font-bold">' .
            $summary["done"] .
            "</td></tr>";
        $html .=
            '<tr><td>Menunggu (Pending)</td><td class="text-center text-danger font-bold">' .
            $summary["pending"] .
            "</td></tr>";
        $html .=
            '<tr style="background:#f9f9f9;"><td class="font-bold">Total Request</td><td class="text-center font-bold">' .
            $summary["total_tiket"] .
            "</td></tr></table>";

        $html .=
            '<table class="table table-bordered" style="font-size:11px; margin-bottom:0; background:#fff; flex: 1; min-width: 250px;">';
        $html .=
            '<tr style="background:#E2E8F0;"><th class="text-center" colspan="3">SKENARIO EKSEKUSI</th></tr>';
        $html .=
            '<tr><td>Ganti IP (Single VM)</td><td class="text-center text-primary font-bold">' .
            $summary["ganti_ip"] .
            "</td></tr>";
        $html .=
            '<tr><td>Tukar Silang (Dual VM)</td><td class="text-center text-warning font-bold">' .
            $summary["tukar_silang"] .
            "</td></tr></table></div>";

        $html .=
            '<div style="flex-grow: 1; border: 1px solid #E2E8F0; border-radius: 4px; overflow: hidden; background: #fff; padding: 10px;">';
        $html .=
            '<table id="previewDataTable" class="table table-bordered table-striped" style="font-size: 11px; white-space: nowrap; width:100%;">';
        $html .= '<thead style="background-color: #34495E; color: white;"><tr>';
        foreach ($headers as $head) {
            $html .= '<th class="text-center" style="padding:8px;">' . $head . "</th>";
        }
        $html .= "</tr></thead><tbody>" . $html_rows . "</tbody></table></div>";

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

        $filter_type = $this->input->get("filter_type", true);
        $start_date = $this->input->get("start_date", true);
        $end_date = $this->input->get("end_date", true);
        $raw_cols = $this->input->get("export_columns", true);

        $selected_cols = !empty($raw_cols)
            ? explode(",", $raw_cols)
            : [
                "no",
                "nama_vms_awal",
                "ip_awal",
                "nama_vms_baru",
                "ip_baru",
                "no_tiket",
                "tanggal",
                "keterangan",
                "aksi",
                "status",
            ];

        $export_query =
            $filter_type == "range"
                ? $this->switch_model->get_data_export_query($start_date, $end_date)
                : $this->switch_model->get_data_export_query();

        if ($filter_type == "range") {
            $data["periode"] =
                date("d-M-Y", strtotime($start_date)) .
                " s/d " .
                date("d-M-Y", strtotime($end_date));
            $filename_date = $start_date . "_sd_" . $end_date;
        } else {
            $data["periode"] = "Semua Waktu (Keseluruhan Data)";
            $filename_date = "All_Data";
        }

        $temp_fp = fopen("php://temp", "r+");
        $summary = [
            "total_tiket" => 0,
            "ganti_ip" => 0,
            "tukar_silang" => 0,
            "done" => 0,
            "pending" => 0,
        ];
        $processed_tickets = [];
        $no = 1;

        while ($row = $export_query->unbuffered_row("array")) {
            $this->_calculate_executive_summary($summary, $processed_tickets, $row);

            if (!empty($row["vms_json"])) {
                $detail_vms = json_decode($row["vms_json"], true);
                if (is_array($detail_vms)) {
                    foreach ($detail_vms as $det) {
                        $html_row =
                            "<tr>" .
                            $this->_build_dynamic_row($selected_cols, $no++, $row, $det, true) .
                            "</tr>\n";
                        fwrite($temp_fp, $html_row);
                    }
                }
            }
        }

        $data["filename"] = "Laporan_Switch_IP_VM_" . $filename_date . ".xls";
        $data["summary"] = $summary;
        $data["headers"] = $this->_get_headers_title($selected_cols);
        $data["temp_fp"] = $temp_fp;

        $this->load->view("vm_switch_ip/export_excel_switch", $data);
    }
}
