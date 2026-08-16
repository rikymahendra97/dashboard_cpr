<?php
defined("BASEPATH") or exit("No direct script access allowed");

/**
 * ============================================================================
 * File Name    : Provisioning.php
 * Modul        : VM Provisioning
 * Purpose      : Controller utama siklus hidup pembuatan Virtual Machine
 * Architecture : Backend Hard-Guard RBAC, Row-Level Lock Trans, Unbuffered Export
 * ============================================================================
 */
class Provisioning extends CI_Controller
{
    // =========================================================================
    // CONSTANT & CONFIGURATION SECTION
    // =========================================================================
    public const ENV_MAPPING = [
        "Production" => 1,
        "Staging" => 2,
        "Development" => 3,
        "Testing" => 4,
        "UAT" => 5,
    ];

    // =========================================================================
    // CONSTRUCTOR SECTION
    // =========================================================================
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
        $this->load->library(["csrf", "form_validation", "Mobile_Detect"]);
        $this->load->model(["Provisioning_model", "user_model", "Virtual_machine_model"]);

        $this->db->query("SET time_zone = '+07:00'");
    }

    // =========================================================================
    // SECURITY & HELPER SECTION
    // =========================================================================

    /**
     * L2 Authorization Guard (Hard-Guard Backend)
     */
    private function _enforce_l2_guard(string $action_name): void
    {
        $user_session = $this->session->userdata("user_data");
        $role = isset($user_session["id_role"]) ? (int) $user_session["id_role"] : 99;

        if (!can_verify_delete($role)) {
            if ($this->input->is_ajax_request()) {
                $this->output
                    ->set_status_header(403)
                    ->set_content_type("application/json")
                    ->set_output(
                        json_encode([
                            "status" => false,
                            "message" => "Akses Ditolak: Otorisasi L2 dibutuhkan untuk {$action_name}.",
                        ]),
                    );
                exit();
            }
            $this->session->set_flashdata("alerts", [
                [
                    "error",
                    "Akses Ditolak (Security Breach Attempt): Anda tidak memiliki hak akses (L2) untuk tindakan ini.",
                ],
            ]);
            redirect("provisioning");
            exit();
        }
    }

    private function _render_layout(string $view_name, array $data): void
    {
        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view($view_name, $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    private function _build_ticket_payload(): array
    {
        return [
            "no_tiket" => $this->input->post("no_tiket", true),
            "link_tiket" => $this->input->post("link_tiket", true),
            "tanggal_masuk_tiket" => normalize_mysql_datetime(
                $this->input->post("tanggal_masuk_tiket", true),
            ),
            "tanggal_masuk_vcenter" => normalize_mysql_datetime(
                $this->input->post("tanggal_masuk_vcenter", true),
            ),
            "tanggal_keluar_tiket" => normalize_mysql_datetime(
                $this->input->post("tanggal_keluar_tiket", true),
            ),
            "nama_server" => trim($this->input->post("nama_server", true)),
            "hostname" => trim($this->input->post("hostname", true)),
            "kritikalitas" => $this->input->post("kritikalitas", true),
            "environment" => $this->input->post("environment", true),
            "aplikasi" => $this->input->post("aplikasi", true),
            "tipe_request" => $this->input->post("tipe_request", true),
            "os" => $this->input->post("os", true),
            "cpu" => $this->input->post("cpu", true),
            "ram" => $this->input->post("ram", true),
            "disk" => $this->input->post("disk", true),
            "detail_disk" => $this->input->post("detail_disk", true),
            "ip" => $this->input->post("ip", true),
            "datastore" => $this->input->post("datastore", true),
            "source_clone" => $this->input->post("source_clone", true),
            "status_clone_recover" => $this->input->post("status_clone_recover", true),
            "keterangan" => $this->input->post("keterangan", true),
            "divisi_requestor" => $this->input->post("divisi_requestor", true),
            "nama_requestor" => $this->input->post("nama_requestor", true),
            "contact" => $this->input->post("contact", true),
        ];
    }

    // =========================================================================
    // MAIN PAGE SECTION
    // =========================================================================
    public function index()
    {
        redirect("provisioning/get_list_provisioning");
    }

    public function get_list_provisioning()
    {
        $id_session = $this->session->userdata("user_data");
        $data["page_title"] = "Daftar Provisioning VM";
        $data["css_arr"] = ["datatables.css", "provisioning.css"];
        $data["js_arr"] = ["datatables/jquery.dataTables.min.js"];
        $data["id"] = $id_session;
        $data["user_session"] = $this->user_model->get($id_session["id_user"]);
        $data["kpi"] = $this->Provisioning_model->get_kpi_summary();
        $this->_render_layout("vm_provisioning/list_provisioning", $data);
    }

    public function tambah()
    {
        $id_session = $this->session->userdata("user_data");
        $data["id"] = (array) $this->user_model->get($id_session["id_user"]);
        $data["user_session"] = $data["id"];
        $data["page_title"] = "Tambah Tiket Provisioning Baru";

        $grouped_os = [];
        foreach ($this->Provisioning_model->get_master_os() as $os) {
            $grouped_os[$os->os_family][] = $os->os_name;
        }
        $data["list_os"] = $grouped_os;

        $grouped_template = [];
        foreach ($this->Provisioning_model->get_master_template() as $tpl) {
            $grouped_template[$tpl->template_family][] = $tpl->template_name;
        }
        $data["list_template"] = $grouped_template;
        $data["master_team"] = $this->Provisioning_model->get_master_team();

        $this->_render_layout("vm_provisioning/form_add_provisioning", $data);
    }

    public function edit($id_tiket = null)
    {
        $id_tiket = (int) $id_tiket;
        if (!$id_tiket) {
            redirect("provisioning");
        }

        $id_session = $this->session->userdata("user_data");
        $data["id"] = (array) $this->user_model->get($id_session["id_user"]);
        $data["user_session"] = $data["id"];
        $data["row"] = $this->Provisioning_model->get_by_id($id_tiket);

        if (empty($data["row"])) {
            show_404();
        }

        $data["page_title"] =
            "Koreksi Tiket & Binding CMDB - " . html_escape($data["row"]->no_tiket);
        $data["relation_vm"] = null;

        if (!empty($data["row"]->id_virtual_machine)) {
            $data["relation_vm"] = $this->Virtual_machine_model->get(
                $data["row"]->id_virtual_machine,
            );
        }

        $grouped_os = [];
        foreach ($this->Provisioning_model->get_master_os() as $os) {
            $grouped_os[$os->os_family][] = $os->os_name;
        }
        $data["list_os"] = $grouped_os;

        $grouped_template = [];
        foreach ($this->Provisioning_model->get_master_template() as $tpl) {
            $grouped_template[$tpl->template_family][] = $tpl->template_name;
        }
        $data["list_template"] = $grouped_template;
        $data["master_team"] = $this->Provisioning_model->get_master_team();

        $this->_render_layout("vm_provisioning/form_edit_provisioning", $data);
    }

    public function detail($id_tiket = null)
    {
        $id_tiket = (int) $id_tiket;
        if (!$id_tiket) {
            redirect("provisioning");
        }

        $id_session = $this->session->userdata("user_data");
        $data["id"] = (array) $this->user_model->get($id_session["id_user"]);
        $data["user_session"] = $data["id"];
        $data["row"] = $this->Provisioning_model->get_by_id($id_tiket);

        if (empty($data["row"])) {
            show_404();
        }

        $data["page_title"] = "Detail Eksekusi VM - " . html_escape($data["row"]->no_tiket);
        $data["relation_vm"] = null;

        if (!empty($data["row"]->id_virtual_machine)) {
            $data["relation_vm"] = $this->Virtual_machine_model->get(
                $data["row"]->id_virtual_machine,
            );
        }

        $this->_render_layout("vm_provisioning/detail_provisioning", $data);
    }

    public function copy_tiket($id = null)
    {
        $id = (int) $id;
        $id_session = $this->session->userdata("user_data");
        $row = $this->Provisioning_model->get_by_id($id);

        if (!$row) {
            show_404();
        }

        $row->nama_server = "";
        $row->hostname = "";
        $row->ip = "";
        $row->datastore = "";
        $row->tanggal_masuk_vcenter = null;
        $row->tanggal_keluar_tiket = null;
        $row->setup_by = null;
        $row->closed_by = null;
        $row->progres_tiket = "Pending";

        $data["row"] = $row;
        $data["id"] = (array) $this->user_model->get($id_session["id_user"]);
        $data["user_session"] = $data["id"];
        $data["page_title"] = "Duplikat Request Provisioning VM";

        $grouped_os = [];
        foreach ($this->Provisioning_model->get_master_os() as $os) {
            $grouped_os[$os->os_family][] = $os->os_name;
        }
        $data["list_os"] = $grouped_os;

        $grouped_template = [];
        foreach ($this->Provisioning_model->get_master_template() as $tpl) {
            $grouped_template[$tpl->template_family][] = $tpl->template_name;
        }
        $data["list_template"] = $grouped_template;
        $data["master_team"] = $this->Provisioning_model->get_master_team();

        $this->_render_layout("vm_provisioning/form_copy_provisioning", $data);
    }

    // =========================================================================
    // CRUD ACTION & STATE MACHINE SECTION (GUARDED)
    // =========================================================================
    public function simpan_data()
    {
        $username = $this->session->userdata("user_data")["username"] ?? "System";
        $data = $this->_build_ticket_payload();

        // Transaction Guard untuk duplikasi
        $this->db->trans_start();
        $is_duplicate = $this->Provisioning_model->check_duplicate_vm(
            $data["no_tiket"],
            $data["nama_server"],
            $data["hostname"],
        );

        if ($is_duplicate) {
            $this->db->trans_rollback();
            $this->session->set_flashdata("alerts", [
                [
                    "error",
                    "GAGAL DISIMPAN: Server dengan nama <b>{$data["nama_server"]}</b> atau Hostname <b>{$data["hostname"]}</b> sudah terdaftar di No Tiket <b>{$data["no_tiket"]}</b>!",
                ],
            ]);
            redirect("provisioning/tambah");
            return;
        }

        $data["progres_tiket"] = $this->input->post("progres_tiket", true) ?: "Pending";
        $data["id_virtual_machine"] = $this->input->post("id_virtual_machine", true);
        $data["created_by"] = $username;

        $insert_id = $this->Provisioning_model->insert_data($data);
        $this->db->trans_complete();

        if ($this->db->trans_status() && $insert_id) {
            $this->session->set_flashdata("alerts", [
                [
                    "success",
                    "Data Provisioning Berhasil Disimpan. Silakan periksa blueprint berikut ini.",
                ],
            ]);
            redirect("provisioning/detail/" . $insert_id);
        } else {
            $this->session->set_flashdata("alerts", [
                ["error", "Terjadi kesalahan sistem saat menyimpan data!"],
            ]);
            redirect("provisioning/tambah");
        }
    }

    public function update_progress_clone()
    {
        $id_tiket = $this->input->post("id_tiket", true);
        $status_clone = $this->input->post("status_clone_recover", true);

        if ($id_tiket && $status_clone) {
            $this->db
                ->where("id_tiket", $id_tiket)
                ->update("tiket_provisioning", ["status_clone_recover" => $status_clone]);
            $this->session->set_flashdata("alerts", [
                ["success", "Progress Clone berhasil diperbarui."],
            ]);
        } else {
            $this->session->set_flashdata("alerts", [["error", "Gagal update progress."]]);
        }
        redirect("provisioning/detail/" . $id_tiket);
    }

    public function ajax_check_duplicate()
    {
        $no_tiket = trim($this->input->post("no_tiket", true));
        $nama_server = trim($this->input->post("nama_server", true));
        $hostname = trim($this->input->post("hostname", true));
        $id_tiket = (int) $this->input->post("id_tiket", true);

        $is_duplicate = $this->Provisioning_model->check_duplicate_vm(
            $no_tiket,
            $nama_server,
            $hostname,
            $id_tiket,
        );
        $response = ["csrf_hash" => $this->security->get_csrf_hash()];

        if ($is_duplicate) {
            $response["status"] = "duplicate";
            $response["message"] =
                "Server dengan nama <strong>" .
                html_escape($nama_server) .
                "</strong> atau Hostname <strong>" .
                html_escape($hostname) .
                "</strong> sudah ada di dalam No Tiket <strong>" .
                html_escape($no_tiket) .
                "</strong>!";
        } else {
            $response["status"] = "safe";
        }
        $this->output->set_content_type("application/json")->set_output(json_encode($response));
    }

    public function update_data()
    {
        $id = $this->input->post("id_tiket", true);
        $progres_tiket = $this->input->post("progres_tiket", true);
        $id_virtual_machine = $this->input->post("id_virtual_machine", true);
        $nama_server_input = trim($this->input->post("nama_server", true));

        // ====================================================================
        // Mencegah L1 memanipulasi POST menjadi "Done"
        // ====================================================================
        $user_session = $this->session->userdata("user_data");
        $role = isset($user_session["id_role"]) ? (int) $user_session["id_role"] : 99;

        if ($progres_tiket === "Done" && !can_verify_delete($role)) {
            $this->session->set_flashdata("alerts", [
                [
                    "error",
                    "Security Guard: Hanya Verifikator (L2) yang diizinkan merubah status tiket menjadi 'Done'.",
                ],
            ]);
            redirect("provisioning/edit/" . $id);
            return;
        }

        if (
            $progres_tiket === "Done" &&
            (empty($id_virtual_machine) || $id_virtual_machine == "0")
        ) {
            $this->session->set_flashdata("alerts", [
                [
                    "error",
                    'Status tiket tidak bisa "Done" karena Target Virtual Machine (CMDB) belum diikat.',
                ],
            ]);
            redirect("provisioning/edit/" . $id);
            return;
        }

        if ($progres_tiket === "Done" && !empty($id_virtual_machine)) {
            $master_vm = $this->Virtual_machine_model->get($id_virtual_machine);
            if (
                !$master_vm ||
                strtolower(trim($master_vm["virtual_machine_name"])) !==
                    strtolower($nama_server_input)
            ) {
                $this->session->set_flashdata("alerts", [
                    [
                        "error",
                        "Verifikasi Gagal: Nama VM yang dipilih dari CMDB tidak identik dengan nama server pada tiket.",
                    ],
                ]);
                redirect("provisioning/edit/" . $id);
                return;
            }
        }

        $username = $this->session->userdata("user_data")["username"] ?? "System";
        $progres = strtolower(trim($progres_tiket));

        $data = $this->_build_ticket_payload();
        $data["progres_tiket"] = $progres_tiket;
        $data["id_virtual_machine"] = empty($id_virtual_machine) ? null : $id_virtual_machine;
        $data["updated_at"] = date("Y-m-d H:i:s");

        if ($this->input->post("tanggal_masuk_vcenter") && empty($this->input->post("setup_by"))) {
            $data["setup_by"] = $username;
        }

        if ($this->input->post("tanggal_keluar_tiket") || $progres === "done") {
            $data["closed_by"] = $username;
        }

        // Lock & Update
        $this->db->trans_start();
        $row_lock = $this->Provisioning_model->get_by_id_for_update($id); // Pessimistic lock

        if ($row_lock) {
            $this->Provisioning_model->update_data($id, $data);
            if ($progres === "done" && !empty($data["id_virtual_machine"])) {
                $data_vm = [
                    "id_env" => self::ENV_MAPPING[$data["environment"]] ?? null,
                    "no_tiket_iris" => $data["no_tiket"],
                    "guest_os_manual" => $data["os"],
                ];
                $this->Virtual_machine_model->update_vm($data["id_virtual_machine"], $data_vm);
            }
        }
        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->session->set_flashdata("alerts", [
                ["success", "Perubahan Data Tiket Berhasil Disimpan!"],
            ]);
        } else {
            $this->session->set_flashdata("alerts", [
                ["error", "Terjadi kegagalan pada transaksi Database. Perubahan di-Rollback."],
            ]);
        }
        redirect("provisioning/detail/" . $id);
    }

    public function action_state_change()
    {
        // Opsional: Buka comment di bawah jika L1 tidak boleh ganti status (misal ke In Progress)
        // $this->_enforce_l2_guard("State Change");

        $id_tiket = (int) $this->input->post("id_tiket", true);
        $target_state = $this->input->post("target_state", true);
        $username = $this->session->userdata("user_data")["username"] ?? "System";

        if (!$id_tiket || !$target_state) {
            show_404();
        }

        // ====================================================================
        // Mencegah L1 melakukan By-Pass State ke "Done"
        // ====================================================================
        $user_session = $this->session->userdata("user_data");
        $role = isset($user_session["id_role"]) ? (int) $user_session["id_role"] : 99;

        if ($target_state === "Done" && !can_verify_delete($role)) {
            $this->session->set_flashdata("alerts", [
                [
                    "error",
                    "Akses Ditolak: Anda tidak memiliki otorisasi L2 untuk melakukan penutupan tiket (Done).",
                ],
            ]);
            redirect("provisioning/detail/{$id_tiket}");
            return;
        }

        // IMPLEMENTASI PESSIMISTIC LOCKING
        $this->db->trans_start();
        $row = $this->Provisioning_model->get_by_id_for_update($id_tiket);

        if (!$row) {
            $this->db->trans_rollback();
            show_404();
        }

        if ($row->progres_tiket === $target_state) {
            $this->db->trans_rollback();
            $this->session->set_flashdata("alerts", [
                ["error", "Status tiket sudah berada di posisi {$target_state}."],
            ]);
            redirect("provisioning/detail/{$id_tiket}");
            return;
        }

        $update_data = [
            "progres_tiket" => $target_state,
            "updated_at" => date("Y-m-d H:i:s"),
        ];

        if ($target_state === "In Progress" && empty($row->setup_by)) {
            $update_data["setup_by"] = $username;
        }

        if ($target_state === "Waiting Sync") {
            $tgl_masuk_vcenter = $this->input->post("tanggal_masuk_vcenter", true);
            if (!empty($tgl_masuk_vcenter)) {
                $update_data["tanggal_masuk_vcenter"] = normalize_mysql_datetime(
                    $tgl_masuk_vcenter,
                );
            } elseif (empty($row->tanggal_masuk_vcenter)) {
                $update_data["tanggal_masuk_vcenter"] = date("Y-m-d H:i:s");
            }
            $update_data["keterangan"] = null;
            if (strtolower(trim($row->tipe_request ?? "")) === "clone") {
                $update_data["status_clone_recover"] = null;
            }
            $update_data["setup_by"] = $username;
        }

        if ($target_state === "Done") {
            $update_data["closed_by"] = $username;
            if (empty($row->tanggal_keluar_tiket)) {
                $update_data["tanggal_keluar_tiket"] = date("Y-m-d H:i:s");
            }
        }

        $status_recover = $this->input->post("status_clone_recover", true);
        if (!empty($status_recover)) {
            $update_data["status_clone_recover"] = $status_recover;
        }

        $this->Provisioning_model->update_data($id_tiket, $update_data);
        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->session->set_flashdata("alerts", [
                ["success", "Status tiket berhasil diperbarui menjadi {$target_state}."],
            ]);
        } else {
            $this->session->set_flashdata("alerts", [
                ["error", "Gagal memperbarui status. Terjadi kesalahan database."],
            ]);
        }
        redirect("provisioning/detail/{$id_tiket}");
    }

    public function bind_cmdb()
    {
        // VERIFIKASI L2 WAJIB: Mencegah bypass penutupan tiket dari L1
        $this->_enforce_l2_guard("Bind CMDB & Close Ticket");

        $id_tiket = (int) $this->input->post("id_tiket", true);
        $id_virtual_machine = $this->input->post("id_virtual_machine", true);
        $tanggal_keluar_tiket = $this->input->post("tanggal_keluar_tiket", true);
        $mismatch_log = $this->input->post("mismatch_log", true);
        $username = $this->session->userdata("user_data")["username"] ?? "System";

        if (!$id_tiket || empty($id_virtual_machine)) {
            $this->session->set_flashdata("alerts", [
                ["error", "Gagal Binding: Target Master VM belum dipilih."],
            ]);
            redirect("provisioning/detail/{$id_tiket}");
            return;
        }

        // IMPLEMENTASI PESSIMISTIC LOCKING
        $this->db->trans_start();
        $row = $this->Provisioning_model->get_by_id_for_update($id_tiket);

        if (strtolower($row->progres_tiket) === "done") {
            $this->db->trans_rollback();
            $this->session->set_flashdata("alerts", [
                ["error", "Tiket ini sudah di-Bind / Closed sebelumnya."],
            ]);
            redirect("provisioning/detail/{$id_tiket}");
            return;
        }

        $master_vm = $this->Virtual_machine_model->get($id_virtual_machine);

        if (
            !$master_vm ||
            strtolower(trim($master_vm["virtual_machine_name"])) !==
                strtolower(trim($row->nama_server))
        ) {
            $this->db->trans_rollback();
            $this->session->set_flashdata("alerts", [
                [
                    "error",
                    "Binding Ditolak: Nama VM dari CMDB (" .
                    html_escape($master_vm["virtual_machine_name"] ?? "Kosong") .
                    ") tidak identik dengan Blueprint Tiket.",
                ],
            ]);
            redirect("provisioning/detail/{$id_tiket}");
            return;
        }

        $keterangan_baru = $row->keterangan ?? "";
        if (!empty($mismatch_log)) {
            $timestamp = date("d-M-Y H:i");
            $keterangan_baru .=
                "\n\n[⚠️ SYSTEM WARNING | {$timestamp}]: Tiket ditutup dengan status spesifikasi MISMATCH.\nDetail Mismatch:\n" .
                trim($mismatch_log);
        }

        $update_data = [
            "id_virtual_machine" => $id_virtual_machine,
            "progres_tiket" => "Done",
            "closed_by" => $username,
            "keterangan" => $keterangan_baru,
            "tanggal_keluar_tiket" =>
                normalize_mysql_datetime($tanggal_keluar_tiket) ?: date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
        ];

        $this->Provisioning_model->update_data($id_tiket, $update_data);

        $data_vm = [
            "id_env" => self::ENV_MAPPING[$row->environment] ?? null,
            "no_tiket_iris" => $row->no_tiket,
            "guest_os_manual" => $row->os,
        ];
        $this->Virtual_machine_model->update_vm($id_virtual_machine, $data_vm);

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->session->set_flashdata("alerts", [
                ["success", "Binding CMDB Berhasil! Tiket resmi ditutup (Closed)."],
            ]);
        } else {
            $this->session->set_flashdata("alerts", [
                ["error", "Terjadi kegagalan Database saat memproses Binding CMDB."],
            ]);
        }
        redirect("provisioning/detail/{$id_tiket}");
    }

    public function lapor_kendala()
    {
        $id_tiket = (int) $this->input->post("id_tiket", true);
        $kendala = trim($this->input->post("kendala_text", true));
        $username = $this->session->userdata("user_data")["username"] ?? "System";

        if (!$id_tiket || empty($kendala)) {
            redirect("provisioning");
        }

        $row = $this->Provisioning_model->get_by_id($id_tiket);
        $timestamp = date("d-M-Y H:i");
        $log_baru = "\n\n[⚠️ KENDALA | {$timestamp} | {$username}]\n{$kendala}";
        $keterangan_lama = trim($row->keterangan ?? "");
        $keterangan_baru = $keterangan_lama . $log_baru;

        $update_data = [
            "keterangan" => $keterangan_baru,
            "updated_at" => date("Y-m-d H:i:s"),
        ];

        if ($this->Provisioning_model->update_data($id_tiket, $update_data)) {
            $this->session->set_flashdata("alerts", [
                ["success", "Kendala berhasil dicatat ke dalam log tiket."],
            ]);
        } else {
            $this->session->set_flashdata("alerts", [["error", "Gagal mencatat kendala."]]);
        }
        redirect("provisioning/detail/{$id_tiket}");
    }

    public function delete_data()
    {
        // VERIFIKASI L2 WAJIB: Mencegah delete dari L1
        $this->_enforce_l2_guard("Delete Data Permanen");

        $id = (int) $this->input->post("id_tiket", true);
        if (!$id) {
            $this->session->set_flashdata("alerts", [
                ["error", "ID Tiket tidak valid untuk dihapus."],
            ]);
            redirect("provisioning");
            return;
        }

        if ($this->Provisioning_model->delete($id)) {
            $this->session->set_flashdata("alerts", [
                ["success", "Data Tiket Berhasil Dihapus Permanen."],
            ]);
        } else {
            $this->session->set_flashdata("alerts", [["error", "Gagal menghapus data tiket."]]);
        }
        redirect("provisioning");
    }

    // =========================================================================
    // AJAX & DATATABLES SECTION
    // =========================================================================
    public function ajax_list(): void
    {
        $list = $this->Provisioning_model->get_datatables();
        $data = [];
        $no = (int) $this->input->post("start");

        $user_session = $this->session->userdata("user_data");
        $role = isset($user_session["id_role"]) ? (int) $user_session["id_role"] : 99;
        $can_edit_execute = can_edit_execute($role);
        $can_verify_delete = can_verify_delete($role);

        $now = new DateTime();

        foreach ($list as $row) {
            $no++;
            $status = strtolower(trim($row->progres_tiket ?? ""));
            $is_closed = $status === "done" || $status === "cancel";

            $no_tiket = !empty($row->link_tiket)
                ? '<a href="' .
                    html_escape($row->link_tiket) .
                    '" target="_blank" style="color:#1ABB9C;font-weight:bold;text-decoration:underline;">' .
                    html_escape($row->no_tiket) .
                    ' <i class="fa fa-external-link"></i></a>'
                : "<strong>" . html_escape($row->no_tiket) . "</strong>";

            $aksi =
                '<div class="action-btn" style="display: flex; justify-content: center; gap: 6px;">';
            $aksi .=
                '<a href="' .
                site_url("provisioning/detail/" . $row->id_tiket) .
                '" class="btn btn-info btn-xs" title="Lihat/Eksekusi" style="margin:0;"><i class="fa fa-search"></i></a>';

            if ($is_closed) {
                $aksi .=
                    '<button type="button" class="btn btn-default btn-xs btn-locked" style="color:#aaa; border-color:#e5e5e5; background:#f9f9f9; margin:0;" title="Akses Terkunci (Closed)"><i class="fa fa-lock"></i></button>';
            } else {
                $aksi .=
                    '<a href="' .
                    site_url("provisioning/copy_tiket/" . $row->id_tiket) .
                    '" class="btn btn-default btn-xs" style="color:#2A3F54; border-color:#2A3F54; margin:0;" title="Duplikat Request"><i class="fa fa-copy"></i></a>';
            }

            if ($can_edit_execute) {
                if ($is_closed) {
                    $aksi .=
                        '<button type="button" class="btn btn-default btn-xs btn-locked" style="color:#aaa; border-color:#e5e5e5; background:#f9f9f9; margin:0;" title="Data Terkunci"><i class="fa fa-lock"></i></button>';
                } else {
                    $aksi .=
                        '<a href="' .
                        site_url("provisioning/edit/" . $row->id_tiket) .
                        '" class="btn btn-default btn-xs" style="color:#d58512; border-color:#d58512; margin:0;" title="Edit Data"><i class="fa fa-edit"></i></a>';
                }
            }

            if ($can_verify_delete) {
                $aksi .=
                    '<button type="button" class="btn btn-default btn-xs btn-trigger-delete" style="color:#ac2925; border-color:#ac2925; margin:0;" data-id="' .
                    $row->id_tiket .
                    '" data-tiket="' .
                    html_escape($row->no_tiket) .
                    '" data-vm="' .
                    html_escape($row->nama_server ?? "-") .
                    '" title="Hapus Permanen"><i class="fa fa-trash-o"></i></button>';
            }
            $aksi .= "</div>";

            $status_maps = [
                "done" => "bg-green",
                "in progress" => "bg-blue",
                "pending" => "bg-orange",
                "cancel" => "bg-black",
                "waiting sync" => "bg-purple",
            ];
            $label_class = array_key_exists($status, $status_maps)
                ? $status_maps[$status]
                : "label-default";
            $status_label =
                '<span class="badge ' .
                $label_class .
                '" style="font-size:11px;padding:4px 8px;">' .
                html_escape($row->progres_tiket ?? "-") .
                "</span>";

            $tgl_masuk_valid =
                !empty($row->tanggal_masuk_tiket) &&
                $row->tanggal_masuk_tiket != "0000-00-00 00:00:00";
            $start_date = $tgl_masuk_valid ? new DateTime($row->tanggal_masuk_tiket) : clone $now;

            if ($status === "done" || $status === "cancel") {
                $sla_color = "#10B981";
                $sla_label = "Closed";
            } else {
                $end_date = clone $now;
                $total_days = $start_date->diff($end_date)->days;
                if ($total_days >= 14) {
                    $sla_color = "#EF4444";
                    $sla_label = $total_days . " Hari";
                } elseif ($total_days >= 7) {
                    $sla_color = "#F59E0B";
                    $sla_label = $total_days . " Hari";
                } else {
                    $sla_color = "#3B82F6";
                    $sla_label = $total_days . " Hari";
                }
            }

            $tgl_masuk_html =
                '<div style="line-height:1.2;">' .
                ($tgl_masuk_valid ? date("d-M-Y", strtotime($row->tanggal_masuk_tiket)) : "-") .
                "<br>";
            $tgl_masuk_html .=
                $status !== "done" && $status !== "cancel"
                    ? '<div style="margin-top:3px;"><span class="label" style="background-color:' .
                        $sla_color .
                        '; font-size:10px;"><i class="fa fa-clock-o"></i> SLA: ' .
                        $sla_label .
                        "</span></div>"
                    : '<div style="margin-top:3px;"><span class="label label-success" style="font-size:10px;"><i class="fa fa-check"></i> ' .
                        $sla_label .
                        "</span></div>";
            $tgl_masuk_html .= "</div>";

            $vm_name = html_escape($row->nama_server ?? "");
            $ip_addr = html_escape($row->ip ?? "");
            $vm_html =
                '<div style="line-height: 1.15; display: inline-block; text-align: center;"><div style="margin-bottom:2px;"><strong style="color:#0F172A; font-size:13px;">' .
                $vm_name .
                '</strong> <i class="fa fa-copy text-primary btn-copy-tbl" style="cursor:pointer;" title="Copy" data-text="' .
                $vm_name .
                '"></i></div>';
            if (!empty($ip_addr)) {
                $vm_html .=
                    '<div style="margin-bottom:0;"><span style="font-size:12px; color:#D9534F; font-family:monospace; font-weight:bold;">' .
                    $ip_addr .
                    '</span> <i class="fa fa-copy text-danger btn-copy-tbl" style="cursor:pointer;" title="Copy IP" data-text="' .
                    $ip_addr .
                    '"></i></div>';
            } else {
                $vm_html .=
                    '<div style="margin-bottom:0;"><span style="font-size:11px; color:#94A3B8; font-style:italic;">IP belum diset</span></div>';
            }
            $vm_html .= "</div>";

            $source_text = html_escape($row->source_clone ?? "-");
            $source_val = '<div style="line-height:1.15;"><b>' . $source_text . "</b> ";
            if ($source_text !== "-") {
                $source_val .=
                    '<i class="fa fa-copy text-primary btn-copy-tbl" style="cursor:pointer;" title="Copy" data-text="' .
                    $source_text .
                    '"></i>';
            }
            $source_val .= "</div>";

            $spec_html =
                '<div style="font-size:12px; line-height:1.15; text-align:left; display:inline-block; color:#334155;">
                <div style="margin-bottom:2px;">vCPU: <strong style="color:#D9534F;">' .
                ((int) $row->cpu) .
                ' Core</strong></div>
                <div style="margin-bottom:2px;">RAM: <strong style="color:#D9534F;">' .
                ((int) $row->ram) .
                ' GB</strong></div>
                <div style="margin-bottom:0;">Disk: <strong style="color:#D9534F;">' .
                ((int) $row->disk) .
                " GB</strong></div></div>";

            $nestedData = [];
            $nestedData[] = $no;
            $nestedData[] = $tgl_masuk_html;
            $nestedData[] = $no_tiket;
            $nestedData[] = $vm_html;
            $nestedData[] = html_escape($row->kritikalitas ?? "");
            $nestedData[] = html_escape($row->environment ?? "");
            $nestedData[] = html_escape($row->tipe_request ?? "");
            $nestedData[] = $spec_html;
            $nestedData[] = html_escape($row->detail_disk ?? "");
            $nestedData[] = html_escape($row->keterangan ?? "");
            $nestedData[] = $source_val;
            $nestedData[] = !empty($row->created_by) ? html_escape($row->created_by) : "-";
            $nestedData[] = !empty($row->setup_by) ? html_escape($row->setup_by) : "-";
            $nestedData[] = !empty($row->closed_by) ? html_escape($row->closed_by) : "-";
            $nestedData[] = $status_label;
            $nestedData[] = $aksi;

            $data[] = $nestedData;
        }

        $output = [
            "draw" => $this->input->post("draw") ? intval($this->input->post("draw")) : 1,
            "recordsTotal" => $this->Provisioning_model->count_all(),
            "recordsFiltered" => $this->Provisioning_model->count_filtered(),
            "data" => $data,
        ];
        $this->output->set_content_type("application/json")->set_output(json_encode($output));
    }

    public function search_vm(): void
    {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
        ini_set("display_errors", "0");

        $keyword = $this->input->post("keyword", true);
        if (!$keyword) {
            $this->output->set_content_type("application/json")->set_output(json_encode([]));
            return;
        }

        $this->db->select(
            "id_virtual_machine, virtual_machine_name, cpu_count, memory_mb, provisioned_gb",
        );
        $this->db->from("master_virtual_machine");
        $this->db->where("is_active", 1);
        $this->db->like("virtual_machine_name", $keyword, "after");
        $this->db->limit(10);
        $result = $this->db->get()->result();

        $this->output->set_content_type("application/json")->set_output(json_encode($result));
    }

    public function ajax_search_datastore(): void
    {
        $keyword = $this->input->post("keyword", true);
        if (!$keyword) {
            $this->output->set_content_type("application/json")->set_output(json_encode([]));
            return;
        }

        $result = $this->Provisioning_model->search_master_datastore($keyword);
        $select2_data = [];

        foreach ($result as $row) {
            $select2_data[] = [
                "id" => $row->datastore_name,
                "text" => $row->datastore_name,
                "capacity" => (float) $row->capacity_gb,
                "used" => (float) $row->used_gb,
                "free" => (float) $row->free_gb,
                "provisioned" => (float) $row->provisioned_gb,
                "overprovision" => (float) $row->overprovision_percent,
            ];
        }

        $this->output->set_content_type("application/json")->set_output(json_encode($select2_data));
    }

    // =========================================================================
    // EXPORT REPORTING SECTION (EXCEL .XLS) - UNBUFFERED STREAMING
    // =========================================================================
    private function _get_export_headers_map(): array
    {
        return [
            "no" => "No",
            "tanggal_masuk_vcenter" => "Tgl Masuk vCenter",
            "bulan" => "Bulan",
            "kritikalitas" => "Kritikalitas",
            "tipe_request" => "Tipe Request",
            "environment" => "Environment",
            "nama_server" => "Virtual Machine",
            "datastore" => "Datastore",
            "cpu" => "vCPU (Core)",
            "ram" => "RAM (GB)",
            "disk" => "Disk (GB)",
            "no_tiket" => "No Tiket iRIS",
            "hostname" => "Hostname",
            "ip" => "IP Address",
            "os" => "Operating System",
            "aplikasi" => "Kelompok Aplikasi",
            "divisi_requestor" => "Fungsi Peminta",
            "nama_requestor" => "Nama PIC",
            "contact" => "Kontak PIC",
            "source_clone" => "Source / Template",
            "detail_disk" => "Detail Partisi",
            "progres_tiket" => "Status Akhir",
            "created_by" => "Diinput Oleh",
            "setup_by" => "Eksekutor",
            "closed_by" => "Ditutup Oleh",
            "tanggal_masuk_tiket" => "Tgl Input Tiket",
            "tanggal_keluar_tiket" => "Tgl Selesai (Closed)",
            "keterangan" => "Catatan Kendala",
        ];
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

    private function _aggregate_summary_row(array &$metrics, array &$uniq_tiket, array $row): void
    {
        $metrics["total_cpu"] += (int) ($row["cpu"] ?? 0);
        $metrics["total_ram"] += (int) ($row["ram"] ?? 0);
        $metrics["total_disk"] += (int) ($row["disk"] ?? 0);

        if (!empty($row["no_tiket"])) {
            $uniq_tiket[trim($row["no_tiket"])] = true;
        }

        if (strtolower(trim($row["environment"] ?? "")) === "production") {
            $metrics["total_prod"]++;
        } else {
            $metrics["total_dev"]++;
        }

        $tipe = strtolower(trim($row["tipe_request"] ?? ""));
        if ($tipe === "fresh" || $tipe === "fresh install") {
            $metrics["total_fresh"]++;
        } elseif ($tipe === "clone") {
            $metrics["total_clone"]++;
        }
    }

    public function ajax_preview_export()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $filter_type = $this->input->post("filter_type", true);
        $tgl_mulai = $this->input->post("tgl_mulai", true);
        $tgl_selesai = $this->input->post("tgl_selesai", true);
        $opt_cols = $this->input->post("opt_cols", true) ?? [];

        if ($filter_type === "range" && (empty($tgl_mulai) || empty($tgl_selesai))) {
            $this->output->set_content_type("application/json")->set_output(
                json_encode([
                    "status" => "error",
                    "message" => "Tanggal wajib diisi untuk filter rentang waktu.",
                ]),
            );
            return;
        }

        $data =
            $filter_type === "range"
                ? $this->Provisioning_model->fetch_data_for_export($tgl_mulai, $tgl_selesai)
                : $this->Provisioning_model->fetch_data_for_export();

        $metrics = [
            "total_cpu" => 0,
            "total_ram" => 0,
            "total_disk" => 0,
            "total_prod" => 0,
            "total_dev" => 0,
            "total_fresh" => 0,
            "total_clone" => 0,
        ];
        $uniq_tiket = [];
        $bulan_indonesia = [
            1 => "Jan",
            2 => "Feb",
            3 => "Mar",
            4 => "Apr",
            5 => "Mei",
            6 => "Jun",
            7 => "Jul",
            8 => "Ags",
            9 => "Sep",
            10 => "Okt",
            11 => "Nov",
            12 => "Des",
        ];
        $default_keys = [
            "no",
            "tanggal_masuk_vcenter",
            "bulan",
            "kritikalitas",
            "tipe_request",
            "environment",
            "nama_server",
            "datastore",
            "cpu",
            "ram",
            "disk",
            "no_tiket",
        ];

        $final_keys = array_merge($default_keys, $opt_cols);
        $map = $this->_get_export_headers_map();
        $result_headers = [];

        foreach ($final_keys as $k) {
            $result_headers[] = $map[$k] ?? $k;
        }

        $result_rows = [];
        $no = 1;
        $total_rows_limit = 0;

        foreach ($data as $obj) {
            $row_arr = (array) $obj;
            $this->_aggregate_summary_row($metrics, $uniq_tiket, $row_arr);

            if ($total_rows_limit < 100) {
                $row_dict = [];
                foreach ($final_keys as $key) {
                    if ($key === "no") {
                        $row_dict[$key] = $no;
                    } elseif ($key === "bulan") {
                        $row_dict[$key] = !empty($row_arr["tanggal_masuk_vcenter"])
                            ? $bulan_indonesia[
                                    (int) date("m", strtotime($row_arr["tanggal_masuk_vcenter"]))
                                ] ?? ""
                            : "";
                    } elseif (
                        in_array($key, [
                            "tanggal_masuk_vcenter",
                            "tanggal_masuk_tiket",
                            "tanggal_keluar_tiket",
                        ])
                    ) {
                        $row_dict[$key] = !empty($row_arr[$key])
                            ? date("d/m/Y H:i", strtotime($row_arr[$key]))
                            : "-";
                    } else {
                        $row_dict[$key] = html_escape($row_arr[$key] ?? "-");
                    }
                }
                $result_rows[] = $row_dict;
            }
            $no++;
            $total_rows_limit++;
        }

        $metrics["total_tiket"] = count($uniq_tiket);
        $metrics["total_vm"] = $total_rows_limit;

        $this->output->set_content_type("application/json")->set_output(
            json_encode([
                "status" => "success",
                "summary" => $metrics,
                "total_rows" => $total_rows_limit,
                "columns" => $final_keys,
                "headers" => $result_headers,
                "data" => $result_rows,
                "csrf_hash" => $this->security->get_csrf_hash(),
            ]),
        );
    }

    public function export_excel()
    {
        // ========================================================================
        // [ENTERPRISE FIX]: RAM-Safe Generator Setup (Streaming)
        // ========================================================================
        set_time_limit(0);
        ini_set("memory_limit", "512M");

        $filter_type =
            $this->input->post("filter_type", true) ?: $this->input->get("filter_type", true);
        $tgl_mulai = $this->input->post("tgl_mulai", true) ?: $this->input->get("tgl_mulai", true);
        $tgl_selesai =
            $this->input->post("tgl_selesai", true) ?: $this->input->get("tgl_selesai", true);
        $opt_cols =
            $this->input->post("opt_cols", true) ?? ($this->input->get("opt_cols", true) ?? []);

        if ($filter_type === "range" && (empty($tgl_mulai) || empty($tgl_selesai))) {
            $this->session->set_flashdata("alerts", [
                ["error", "Tanggal wajib diisi untuk export."],
            ]);
            redirect("provisioning/get_list_provisioning");
            return;
        }

        $export_query =
            $filter_type === "range"
                ? $this->Provisioning_model->get_data_export_query($tgl_mulai, $tgl_selesai)
                : $this->Provisioning_model->get_data_export_query();

        if ($filter_type === "range") {
            $data["periode"] =
                date("d M Y", strtotime($tgl_mulai)) .
                " s/d " .
                date("d M Y", strtotime($tgl_selesai));
            $filename_date = $tgl_mulai . "_sd_" . $tgl_selesai;
        } else {
            $data["periode"] = "Semua Waktu (Keseluruhan Data)";
            $filename_date = "All_Data";
        }

        $data["filename"] = "Laporan_Provisioning_VM_" . $filename_date . ".xls";
        $default_keys = [
            "no",
            "tanggal_masuk_vcenter",
            "bulan",
            "kritikalitas",
            "tipe_request",
            "environment",
            "nama_server",
            "datastore",
            "cpu",
            "ram",
            "disk",
            "no_tiket",
        ];
        $data["selected_cols"] = array_merge($default_keys, $opt_cols);
        $map = $this->_get_export_headers_map();
        $data["headers"] = [];

        foreach ($data["selected_cols"] as $k) {
            $data["headers"][] = $map[$k] ?? $k;
        }

        // Buka Aliran Memori Sementara (O(1) Memory Concept)
        $temp_fp = fopen("php://temp", "r+");
        $bulan_indonesia = [
            1 => "Jan",
            2 => "Feb",
            3 => "Mar",
            4 => "Apr",
            5 => "Mei",
            6 => "Jun",
            7 => "Jul",
            8 => "Ags",
            9 => "Sep",
            10 => "Okt",
            11 => "Nov",
            12 => "Des",
        ];

        $metrics = [
            "total_cpu" => 0,
            "total_ram" => 0,
            "total_disk" => 0,
            "total_prod" => 0,
            "total_dev" => 0,
            "total_fresh" => 0,
            "total_clone" => 0,
        ];
        $uniq_tiket = [];
        $no = 1;

        while ($row = $export_query->unbuffered_row("array")) {
            $this->_aggregate_summary_row($metrics, $uniq_tiket, $row);

            $html_row = "<tr>";
            foreach ($data["selected_cols"] as $col_key) {
                if ($col_key === "no") {
                    $html_row .= '<td align="center">' . $no . "</td>";
                } elseif ($col_key === "bulan") {
                    $bln = !empty($row["tanggal_masuk_vcenter"])
                        ? $bulan_indonesia[
                                (int) date("m", strtotime($row["tanggal_masuk_vcenter"]))
                            ] ?? ""
                        : "";
                    $html_row .= '<td align="center">' . $bln . "</td>";
                } elseif (
                    in_array($col_key, [
                        "tanggal_masuk_vcenter",
                        "tanggal_masuk_tiket",
                        "tanggal_keluar_tiket",
                    ])
                ) {
                    $dt_val = !empty($row[$col_key])
                        ? date("d/m/Y H:i", strtotime($row[$col_key]))
                        : "-";
                    $html_row .= '<td class="str" align="center">' . $dt_val . "</td>";
                } elseif ($col_key === "no_tiket") {
                    $html_row .=
                        '<td class="str" style="mso-number-format:\@;"><b>' .
                        $this->_sanitize_excel_formula($row[$col_key]) .
                        "</b></td>";
                } else {
                    $html_row .=
                        "<td>" .
                        html_escape($this->_sanitize_excel_formula($row[$col_key] ?? "-")) .
                        "</td>";
                }
            }
            $html_row .= "</tr>\n";
            fwrite($temp_fp, $html_row);
            $no++;
        }

        $metrics["total_tiket"] = count($uniq_tiket);
        $metrics["total_vm"] = $no - 1;

        $data["summary"] = $metrics;
        $data["temp_fp"] = $temp_fp; // Passing ke View

        $this->load->view("vm_provisioning/export_excel_provisioning", $data);
    }
}
