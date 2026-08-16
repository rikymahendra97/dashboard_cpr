<?php
/**
 * ============================================================================
 * File Name    : Vm_change_resource.php
 * Modul        : VM Change Resource
 * Purpose      : Controller utama untuk modul VM Change Resource
 * Architecture : Backend Hard-Guard RBAC, Row-Level Locking, Streaming Export
 * ============================================================================
 */
if (!defined("BASEPATH")) {
    exit("No direct script access allowed");
}

class Vm_change_resource extends CI_Controller
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

        $this->load->helper("datetime");
        $this->load->library(["csrf", "form_validation"]);
        $this->load->model("vm_change_resource_model", "change_model");
        $this->load->model("user_model");

        $this->db->query("SET time_zone = '+07:00'");
    }

    public function index()
    {
        redirect("vm_change_resource/get_list_changes");
    }

    public function get_list_changes()
    {
        $user_session = $this->session->userdata("user_data");
        $data["id"] = $user_session;
        $data["user_session"] = $this->user_model->get($user_session["id_user"]);
        $data["kpi"] = $this->change_model->get_kpi_summary();

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_change_resource/list_vm_change", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function ajax_get_vm_spec()
    {
        $id_vm = (int) $this->input->post("id_virtual_machine");
        if (!$id_vm) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(json_encode(["status" => false]));
            return;
        }
        $detail = $this->change_model->get_vm_detail_ajax($id_vm);
        $this->output
            ->set_content_type("application/json")
            ->set_output(json_encode(["status" => true, "data" => $detail]));
    }

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
        $no_tiket = $this->input->post("no_tiket", true);
        $id_vm = (int) $this->input->post("id_vm", true);
        $id_change = (int) $this->input->post("id_change", true);

        $is_duplicate = $this->change_model->check_duplicate_change($no_tiket, $id_vm, $id_change);
        $response = ["csrf_hash" => $this->security->get_csrf_hash()];

        if ($is_duplicate) {
            $response["status"] = "duplicate";
            $response["message"] =
                "Data Ditolak: Virtual Machine tersebut sudah didaftarkan pada nomor tiket <strong>" .
                html_escape($no_tiket) .
                "</strong>!";
        } else {
            $response["status"] = "safe";
        }
        $this->output->set_content_type("application/json")->set_output(json_encode($response));
    }

    public function tambah()
    {
        $user_session = $this->session->userdata("user_data");
        $data["id"] = $user_session;
        $data["user_session"] = $this->user_model->get($user_session["id_user"]);
        $duplicate_id = (int) $this->input->get("duplicate_from", true);

        if ($duplicate_id > 0) {
            $data["duplicate_data"] = $this->change_model->get_change_detail($duplicate_id);
            if (!empty($data["duplicate_data"])) {
                $no_tiket = $data["duplicate_data"]["no_tiket_eksternal"] ?? "";
                $data["title"] = "Duplikat Request - " . html_escape($no_tiket);
            }
        }
        $data["list_vm"] = $this->change_model->get_active_vms();
        $data["master_team"] = $this->change_model->get_master_team();

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_change_resource/form_add_vm_change", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function detail(string $id_change)
    {
        $id_change = (int) $id_change;
        $user_session = $this->session->userdata("user_data");
        $data["id"] = $user_session;
        $data["user_session"] = $this->user_model->get($user_session["id_user"]);
        $data["detail"] = $this->change_model->get_change_detail($id_change);
        $data["disks"] = $this->change_model->get_change_disks($id_change);

        if (empty($data["detail"])) {
            redirect("vm_change_resource");
        }

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_change_resource/detail_vm_change", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function simpan_data()
    {
        $user_session = $this->session->userdata("user_data");
        if ($this->input->post()) {
            $no_tiket = $this->input->post("no_tiket", true);
            $id_vm = (int) $this->input->post("id_vm", true);

            if ($this->change_model->check_duplicate_change($no_tiket, $id_vm, 0)) {
                $this->session->set_flashdata("alerts", [
                    [
                        "error",
                        "Data Ditolak: VM tersebut sudah didaftarkan pada nomor tiket yang sama!",
                    ],
                ]);
                redirect("vm_change_resource/tambah");
                return;
            }

            $insert_id = $this->change_model->simpan_data_awal((int) $user_session["id_user"]);
            if ($insert_id) {
                $this->session->set_flashdata("alerts", [
                    ["success", "Request perubahan resource berhasil dicatat."],
                ]);
                redirect("vm_change_resource/detail/" . $insert_id . "?_=" . time());
            } else {
                $this->session->set_flashdata("alerts", [
                    ["error", "Gagal mencatat request. Integritas data partisi mungkin rusak."],
                ]);
                redirect("vm_change_resource/tambah?_=" . time());
            }
        }
    }

    public function edit(string $id_change)
    {
        $id_change = (int) $id_change;
        $user_session = $this->session->userdata("user_data");

        if (!can_edit_execute($user_session["id_role"])) {
            $this->session->set_flashdata("alerts", [
                ["error", "Akses Ditolak! Anda tidak memiliki wewenang edit data."],
            ]);
            redirect("vm_change_resource");
            return;
        }

        $data["id"] = $user_session;
        $data["user_session"] = $this->user_model->get($user_session["id_user"]);
        $data["detail"] = $this->change_model->get_change_detail($id_change);

        if (empty($data["detail"])) {
            redirect("vm_change_resource");
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
            redirect("vm_change_resource");
            return;
        }

        $data["disks"] = $this->change_model->get_change_disks($id_change);
        $data["list_vm"] = $this->change_model->get_active_vms();
        $data["master_team"] = $this->change_model->get_master_team();

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("vm_change_resource/form_edit_vm_change", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function update_data()
    {
        $user_session = $this->session->userdata("user_data");
        $id_change = (int) $this->input->post("id_change");

        if (!can_edit_execute($user_session["id_role"])) {
            $this->session->set_flashdata("alerts", [
                ["error", "Akses Ditolak! Anda tidak memiliki wewenang edit data."],
            ]);
            redirect("vm_change_resource/detail/" . $id_change);
            return;
        }

        if ($this->input->post()) {
            $no_tiket = $this->input->post("no_tiket", true);
            $id_vm = (int) $this->input->post("id_vm", true);

            if ($this->change_model->check_duplicate_change($no_tiket, $id_vm, $id_change)) {
                $this->session->set_flashdata("alerts", [
                    [
                        "error",
                        "Data Ditolak: VM tersebut sudah terdaftar pada nomor tiket yang sama!",
                    ],
                ]);
                redirect("vm_change_resource/edit/" . $id_change);
                return;
            }

            $status = $this->change_model->update_data_awal($id_change);
            if ($status) {
                $this->session->set_flashdata("alerts", [["success", "Data berhasil diperbarui."]]);
            } else {
                $this->session->set_flashdata("alerts", [["error", "Gagal memperbarui data."]]);
            }
        }
        redirect("vm_change_resource/detail/" . $id_change . "?_=" . time());
    }

    public function hapus()
    {
        $id_change = (int) $this->input->post("id_change", true);
        $user_session = $this->session->userdata("user_data");

        if (!$id_change) {
            $this->session->set_flashdata("alerts", [["error", "ID Tiket tidak valid."]]);
            redirect("vm_change_resource");
            return;
        }

        if (can_verify_delete($user_session["id_role"])) {
            $status = $this->change_model->hapus_data($id_change);
            if ($status) {
                $this->session->set_flashdata("alerts", [
                    ["success", "Data log dihapus permanen."],
                ]);
            } else {
                $this->session->set_flashdata("alerts", [["error", "Gagal menghapus data."]]);
            }
        } else {
            $this->session->set_flashdata("alerts", [
                [
                    "error",
                    "Akses Ditolak! Privilege Admin/Verifikator diperlukan untuk menghapus data.",
                ],
            ]);
        }
        redirect("vm_change_resource?_=" . time());
    }

    public function ajax_execute_workflow()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $user_session = $this->session->userdata("user_data");
        $id_change = (int) $this->input->post("id_change", true);
        $action_type = $this->input->post("action_type", true);

        if (empty($id_change) || empty($action_type)) {
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
        // ROW-LEVEL LOCKING & DATABASE TRANSACTION
        // Melindungi data dari bentrokan (Race Condition) saat Verify/Execute
        // ====================================================================
        $this->db->trans_start();

        // 1. Ambil data dengan For Update Lock
        $dt_vm = $this->change_model->get_change_detail_for_update($id_change);

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
            $target_cpu = (int) $this->input->post("target_cpu");
            $target_ram_gb = (float) $this->input->post("target_ram_gb");
            $tanggal_eksekusi = $this->input->post("tanggal_eksekusi", true);

            $payload_update = [
                "status_eksekusi" => "Telah Dieksekusi",
                "id_executor" => $user_session["id_user"],
                "tanggal_eksekusi" =>
                    normalize_mysql_datetime($tanggal_eksekusi) ?? date("Y-m-d H:i:s"),
                "catatan_eksekusi" => $this->input->post("catatan_eksekusi", true),
                "target_cpu_count" => $target_cpu,
                "target_memory_mb" => (int) ($target_ram_gb * 1024),
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

        $this->change_model->update_workflow_status($id_change, $payload_update);

        $force_close_incident = $this->input->post("force_close_incident", true);
        $resolve_incident_id = $this->input->post("resolve_incident_id", true);

        if ($action_type === "execute" && !empty($resolve_incident_id)) {
            $this->db
                ->where("id_change", $id_change)
                ->update("trx_vm_change_resource", ["id_incident" => (int) $resolve_incident_id]);

            if ($force_close_incident === "1") {
                $this->load->model("Vm_incident_model");
                $payload_incident = [
                    "status_insiden" => "Done/Close",
                    "resolved_at" => $payload_update["tanggal_eksekusi"],
                    "catatan_resolusi" =>
                        "Telah diselesaikan otomatis via Modul Change Resource. Catatan teknisi: " .
                        $payload_update["catatan_eksekusi"],
                ];
                $this->Vm_incident_model->update_incident_workflow(
                    (int) $resolve_incident_id,
                    $payload_incident,
                );
            }
        }

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
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => false,
                    "message" => "Transaksi basis data gagal. Sistem di-Rollback otomatis.",
                ]),
            );
        }
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
            $this->output->set_content_type("application/json")->set_output(
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
            $this->output->set_content_type("application/json")->set_output(
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
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => false,
                    "message" => "Gagal menyimpan data ke database server.",
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
        $user_session = $this->session->userdata("user_data");
        $id_change = (int) $this->input->post("id_change", true);
        $kendala = $this->input->post("kendala", true);

        if (empty($id_change) || empty($kendala)) {
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode(["status" => false, "message" => "Catatan tidak boleh kosong."]),
                );
            return;
        }
        $username = isset($user_session["nama_lengkap"]) ? $user_session["nama_lengkap"] : "System";

        $process = $this->change_model->update_kendala($id_change, $kendala, $username);

        if ($process) {
            $this->session->set_flashdata("alerts", [["success", "Catatan berhasil diperbarui."]]);
            $this->output
                ->set_content_type("application/json")
                ->set_output(
                    json_encode(["status" => true, "message" => "Catatan berhasil diperbarui."]),
                );
        } else {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => false,
                    "message" => "Gagal memperbarui catatan di database.",
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

        $list = $this->change_model->get_datatables();
        $data = [];
        $no = $_POST["start"];
        $now = new DateTime();

        foreach ($list as $row) {
            $no++;
            $tbody = [];
            $tbody[] = '<div class="text-center">' . $no . "</div>";

            $status = trim($row["status_eksekusi"] ?? "");
            $is_closed = $status === "Selesai Verified" || $status === "Cancel by User";

            $tiket_html = "";
            if (!empty($row["link_tiket_eksternal"])) {
                $tiket_html .=
                    '<a href="' .
                    html_escape($row["link_tiket_eksternal"]) .
                    '" target="_blank" class="text-primary font-bold" title="Buka Tiket Eksternal"><u>' .
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
                $start_date = new DateTime($row["created_at"]);
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

            $nama_vm = html_escape($row["snapshot_vm_name"]);
            $ip_vm = html_escape($row["snapshot_ip_address"]);
            $vm_html = "<div style='line-height:1.4;'>
                            <strong class='text-primary'>{$nama_vm}</strong>
                            <i class='fa fa-copy inline-copy-trigger' data-text='{$nama_vm}' title='Salin Nama VM' style='color:#cbd5e1; cursor:pointer; font-size:12px; margin-left:3px;'></i><br>
                            <small class='text-muted'>{$ip_vm}</small>
                            <i class='fa fa-copy inline-copy-trigger' data-text='{$ip_vm}' title='Salin IP' style='color:#cbd5e1; cursor:pointer; font-size:11px; margin-left:3px;'></i>
                        </div>";
            $tbody[] = $vm_html;

            if ($row["jenis_perubahan"] == "Upgrade") {
                $jenis =
                    '<span class="label label-success"><i class="fa fa-arrow-up"></i> Upgrade</span>';
            } elseif ($row["jenis_perubahan"] == "Downgrade") {
                $jenis =
                    '<span class="label label-warning"><i class="fa fa-arrow-down"></i> Downgrade</span>';
            } else {
                $jenis =
                    '<span class="label label-info"><i class="fa fa-exchange"></i> Mixed</span>';
            }
            $tbody[] = '<div class="text-center">' . $jenis . "</div>";

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

            $tbody[] = !empty($row["nama_executor"])
                ? "<strong>" . html_escape($row["nama_executor"]) . "</strong>"
                : '<span class="text-muted">-</span>';

            $catatan = $row["catatan_eksekusi"];
            $deskripsi = isset($row["keterangan_request_asli"])
                ? $row["keterangan_request_asli"]
                : "";
            $catatan_html = "";
            $isi_konten = "";

            $raw_catatan = html_escape($catatan);
            $icon_edit = $can_edit_execute
                ? "<i class='fa fa-pencil btn-kendala' data-id='{$row["id_change"]}' data-notes='{$raw_catatan}' title='Update Catatan' style='color:#3498DB; cursor:pointer; font-size:13px; margin-left:5px;'></i>"
                : "";

            if ($status == "Menunggu Eksekusi") {
                if (!empty($catatan)) {
                    $isi_konten =
                        "<div><strong style='color:#b18c00;'><i class='fa fa-info-circle'></i> Info/Kendala Terkini:</strong> $icon_edit </div><div style='margin-top:4px;'>" .
                        nl2br(html_escape($catatan)) .
                        "</div>";
                } elseif (!empty($deskripsi)) {
                    $isi_konten =
                        "<div><strong class='text-muted'><i class='fa fa-file-text-o'></i> Keterangan Request:</strong> $icon_edit </div><div style='margin-top:4px; font-style:italic;'>" .
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

            $aksi =
                '<div class="action-btn" style="display: flex; justify-content: center; align-items: center; gap: 8px;">';
            $aksi .=
                '<a href="' .
                site_url("vm_change_resource/detail/" . $row["id_change"]) .
                '" class="btn btn-info btn-xs" title="Detail" style="margin:0;"><i class="fa fa-search"></i></a> ';

            if ($is_closed) {
                $aksi .=
                    '<button type="button" class="btn btn-default btn-xs btn-locked" style="color:#aaa; border-color:#e5e5e5; background:#f9f9f9; margin:0;" title="Akses Terkunci (Closed)"><i class="fa fa-lock"></i></button> ';
            } else {
                $aksi .=
                    '<a href="' .
                    site_url("vm_change_resource/tambah?duplicate_from=" . $row["id_change"]) .
                    '" class="btn btn-default btn-xs" style="color:#2A3F54; border-color:#2A3F54; margin:0;" title="Duplikat Request"><i class="fa fa-copy"></i></a> ';
            }

            if ($can_edit_execute) {
                if ($is_closed) {
                    $aksi .=
                        '<button type="button" class="btn btn-default btn-xs btn-locked" style="color:#aaa; border-color:#e5e5e5; background:#f9f9f9; margin:0;" title="Data Terkunci"><i class="fa fa-lock"></i></button> ';
                } else {
                    $aksi .=
                        '<a href="' .
                        site_url("vm_change_resource/edit/" . $row["id_change"]) .
                        '" class="btn btn-default btn-xs" style="color:#d58512; border-color:#d58512; margin:0;" title="Edit Data"><i class="fa fa-edit"></i></a> ';
                }
            }

            if ($can_verify_delete) {
                $aksi .=
                    '<button type="button" class="btn btn-default btn-xs btn_del" data-id="' .
                    $row["id_change"] .
                    '" style="color:#ac2925; border-color:#ac2925; margin:0;" title="Hapus Permanen"><i class="fa fa-trash-o"></i></button>';
            }
            $aksi .= "</div>";

            $tbody[] = $aksi;
            $data[] = $tbody;
        }

        $output = [
            "draw" => $_POST["draw"],
            "recordsTotal" => $this->change_model->count_all(),
            "recordsFiltered" => $this->change_model->count_filtered(),
            "data" => $data,
        ];

        $this->output->set_content_type("application/json")->set_output(json_encode($output));
    }

    // ========================================================================
    // SECTION 6: ENTERPRISE EXPORT ENGINE & LIVE PREVIEW (AJAX) SPA
    // ========================================================================
    private function _get_headers_title(array $cols_array): array
    {
        $headers = [];
        $map_title = [
            "no" => "No",
            "nama_server" => "Nama Server",
            "tanggal" => "Tanggal Eksekusi",
            "tipe_request" => "Tipe Request",
            "delta_spec" => "Penambahan / Pengurangan Spesifikasi",
            "end_spec" => "Spesifikasi Akhir",
            "no_tiket" => "Request by IRIS",
            "status" => "Status Akhir",
            "implementer" => "Implementer",
            "catatan" => "Catatan Teknis",
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
        $t_no = $row["no_tiket_eksternal"];
        $status = $row["status_eksekusi"];

        if (in_array($row["id_change"], $processed_tickets)) {
            return;
        }
        $processed_tickets[] = $row["id_change"];

        if (!isset($summary["ticket_status"][$t_no])) {
            $summary["ticket_status"][$t_no] = [];
        }
        $summary["ticket_status"][$t_no][] = $status;

        if (in_array($status, ["Telah Dieksekusi", "Selesai Verified"])) {
            $summary["vm"]["sudah"]++;
            $diff_cpu = $row["target_cpu_count"] - $row["current_cpu_count"];
            $diff_ram = ($row["target_memory_mb"] - $row["current_memory_mb"]) / 1024;

            if ($diff_cpu > 0) {
                $summary["res"]["cpu_plus"] += $diff_cpu;
            }
            if ($diff_cpu < 0) {
                $summary["res"]["cpu_minus"] += abs($diff_cpu);
            }
            $summary["res"]["cpu_net"] += $diff_cpu;

            if ($diff_ram > 0) {
                $summary["res"]["ram_plus"] += $diff_ram;
            }
            if ($diff_ram < 0) {
                $summary["res"]["ram_minus"] += abs($diff_ram);
            }
            $summary["res"]["ram_net"] += $diff_ram;

            if (!empty($row["disks_json"])) {
                $disks = json_decode($row["disks_json"], true);
                if (is_array($disks)) {
                    foreach ($disks as $d) {
                        if ($d["additional_gb"] > 0) {
                            $summary["res"]["hdd_plus"] += $d["additional_gb"];
                        }
                        if ($d["additional_gb"] < 0) {
                            $summary["res"]["hdd_minus"] += abs($d["additional_gb"]);
                        }
                        $summary["res"]["hdd_net"] += $d["additional_gb"];
                    }
                }
            }
        } elseif ($status === "Cancel by User") {
            $summary["vm"]["cancel"]++;
        } else {
            $summary["vm"]["menunggu"]++;
        }
        $summary["vm"]["total"]++;
    }

    private function _build_dynamic_row(
        array $cols_array,
        int $no,
        array $row,
        bool $is_excel = false,
    ): string {
        $is_cancel = $row["status_eksekusi"] == "Cancel by User";
        $c_ram = $row["current_memory_mb"] / 1024;
        $t_ram = $row["target_memory_mb"] / 1024;
        $c_cpu = $row["current_cpu_count"];
        $t_cpu = $row["target_cpu_count"];

        if ($is_cancel) {
            $delta_ram = 0;
            $delta_cpu = 0;
            $end_ram = 0;
            $end_cpu = 0;
        } else {
            $diff_ram = $t_ram - $c_ram;
            $diff_cpu = $t_cpu - $c_cpu;
            $delta_ram = $row["jenis_perubahan"] == "Mixed" ? $diff_ram : abs($diff_ram);
            $delta_cpu = $row["jenis_perubahan"] == "Mixed" ? $diff_cpu : abs($diff_cpu);
            $end_ram = $delta_ram == 0 ? 0 : $t_ram;
            $end_cpu = $delta_cpu == 0 ? 0 : $t_cpu;
        }

        $sum_d_hdd = 0;
        $sum_e_hdd = 0;
        $str_d_hdd = "";
        $str_e_hdd = "";

        if (!$is_cancel && !empty($row["disks_json"])) {
            $disks = json_decode($row["disks_json"], true);
            if (is_array($disks)) {
                foreach ($disks as $d) {
                    $val_delta =
                        $row["jenis_perubahan"] == "Mixed"
                            ? $d["additional_gb"]
                            : abs($d["additional_gb"]);
                    $sum_d_hdd += $d["additional_gb"];
                    $sum_e_hdd += $d["end_state_gb"];

                    $raw_drive = trim($d["nama_drive"]);
                    if (preg_match('/^[a-zA-Z]$/', $raw_drive)) {
                        $clean_drive = strtoupper($raw_drive) . ":\\";
                    } elseif (preg_match('/^[a-zA-Z]:$/', $raw_drive)) {
                        $clean_drive = strtoupper($raw_drive) . "\\";
                    } else {
                        $clean_drive =
                            strpos($raw_drive, "/") === 0
                                ? strtolower($raw_drive)
                                : strtoupper($raw_drive);
                    }

                    $fmt_delta = number_format($val_delta, 0, "", "");
                    $fmt_end = number_format($d["end_state_gb"], 0, "", "");

                    $str_d_hdd .=
                        "<br style='mso-data-placement:same-cell;'>" .
                        html_escape($clean_drive) .
                        " " .
                        $fmt_delta .
                        " GB";
                    $str_e_hdd .=
                        "<br style='mso-data-placement:same-cell;'>" .
                        html_escape($clean_drive) .
                        " " .
                        $fmt_end .
                        " GB";
                }
            }
        } elseif ($is_cancel) {
            $str_d_hdd = "<br style='mso-data-placement:same-cell;'><i>(Dibatalkan)</i>";
            $str_e_hdd = "<br style='mso-data-placement:same-cell;'><i>(Tetap)</i>";
        }

        $sum_d_hdd = $row["jenis_perubahan"] == "Mixed" ? $sum_d_hdd : abs($sum_d_hdd);
        if ($sum_d_hdd == 0) {
            $sum_e_hdd = 0;
        }

        $fmt_sum_d = number_format($sum_d_hdd, 0, "", "");
        $fmt_sum_e = number_format($sum_e_hdd, 0, "", "");

        $col_delta =
            "RAM {$delta_ram} GB / CPU {$delta_cpu} Core / HDD {$fmt_sum_d} GB" . $str_d_hdd;
        $col_end = "RAM {$end_ram} GB / CPU {$end_cpu} Core / HDD {$fmt_sum_e} GB" . $str_e_hdd;

        $html = "";
        $map_data = [
            "no" => '<td align="center">' . $no . "</td>",
            "nama_server" =>
                "<td>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["snapshot_vm_name"])
                        : $row["snapshot_vm_name"],
                ) .
                "</td>",
            "tanggal" =>
                '<td align="center" ' .
                ($is_excel ? 'class="str"' : "") .
                ">" .
                (!empty($row["tanggal_eksekusi"])
                    ? date("d-M-Y", strtotime($row["tanggal_eksekusi"]))
                    : "-") .
                "</td>",
            "tipe_request" =>
                '<td align="center">' . html_escape($row["jenis_perubahan"]) . "</td>",
            "delta_spec" =>
                '<td style="text-align: left; vertical-align: top;">' . $col_delta . "</td>",
            "end_spec" =>
                '<td style="text-align: left; vertical-align: top;">' . $col_end . "</td>",
            "no_tiket" =>
                '<td align="center" ' .
                ($is_excel ? 'style="mso-number-format:\@;" class="str font-bold"' : "") .
                "><strong>" .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["no_tiket_eksternal"])
                        : $row["no_tiket_eksternal"],
                ) .
                "</strong></td>",
            "status" => '<td align="center">' . html_escape($row["status_eksekusi"]) . "</td>",
            "implementer" =>
                '<td align="center">' .
                html_escape(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["nama_executor"] ?? "-")
                        : $row["nama_executor"] ?? "-",
                ) .
                "</td>",
            "catatan" =>
                "<td>" .
                strip_tags(
                    $is_excel
                        ? $this->_sanitize_excel_formula($row["catatan_eksekusi"] ?? "")
                        : $row["catatan_eksekusi"] ?? "",
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
                    "tanggal",
                    "tipe_request",
                    "delta_spec",
                    "end_spec",
                    "no_tiket",
                ];

        $export_query =
            $filter_type == "range"
                ? $this->change_model->get_data_export_query($start_date, $end_date)
                : $this->change_model->get_data_export_query();

        $laporan_full = $export_query->result_array();

        if (empty($laporan_full)) {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => "empty",
                    "html_preview" =>
                        '<div class="alert alert-danger text-center"><i class="fa fa-warning"></i> Tidak ada data tiket eksekusi pada filter tersebut.</div>',
                    "csrf_hash" => $this->security->get_csrf_hash(),
                ]),
            );
            return;
        }

        $summary = [
            "ticket" => ["done" => 0, "pending" => 0, "total" => 0],
            "vm" => ["sudah" => 0, "cancel" => 0, "menunggu" => 0, "total" => 0],
            "res" => [
                "cpu_plus" => 0,
                "cpu_minus" => 0,
                "cpu_net" => 0,
                "ram_plus" => 0,
                "ram_minus" => 0,
                "ram_net" => 0,
                "hdd_plus" => 0,
                "hdd_minus" => 0,
                "hdd_net" => 0,
            ],
            "ticket_status" => [],
        ];
        $processed_tickets = [];
        $html_rows = "";
        $no = 1;

        foreach ($laporan_full as $row) {
            $this->_calculate_executive_summary($summary, $processed_tickets, $row);

            if ($no <= 100) {
                $html_rows .=
                    "<tr>" . $this->_build_dynamic_row($selected_cols, $no, $row, false) . "</tr>";
            }
            $no++;
        }

        foreach ($summary["ticket_status"] as $t_no => $statuses) {
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

        $headers = $this->_get_headers_title($selected_cols);

        $html =
            '<div style="margin-bottom:15px; background:#F1F5F9; padding:12px; border:1px solid #E2E8F0; border-radius:6px; flex-shrink: 0; display: flex; gap: 10px; flex-wrap: wrap;">';
        $html .=
            '<table class="table table-bordered" style="font-size:11px; margin-bottom:0; background:#fff; flex: 1; min-width: 300px;">';
        $html .=
            '<tr style="background:#E2E8F0;"><th class="text-center" colspan="3">METRIK TIKET & VM TARGET</th></tr>';
        $html .=
            '<tr><td>Tiket Selesai / Pending</td><td class="text-center text-success font-bold">' .
            $summary["ticket"]["done"] .
            '</td><td class="text-center text-danger font-bold">' .
            $summary["ticket"]["pending"] .
            "</td></tr>";
        $html .=
            '<tr><td>VM Dieksekusi / Menunggu</td><td class="text-center text-success font-bold">' .
            $summary["vm"]["sudah"] .
            '</td><td class="text-center text-danger font-bold">' .
            $summary["vm"]["menunggu"] .
            "</td></tr>";
        $html .= "</table>";

        $html .=
            '<table class="table table-bordered" style="font-size:11px; margin-bottom:0; background:#fff; flex: 1.5; min-width: 400px;">';
        $html .=
            '<tr style="background:#E2E8F0;"><th class="text-center">KAPASITAS</th><th class="text-center text-success">PLUS (+)</th><th class="text-center text-danger">MINUS (-)</th><th class="text-center font-bold">NET DELTA</th></tr>';
        $html .=
            '<tr><td>vCPU (Core)</td><td class="text-center text-success">+ ' .
            $summary["res"]["cpu_plus"] .
            '</td><td class="text-center text-danger">- ' .
            $summary["res"]["cpu_minus"] .
            '</td><td class="text-center font-bold">' .
            $summary["res"]["cpu_net"] .
            "</td></tr>";
        $html .=
            '<tr><td>RAM (GB)</td><td class="text-center text-success">+ ' .
            $summary["res"]["ram_plus"] .
            '</td><td class="text-center text-danger">- ' .
            $summary["res"]["ram_minus"] .
            '</td><td class="text-center font-bold">' .
            $summary["res"]["ram_net"] .
            "</td></tr>";
        $html .=
            '<tr><td>Disk (GB)</td><td class="text-center text-success">+ ' .
            number_format($summary["res"]["hdd_plus"], 0, "", "") .
            '</td><td class="text-center text-danger">- ' .
            number_format($summary["res"]["hdd_minus"], 0, "", "") .
            '</td><td class="text-center font-bold">' .
            number_format($summary["res"]["hdd_net"], 0, "", "") .
            "</td></tr>";
        $html .= "</table></div>";

        $html .=
            '<div style="flex-grow: 1; border: 1px solid #E2E8F0; border-radius: 4px; overflow: hidden; background: #fff; padding: 10px;">';
        $html .=
            '<table id="previewDataTable" class="table table-striped table-bordered" style="width:100%; font-size: 11px; white-space: nowrap;">';
        $html .= '<thead style="background-color: #34495E; color: white;"><tr>';

        foreach ($headers as $head) {
            $html .= '<th style="text-align:center; padding:8px;">' . $head . "</th>";
        }
        $html .= "</tr></thead><tbody>" . $html_rows . "</tbody></table></div>";

        if (count($laporan_full) > 100) {
            $html .=
                '<div class="alert alert-info text-center" style="padding:8px; margin-top: 10px;"><i>Tabel rincian dipotong 100 baris. Download Excel untuk melihat seluruh <b>' .
                number_format(count($laporan_full)) .
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
            : [
                "no",
                "nama_server",
                "tanggal",
                "tipe_request",
                "delta_spec",
                "end_spec",
                "no_tiket",
            ];

        $export_query =
            $filter_type == "range"
                ? $this->change_model->get_data_export_query($start_date, $end_date)
                : $this->change_model->get_data_export_query();

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
            "ticket" => ["done" => 0, "pending" => 0, "total" => 0],
            "vm" => ["sudah" => 0, "cancel" => 0, "menunggu" => 0, "total" => 0],
            "res" => [
                "cpu_plus" => 0,
                "cpu_minus" => 0,
                "cpu_net" => 0,
                "ram_plus" => 0,
                "ram_minus" => 0,
                "ram_net" => 0,
                "hdd_plus" => 0,
                "hdd_minus" => 0,
                "hdd_net" => 0,
            ],
            "ticket_status" => [],
        ];
        $processed_tickets = [];
        $no = 1;

        while ($row = $export_query->unbuffered_row("array")) {
            $this->_calculate_executive_summary($summary, $processed_tickets, $row);

            $html_row =
                "<tr>" . $this->_build_dynamic_row($selected_cols, $no, $row, true) . "</tr>\n";
            fwrite($temp_fp, $html_row);
            $no++;
        }

        foreach ($summary["ticket_status"] as $t_no => $statuses) {
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

        $data["filename"] = "Laporan_Change_Resource_VM_" . $filename_date . ".xls";
        $data["summary"] = $summary;
        $data["headers"] = $this->_get_headers_title($selected_cols);
        $data["temp_fp"] = $temp_fp;

        $this->load->view("vm_change_resource/export_excel_change", $data);
    }
}
