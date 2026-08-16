<?php
/**
 * ============================================================================
 * File Name    : Master_os.php
 * Modul        : Master OS
 * Purpose      : Controller utama untuk Master Operating System.
 * Architecture : Enterprise Standard CP-05 (Separate Form CRUD)
 * ============================================================================
 */
defined("BASEPATH") or exit("No direct script access allowed");

class Master_os extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (empty($this->session->userdata("user_data"))) {
            redirect("auth/login");
        }

        $user_session = $this->session->userdata("user_data");
        // Hanya Superadmin (0) dan Admin (1) yang boleh akses Modul Data Master
        if ($user_session["id_role"] > 1) {
            $this->session->set_flashdata("alerts", [
                ["error", "Akses Ditolak. Privilege tidak mencukupi."],
            ]);
            redirect("dashboard");
        }

        $this->load->model("Master_os_model");
        $this->load->library("form_validation");
    }

    public function index()
    {
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $data["user_session"];
        $data["title"] = "Data Master Operating System (OS)";

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        // [ENTERPRISE FIX]: Path view disesuaikan ke folder master_os
        $this->load->view("master_os/list_os", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function ajax_list()
    {
        $list = $this->Master_os_model->get_datatables();
        $data = [];
        $no = $_POST["start"];

        foreach ($list as $row) {
            $no++;
            $tbody = [];
            $tbody[] = '<div class="text-center">' . $no . "</div>";
            $tbody[] = html_escape($row->os_family);
            $tbody[] = "<strong>" . html_escape($row->os_name) . "</strong>";

            $status =
                $row->is_active == 1
                    ? '<span class="label label-success">Aktif</span>'
                    : '<span class="label label-danger">Non-Aktif</span>';
            $tbody[] = '<div class="text-center">' . $status . "</div>";

            $btn =
                '<div class="text-center" style="display:flex; justify-content:center; gap:6px;">';
            $btn .=
                '<a href="' .
                site_url("master_os/edit/" . $row->id_os) .
                '" class="btn btn-primary btn-xs" style="margin:0;"><i class="fa fa-edit"></i> Edit</a>';
            $btn .=
                '<button type="button" class="btn btn-danger btn-xs btn-delete" data-id="' .
                $row->id_os .
                '" style="margin:0;"><i class="fa fa-trash"></i> Hapus</button>';
            $btn .= "</div>";

            $tbody[] = $btn;
            $data[] = $tbody;
        }

        $output = [
            "draw" => $_POST["draw"],
            "recordsTotal" => $this->Master_os_model->count_all(),
            "recordsFiltered" => $this->Master_os_model->count_filtered(),
            "data" => $data,
        ];
        echo json_encode($output);
    }

    public function tambah()
    {
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $data["user_session"];
        $data["title"] = "Tambah Data Master OS";

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("master_os/form_add_os", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function edit(string $id_os)
    {
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $data["user_session"];

        $detail = $this->Master_os_model->get_by_id((int) $id_os);
        if (empty($detail)) {
            $this->session->set_flashdata("alerts", [["error", "Data OS tidak ditemukan."]]);
            redirect("master_os");
        }

        $data["title"] = "Edit Data Master OS";
        $data["detail"] = $detail;

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("master_os/form_edit_os", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function simpan()
    {
        $this->form_validation->set_rules("os_family", "Grup OS", "required");
        $this->form_validation->set_rules("os_name", "Nama OS", "required");

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata("alerts", [["error", "Harap isi semua kolom wajib."]]);
            redirect("master_os/tambah");
            return;
        }

        $data = [
            "os_family" => $this->input->post("os_family", true),
            "os_name" => trim($this->input->post("os_name", true)),
            "is_active" => $this->input->post("is_active", true),
        ];

        $this->Master_os_model->insert_data($data);
        $this->session->set_flashdata("alerts", [
            ["success", "Sistem Operasi Baru Berhasil Ditambahkan"],
        ]);
        redirect("master_os");
    }

    public function update()
    {
        $id = $this->input->post("id_os", true);

        $data = [
            "os_family" => $this->input->post("os_family", true),
            "os_name" => trim($this->input->post("os_name", true)),
            "is_active" => $this->input->post("is_active", true),
        ];

        $this->Master_os_model->update_data((int) $id, $data);
        $this->session->set_flashdata("alerts", [
            ["success", "Data Master OS Berhasil Diperbarui"],
        ]);
        redirect("master_os");
    }

    public function hapus()
    {
        $id = $this->input->post("id_os", true);
        $this->Master_os_model->delete_data((int) $id);
        $this->session->set_flashdata("alerts", [["success", "Data Master OS Dihapus Permanen"]]);
        redirect("master_os");
    }
}
