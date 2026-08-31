<?php
defined("BASEPATH") or exit("No direct script access allowed");

/**
 * ============================================================
 * REPLICATION & BACKUP REPORT - CLI CONTROLLER
 * ============================================================
 *
 * Controller khusus automation / scheduler.
 *
 * Tidak menggunakan:
 * - session login
 * - redirect auth
 * - halaman browser
 *
 * Contoh:
 *
 * php index.php Replication_backup_report snapshot_weekly
 */
class Replication_backup_report extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        /**
         * ========================================================
         * CLI ONLY
         * ========================================================
         *
         * Controller ini tidak boleh diakses melalui browser.
         */
        if (!$this->input->is_cli_request()) {
            show_404();
            exit;
        }


        /**
         * Model Replication & Backup.
         */
        $this->load->model(
            "Replication_backup_model"
        );
    }


    /**
     * ============================================================
     * WEEKLY SNAPSHOT
     * ============================================================
     *
     * Nantinya dijalankan otomatis:
     *
     * Rabu
     * 06:00 WIB
     *
     * melalui Windows Task Scheduler.
     */
    public function snapshot_weekly()
    {
        $result =
            $this->Replication_backup_model
                ->create_report_snapshot();


        /**
         * ========================================================
         * SUCCESS
         * ========================================================
         */
        if (
            isset($result["success"])
            &&
            $result["success"] === true
        ) {
            echo PHP_EOL;

            echo
                "Replication & Backup Weekly Snapshot"
                . PHP_EOL;

            echo
                "===================================="
                . PHP_EOL;

            echo
                "Status        : SUCCESS"
                . PHP_EOL;

            echo
                "Snapshot ID   : "
                . (int) $result["id_snapshot"]
                . PHP_EOL;

            echo
                "Snapshot Date : "
                . $result["snapshot_date"]
                . PHP_EOL;

            echo
                "Snapshot At   : "
                . $result["snapshot_at"]
                . PHP_EOL;

            echo
                "Total VM      : "
                . (int) $result["total_vm"]
                . PHP_EOL;

            echo PHP_EOL;

            exit(0);
        }


        /**
         * ========================================================
         * SNAPSHOT SUDAH ADA
         * ========================================================
         *
         * Tidak dianggap error karena snapshot tanggal yang sama
         * memang tidak boleh duplicate.
         */
        if (
            isset($result["status"])
            &&
            $result["status"] === "exists"
        ) {
            echo PHP_EOL;

            echo
                "Replication & Backup Weekly Snapshot"
                . PHP_EOL;

            echo
                "===================================="
                . PHP_EOL;

            echo
                "Status        : SKIPPED"
                . PHP_EOL;

            echo
                "Reason        : Snapshot pada tanggal ini sudah ada."
                . PHP_EOL;

            echo
                "Snapshot ID   : "
                . (int) $result["id_snapshot"]
                . PHP_EOL;

            echo
                "Snapshot Date : "
                . $result["snapshot_date"]
                . PHP_EOL;

            echo
                "Snapshot At   : "
                . $result["snapshot_at"]
                . PHP_EOL;

            echo PHP_EOL;

            exit(0);
        }


        /**
         * ========================================================
         * FAILED
         * ========================================================
         */
        $status =
            isset($result["status"])
                ? $result["status"]
                : "unknown_error";


        echo PHP_EOL;

        echo
            "Replication & Backup Weekly Snapshot"
            . PHP_EOL;

        echo
            "===================================="
            . PHP_EOL;

        echo
            "Status : FAILED"
            . PHP_EOL;

        echo
            "Reason : "
            . $status
            . PHP_EOL;


        /**
         * Snapshot parsial.
         */
        if ($status === "incomplete") {

            echo
                "Expected VM : "
                . (int) (
                    $result["expected_vm"] ?? 0
                )
                . PHP_EOL;

            echo
                "Inserted VM : "
                . (int) (
                    $result["inserted_vm"] ?? 0
                )
                . PHP_EOL;
        }


        echo PHP_EOL;

        exit(1);
    }
}