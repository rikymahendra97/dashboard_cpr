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

        $data["css_arr"] = [];
        $data["js_arr"] = [];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("replication_backup/details_vm", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }
}