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

        /**
         * ============================================================
         * NEED BACKUP REASON
         * ============================================================
         *
         * Kategori aktif + jumlah VM GTI NEED BACKUP
         * yang saat ini menggunakan masing-masing kategori.
         */
        $need_backup_reasons =
            $this->Replication_backup_model
                ->get_need_backup_reasons(true);

        $data["need_backup_reasons"] = array();

        foreach ($need_backup_reasons as $reason) {

            $id_need_backup_reason =
                (int) $reason->id_need_backup_reason;

            $data["need_backup_reasons"][] = array(
                "id_need_backup_reason" =>
                    $id_need_backup_reason,

                "reason_name" =>
                    $reason->reason_name,

                "vm_count" =>
                    $this->Replication_backup_model
                        ->count_vm_by_need_backup_reason(
                            $id_need_backup_reason
                        )
            );
        }

        $data["css_arr"] = [];
        $data["js_arr"] = [];

        $this->load->view("main/1head", $data);
        $this->load->view("main/2sidebar", $data);
        $this->load->view("main/3topnavigation", $data);
        $this->load->view("replication_backup/index", $data);
        $this->load->view("main/5footer", $data);
        $this->load->view("main/6bottom", $data);
    }

    /**
     * ============================================================
     * REPLICATION & BACKUP REPORT
     * ============================================================
     *
     * Halaman khusus Report 1 - Report 5.
     */
    public function report()
    {
        $session =
            $this->session->userdata("user_data");

        $data["page_title"] =
            "Replication & Backup Report";

        $data["id"] =
            $session;

        $data["user_session"] =
            $this->User_model->get(
                $session["id_user"]
            );


        /**
         * ============================================================
         * SNAPSHOT PERIOD
         * ============================================================
         */
        $report_snapshot_period =
            $this->Replication_backup_model
                ->get_report_snapshot_period();


        $data["report_snapshot_period"] =
            $report_snapshot_period;


        /**
         * Default state jika belum ada snapshot.
         */
        $data["report_snapshot_available"] = false;

        $data["report_gti_protection"] = null;

        $data["report_gti_replication_summary"] = null;

        $data["report_gti_vrep_rubrik"] = null;

        $data["report_gti_need_backup_reason"] = null;

        $data["report_tbn_backup_power_state"] = null;


        /**
         * ============================================================
         * CURRENT SNAPSHOT
         * ============================================================
         */
        if (!empty($report_snapshot_period["current"])) {

            $current_snapshot =
                $report_snapshot_period["current"];


            $current_snapshot_id =
                (int) $current_snapshot->id_snapshot;


            /**
             * Previous snapshot boleh belum tersedia.
             */
            $previous_snapshot_id = null;

            if (!empty($report_snapshot_period["previous"])) {

                $previous_snapshot_id =
                    (int) $report_snapshot_period[
                        "previous"
                    ]->id_snapshot;
            }


            $data["report_snapshot_available"] = true;


            /**
             * REPORT 1
             * GTI Protection by Criticality.
             */
            $data["report_gti_protection"] =
                $this->Replication_backup_model
                    ->get_report_gti_protection_by_criticality(
                        $current_snapshot_id,
                        $previous_snapshot_id
                    );


            /**
             * REPORT 2
             * GTI Replication Status.
             */
            $data["report_gti_replication_summary"] =
                $this->Replication_backup_model
                    ->get_report_gti_replication_summary(
                        $current_snapshot_id
                    );


            /**
             * REPORT 3
             * GTI vReps & Rubrik.
             */
            $data["report_gti_vrep_rubrik"] =
                $this->Replication_backup_model
                    ->get_report_gti_vrep_rubrik_by_criticality(
                        $current_snapshot_id
                    );


            /**
             * REPORT 4
             * GTI Need Backup by Reason.
             */
            $data["report_gti_need_backup_reason"] =
                $this->Replication_backup_model
                    ->get_report_gti_need_backup_by_reason(
                        $current_snapshot_id,
                        $previous_snapshot_id
                    );


            /**
             * REPORT 5
             * TBN Backup by Power State.
             */
            $data["report_tbn_backup_power_state"] =
                $this->Replication_backup_model
                    ->get_report_tbn_backup_by_power_state(
                        $current_snapshot_id
                    );
        }


        /**
         * Assets.
         */
        $data["css_arr"] = array();
        $data["js_arr"] = array();


        /**
         * Layout.
         */
        $this->load->view(
            "main/1head",
            $data
        );

        $this->load->view(
            "main/2sidebar",
            $data
        );

        $this->load->view(
            "main/3topnavigation",
            $data
        );

        $this->load->view(
            "replication_backup/report",
            $data
        );

        $this->load->view(
            "main/5footer",
            $data
        );

        $this->load->view(
            "main/6bottom",
            $data
        );
    }

    /**
     * ============================================================
     * EXPORT REPLICATION & BACKUP REPORT
     * ============================================================
     *
     * Export Report 1 - Report 5 berdasarkan snapshot terbaru.
     *
     * Current Snapshot:
     * - snapshot terbaru
     *
     * Previous Snapshot:
     * - snapshot tepat sebelumnya
     * - boleh NULL jika baru ada satu snapshot
     *
     * Format export mengikuti export existing project:
     * HTML Table -> .xls
     */
    public function export_report()
    {
        /**
         * Export report hanya membaca data snapshot.
         */
        set_time_limit(0);

        ini_set(
            "memory_limit",
            "512M"
        );


        /**
         * ============================================================
         * SNAPSHOT PERIOD
         * ============================================================
         */
        $report_snapshot_period =
            $this->Replication_backup_model
                ->get_report_snapshot_period();


        /**
         * Export tidak dapat dilakukan
         * jika belum ada snapshot sama sekali.
         */
        if (
            empty(
                $report_snapshot_period["current"]
            )
        ) {

            show_error(
                "Belum tersedia snapshot Replication & Backup untuk diexport.",
                404,
                "Export Report Gagal"
            );

            return;
        }


        $current_snapshot =
            $report_snapshot_period["current"];


        $current_snapshot_id =
            (int) $current_snapshot->id_snapshot;


        /**
         * Previous Snapshot boleh belum tersedia.
         */
        $previous_snapshot =
            $report_snapshot_period["previous"]
            ?? null;


        $previous_snapshot_id = null;


        if (!empty($previous_snapshot)) {

            $previous_snapshot_id =
                (int) $previous_snapshot->id_snapshot;
        }


        /**
         * ============================================================
         * REPORT 1
         * GTI Protection by Criticality
         * ============================================================
         */
        $report_gti_protection =
            $this->Replication_backup_model
                ->get_report_gti_protection_by_criticality(
                    $current_snapshot_id,
                    $previous_snapshot_id
                );


        /**
         * ============================================================
         * REPORT 2
         * GTI Replication Status Summary
         * ============================================================
         */
        $report_gti_replication_summary =
            $this->Replication_backup_model
                ->get_report_gti_replication_summary(
                    $current_snapshot_id
                );


        /**
         * ============================================================
         * REPORT 3
         * GTI vReps & Rubrik by Criticality
         * ============================================================
         */
        $report_gti_vrep_rubrik =
            $this->Replication_backup_model
                ->get_report_gti_vrep_rubrik_by_criticality(
                    $current_snapshot_id
                );


        /**
         * ============================================================
         * REPORT 4
         * GTI NEED BACKUP by Reason
         * ============================================================
         */
        $report_gti_need_backup_reason =
            $this->Replication_backup_model
                ->get_report_gti_need_backup_by_reason(
                    $current_snapshot_id,
                    $previous_snapshot_id
                );


        /**
         * ============================================================
         * REPORT 5
         * TBN Backup Status by Power State
         * ============================================================
         */
        $report_tbn_backup_power_state =
            $this->Replication_backup_model
                ->get_report_tbn_backup_by_power_state(
                    $current_snapshot_id
                );


        /**
         * ============================================================
         * DATA VIEW EXPORT
         * ============================================================
         */
        $data = array();


        $data["current_snapshot"] =
            $current_snapshot;


        $data["previous_snapshot"] =
            $previous_snapshot;


        $data["report_gti_protection"] =
            $report_gti_protection;


        $data["report_gti_replication_summary"] =
            $report_gti_replication_summary;


        $data["report_gti_vrep_rubrik"] =
            $report_gti_vrep_rubrik;


        $data["report_gti_need_backup_reason"] =
            $report_gti_need_backup_reason;


        $data["report_tbn_backup_power_state"] =
            $report_tbn_backup_power_state;


        /**
         * Waktu file dibuat.
         */
        $data["generated_at"] =
            date(
                "d-m-Y H:i:s"
            );


        /**
         * Filename menggunakan tanggal Current Snapshot.
         *
         * Contoh:
         * Replication_Backup_Report_20260831.xls
         */
        $snapshot_filename_date =
            !empty(
                $current_snapshot->snapshot_date
            )
                ? date(
                    "Ymd",
                    strtotime(
                        $current_snapshot->snapshot_date
                    )
                )
                : date("Ymd");


        $data["filename"] =
            "Replication_Backup_Report_"
            . $snapshot_filename_date
            . ".xls";


        /**
         * Response header Excel akan ditangani
         * oleh View export_report.php,
         * mengikuti pola export_excel.php existing.
         */
        $this->load->view(
            "replication_backup/export_report",
            $data
        );
    }

    /**
     * ============================================================
     * ADD NEED BACKUP REASON
     * ============================================================
     *
     * AJAX endpoint untuk menambahkan kategori Need Backup.
     */
    public function add_need_backup_reason()
    {
        /**
         * Hanya menerima POST.
         */
        if (
            strtolower(
                $this->input->method()
            ) !== "post"
        ) {
            show_404();
            return;
        }

        $reason_name =
            $this->input->post(
                "reason_name",
                true
            );

        $result =
            $this->Replication_backup_model
                ->add_need_backup_reason(
                    $reason_name
                );


        /**
         * Default response.
         */
        $response = array(
            "success" => false,
            "message" =>
                "Gagal menambahkan kategori Need Backup."
        );


        if (
            isset($result["success"]) &&
            $result["success"] === true
        ) {

            if (
                isset($result["status"]) &&
                $result["status"] === "reactivated"
            ) {

                $response = array(
                    "success" => true,
                    "message" =>
                        "Kategori berhasil diaktifkan kembali."
                );

            } else {

                $response = array(
                    "success" => true,
                    "message" =>
                        "Kategori Need Backup berhasil ditambahkan."
                );
            }

        } else {

            $status =
                isset($result["status"])
                    ? $result["status"]
                    : "error";

            if ($status === "invalid") {

                $response["message"] =
                    "Nama kategori tidak boleh kosong.";

            } elseif ($status === "too_long") {

                $response["message"] =
                    "Nama kategori maksimal 150 karakter.";

            } elseif ($status === "exists") {

                $response["message"] =
                    "Kategori dengan nama tersebut sudah tersedia.";
            }
        }


        $this->output
            ->set_content_type(
                "application/json"
            )
            ->set_output(
                json_encode(
                    $response
                )
            );
    }

    /**
     * ============================================================
     * DEACTIVATE NEED BACKUP REASON
     * ============================================================
     *
     * AJAX endpoint untuk soft delete kategori.
     *
     * PENTING:
     * Tidak melakukan DELETE permanen.
     * Model hanya mengubah is_active menjadi 0.
     */
    public function deactivate_need_backup_reason()
    {
        /**
         * Hanya menerima POST.
         */
        if (
            strtolower(
                $this->input->method()
            ) !== "post"
        ) {
            show_404();
            return;
        }


        $id_need_backup_reason =
            (int) $this->input->post(
                "id_need_backup_reason",
                true
            );


        if ($id_need_backup_reason <= 0) {

            $this->output
                ->set_content_type(
                    "application/json"
                )
                ->set_output(
                    json_encode(
                        array(
                            "success" => false,
                            "message" =>
                                "Kategori Need Backup tidak valid."
                        )
                    )
                );

            return;
        }


        /**
         * Hitung penggunaan kategori sebelum dinonaktifkan.
         *
         * Nilai ini bisa dikembalikan ke frontend
         * sebagai informasi tambahan.
         */
        $vm_count =
            $this->Replication_backup_model
                ->count_vm_by_need_backup_reason(
                    $id_need_backup_reason
                );


        $result =
            $this->Replication_backup_model
                ->deactivate_need_backup_reason(
                    $id_need_backup_reason
                );


        $response = array(
            "success" => false,
            "message" =>
                "Gagal menghapus kategori Need Backup.",
            "vm_count" =>
                (int) $vm_count
        );


        if (
            isset($result["success"]) &&
            $result["success"] === true
        ) {

            $response["success"] = true;

            if (
                isset($result["status"]) &&
                $result["status"] === "already_inactive"
            ) {

                $response["message"] =
                    "Kategori sudah tidak aktif.";

            } else {

                $response["message"] =
                    "Kategori Need Backup berhasil dihapus.";
            }

        } else {

            $status =
                isset($result["status"])
                    ? $result["status"]
                    : "error";

            if ($status === "invalid") {

                $response["message"] =
                    "Kategori Need Backup tidak valid.";

            } elseif ($status === "not_found") {

                $response["message"] =
                    "Kategori Need Backup tidak ditemukan.";
            }
        }


        $this->output
            ->set_content_type(
                "application/json"
            )
            ->set_output(
                json_encode(
                    $response
                )
            );
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

        /**
         * Kategori Need Backup yang masih aktif.
         *
         * Dipakai sebagai pilihan valid pada dropdown
         * Reason Need Backup di halaman Edit VM.
         */
        $data["need_backup_reasons"] =
            $this->Replication_backup_model
                ->get_need_backup_reasons(true);

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
         * Need Backup Reason.
         *
         * Model yang menentukan apakah field ini wajib,
         * berdasarkan:
         * - Site = GTI
         * - actual status = NEED BACKUP
         */
        $id_need_backup_reason =
            $this->input->post(
                "id_need_backup_reason",
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
                    $id_need_backup_reason,
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

    /**
     * ============================================================
     * EXPORT EXCEL
     * ============================================================
     *
     * Export seluruh VM yang lolos filter/search
     * pada halaman Replication & Backup.
     *
     * Data yang diexport menggunakan data lengkap
     * seperti halaman Detail VM.
     */
    public function export_excel()
    {
        /**
         * Export hanya menerima POST dari halaman list.
         */
        if (
            strtoupper(
                $this->input->method()
            ) !== "POST"
        ) {
            show_404();
            return;
        }


        /**
         * Export dapat berisi banyak VM.
         */
        set_time_limit(0);
        ini_set(
            "memory_limit",
            "512M"
        );


        /**
         * ============================================================
         * AMBIL ID VM DARI BROWSER
         * ============================================================
         */
        $raw_vm_ids =
            trim(
                (string) $this->input->post(
                    "vm_ids",
                    true
                )
            );


        if ($raw_vm_ids === "") {

            show_error(
                "Tidak ada data VM yang dipilih untuk export.",
                400,
                "Export Gagal"
            );

            return;
        }


        /**
         * ============================================================
         * NORMALISASI ID
         * ============================================================
         */
        $raw_ids =
            explode(
                ",",
                $raw_vm_ids
            );

        $vm_ids = array();
        $seen_ids = array();


        foreach ($raw_ids as $id_virtual_machine) {

            $id_virtual_machine =
                (int) trim(
                    (string) $id_virtual_machine
                );

            /**
             * Abaikan ID invalid.
             */
            if ($id_virtual_machine <= 0) {
                continue;
            }


            /**
             * Hindari duplicate ID.
             */
            if (
                isset(
                    $seen_ids[$id_virtual_machine]
                )
            ) {
                continue;
            }


            $seen_ids[$id_virtual_machine] =
                true;

            $vm_ids[] =
                $id_virtual_machine;
        }


        if (empty($vm_ids)) {

            show_error(
                "Tidak ada ID Virtual Machine yang valid untuk export.",
                400,
                "Export Gagal"
            );

            return;
        }


        /**
         * ============================================================
         * AMBIL DATA EXPORT
         * ============================================================
         */
        $export_data =
            $this->Replication_backup_model
                ->get_export_data(
                    $vm_ids
                );


        if (empty($export_data)) {

            show_error(
                "Data Virtual Machine untuk export tidak ditemukan.",
                404,
                "Export Gagal"
            );

            return;
        }


        /**
         * ============================================================
         * DATA VIEW EXPORT
         * ============================================================
         */
        $data = array();

        $data["export_data"] =
            $export_data;

        $data["generated_at"] =
            date(
                "d-m-Y H:i:s"
            );

        $data["filename"] =
            "Replication_Backup_" .
            date(
                "Ymd_His"
            ) .
            ".xls";


        /**
         * View ini akan kita buat pada Step berikutnya.
         */
        $this->load->view(
            "replication_backup/export_excel",
            $data
        );
    }

}