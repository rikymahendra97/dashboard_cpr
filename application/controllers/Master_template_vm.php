<?php
/**
 * ============================================================================
 * File Name    : Master_template_vm.php
 * Modul        : Master Template VM
 * Purpose      : Controller utama untuk Master Template Clone (Form Terpisah).
 * Architecture : Enterprise Standard CP-05 (Separate Form CRUD)
 * ============================================================================
 */
defined("BASEPATH") or exit("No direct script access allowed");

class Master_template_vm extends CI_Controller
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

        $this->load->model("Master_template_vm_model", "template_model");
        $this->load->library("form_validation");
    }

    public function index()
    {
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $data["user_session"];
        $data["title"] = "Data Master Template VM";

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("master_template_vm/list_template", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function ajax_list()
    {
        $list = $this->template_model->get_datatables();
        $data = [];
        $no = $_POST["start"];

        foreach ($list as $row) {
            $no++;
            $tbody = [];
            $tbody[] = '<div class="text-center">' . $no . "</div>";
            $tbody[] = html_escape($row->template_family);
            $tbody[] = "<strong>" . html_escape($row->template_name) . "</strong>";

            $status =
                $row->is_active == 1
                    ? '<span class="label label-success">Aktif</span>'
                    : '<span class="label label-danger">Non-Aktif</span>';
            $tbody[] = '<div class="text-center">' . $status . "</div>";

            $tbody[] =
                '<div class="text-center">' .
                date("d-M-Y H:i", strtotime($row->created_at)) .
                "</div>";

            $btn =
                '<div class="text-center" style="display:flex; justify-content:center; gap:6px;">';
            $btn .=
                '<a href="' .
                site_url("master_template_vm/edit/" . $row->id_template) .
                '" class="btn btn-primary btn-xs" style="margin:0;"><i class="fa fa-edit"></i> Edit</a>';
            $btn .=
                '<button type="button" class="btn btn-danger btn-xs btn-delete" data-id="' .
                $row->id_template .
                '" style="margin:0;"><i class="fa fa-trash"></i> Hapus</button>';
            $btn .= "</div>";

            $tbody[] = $btn;
            $data[] = $tbody;
        }

        $output = [
            "draw" => $_POST["draw"],
            "recordsTotal" => $this->template_model->count_all(),
            "recordsFiltered" => $this->template_model->count_filtered(),
            "data" => $data,
        ];
        echo json_encode($output);
    }

    public function tambah()
    {
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $data["user_session"];
        $data["title"] = "Tambah Data Master Template";

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("master_template_vm/form_add_template", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    // [ENTERPRISE FIX]: Menambahkan Type Hinting string untuk menghindari Linter P1132
    public function edit(string $id_template)
    {
        $data["user_session"] = $this->session->userdata("user_data");
        $data["id"] = $data["user_session"];

        $detail = $this->template_model->get_by_id((int) $id_template);
        if (empty($detail)) {
            $this->session->set_flashdata("alerts", [["error", "Data template tidak ditemukan."]]);
            redirect("master_template_vm");
        }

        $data["title"] = "Edit Data Master Template";
        $data["detail"] = $detail;

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("master_template_vm/form_edit_template", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function simpan()
    {
        $this->form_validation->set_rules("template_family", "Grup OS", "required");
        $this->form_validation->set_rules("template_name", "Nama Template", "required");

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata("alerts", [["error", "Harap isi semua kolom wajib."]]);
            redirect("master_template_vm/tambah");
            return;
        }

        $data = [
            "template_family" => $this->input->post("template_family", true),
            "template_name" => trim($this->input->post("template_name", true)),
            "is_active" => $this->input->post("is_active", true),
        ];

        $this->template_model->insert_data($data);
        $this->session->set_flashdata("alerts", [
            ["success", "Template Clone Baru Berhasil Ditambahkan"],
        ]);
        redirect("master_template_vm");
    }

    public function update()
    {
        $id = $this->input->post("id_template", true);

        $data = [
            "template_family" => $this->input->post("template_family", true),
            "template_name" => trim($this->input->post("template_name", true)),
            "is_active" => $this->input->post("is_active", true),
        ];

        $this->template_model->update_data((int) $id, $data);
        $this->session->set_flashdata("alerts", [
            ["success", "Data Master Template Berhasil Diperbarui"],
        ]);
        redirect("master_template_vm");
    }

    public function hapus()
    {
        $id = $this->input->post("id_template", true);
        $this->template_model->delete_data((int) $id);
        $this->session->set_flashdata("alerts", [
            ["success", "Data Master Template Dihapus Permanen"],
        ]);
        redirect("master_template_vm");
    }
}
