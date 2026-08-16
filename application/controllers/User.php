<?php
defined("BASEPATH") or exit("No direct script access allowed");

/**
 * ========================================================================
 * File Name    : User.php
 * Modul        : User Management & Profile
 * Purpose      : Controller utama manajemen Data Pengguna & Otorisasi.
 * Architecture : Enterprise CP-05 (Centralized RBAC Helper, IDOR Protection)
 * ========================================================================
 */
class User extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $user_session = $this->session->userdata("user_data");
        if (empty($user_session)) {
            if ($this->input->is_ajax_request()) {
                $this->output->set_status_header(401)->set_output("Session expired");
                exit();
            }
            redirect(site_url("auth/login"));
        }

        // HTTP Caching Prevention
        $this->output->set_header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        $this->output->set_header("Cache-Control: post-check=0, pre-check=0", false);
        $this->output->set_header("Pragma: no-cache");

        $this->load->helper(["form", "url"]);
        // Note: Asumsi rbac_helper sudah di-autoload di autoload.php.
        // Jika belum, uncomment baris di bawah ini:
        // $this->load->helper("rbac");

        $this->load->library(["csrf", "form_validation", "Mobile_Detect"]);
        $this->load->model(["user_model", "role_model"]);
    }

    /**
     * Helper Otorisasi Internal (Terpusat)
     * Mengadopsi rbac_helper agar satu pintu dengan modul lain.
     */
    private function _is_admin(): bool
    {
        $user_session = $this->session->userdata("user_data");
        $role = isset($user_session["id_role"]) ? (int) $user_session["id_role"] : 99;

        // Memanggil fungsi can_verify_delete() dari rbac_helper
        // Jika helper belum ter-load sempurna, fungsi fallback ke false demi keamanan.
        return function_exists("can_verify_delete") ? can_verify_delete($role) : false;
    }

    public function index()
    {
        redirect(site_url("user/get_list_user"));
    }

    public function get_list_user()
    {
        // RBAC - Hanya Admin/L2 yang boleh melihat daftar user
        if (!$this->_is_admin()) {
            show_error(
                "Akses Ditolak: Anda tidak memiliki wewenang melihat daftar pengguna.",
                403,
                "Forbidden",
            );
        }

        $id = $this->session->userdata("user_data");
        $data["id"] = $id;
        $data["user_session"] = $this->user_model->get((int) $id["id_user"]);
        $data["list_user"] = $this->user_model->get_all_user_complete();
        $data["page_title"] = "Daftar User";
        $data["css_arr"] = ["datatables.css"];
        $data["js_arr"] = ["datatables/jquery.dataTables.min.js"];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);

        $this->load->view("user/list_user", $data);

        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function get_log_user()
    {
        if (!$this->_is_admin()) {
            show_error(
                "Akses Ditolak: Anda tidak memiliki wewenang melihat log akses.",
                403,
                "Forbidden",
            );
        }

        $id = $this->session->userdata("user_data");
        $data["id"] = $id;
        $data["user_session"] = $this->user_model->get((int) $id["id_user"]);
        $data["list_log_user"] = $this->user_model->get_log_user();
        $data["page_title"] = "Daftar Akses Login (Log)";
        $data["css_arr"] = ["datatables.css"];
        $data["js_arr"] = ["datatables/jquery.dataTables.min.js"];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);

        $this->load->view("user/list_log_user", $data);

        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function tambah_user()
    {
        if (!$this->_is_admin()) {
            show_error("Akses Ditolak.", 403, "Forbidden");
        }

        $id = $this->session->userdata("user_data");
        $data["id"] = $id;
        $data["user_session"] = $this->user_model->get((int) $id["id_user"]);
        $data["role"] = $this->user_model->get_all_role();
        $data["page_title"] = "Form Tambah User";
        $data["css_arr"] = ["select2.css"];
        $data["js_arr"] = ["select2/select2.min.js"];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);

        $this->load->view("user/form_tambah_user", $data);

        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function edit_user(string $id_user)
    {
        $id_user = (int) $id_user;
        $session_id = (int) $this->session->userdata("user_data")["id_user"];

        // IDOR Protection: Hanya admin atau user itu sendiri yang boleh edit
        if ($session_id !== $id_user && !$this->_is_admin()) {
            show_error(
                "Akses Ditolak: Anda tidak diizinkan menyunting profil orang lain.",
                403,
                "Forbidden",
            );
        }

        $id = $this->session->userdata("user_data");
        $data["id"] = $id;
        $data["user_session"] = $this->user_model->get((int) $id["id_user"]);
        $data["query"] = $this->user_model->get($id_user);

        if (empty($data["query"])) {
            show_404();
        }

        $data["page_title"] = "Form Edit Password User";

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);

        $this->load->view("user/form_edit_user", $data);

        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function edit_user_detail(string $id_user)
    {
        $id_user = (int) $id_user;
        $session_id = (int) $this->session->userdata("user_data")["id_user"];

        // IDOR Protection
        if ($session_id !== $id_user && !$this->_is_admin()) {
            show_error(
                "Akses Ditolak: Anda tidak diizinkan menyunting profil orang lain.",
                403,
                "Forbidden",
            );
        }

        $id = $this->session->userdata("user_data");
        $data["id"] = $id;
        $data["user_session"] = $this->user_model->get((int) $id["id_user"]);
        $data["query"] = $this->user_model->get($id_user);

        if (empty($data["query"])) {
            show_404();
        }

        $data["role"] = $this->role_model->get_all_role();
        $data["page_title"] = "Form Edit Profil User";

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);

        $this->load->view("user/form_edit_user_detail", $data);

        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    // ========================================================================
    // SECTION: FORM PROCESSING & DB WRITES
    // ========================================================================

    public function simpan_data()
    {
        if (!$this->_is_admin()) {
            $this->session->set_flashdata("alerts", [["error", "Akses ditolak."]]);
            redirect("user");
        }

        // Validasi Form
        $this->form_validation->set_rules(
            "nama_user",
            "Username",
            "required|trim|is_unique[master_user.username]",
        );
        $this->form_validation->set_rules("nama_lengkap", "Nama Lengkap", "required|trim");
        $this->form_validation->set_rules("password", "Password", "required|min_length[6]");

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata("alerts", [["error", validation_errors("", "")]]);
            redirect("user/tambah_user");
        } else {
            // Payload disusun di Controller, XSS Flag TRUE
            $payload = [
                "nama_lengkap" => $this->input->post("nama_lengkap", true),
                "username" => $this->input->post("nama_user", true),
                "password" => md5("xx-" . $this->input->post("password") . "-xx"), // Legacy Format Maintained
                "id_role" => (int) $this->input->post("id_role", true),
                "email" => $this->input->post("email", true),
                "id_kartu" => $this->input->post("id_kartu", true),
                "alamat" => $this->input->post("alamat", true),
                "no_phone" => $this->input->post("no_phone", true),
                "img_file" => "avatar_default.jpg",
            ];

            if ($this->user_model->simpan_data($payload)) {
                $this->session->set_flashdata("alerts", [
                    ["success", "Pengguna baru berhasil didaftarkan."],
                ]);
            } else {
                $this->session->set_flashdata("alerts", [
                    ["error", "Gagal menyimpan ke database."],
                ]);
            }
            redirect("user/get_list_user");
        }
    }

    public function update_data()
    {
        // Update Password
        $id_target = (int) $this->input->post("id_user", true);
        $session_id = (int) $this->session->userdata("user_data")["id_user"];

        // IDOR Protection
        if ($session_id !== $id_target && !$this->_is_admin()) {
            $this->session->set_flashdata("alerts", [
                ["error", "Pelanggaran Keamanan: Anda tidak berhak mengubah data ini."],
            ]);
            redirect("user");
            return;
        }

        $this->form_validation->set_rules("password", "Password Baru", "required|min_length[6]");

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata("alerts", [["error", validation_errors("", "")]]);
            redirect("user/edit_user/" . $id_target);
        } else {
            $payload = [
                "password" => md5("xx-" . $this->input->post("password") . "-xx"),
            ];

            $this->user_model->update_data($id_target, $payload);
            $this->session->set_flashdata("alerts", [["success", "Password berhasil diperbarui."]]);

            // Redirect based on role
            if ($this->_is_admin()) {
                redirect("user/get_list_user");
            } else {
                redirect("user/edit_user_detail/" . $id_target);
            }
        }
    }

    public function update_data_detail()
    {
        // Update Profil Umum
        $id_target = (int) $this->input->post("id_user", true);
        $session_id = (int) $this->session->userdata("user_data")["id_user"];

        // IDOR Protection
        if ($session_id !== $id_target && !$this->_is_admin()) {
            $this->session->set_flashdata("alerts", [
                ["error", "Pelanggaran Keamanan: Anda tidak berhak mengubah data ini."],
            ]);
            redirect("user");
            return;
        }

        $this->form_validation->set_rules("nama_lengkap", "Nama Lengkap", "required|trim");

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata("alerts", [["error", validation_errors("", "")]]);
            redirect("user/edit_user_detail/" . $id_target);
        } else {
            $payload = [
                "nama_lengkap" => $this->input->post("nama_lengkap", true),
                "email" => $this->input->post("email", true),
                "id_kartu" => $this->input->post("id_kartu", true),
                "alamat" => $this->input->post("alamat", true),
                "no_phone" => $this->input->post("no_phone", true),
            ];

            // Hanya admin yang bisa mengganti Role ID
            if ($this->_is_admin() && $this->input->post("id_role") !== null) {
                $payload["id_role"] = (int) $this->input->post("id_role", true);
            }

            $this->user_model->update_data($id_target, $payload);

            // Update session if editing self
            if ($session_id === $id_target) {
                $updated_user_data = $this->user_model->get($id_target);
                $this->session->set_userdata("user_data", $updated_user_data);
            }

            $this->session->set_flashdata("alerts", [
                ["success", "Profil pengguna berhasil diperbarui."],
            ]);

            if ($this->_is_admin()) {
                redirect("user/get_list_user");
            } else {
                redirect("user/edit_user_detail/" . $id_target);
            }
        }
    }

    // Hapus User dipaksa menggunakan POST demi mitigasi CSRF & SQLi
    public function hapus()
    {
        if (!$this->_is_admin()) {
            $this->session->set_flashdata("alerts", [["error", "Akses Ditolak."]]);
            redirect("user");
        }

        $id_target = (int) $this->input->post("id_user", true);
        $session_id = (int) $this->session->userdata("user_data")["id_user"];

        if ($id_target === $session_id) {
            $this->session->set_flashdata("alerts", [
                ["error", "Anda tidak dapat menghapus akun Anda sendiri saat sedang aktif."],
            ]);
        } elseif ($id_target > 0) {
            $this->user_model->hapus($id_target);
            $this->session->set_flashdata("alerts", [
                ["success", "Data Pengguna berhasil dihapus secara permanen."],
            ]);
        }

        redirect("user/get_list_user");
    }

    public function aksi_upload()
    {
        $id_target = (int) $this->input->post("id_user", true);
        $session_id = (int) $this->session->userdata("user_data")["id_user"];

        // IDOR Validation
        if ($session_id !== $id_target && !$this->_is_admin()) {
            $this->session->set_flashdata("alerts", [
                ["error", "Pelanggaran Keamanan: Otorisasi Ditolak."],
            ]);
            redirect("user");
            return;
        }

        $upload_path = FCPATH . "images/";

        // Defensive Programming - Auto Create Folder
        // Memastikan folder selalu ada sebelum proses upload berjalan
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $img_base = $upload_path . $id_target;

        $extensions = [".jpg", ".jpeg", ".png", ".gif"];
        foreach ($extensions as $ext) {
            $file_lama = $img_base . $ext;
            if (file_exists($file_lama)) {
                @unlink($file_lama);
            }
        }

        $config["upload_path"] = $upload_path;
        $config["allowed_types"] = "gif|jpg|jpeg|png";
        $config["max_size"] = 2048; // Diselaraskan dengan limit default server (2MB = 2048KB)
        $config["file_name"] = (string) $id_target;
        $config["overwrite"] = true;

        $this->load->library("upload", $config);

        if (!$this->upload->do_upload("berkas")) {
            $error_msg = strip_tags($this->upload->display_errors("", ""));
            $this->session->set_flashdata("alerts", [["error", "Gagal upload: " . $error_msg]]);
        } else {
            $upload_data = $this->upload->data();
            $file_name = $upload_data["file_name"];

            $this->user_model->update_data($id_target, ["img_file" => $file_name]);

            // Update session if uploading for self
            if ($session_id === $id_target) {
                $updated_user_data = $this->user_model->get($id_target);
                $this->session->set_userdata("user_data", $updated_user_data);
            }

            $this->session->set_flashdata("alerts", [
                ["success", "Foto profil berhasil diperbarui!"],
            ]);
        }

        redirect("user/edit_user_detail/" . $id_target);
    }

    public function cek_username()
    {
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        $username = $this->input->get("username", true);
        $data = $this->user_model->cek_username($username);

        $this->output->set_content_type("application/json")->set_output(json_encode($data));
    }
}
