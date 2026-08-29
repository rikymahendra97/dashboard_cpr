<?php
defined("BASEPATH") or exit("No direct script access allowed");

class Replication_backup extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (empty($this->session->userdata("user_data"))) {
            redirect("auth/login");
        }

        $this->load->model("Replication_backup_model");
        $this->load->model("User_model");

        $this->output->set_header(
            "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
        );
        $this->output->set_header("Pragma: no-cache");
    }

    public function index()
    {
        $session = $this->session->userdata("user_data");

        $data["page_title"] = "Replication & Backup";
        $data["id"] = $session;
        $data["user_session"] = $this->User_model->get($session["id_user"]);

        // KPI
        $data["summary"] = $this->Replication_backup_model->get_summary();

        // List VM
        $data["list_vm"] = $this->Replication_backup_model->get_vm_list();

        $data["css_arr"] = [];
        $data["js_arr"] = [];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("replication_backup/index", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function details_vm($id_virtual_machine)
    {
        $session = $this->session->userdata("user_data");

        $data["page_title"] = "Replication & Backup - Detail";
        $data["id"] = $session;
        $data["user_session"] = $this->User_model->get($session["id_user"]);

        $data["vm_detail"] =
            $this->Replication_backup_model->get_vm_detail(
                $id_virtual_machine
            );

        if (!$data["vm_detail"]) {
            show_404();
            return;
        }

        $data["vm_pairs"] =
            $this->Replication_backup_model->get_vm_pairs(
                $id_virtual_machine
            );

        $data["css_arr"] = [];
        $data["js_arr"] = [];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("replication_backup/details_vm", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function edit_vm($id_virtual_machine)
    {
        $session = $this->session->userdata("user_data");

        $data["page_title"] = "Replication & Backup - Edit";
        $data["id"] = $session;
        $data["user_session"] = $this->User_model->get($session["id_user"]);

        /**
         * Detail VM utama
         */
        $data["vm_detail"] =
            $this->Replication_backup_model->get_vm_detail(
                $id_virtual_machine
            );

        if (!$data["vm_detail"]) {
            show_404();
            return;
        }

        /**
         * Pasangan VM yang sudah tersimpan
         */
        $data["vm_pairs"] =
            $this->Replication_backup_model->get_vm_pairs(
                $id_virtual_machine
            );

        /**
         * Semua VM aktif untuk searchable multi-select.
         * VM yang sedang diedit dikeluarkan dari pilihan.
         */
        $data["vm_options"] =
            $this->Replication_backup_model->get_vm_options(
                $id_virtual_machine
            );

        $data["save_success"] =
            $this->input->get("saved") === "1";

        $data["error_message"] =
            $this->session->flashdata("error");

        $data["css_arr"] = [];
        $data["js_arr"] = [];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("replication_backup/edit_vm", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    public function update_vm($id_virtual_machine)
    {
        /**
         * Hanya menerima POST.
         */
        if (strtolower($this->input->method()) !== "post") {
            redirect(
                "replication_backup/edit_vm/" . $id_virtual_machine
            );

            return;
        }

        /**
         * Pastikan VM ada.
         */
        $vm_detail =
            $this->Replication_backup_model->get_vm_detail(
                $id_virtual_machine
            );

        if (!$vm_detail) {
            show_404();
            return;
        }

        /**
         * Data manual dari form.
         */
        $status_referensi =
            $this->input->post(
                "status_referensi",
                true
            );

        $db =
            $this->input->post(
                "db",
                true
            );

        $ha =
            $this->input->post(
                "ha",
                true
            );

        $slave =
            $this->input->post(
                "slave",
                true
            );

        $standby =
            $this->input->post(
                "standby",
                true
            );

        /**
         * Multi VM pasangan.
         *
         * Jika tidak ada pilihan,
         * hasilnya dibuat array kosong.
         */
        $pairs = array(
            "DB" => (array) $this->input->post("id_vm_db"),
            "HA" => (array) $this->input->post("id_vm_ha"),
            "SLAVE" => (array) $this->input->post("id_vm_slave"),
            "STANDBY" => (array) $this->input->post("id_vm_standby")
        );

        /**
         * Simpan.
         */
        $saved =
            $this->Replication_backup_model
                ->save_edit_configuration(
                    $id_virtual_machine,
                    $status_referensi,
                    $db,
                    $ha,
                    $slave,
                    $standby,
                    $pairs
                );

        if (!$saved) {

            $this->session->set_flashdata(
                "error",
                "Gagal menyimpan konfigurasi Replication & Backup."
            );

            redirect(
                "replication_backup/edit_vm/"
                . $id_virtual_machine
            );

            return;
        }

        redirect(
            "replication_backup/edit_vm/"
            . $id_virtual_machine
            . "?saved=1"
        );
    }
}