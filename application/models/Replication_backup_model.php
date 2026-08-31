<?php
defined("BASEPATH") or exit("No direct script access allowed");

class Replication_backup_model extends CI_Model
{
    protected $table_backup = "virtual_machine_backup";
    protected $table_vm = "master_virtual_machine";

    /**
     * ============================================================
     * SUMMARY / KPI
     * ============================================================
     */
    public function get_summary()
    {
        $query = $this->db
            ->select("
                SUM(CASE
                    WHEN vm.id_site = 'GTI'
                    AND UPPER(TRIM(COALESCE(b.status, ''))) = 'DONE BACKUP'
                    THEN 1 ELSE 0
                END) AS done_replication,

                SUM(CASE
                    WHEN vm.id_site = 'GTI'
                    AND UPPER(TRIM(COALESCE(b.status, ''))) = 'NEED BACKUP'
                    THEN 1 ELSE 0
                END) AS need_replication,

                SUM(CASE
                    WHEN vm.id_site = 'GTI'
                    AND UPPER(TRIM(COALESCE(b.status, ''))) = 'NO NEED BACKUP'
                    THEN 1 ELSE 0
                END) AS no_need_replication,

                SUM(CASE
                    WHEN vm.id_site = 'TBN'
                    AND UPPER(TRIM(COALESCE(b.status, ''))) = 'DONE BACKUP'
                    THEN 1 ELSE 0
                END) AS done_backup,

                SUM(CASE
                    WHEN vm.id_site = 'TBN'
                    AND UPPER(TRIM(COALESCE(b.status, ''))) = 'NEED BACKUP'
                    THEN 1 ELSE 0
                END) AS need_backup,

                SUM(CASE
                    WHEN vm.id_site = 'TBN'
                    AND UPPER(TRIM(COALESCE(b.status, ''))) = 'NO NEED BACKUP'
                    THEN 1 ELSE 0
                END) AS no_need_backup,

                SUM(CASE
                    WHEN b.vrep = 1
                    THEN 1 ELSE 0
                END) AS vrep,

                SUM(CASE
                    WHEN b.rubrik = 1
                    THEN 1 ELSE 0
                END) AS rubrik,

                SUM(CASE
                    WHEN b.ha = 1
                    THEN 1 ELSE 0
                END) AS ha,

                SUM(CASE
                    WHEN b.db = 1
                    THEN 1 ELSE 0
                END) AS db_count
            ", false)
            ->from('virtual_machine_backup b')
            ->join(
                'master_virtual_machine vm',
                'vm.id_virtual_machine = b.id_virtual_machine',
                'inner'
            )
            ->where('vm.is_active', 1)
            ->get()
            ->row();

        return [
            "done_replication" => (int) ($query->done_replication ?? 0),
            "need_replication" => (int) ($query->need_replication ?? 0),
            "no_need_replication" => (int) ($query->no_need_replication ?? 0),

            "done_backup" => (int) ($query->done_backup ?? 0),
            "need_backup" => (int) ($query->need_backup ?? 0),
            "no_need_backup" => (int) ($query->no_need_backup ?? 0),

            "vrep" => (int) ($query->vrep ?? 0),
            "rubrik" => (int) ($query->rubrik ?? 0),
            "ha" => (int) ($query->ha ?? 0),
            "db" => (int) ($query->db_count ?? 0),
        ];
    }

    /**
     * ============================================================
     * LIST VM
     * ============================================================
     */
    public function get_vm_list()
    {
        $this->db->select("
            vm.id_virtual_machine,
            vm.virtual_machine_name,
            vm.power_state,
            vm.vcenter_name,
            vm.id_site,
            vm.environment,

            GROUP_CONCAT(
                DISTINCT app.application_system_name
                ORDER BY app.application_system_name
                SEPARATOR ', '
            ) AS application_systems,

            CASE MAX(
                CASE
                    WHEN cr.criticality_name = 'Critical' THEN 5
                    WHEN cr.criticality_name = 'Very High' THEN 4
                    WHEN cr.criticality_name = 'High' THEN 3
                    WHEN cr.criticality_name = 'Medium' THEN 2
                    WHEN cr.criticality_name = 'Low' THEN 1
                    ELSE 0
                END
            )
                WHEN 5 THEN 'Critical'
                WHEN 4 THEN 'Very High'
                WHEN 3 THEN 'High'
                WHEN 2 THEN 'Medium'
                WHEN 1 THEN 'Low'
                ELSE 'Others'
            END AS criticality,

            b.status AS backup_status,
            b.status_referensi,

            MAX(
                b.id_need_backup_reason
            ) AS id_need_backup_reason,

            MAX(
                nbr.reason_name
            ) AS need_backup_reason_name,
            
            b.vrep,
            b.rubrik,
            b.ha,
            b.db,
            b.slave,
            b.standby
        ", false);

        $this->db->from($this->table_vm . " vm");

        /**
         * Backup / Replication
         */
        $this->db->join(
            $this->table_backup . " b",
            "b.id_virtual_machine = vm.id_virtual_machine",
            "left"
        );

        /**
         * Need Backup Reason.
         *
         * Tidak memakai filter is_active karena reason historical
         * tetap harus dapat dibaca apabila kategori sudah dinonaktifkan.
         */
        $this->db->join(
            "master_need_backup_reason nbr",
            "nbr.id_need_backup_reason = b.id_need_backup_reason",
            "left"
        );

        /**
         * VM -> Application System
         */
        $this->db->join(
            "relation_table rt",
            "rt.id_virtual_machine = vm.id_virtual_machine",
            "left"
        );

        /**
         * Application System
         */
        $this->db->join(
            "master_application_system app",
            "app.id_application_system = rt.id_application_system",
            "left"
        );

        /**
         * Application System -> Criticality
         */
        $this->db->join(
            "master_criticality cr",
            "cr.id_criticality = app.id_criticality",
            "left"
        );

        $this->db->where("vm.is_active", 1);

        $this->db->group_by("vm.id_virtual_machine");

        $this->db->order_by("vm.virtual_machine_name", "ASC");

        return $this->db->get()->result();
    }

    /**
     * ============================================================
     * DETAIL VM
     * ============================================================
     */
    public function get_vm_detail($id_virtual_machine)
    {
        $this->db->select("
            vm.id_virtual_machine,
            vm.virtual_machine_name,
            vm.power_state,
            vm.vcenter_name,
            vm.id_site,
            vm.environment,

            GROUP_CONCAT(
                DISTINCT app.application_system_name
                ORDER BY app.application_system_name
                SEPARATOR ', '
            ) AS application_systems,

            CASE MAX(
                CASE
                    WHEN cr.criticality_name = 'Critical' THEN 5
                    WHEN cr.criticality_name = 'Very High' THEN 4
                    WHEN cr.criticality_name = 'High' THEN 3
                    WHEN cr.criticality_name = 'Medium' THEN 2
                    WHEN cr.criticality_name = 'Low' THEN 1
                    ELSE 0
                END
            )
                WHEN 5 THEN 'Critical'
                WHEN 4 THEN 'Very High'
                WHEN 3 THEN 'High'
                WHEN 2 THEN 'Medium'
                WHEN 1 THEN 'Low'
                ELSE 'Others'
            END AS criticality,

            MAX(sla.sla_rubrik) AS sla_rubrik,

            b.status AS backup_status,
            b.status_referensi,

            MAX(
                b.id_need_backup_reason
            ) AS id_need_backup_reason,

            MAX(
                nbr.reason_name
            ) AS need_backup_reason_name,

            b.vrep,
            b.rubrik,
            b.db,
            b.ha,
            b.slave,
            b.standby
        ", false);

        $this->db->from($this->table_vm . " vm");

        /**
         * Backup / Replication
         */
        $this->db->join(
            $this->table_backup . " b",
            "b.id_virtual_machine = vm.id_virtual_machine",
            "left"
        );

        /**
         * Need Backup Reason
         *
         * Tidak difilter is_active di sini.
         *
         * Alasannya:
         * kategori yang sudah dinonaktifkan tetap perlu
         * bisa ditampilkan pada data VM lama.
         */
        $this->db->join(
            "master_need_backup_reason nbr",
            "nbr.id_need_backup_reason = b.id_need_backup_reason",
            "left"
        );

        /**
         * VM -> Application System
         */
        $this->db->join(
            "relation_table rt",
            "rt.id_virtual_machine = vm.id_virtual_machine",
            "left"
        );

        /**
         * Application System
         */
        $this->db->join(
            "master_application_system app",
            "app.id_application_system = rt.id_application_system",
            "left"
        );

        /**
         * Application System -> Criticality
         */
        $this->db->join(
            "master_criticality cr",
            "cr.id_criticality = app.id_criticality",
            "left"
        );

        /**
         * SLA Rubrik
         */
        $this->db->join(
            "(
                SELECT
                    s.virtual_machine,
                    vc_sla.vcenter_name,
                    vc_sla.id_site,
                    s.location,
                    MAX(s.sla) AS sla_rubrik
                FROM (
                    SELECT
                        virtual_machine,
                        sla,
                        location
                    FROM live_mount_log_tbn

                    UNION ALL

                    SELECT
                        virtual_machine,
                        sla,
                        location
                    FROM live_mount_log_gti

                    UNION ALL

                    SELECT
                        virtual_machine,
                        sla,
                        location
                    FROM live_mount_log_odc
                ) s

                INNER JOIN master_vcenter vc_sla
                    ON s.location = vc_sla.vcenter_ip

                WHERE vc_sla.id_site IN ('GTI', 'TBN', 'ODC')

                GROUP BY
                    s.virtual_machine,
                    vc_sla.vcenter_name,
                    vc_sla.id_site,
                    s.location
            ) sla",
            "sla.virtual_machine = vm.virtual_machine_name
            AND sla.vcenter_name = vm.vcenter_name
            AND sla.id_site = vm.id_site",
            "left",
            false
        );

        $this->db->where(
            "vm.id_virtual_machine",
            $id_virtual_machine
        );

        $this->db->where(
            "vm.is_active",
            1
        );

        $this->db->group_by(
            "vm.id_virtual_machine"
        );

        return $this->db->get()->row();
    }

    /**
     * ============================================================
     * PAIRS VM
     * ============================================================
     */
    public function get_vm_pairs($id_virtual_machine)
    {
        $this->db->select("
            p.pair_type,
            p.id_vm_pair,
            vm_pair.virtual_machine_name
        ");

        $this->db->from("virtual_machine_backup_pair p");

        $this->db->join(
            $this->table_vm . " vm_pair",
            "vm_pair.id_virtual_machine = p.id_vm_pair",
            "left"
        );

        $this->db->where(
            "p.id_virtual_machine",
            $id_virtual_machine
        );

        $this->db->where_in(
            "p.pair_type",
            array(
                "DB",
                "HA",
                "SLAVE",
                "STANDBY"
            )
        );

        $this->db->order_by(
            "p.pair_type",
            "ASC"
        );

        $this->db->order_by(
            "vm_pair.virtual_machine_name",
            "ASC"
        );

        return $this->db->get()->result();
    }

    /**
     * ============================================================
     * CREATE WEEKLY REPORT SNAPSHOT
     * ============================================================
     *
     * Snapshot menyimpan kondisi seluruh VM aktif pada satu waktu.
     *
     * Dipakai sebagai sumber:
     * - Report GTI Replication & Backup
     * - Report comparison minggu sebelumnya
     * - Report TBN Backup
     *
     * Actual backup status berasal dari:
     * virtual_machine_backup.status
     *
     * Criticality:
     * Critical > Very High > High > Medium > Low > Others
     *
     * @param string|null $snapshot_at Format: Y-m-d H:i:s
     *
     * @return array
     */
    public function create_report_snapshot(
        $snapshot_at = null
    ) {
        /**
         * ============================================================
         * VALIDASI SNAPSHOT TIME
         * ============================================================
         */
        if ($snapshot_at === null || trim((string) $snapshot_at) === "") {

            $snapshot_at =
                date("Y-m-d H:i:s");
        }


        $snapshot_at =
            trim((string) $snapshot_at);


        $snapshot_timestamp =
            strtotime($snapshot_at);


        if ($snapshot_timestamp === false) {

            return array(
                "success" => false,
                "status" => "invalid_datetime"
            );
        }


        $snapshot_at =
            date(
                "Y-m-d H:i:s",
                $snapshot_timestamp
            );


        $snapshot_date =
            date(
                "Y-m-d",
                $snapshot_timestamp
            );


        /**
         * ============================================================
         * CEK SNAPSHOT TANGGAL YANG SAMA
         * ============================================================
         *
         * Satu hari hanya boleh mempunyai satu snapshot.
         */
        $existing_snapshot =
            $this->db
                ->select("
                    id_snapshot,
                    snapshot_date,
                    snapshot_at
                ")
                ->from(
                    "replication_backup_report_snapshot"
                )
                ->where(
                    "snapshot_date",
                    $snapshot_date
                )
                ->limit(1)
                ->get()
                ->row();


        if ($existing_snapshot) {

            return array(
                "success" => false,
                "status" => "exists",
                "id_snapshot" =>
                    (int) $existing_snapshot->id_snapshot,
                "snapshot_date" =>
                    $existing_snapshot->snapshot_date,
                "snapshot_at" =>
                    $existing_snapshot->snapshot_at
            );
        }


        /**
         * Pastikan ada VM aktif yang akan disnapshot.
         */
        $total_active_vm =
            (int) $this->db
                ->where(
                    "is_active",
                    1
                )
                ->count_all_results(
                    $this->table_vm
                );


        if ($total_active_vm <= 0) {

            return array(
                "success" => false,
                "status" => "no_data"
            );
        }


        /**
         * ============================================================
         * TRANSACTION START
         * ============================================================
         */
        $this->db->trans_begin();


        /**
         * ============================================================
         * INSERT SNAPSHOT HEADER
         * ============================================================
         */
        $header_inserted =
            $this->db->insert(
                "replication_backup_report_snapshot",
                array(
                    "snapshot_date" =>
                        $snapshot_date,

                    "snapshot_at" =>
                        $snapshot_at
                )
            );


        if (!$header_inserted) {

            $this->db->trans_rollback();

            return array(
                "success" => false,
                "status" => "error_header"
            );
        }


        $id_snapshot =
            (int) $this->db->insert_id();


        if ($id_snapshot <= 0) {

            $this->db->trans_rollback();

            return array(
                "success" => false,
                "status" => "error_header"
            );
        }


        /**
         * ============================================================
         * INSERT SNAPSHOT VM
         * ============================================================
         *
         * Criticality dihitung terlebih dahulu pada derived table
         * supaya satu VM hanya mempunyai satu criticality tertinggi.
         */
        $sql = "
            INSERT INTO replication_backup_report_snapshot_vm (
                id_snapshot,
                id_virtual_machine,
                virtual_machine_name,
                id_site,
                power_state,
                criticality,
                backup_status,
                id_need_backup_reason,
                need_backup_reason_name,
                vrep,
                rubrik,
                db,
                ha
            )

            SELECT
                ? AS id_snapshot,

                vm.id_virtual_machine,
                vm.virtual_machine_name,
                vm.id_site,
                vm.power_state,

                COALESCE(
                    vm_criticality.criticality,
                    'Others'
                ) AS criticality,

                b.status AS backup_status,

                b.id_need_backup_reason,

                nbr.reason_name
                    AS need_backup_reason_name,

                COALESCE(b.vrep, 0) AS vrep,
                COALESCE(b.rubrik, 0) AS rubrik,
                COALESCE(b.db, 0) AS db,
                COALESCE(b.ha, 0) AS ha

            FROM {$this->table_vm} vm


            LEFT JOIN {$this->table_backup} b
                ON b.id_virtual_machine =
                    vm.id_virtual_machine


            LEFT JOIN master_need_backup_reason nbr
                ON nbr.id_need_backup_reason =
                    b.id_need_backup_reason


            LEFT JOIN (

                SELECT
                    rt.id_virtual_machine,

                    CASE MAX(
                        CASE
                            WHEN cr.criticality_name = 'Critical'
                                THEN 5

                            WHEN cr.criticality_name = 'Very High'
                                THEN 4

                            WHEN cr.criticality_name = 'High'
                                THEN 3

                            WHEN cr.criticality_name = 'Medium'
                                THEN 2

                            WHEN cr.criticality_name = 'Low'
                                THEN 1

                            ELSE 0
                        END
                    )

                        WHEN 5 THEN 'Critical'
                        WHEN 4 THEN 'Very High'
                        WHEN 3 THEN 'High'
                        WHEN 2 THEN 'Medium'
                        WHEN 1 THEN 'Low'
                        ELSE 'Others'

                    END AS criticality

                FROM relation_table rt

                LEFT JOIN master_application_system app
                    ON app.id_application_system =
                        rt.id_application_system

                LEFT JOIN master_criticality cr
                    ON cr.id_criticality =
                        app.id_criticality

                GROUP BY
                    rt.id_virtual_machine

            ) vm_criticality

                ON vm_criticality.id_virtual_machine =
                    vm.id_virtual_machine


            WHERE
                vm.is_active = 1
        ";


        $detail_inserted =
            $this->db->query(
                $sql,
                array(
                    $id_snapshot
                )
            );


        if (!$detail_inserted) {

            $this->db->trans_rollback();

            return array(
                "success" => false,
                "status" => "error_detail"
            );
        }


        $inserted_vm =
            (int) $this->db->affected_rows();


        /**
         * ============================================================
         * VALIDASI JUMLAH SNAPSHOT
         * ============================================================
         *
         * Snapshot dianggap gagal apabila jumlah VM detail
         * berbeda dari jumlah VM aktif.
         *
         * Tujuannya supaya tidak pernah ada snapshot parsial.
         */
        if ($inserted_vm !== $total_active_vm) {

            $this->db->trans_rollback();

            return array(
                "success" => false,
                "status" => "incomplete",
                "expected_vm" =>
                    $total_active_vm,
                "inserted_vm" =>
                    $inserted_vm
            );
        }


        /**
         * ============================================================
         * TRANSACTION CHECK
         * ============================================================
         */
        if ($this->db->trans_status() === false) {

            $this->db->trans_rollback();

            return array(
                "success" => false,
                "status" => "transaction_error"
            );
        }


        $this->db->trans_commit();


        return array(
            "success" => true,
            "status" => "created",
            "id_snapshot" =>
                $id_snapshot,
            "snapshot_date" =>
                $snapshot_date,
            "snapshot_at" =>
                $snapshot_at,
            "total_vm" =>
                $inserted_vm
        );
    }

    /**
     * ============================================================
     * GET REPORT SNAPSHOT PERIOD
     * ============================================================
     *
     * Mengambil:
     * - current snapshot  = snapshot terbaru
     * - previous snapshot = snapshot tepat sebelumnya
     *
     * @return array
     */
    public function get_report_snapshot_period()
    {
        $snapshots =
            $this->db
                ->select("
                    id_snapshot,
                    snapshot_date,
                    snapshot_at
                ")
                ->from(
                    "replication_backup_report_snapshot"
                )
                ->order_by(
                    "snapshot_at",
                    "DESC"
                )
                ->limit(2)
                ->get()
                ->result();


        $current = null;
        $previous = null;


        if (isset($snapshots[0])) {

            $current = $snapshots[0];
        }


        if (isset($snapshots[1])) {

            $previous = $snapshots[1];
        }


        return array(
            "current" => $current,
            "previous" => $previous
        );
    }

    /**
     * ============================================================
     * REPORT 1
     * GTI PROTECTION BY CRITICALITY
     * ============================================================
     *
     * Menampilkan jumlah VM GTI berdasarkan:
     * - Criticality
     * - vReps
     * - DB
     * - HA
     * - Rubrik
     *
     * Current snapshot dibandingkan dengan previous snapshot.
     *
     * @param int      $current_snapshot_id
     * @param int|null $previous_snapshot_id
     *
     * @return array
     */
    public function get_report_gti_protection_by_criticality(
        $current_snapshot_id,
        $previous_snapshot_id = null
    ) {
        $current_snapshot_id =
            (int) $current_snapshot_id;

        $previous_snapshot_id =
            (int) $previous_snapshot_id;


        /**
         * Criticality standard dashboard.
         */
        $criticalities = array(
            "Critical",
            "Very High",
            "High",
            "Medium",
            "Low",
            "Others"
        );


        /**
         * Default result.
         *
         * Tetap membuat semua criticality walaupun
         * nilai pada snapshot = 0.
         */
        $result = array();

        foreach ($criticalities as $criticality) {

            $result[$criticality] = array(
                "criticality" => $criticality,

                "vrep" => array(
                    "current" => 0,
                    "previous" => null,
                    "delta" => null
                ),

                "db" => array(
                    "current" => 0,
                    "previous" => null,
                    "delta" => null
                ),

                "ha" => array(
                    "current" => 0,
                    "previous" => null,
                    "delta" => null
                ),

                "rubrik" => array(
                    "current" => 0,
                    "previous" => null,
                    "delta" => null
                )
            );
        }


        /**
         * ============================================================
         * HELPER QUERY
         * ============================================================
         */
        $get_snapshot_data = function ($id_snapshot) {

            if ((int) $id_snapshot <= 0) {
                return array();
            }


            $rows =
                $this->db
                    ->select("
                        criticality,

                        SUM(
                            CASE
                                WHEN vrep = 1 THEN 1
                                ELSE 0
                            END
                        ) AS total_vrep,

                        SUM(
                            CASE
                                WHEN db = 1 THEN 1
                                ELSE 0
                            END
                        ) AS total_db,

                        SUM(
                            CASE
                                WHEN ha = 1 THEN 1
                                ELSE 0
                            END
                        ) AS total_ha,

                        SUM(
                            CASE
                                WHEN rubrik = 1 THEN 1
                                ELSE 0
                            END
                        ) AS total_rubrik
                    ", false)
                    ->from(
                        "replication_backup_report_snapshot_vm"
                    )
                    ->where(
                        "id_snapshot",
                        (int) $id_snapshot
                    )
                    ->where(
                        "UPPER(TRIM(COALESCE(id_site, ''))) = 'GTI'",
                        null,
                        false
                    )
                    ->group_by(
                        "criticality"
                    )
                    ->get()
                    ->result();


            $data = array();

            foreach ($rows as $row) {

                $criticality =
                    trim(
                        (string) $row->criticality
                    );


                /**
                 * Nilai di luar standard dashboard
                 * dimasukkan ke Others.
                 */
                if (
                    !in_array(
                        $criticality,
                        array(
                            "Critical",
                            "Very High",
                            "High",
                            "Medium",
                            "Low"
                        ),
                        true
                    )
                ) {
                    $criticality = "Others";
                }


                if (!isset($data[$criticality])) {

                    $data[$criticality] = array(
                        "vrep" => 0,
                        "db" => 0,
                        "ha" => 0,
                        "rubrik" => 0
                    );
                }


                $data[$criticality]["vrep"] +=
                    (int) $row->total_vrep;

                $data[$criticality]["db"] +=
                    (int) $row->total_db;

                $data[$criticality]["ha"] +=
                    (int) $row->total_ha;

                $data[$criticality]["rubrik"] +=
                    (int) $row->total_rubrik;
            }


            return $data;
        };


        /**
         * Current snapshot.
         */
        $current_data =
            $get_snapshot_data(
                $current_snapshot_id
            );


        /**
         * Previous snapshot.
         */
        $previous_data = array();

        if ($previous_snapshot_id > 0) {

            $previous_data =
                $get_snapshot_data(
                    $previous_snapshot_id
                );
        }


        /**
         * ============================================================
         * BUILD CURRENT / PREVIOUS / DELTA
         * ============================================================
         */
        foreach ($criticalities as $criticality) {

            foreach (
                array(
                    "vrep",
                    "db",
                    "ha",
                    "rubrik"
                )
                as $metric
            ) {

                $current_value =
                    isset(
                        $current_data[
                            $criticality
                        ][$metric]
                    )
                        ? (int) $current_data[
                            $criticality
                        ][$metric]
                        : 0;


                $result[
                    $criticality
                ][$metric]["current"] =
                    $current_value;


                /**
                 * Snapshot pertama belum mempunyai comparison.
                 */
                if ($previous_snapshot_id <= 0) {

                    continue;
                }


                $previous_value =
                    isset(
                        $previous_data[
                            $criticality
                        ][$metric]
                    )
                        ? (int) $previous_data[
                            $criticality
                        ][$metric]
                        : 0;


                $result[
                    $criticality
                ][$metric]["previous"] =
                    $previous_value;


                $result[
                    $criticality
                ][$metric]["delta"] =
                    $current_value
                    -
                    $previous_value;
            }
        }


        /**
         * ============================================================
         * GRAND TOTAL
         * ============================================================
         */
        $grand_total = array(
            "vrep" => array(
                "current" => 0,
                "previous" => null,
                "delta" => null
            ),

            "db" => array(
                "current" => 0,
                "previous" => null,
                "delta" => null
            ),

            "ha" => array(
                "current" => 0,
                "previous" => null,
                "delta" => null
            ),

            "rubrik" => array(
                "current" => 0,
                "previous" => null,
                "delta" => null
            )
        );


        foreach ($result as $row) {

            foreach (
                array(
                    "vrep",
                    "db",
                    "ha",
                    "rubrik"
                )
                as $metric
            ) {

                $grand_total[
                    $metric
                ]["current"] +=
                    (int) $row[
                        $metric
                    ]["current"];


                if ($previous_snapshot_id > 0) {

                    $grand_total[
                        $metric
                    ]["previous"] =
                        (int) (
                            $grand_total[
                                $metric
                            ]["previous"] ?? 0
                        )
                        +
                        (int) $row[
                            $metric
                        ]["previous"];
                }
            }
        }


        if ($previous_snapshot_id > 0) {

            foreach (
                array(
                    "vrep",
                    "db",
                    "ha",
                    "rubrik"
                )
                as $metric
            ) {

                $grand_total[
                    $metric
                ]["delta"] =
                    $grand_total[
                        $metric
                    ]["current"]
                    -
                    $grand_total[
                        $metric
                    ]["previous"];
            }
        }


        return array(
            "rows" =>
                array_values($result),

            "grand_total" =>
                $grand_total
        );
    }

    /**
     * ============================================================
     * REPORT 2
     * GTI REPLICATION STATUS SUMMARY
     * ============================================================
     *
     * Mapping actual backup status:
     *
     * DONE BACKUP
     *     -> Done Replication
     *
     * NEED BACKUP
     *     -> Need Replication
     *
     * NO NEED BACKUP
     *     -> No Need Replication
     *
     * Grand Total merupakan jumlah ketiga status tersebut.
     *
     * @param int $snapshot_id
     *
     * @return array
     */
    public function get_report_gti_replication_summary(
        $snapshot_id
    ) {
        $snapshot_id =
            (int) $snapshot_id;


        /**
         * Default result.
         */
        $result = array(
            "done_replication" => 0,
            "need_replication" => 0,
            "no_need_replication" => 0,
            "grand_total" => 0
        );


        if ($snapshot_id <= 0) {
            return $result;
        }


        $row =
            $this->db
                ->select("
                    SUM(
                        CASE
                            WHEN UPPER(
                                TRIM(
                                    COALESCE(
                                        backup_status,
                                        ''
                                    )
                                )
                            ) = 'DONE BACKUP'
                            THEN 1
                            ELSE 0
                        END
                    ) AS done_replication,

                    SUM(
                        CASE
                            WHEN UPPER(
                                TRIM(
                                    COALESCE(
                                        backup_status,
                                        ''
                                    )
                                )
                            ) = 'NEED BACKUP'
                            THEN 1
                            ELSE 0
                        END
                    ) AS need_replication,

                    SUM(
                        CASE
                            WHEN UPPER(
                                TRIM(
                                    COALESCE(
                                        backup_status,
                                        ''
                                    )
                                )
                            ) = 'NO NEED BACKUP'
                            THEN 1
                            ELSE 0
                        END
                    ) AS no_need_replication
                ", false)
                ->from(
                    "replication_backup_report_snapshot_vm"
                )
                ->where(
                    "id_snapshot",
                    $snapshot_id
                )
                ->where(
                    "UPPER(TRIM(COALESCE(id_site, ''))) = 'GTI'",
                    null,
                    false
                )
                ->get()
                ->row();


        if (!$row) {
            return $result;
        }


        $result["done_replication"] =
            (int) $row->done_replication;

        $result["need_replication"] =
            (int) $row->need_replication;

        $result["no_need_replication"] =
            (int) $row->no_need_replication;


        /**
         * Grand Total hanya menjumlahkan:
         *
         * Done Replication
         * Need Replication
         * No Need Replication
         */
        $result["grand_total"] =
            $result["done_replication"]
            +
            $result["need_replication"]
            +
            $result["no_need_replication"];


        return $result;
    }

    /**
     * ============================================================
     * REPORT 3
     * GTI VREPS & RUBRIK BY CRITICALITY
     * ============================================================
     *
     * Rules:
     *
     * vReps:
     * - Sukses = jumlah VM dengan vrep = 1
     * - Gagal  = 0
     * - Jumlah = Sukses + Gagal
     *
     * Rubrik:
     * - Sukses = jumlah VM dengan rubrik = 1
     * - Gagal  = 0
     * - Jumlah = Sukses + Gagal
     *
     * Scope:
     * - Snapshot tertentu
     * - Site GTI
     *
     * @param int $snapshot_id
     *
     * @return array
     */
    public function get_report_gti_vrep_rubrik_by_criticality(
        $snapshot_id
    ) {
        $snapshot_id =
            (int) $snapshot_id;


        /**
         * Standard criticality dashboard.
         */
        $criticalities = array(
            "Critical",
            "Very High",
            "High",
            "Medium",
            "Low",
            "Others"
        );


        /**
         * Default rows.
         *
         * Semua criticality tetap ditampilkan
         * walaupun nilainya 0.
         */
        $rows = array();

        foreach ($criticalities as $criticality) {

            $rows[$criticality] = array(
                "criticality" => $criticality,

                "vrep" => array(
                    "success" => 0,
                    "failed" => 0,
                    "total" => 0
                ),

                "rubrik" => array(
                    "success" => 0,
                    "failed" => 0,
                    "total" => 0
                )
            );
        }


        /**
         * Default grand total.
         */
        $grand_total = array(
            "vrep" => array(
                "success" => 0,
                "failed" => 0,
                "total" => 0
            ),

            "rubrik" => array(
                "success" => 0,
                "failed" => 0,
                "total" => 0
            )
        );


        /**
         * Default overall summary.
         *
         * Digunakan untuk bagian bawah report:
         *
         * Total Sukses
         * Total Gagal
         * Jumlah Replikasi
         */
        $summary = array(
            "total_success" => 0,
            "total_failed" => 0,
            "total_replication" => 0,
            "success_percentage" => 0,
            "failed_percentage" => 0
        );


        if ($snapshot_id <= 0) {

            return array(
                "rows" => array_values($rows),
                "grand_total" => $grand_total,
                "summary" => $summary
            );
        }


        /**
         * ============================================================
         * QUERY SNAPSHOT
         * ============================================================
         */
        $query_rows =
            $this->db
                ->select("
                    criticality,

                    SUM(
                        CASE
                            WHEN vrep = 1
                            THEN 1
                            ELSE 0
                        END
                    ) AS vrep_success,

                    SUM(
                        CASE
                            WHEN rubrik = 1
                            THEN 1
                            ELSE 0
                        END
                    ) AS rubrik_success
                ", false)
                ->from(
                    "replication_backup_report_snapshot_vm"
                )
                ->where(
                    "id_snapshot",
                    $snapshot_id
                )
                ->where(
                    "UPPER(TRIM(COALESCE(id_site, ''))) = 'GTI'",
                    null,
                    false
                )
                ->group_by(
                    "criticality"
                )
                ->get()
                ->result();


        /**
         * ============================================================
         * BUILD ROWS
         * ============================================================
         */
        foreach ($query_rows as $row) {

            $criticality =
                trim(
                    (string) $row->criticality
                );


            /**
             * Nilai selain standard dashboard
             * digabung ke Others.
             */
            if (
                !in_array(
                    $criticality,
                    array(
                        "Critical",
                        "Very High",
                        "High",
                        "Medium",
                        "Low"
                    ),
                    true
                )
            ) {
                $criticality = "Others";
            }


            $vrep_success =
                (int) $row->vrep_success;

            $rubrik_success =
                (int) $row->rubrik_success;


            /**
             * Sesuai requirement:
             * Gagal selalu 0.
             */
            $vrep_failed = 0;
            $rubrik_failed = 0;


            $rows[
                $criticality
            ]["vrep"]["success"] +=
                $vrep_success;

            $rows[
                $criticality
            ]["vrep"]["failed"] +=
                $vrep_failed;

            $rows[
                $criticality
            ]["vrep"]["total"] +=
                $vrep_success
                +
                $vrep_failed;


            $rows[
                $criticality
            ]["rubrik"]["success"] +=
                $rubrik_success;

            $rows[
                $criticality
            ]["rubrik"]["failed"] +=
                $rubrik_failed;

            $rows[
                $criticality
            ]["rubrik"]["total"] +=
                $rubrik_success
                +
                $rubrik_failed;
        }


        /**
         * ============================================================
         * GRAND TOTAL
         * ============================================================
         */
        foreach ($rows as $row) {

            $grand_total[
                "vrep"
            ]["success"] +=
                (int) $row[
                    "vrep"
                ]["success"];

            $grand_total[
                "vrep"
            ]["failed"] +=
                (int) $row[
                    "vrep"
                ]["failed"];

            $grand_total[
                "vrep"
            ]["total"] +=
                (int) $row[
                    "vrep"
                ]["total"];


            $grand_total[
                "rubrik"
            ]["success"] +=
                (int) $row[
                    "rubrik"
                ]["success"];

            $grand_total[
                "rubrik"
            ]["failed"] +=
                (int) $row[
                    "rubrik"
                ]["failed"];

            $grand_total[
                "rubrik"
            ]["total"] +=
                (int) $row[
                    "rubrik"
                ]["total"];
        }


        /**
         * ============================================================
         * OVERALL SUMMARY
         * ============================================================
         *
         * Contoh dari gambar:
         *
         * vReps sukses  = 666
         * Rubrik sukses = 5501
         *
         * Total Sukses  = 6167
         */
        $summary["total_success"] =
            $grand_total[
                "vrep"
            ]["success"]
            +
            $grand_total[
                "rubrik"
            ]["success"];


        $summary["total_failed"] =
            $grand_total[
                "vrep"
            ]["failed"]
            +
            $grand_total[
                "rubrik"
            ]["failed"];


        $summary["total_replication"] =
            $summary["total_success"]
            +
            $summary["total_failed"];


        /**
         * Percentage.
         */
        if ($summary["total_replication"] > 0) {

            $summary["success_percentage"] =
                round(
                    (
                        $summary["total_success"]
                        /
                        $summary["total_replication"]
                    )
                    *
                    100,
                    2
                );


            $summary["failed_percentage"] =
                round(
                    (
                        $summary["total_failed"]
                        /
                        $summary["total_replication"]
                    )
                    *
                    100,
                    2
                );
        }


        return array(
            "rows" =>
                array_values($rows),

            "grand_total" =>
                $grand_total,

            "summary" =>
                $summary
        );
    }

    /**
     * ============================================================
     * REPORT 4
     * GTI NEED BACKUP BY REASON & CRITICALITY
     * ============================================================
     *
     * Scope:
     * - Site GTI
     * - actual backup_status = NEED BACKUP
     *
     * Reason:
     * - dynamic
     * - kategori aktif tetap ditampilkan walaupun current = 0
     * - kategori nonaktif tetap ditampilkan jika ada pada
     *   current / previous snapshot
     *
     * Criticality:
     * - Critical
     * - Very High
     * - High
     * - Medium
     * - Low
     * - Others
     *
     * Comparison:
     * delta = current - previous
     *
     * @param int      $current_snapshot_id
     * @param int|null $previous_snapshot_id
     *
     * @return array
     */
    public function get_report_gti_need_backup_by_reason(
        $current_snapshot_id,
        $previous_snapshot_id = null
    ) {
        $current_snapshot_id =
            (int) $current_snapshot_id;

        $previous_snapshot_id =
            (int) $previous_snapshot_id;


        /**
         * ============================================================
         * STANDARD CRITICALITY
         * ============================================================
         */
        $criticalities = array(
            "Critical",
            "Very High",
            "High",
            "Medium",
            "Low",
            "Others"
        );


        /**
         * ============================================================
         * HELPER DEFAULT METRIC
         * ============================================================
         */
        $create_metric = function () {

            return array(
                "current" => 0,
                "previous" => null,
                "delta" => null
            );
        };


        /**
         * ============================================================
         * BASE REASON
         * ============================================================
         *
         * Kategori aktif selalu dimunculkan.
         */
        $master_reasons =
            $this->db
                ->select("
                    id_need_backup_reason,
                    reason_name,
                    is_active
                ")
                ->from(
                    "master_need_backup_reason"
                )
                ->where(
                    "is_active",
                    1
                )
                ->order_by(
                    "reason_name",
                    "ASC"
                )
                ->get()
                ->result();


        $rows = array();


        foreach ($master_reasons as $reason) {

            $reason_id =
                (int) $reason->id_need_backup_reason;

            $reason_key =
                "reason_" . $reason_id;


            $rows[$reason_key] = array(
                "id_need_backup_reason" =>
                    $reason_id,

                "reason_name" =>
                    trim(
                        (string) $reason->reason_name
                    ),

                "is_active" => 1,

                "criticalities" => array(),

                "total" =>
                    $create_metric()
            );


            foreach ($criticalities as $criticality) {

                $rows[
                    $reason_key
                ]["criticalities"][
                    $criticality
                ] =
                    $create_metric();
            }
        }


        /**
         * ============================================================
         * HELPER SNAPSHOT QUERY
         * ============================================================
         */
        $get_snapshot_data = function ($id_snapshot) {

            if ((int) $id_snapshot <= 0) {
                return array();
            }


            return $this->db
                ->select("
                    id_need_backup_reason,
                    need_backup_reason_name,
                    criticality,
                    COUNT(*) AS total_vm
                ", false)
                ->from(
                    "replication_backup_report_snapshot_vm"
                )
                ->where(
                    "id_snapshot",
                    (int) $id_snapshot
                )
                ->where(
                    "UPPER(TRIM(COALESCE(id_site, ''))) = 'GTI'",
                    null,
                    false
                )
                ->where(
                    "UPPER(TRIM(COALESCE(backup_status, ''))) = 'NEED BACKUP'",
                    null,
                    false
                )
                ->group_by(
                    array(
                        "id_need_backup_reason",
                        "need_backup_reason_name",
                        "criticality"
                    )
                )
                ->get()
                ->result();
        };


        $current_data =
            $get_snapshot_data(
                $current_snapshot_id
            );


        $previous_data = array();

        if ($previous_snapshot_id > 0) {

            $previous_data =
                $get_snapshot_data(
                    $previous_snapshot_id
                );
        }


        /**
         * ============================================================
         * HELPER ENSURE REASON ROW
         * ============================================================
         *
         * Berguna untuk:
         * - kategori yang sudah nonaktif
         * - data historis
         * - reason NULL
         */
        $ensure_reason_row =
            function (
                $reason_id,
                $reason_name
            ) use (
                &$rows,
                $criticalities,
                $create_metric
            ) {
                $reason_id =
                    (int) $reason_id;

                $reason_name =
                    trim(
                        (string) $reason_name
                    );


                /**
                 * VM belum mempunyai reason.
                 */
                if ($reason_id <= 0) {

                    $reason_key =
                        "unassigned";

                    if (!isset($rows[$reason_key])) {

                        $rows[$reason_key] = array(
                            "id_need_backup_reason" =>
                                null,

                            "reason_name" =>
                                "Belum Ditentukan",

                            "is_active" => 0,

                            "criticalities" => array(),

                            "total" =>
                                $create_metric()
                        );


                        foreach (
                            $criticalities
                            as $criticality
                        ) {
                            $rows[
                                $reason_key
                            ]["criticalities"][
                                $criticality
                            ] =
                                $create_metric();
                        }
                    }


                    return $reason_key;
                }


                $reason_key =
                    "reason_" . $reason_id;


                if (!isset($rows[$reason_key])) {

                    if ($reason_name === "") {

                        $reason_name =
                            "Reason #" . $reason_id;
                    }


                    $rows[$reason_key] = array(
                        "id_need_backup_reason" =>
                            $reason_id,

                        "reason_name" =>
                            $reason_name,

                        /**
                         * Jika tidak ada di daftar active master,
                         * treat sebagai historical/nonaktif.
                         */
                        "is_active" => 0,

                        "criticalities" => array(),

                        "total" =>
                            $create_metric()
                    );


                    foreach (
                        $criticalities
                        as $criticality
                    ) {
                        $rows[
                            $reason_key
                        ]["criticalities"][
                            $criticality
                        ] =
                            $create_metric();
                    }
                }


                return $reason_key;
            };


        /**
         * ============================================================
         * APPLY CURRENT DATA
         * ============================================================
         */
        foreach ($current_data as $row) {

            $reason_key =
                $ensure_reason_row(
                    $row->id_need_backup_reason,
                    $row->need_backup_reason_name
                );


            $criticality =
                trim(
                    (string) $row->criticality
                );


            if (
                !in_array(
                    $criticality,
                    array(
                        "Critical",
                        "Very High",
                        "High",
                        "Medium",
                        "Low"
                    ),
                    true
                )
            ) {
                $criticality =
                    "Others";
            }


            $total_vm =
                (int) $row->total_vm;


            $rows[
                $reason_key
            ]["criticalities"][
                $criticality
            ]["current"] +=
                $total_vm;


            $rows[
                $reason_key
            ]["total"]["current"] +=
                $total_vm;
        }


        /**
         * ============================================================
         * APPLY PREVIOUS DATA
         * ============================================================
         */
        if ($previous_snapshot_id > 0) {

            /**
             * Semua existing row mulai previous = 0.
             */
            foreach ($rows as &$reason_row) {

                foreach (
                    $criticalities
                    as $criticality
                ) {
                    $reason_row[
                        "criticalities"
                    ][
                        $criticality
                    ]["previous"] = 0;
                }

                $reason_row[
                    "total"
                ]["previous"] = 0;
            }

            unset($reason_row);


            foreach ($previous_data as $row) {

                $reason_key =
                    $ensure_reason_row(
                        $row->id_need_backup_reason,
                        $row->need_backup_reason_name
                    );


                /**
                 * Row historical mungkin baru dibuat
                 * setelah proses initialize previous = 0.
                 */
                foreach (
                    $criticalities
                    as $criticality_name
                ) {
                    if (
                        $rows[
                            $reason_key
                        ]["criticalities"][
                            $criticality_name
                        ]["previous"] === null
                    ) {
                        $rows[
                            $reason_key
                        ]["criticalities"][
                            $criticality_name
                        ]["previous"] = 0;
                    }
                }


                if (
                    $rows[
                        $reason_key
                    ]["total"]["previous"] === null
                ) {
                    $rows[
                        $reason_key
                    ]["total"]["previous"] = 0;
                }


                $criticality =
                    trim(
                        (string) $row->criticality
                    );


                if (
                    !in_array(
                        $criticality,
                        array(
                            "Critical",
                            "Very High",
                            "High",
                            "Medium",
                            "Low"
                        ),
                        true
                    )
                ) {
                    $criticality =
                        "Others";
                }


                $total_vm =
                    (int) $row->total_vm;


                $rows[
                    $reason_key
                ]["criticalities"][
                    $criticality
                ]["previous"] +=
                    $total_vm;


                $rows[
                    $reason_key
                ]["total"]["previous"] +=
                    $total_vm;
            }


            /**
             * ========================================================
             * DELTA
             * ========================================================
             */
            foreach ($rows as &$reason_row) {

                foreach (
                    $criticalities
                    as $criticality
                ) {
                    $reason_row[
                        "criticalities"
                    ][
                        $criticality
                    ]["delta"] =
                        $reason_row[
                            "criticalities"
                        ][
                            $criticality
                        ]["current"]
                        -
                        $reason_row[
                            "criticalities"
                        ][
                            $criticality
                        ]["previous"];
                }


                $reason_row[
                    "total"
                ]["delta"] =
                    $reason_row[
                        "total"
                    ]["current"]
                    -
                    $reason_row[
                        "total"
                    ]["previous"];
            }

            unset($reason_row);
        }


        /**
         * ============================================================
         * GRAND TOTAL
         * ============================================================
         */
        $grand_total = array(
            "criticalities" => array(),
            "total" =>
                $create_metric()
        );


        foreach ($criticalities as $criticality) {

            $grand_total[
                "criticalities"
            ][
                $criticality
            ] =
                $create_metric();

            if ($previous_snapshot_id > 0) {

                $grand_total[
                    "criticalities"
                ][
                    $criticality
                ]["previous"] = 0;
            }
        }


        if ($previous_snapshot_id > 0) {

            $grand_total[
                "total"
            ]["previous"] = 0;
        }


        foreach ($rows as $reason_row) {

            foreach (
                $criticalities
                as $criticality
            ) {
                $grand_total[
                    "criticalities"
                ][
                    $criticality
                ]["current"] +=
                    (int) $reason_row[
                        "criticalities"
                    ][
                        $criticality
                    ]["current"];


                if ($previous_snapshot_id > 0) {

                    $grand_total[
                        "criticalities"
                    ][
                        $criticality
                    ]["previous"] +=
                        (int) $reason_row[
                            "criticalities"
                        ][
                            $criticality
                        ]["previous"];
                }
            }


            $grand_total[
                "total"
            ]["current"] +=
                (int) $reason_row[
                    "total"
                ]["current"];


            if ($previous_snapshot_id > 0) {

                $grand_total[
                    "total"
                ]["previous"] +=
                    (int) $reason_row[
                        "total"
                    ]["previous"];
            }
        }


        /**
         * Grand Total Delta.
         */
        if ($previous_snapshot_id > 0) {

            foreach (
                $criticalities
                as $criticality
            ) {
                $grand_total[
                    "criticalities"
                ][
                    $criticality
                ]["delta"] =
                    $grand_total[
                        "criticalities"
                    ][
                        $criticality
                    ]["current"]
                    -
                    $grand_total[
                        "criticalities"
                    ][
                        $criticality
                    ]["previous"];
            }


            $grand_total[
                "total"
            ]["delta"] =
                $grand_total[
                    "total"
                ]["current"]
                -
                $grand_total[
                    "total"
                ]["previous"];
        }


        /**
         * ============================================================
         * SORT REASON
         * ============================================================
         *
         * Active categories dahulu.
         * Setelah itu nama kategori.
         * Belum Ditentukan diletakkan terakhir.
         */
        uasort(
            $rows,
            function ($a, $b) {

                if (
                    $a["reason_name"] ===
                    "Belum Ditentukan"
                ) {
                    return 1;
                }

                if (
                    $b["reason_name"] ===
                    "Belum Ditentukan"
                ) {
                    return -1;
                }


                if (
                    (int) $a["is_active"]
                    !==
                    (int) $b["is_active"]
                ) {
                    return
                        (int) $b["is_active"]
                        <=>
                        (int) $a["is_active"];
                }


                return strcasecmp(
                    $a["reason_name"],
                    $b["reason_name"]
                );
            }
        );


        return array(
            "criticalities" =>
                $criticalities,

            "rows" =>
                array_values($rows),

            "grand_total" =>
                $grand_total
        );
    }

    /**
     * ============================================================
     * REPORT 5
     * TBN BACKUP STATUS BY POWER STATE
     * ============================================================
     *
     * Scope:
     * - Snapshot tertentu
     * - Site TBN
     *
     * Actual Backup Status:
     * - DONE BACKUP
     * - NEED BACKUP
     * - NO NEED BACKUP
     *
     * Power State:
     * - ON  -> Power On
     * - OFF -> Power Off
     *
     * @param int $snapshot_id
     *
     * @return array
     */
    public function get_report_tbn_backup_by_power_state(
        $snapshot_id
    ) {
        $snapshot_id =
            (int) $snapshot_id;


        /**
         * Default result.
         */
        $result = array(
            "done_backup" => array(
                "power_on" => 0,
                "power_off" => 0,
                "total" => 0
            ),

            "need_backup" => array(
                "power_on" => 0,
                "power_off" => 0,
                "total" => 0
            ),

            "no_need_backup" => array(
                "power_on" => 0,
                "power_off" => 0,
                "total" => 0
            ),

            /**
             * Audit field.
             *
             * Tidak perlu ditampilkan pada report.
             * Dipakai untuk mengetahui jika suatu saat
             * ada power_state selain ON / OFF.
             */
            "unmapped_power_state" => 0
        );


        if ($snapshot_id <= 0) {
            return $result;
        }


        /**
         * ============================================================
         * QUERY SNAPSHOT TBN
         * ============================================================
         */
        $row =
            $this->db
                ->select("
                    SUM(
                        CASE
                            WHEN
                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            backup_status,
                                            ''
                                        )
                                    )
                                ) = 'DONE BACKUP'

                                AND

                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            power_state,
                                            ''
                                        )
                                    )
                                ) = 'ON'

                            THEN 1
                            ELSE 0
                        END
                    ) AS done_power_on,


                    SUM(
                        CASE
                            WHEN
                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            backup_status,
                                            ''
                                        )
                                    )
                                ) = 'DONE BACKUP'

                                AND

                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            power_state,
                                            ''
                                        )
                                    )
                                ) = 'OFF'

                            THEN 1
                            ELSE 0
                        END
                    ) AS done_power_off,


                    SUM(
                        CASE
                            WHEN
                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            backup_status,
                                            ''
                                        )
                                    )
                                ) = 'NEED BACKUP'

                                AND

                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            power_state,
                                            ''
                                        )
                                    )
                                ) = 'ON'

                            THEN 1
                            ELSE 0
                        END
                    ) AS need_power_on,


                    SUM(
                        CASE
                            WHEN
                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            backup_status,
                                            ''
                                        )
                                    )
                                ) = 'NEED BACKUP'

                                AND

                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            power_state,
                                            ''
                                        )
                                    )
                                ) = 'OFF'

                            THEN 1
                            ELSE 0
                        END
                    ) AS need_power_off,


                    SUM(
                        CASE
                            WHEN
                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            backup_status,
                                            ''
                                        )
                                    )
                                ) = 'NO NEED BACKUP'

                                AND

                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            power_state,
                                            ''
                                        )
                                    )
                                ) = 'ON'

                            THEN 1
                            ELSE 0
                        END
                    ) AS no_need_power_on,


                    SUM(
                        CASE
                            WHEN
                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            backup_status,
                                            ''
                                        )
                                    )
                                ) = 'NO NEED BACKUP'

                                AND

                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            power_state,
                                            ''
                                        )
                                    )
                                ) = 'OFF'

                            THEN 1
                            ELSE 0
                        END
                    ) AS no_need_power_off,


                    SUM(
                        CASE
                            WHEN
                                UPPER(
                                    TRIM(
                                        COALESCE(
                                            power_state,
                                            ''
                                        )
                                    )
                                ) NOT IN (
                                    'ON',
                                    'OFF'
                                )

                            THEN 1
                            ELSE 0
                        END
                    ) AS unmapped_power_state
                ", false)
                ->from(
                    "replication_backup_report_snapshot_vm"
                )
                ->where(
                    "id_snapshot",
                    $snapshot_id
                )
                ->where(
                    "UPPER(TRIM(COALESCE(id_site, ''))) = 'TBN'",
                    null,
                    false
                )
                ->get()
                ->row();


        if (!$row) {
            return $result;
        }


        /**
         * ============================================================
         * DONE BACKUP
         * ============================================================
         */
        $result[
            "done_backup"
        ]["power_on"] =
            (int) $row->done_power_on;

        $result[
            "done_backup"
        ]["power_off"] =
            (int) $row->done_power_off;

        $result[
            "done_backup"
        ]["total"] =
            $result[
                "done_backup"
            ]["power_on"]
            +
            $result[
                "done_backup"
            ]["power_off"];


        /**
         * ============================================================
         * NEED BACKUP
         * ============================================================
         */
        $result[
            "need_backup"
        ]["power_on"] =
            (int) $row->need_power_on;

        $result[
            "need_backup"
        ]["power_off"] =
            (int) $row->need_power_off;

        $result[
            "need_backup"
        ]["total"] =
            $result[
                "need_backup"
            ]["power_on"]
            +
            $result[
                "need_backup"
            ]["power_off"];


        /**
         * ============================================================
         * NO NEED BACKUP
         * ============================================================
         */
        $result[
            "no_need_backup"
        ]["power_on"] =
            (int) $row->no_need_power_on;

        $result[
            "no_need_backup"
        ]["power_off"] =
            (int) $row->no_need_power_off;

        $result[
            "no_need_backup"
        ]["total"] =
            $result[
                "no_need_backup"
            ]["power_on"]
            +
            $result[
                "no_need_backup"
            ]["power_off"];


        /**
         * Power state selain ON/OFF.
         */
        $result["unmapped_power_state"] =
            (int) $row->unmapped_power_state;


        return $result;
    }

    /**
     * ============================================================
     * EXPORT DATA
     * ============================================================
     *
     * Mengambil data lengkap VM untuk kebutuhan Export Excel.
     *
     * Data utama mengikuti struktur get_vm_detail():
     * - VM Information
     * - Replication & Backup
     * - SLA Rubrik
     *
     * VM Pasangan diambil secara batch dari:
     * virtual_machine_backup_pair
     *
     * Urutan hasil mengikuti urutan ID yang diterima dari browser.
     */
    public function get_export_data($vm_ids = array())
    {
        /**
         * ============================================================
         * NORMALISASI ID VM
         * ============================================================
         */
        $normalized_ids = array();
        $seen_ids = array();

        foreach ((array) $vm_ids as $id_virtual_machine) {

            $id_virtual_machine = (int) $id_virtual_machine;

            if ($id_virtual_machine <= 0) {
                continue;
            }

            if (isset($seen_ids[$id_virtual_machine])) {
                continue;
            }

            $seen_ids[$id_virtual_machine] = true;
            $normalized_ids[] = $id_virtual_machine;
        }

        if (empty($normalized_ids)) {
            return array();
        }


        /**
         * ============================================================
         * DATA UTAMA VM
         * ============================================================
         *
         * Query dibuat mengikuti get_vm_detail()
         * supaya nilai pada Excel konsisten dengan halaman Detail VM.
         */
        $this->db->select("
            vm.id_virtual_machine,
            vm.virtual_machine_name,
            vm.power_state,
            vm.vcenter_name,
            vm.id_site,
            vm.environment,

            GROUP_CONCAT(
                DISTINCT app.application_system_name
                ORDER BY app.application_system_name
                SEPARATOR ', '
            ) AS application_systems,

            CASE MAX(
                CASE
                    WHEN cr.criticality_name = 'Critical' THEN 5
                    WHEN cr.criticality_name = 'Very High' THEN 4
                    WHEN cr.criticality_name = 'High' THEN 3
                    WHEN cr.criticality_name = 'Medium' THEN 2
                    WHEN cr.criticality_name = 'Low' THEN 1
                    ELSE 0
                END
            )
                WHEN 5 THEN 'Critical'
                WHEN 4 THEN 'Very High'
                WHEN 3 THEN 'High'
                WHEN 2 THEN 'Medium'
                WHEN 1 THEN 'Low'
                ELSE 'Others'
            END AS criticality,

            MAX(sla.sla_rubrik) AS sla_rubrik,

            b.status AS backup_status,
            b.status_referensi,
            b.vrep,
            b.rubrik,
            b.db,
            b.ha,
            b.slave,
            b.standby
        ", false);

        $this->db->from(
            $this->table_vm . " vm"
        );


        /**
         * Backup / Replication
         */
        $this->db->join(
            $this->table_backup . " b",
            "b.id_virtual_machine = vm.id_virtual_machine",
            "left"
        );


        /**
         * VM -> Application System
         */
        $this->db->join(
            "relation_table rt",
            "rt.id_virtual_machine = vm.id_virtual_machine",
            "left"
        );


        /**
         * Application System
         */
        $this->db->join(
            "master_application_system app",
            "app.id_application_system = rt.id_application_system",
            "left"
        );


        /**
         * Application System -> Criticality
         */
        $this->db->join(
            "master_criticality cr",
            "cr.id_criticality = app.id_criticality",
            "left"
        );


        /**
         * SLA Rubrik
         *
         * Source dan cara matching dibuat sama
         * dengan get_vm_detail().
         */
        $this->db->join(
            "(
                SELECT
                    s.virtual_machine,
                    vc_sla.vcenter_name,
                    vc_sla.id_site,
                    s.location,
                    MAX(s.sla) AS sla_rubrik
                FROM (
                    SELECT
                        virtual_machine,
                        sla,
                        location
                    FROM live_mount_log_tbn

                    UNION ALL

                    SELECT
                        virtual_machine,
                        sla,
                        location
                    FROM live_mount_log_gti

                    UNION ALL

                    SELECT
                        virtual_machine,
                        sla,
                        location
                    FROM live_mount_log_odc
                ) s

                INNER JOIN master_vcenter vc_sla
                    ON s.location = vc_sla.vcenter_ip

                WHERE vc_sla.id_site IN (
                    'GTI',
                    'TBN',
                    'ODC'
                )

                GROUP BY
                    s.virtual_machine,
                    vc_sla.vcenter_name,
                    vc_sla.id_site,
                    s.location
            ) sla",
            "sla.virtual_machine = vm.virtual_machine_name
            AND sla.vcenter_name = vm.vcenter_name
            AND sla.id_site = vm.id_site",
            "left",
            false
        );


        /**
         * Hanya VM yang dipilih dari hasil filter browser.
         */
        $this->db->where_in(
            "vm.id_virtual_machine",
            $normalized_ids
        );


        /**
         * Hanya VM aktif.
         */
        $this->db->where(
            "vm.is_active",
            1
        );


        $this->db->group_by(
            "vm.id_virtual_machine"
        );


        $vm_rows = $this->db
            ->get()
            ->result_array();


        /**
         * ============================================================
         * INDEX DATA UTAMA BERDASARKAN ID VM
         * ============================================================
         */
        $vm_map = array();

        foreach ($vm_rows as $row) {

            $id_virtual_machine =
                (int) $row["id_virtual_machine"];

            /**
             * Siapkan container seluruh pair type.
             */
            $row["vm_pairs"] = array(
                "DB" => array(),
                "HA" => array(),
                "SLAVE" => array(),
                "STANDBY" => array()
            );

            $vm_map[$id_virtual_machine] = $row;
        }


        /**
         * ============================================================
         * MULTI VM PASANGAN
         * ============================================================
         *
         * Satu query untuk seluruh VM hasil export.
         * Tidak menggunakan get_vm_pairs() di dalam loop
         * supaya tidak terjadi N+1 query.
         */
        if (!empty($vm_map)) {

            $pair_rows = $this->db
                ->select("
                    p.id_virtual_machine,
                    p.pair_type,
                    p.id_vm_pair,
                    vm_pair.virtual_machine_name
                ")
                ->from(
                    "virtual_machine_backup_pair p"
                )
                ->join(
                    $this->table_vm . " vm_pair",
                    "vm_pair.id_virtual_machine = p.id_vm_pair",
                    "left"
                )
                ->where_in(
                    "p.id_virtual_machine",
                    array_keys($vm_map)
                )
                ->where_in(
                    "p.pair_type",
                    array(
                        "DB",
                        "HA",
                        "SLAVE",
                        "STANDBY"
                    )
                )
                ->order_by(
                    "p.id_virtual_machine",
                    "ASC"
                )
                ->order_by(
                    "p.pair_type",
                    "ASC"
                )
                ->order_by(
                    "vm_pair.virtual_machine_name",
                    "ASC"
                )
                ->get()
                ->result_array();


            foreach ($pair_rows as $pair) {

                $id_virtual_machine =
                    (int) $pair["id_virtual_machine"];

                $pair_type =
                    strtoupper(
                        trim(
                            (string) $pair["pair_type"]
                        )
                    );

                if (!isset($vm_map[$id_virtual_machine])) {
                    continue;
                }

                if (
                    !isset(
                        $vm_map[$id_virtual_machine]["vm_pairs"][$pair_type]
                    )
                ) {
                    continue;
                }

                $pair_name =
                    trim(
                        (string) $pair["virtual_machine_name"]
                    );

                if ($pair_name === "") {
                    continue;
                }

                $vm_map[$id_virtual_machine]["vm_pairs"][$pair_type][] =
                    $pair_name;
            }
        }


        /**
         * ============================================================
         * PERTAHANKAN URUTAN DARI BROWSER
         * ============================================================
         *
         * normalized_ids mengikuti urutan DataTables yang dikirim
         * dari halaman Replication & Backup.
         */
        $result = array();

        foreach ($normalized_ids as $id_virtual_machine) {

            if (!isset($vm_map[$id_virtual_machine])) {
                continue;
            }

            $result[] =
                $vm_map[$id_virtual_machine];
        }

        return $result;
    }

    /**
     * ============================================================
     * NEED BACKUP REASON
     * ============================================================
     *
     * Mengambil master kategori reason NEED BACKUP.
     *
     * Default:
     * hanya kategori aktif yang ditampilkan.
     */
    public function get_need_backup_reasons($active_only = true)
    {
        $this->db->select("
            id_need_backup_reason,
            reason_name,
            is_active
        ");

        $this->db->from(
            "master_need_backup_reason"
        );

        if ($active_only) {

            $this->db->where(
                "is_active",
                1
            );
        }

        $this->db->order_by(
            "reason_name",
            "ASC"
        );

        return $this->db
            ->get()
            ->result();
    }

    /**
     * ============================================================
     * ADD NEED BACKUP REASON
     * ============================================================
     *
     * Menambahkan kategori baru.
     *
     * Jika kategori dengan nama yang sama:
     * - masih aktif  -> tidak membuat duplicate
     * - sudah nonaktif -> aktifkan kembali
     */
    public function add_need_backup_reason($reason_name)
    {
        /**
         * Normalisasi input.
         */
        $reason_name =
            trim(
                strip_tags(
                    (string) $reason_name
                )
            );

        if ($reason_name === "") {
            return array(
                "success" => false,
                "status" => "invalid"
            );
        }


        /**
         * Maksimal mengikuti VARCHAR(150).
         */
        $reason_length =
            function_exists("mb_strlen")
                ? mb_strlen(
                    $reason_name,
                    "UTF-8"
                )
                : strlen(
                    $reason_name
                );

        if ($reason_length > 150) {
            return array(
                "success" => false,
                "status" => "too_long"
            );
        }


        /**
         * ============================================================
         * CEK KATEGORI EXISTING
         * ============================================================
         *
         * Pengecekan dibuat case-insensitive supaya:
         *
         * "Menunggu Replikasi DB"
         * dan
         * "menunggu replikasi db"
         *
         * tidak menjadi dua kategori berbeda.
         */
        $reason_key =
            function_exists("mb_strtolower")
                ? mb_strtolower(
                    $reason_name,
                    "UTF-8"
                )
                : strtolower(
                    $reason_name
                );

        $existing =
            $this->db
                ->select("
                    id_need_backup_reason,
                    reason_name,
                    is_active
                ")
                ->from(
                    "master_need_backup_reason"
                )
                ->where(
                    "LOWER(TRIM(reason_name)) = " .
                    $this->db->escape(
                        $reason_key
                    ),
                    null,
                    false
                )
                ->limit(1)
                ->get()
                ->row();


        /**
         * Kategori sudah ada.
         */
        if ($existing) {

            /**
             * Kalau masih aktif, jangan duplicate.
             */
            if ((int) $existing->is_active === 1) {

                return array(
                    "success" => false,
                    "status" => "exists",
                    "id_need_backup_reason" =>
                        (int) $existing->id_need_backup_reason
                );
            }


            /**
             * Kalau sebelumnya pernah dihapus/nonaktif,
             * aktifkan kembali.
             */
            $this->db
                ->where(
                    "id_need_backup_reason",
                    (int) $existing->id_need_backup_reason
                )
                ->update(
                    "master_need_backup_reason",
                    array(
                        "reason_name" => $reason_name,
                        "is_active" => 1
                    )
                );


            if (!$this->db->affected_rows()) {

                return array(
                    "success" => false,
                    "status" => "error"
                );
            }


            return array(
                "success" => true,
                "status" => "reactivated",
                "id_need_backup_reason" =>
                    (int) $existing->id_need_backup_reason
            );
        }


        /**
         * ============================================================
         * INSERT KATEGORI BARU
         * ============================================================
         */
        $inserted =
            $this->db->insert(
                "master_need_backup_reason",
                array(
                    "reason_name" => $reason_name,
                    "is_active" => 1
                )
            );


        if (!$inserted) {

            return array(
                "success" => false,
                "status" => "error"
            );
        }


        return array(
            "success" => true,
            "status" => "created",
            "id_need_backup_reason" =>
                (int) $this->db->insert_id()
        );
    }


    /**
     * ============================================================
     * DELETE / DEACTIVATE NEED BACKUP REASON
     * ============================================================
     *
     * Tidak melakukan DELETE permanen.
     *
     * Kategori hanya diubah menjadi:
     * is_active = 0
     *
     * supaya VM yang pernah menggunakan kategori tersebut
     * tetap mempunyai history/reference.
     */
    public function deactivate_need_backup_reason(
        $id_need_backup_reason
    ) {
        $id_need_backup_reason =
            (int) $id_need_backup_reason;


        if ($id_need_backup_reason <= 0) {

            return array(
                "success" => false,
                "status" => "invalid"
            );
        }


        /**
         * Pastikan kategori memang ada.
         */
        $reason =
            $this->db
                ->select("
                    id_need_backup_reason,
                    is_active
                ")
                ->from(
                    "master_need_backup_reason"
                )
                ->where(
                    "id_need_backup_reason",
                    $id_need_backup_reason
                )
                ->limit(1)
                ->get()
                ->row();


        if (!$reason) {

            return array(
                "success" => false,
                "status" => "not_found"
            );
        }


        /**
         * Sudah nonaktif.
         */
        if ((int) $reason->is_active === 0) {

            return array(
                "success" => true,
                "status" => "already_inactive"
            );
        }


        /**
         * Soft delete.
         */
        $updated =
            $this->db
                ->where(
                    "id_need_backup_reason",
                    $id_need_backup_reason
                )
                ->update(
                    "master_need_backup_reason",
                    array(
                        "is_active" => 0
                    )
                );


        if (!$updated) {

            return array(
                "success" => false,
                "status" => "error"
            );
        }


        return array(
            "success" => true,
            "status" => "deactivated"
        );
    }

    /**
     * ============================================================
     * COUNT VM BY NEED BACKUP REASON
     * ============================================================
     *
     * Menghitung jumlah VM aktif yang saat ini menggunakan
     * kategori Need Backup tertentu.
     *
     * Dibatasi untuk:
     * - Site GTI
     * - Status NEED BACKUP
     */
    public function count_vm_by_need_backup_reason(
        $id_need_backup_reason
    ) {
        $id_need_backup_reason =
            (int) $id_need_backup_reason;

        if ($id_need_backup_reason <= 0) {
            return 0;
        }

        return (int) $this->db
            ->from(
                $this->table_backup . " b"
            )
            ->join(
                $this->table_vm . " vm",
                "vm.id_virtual_machine = b.id_virtual_machine",
                "inner"
            )
            ->where(
                "b.id_need_backup_reason",
                $id_need_backup_reason
            )
            ->where(
                "vm.id_site",
                "GTI"
            )
            ->where(
                "vm.is_active",
                1
            )
            ->where(
                "UPPER(TRIM(COALESCE(b.status, ''))) = 'NEED BACKUP'",
                null,
                false
            )
            ->count_all_results();
    }

    /**
     * ============================================================
     * VM OPTIONS
     * List VM aktif untuk pilihan pasangan pada halaman Edit
     * ============================================================
     */
    public function get_vm_options($exclude_id = null)
    {
        $this->db->select("
            id_virtual_machine,
            virtual_machine_name,
            id_site,
            vcenter_name,
            environment
        ");

        $this->db->from($this->table_vm);

        $this->db->where(
            "is_active",
            1
        );

        /**
         * VM yang sedang diedit tidak boleh
         * menjadi pasangan dirinya sendiri.
         */
        if (!empty($exclude_id)) {
            $this->db->where(
                "id_virtual_machine !=",
                $exclude_id
            );
        }

        $this->db->order_by(
            "virtual_machine_name",
            "ASC"
        );

        return $this->db->get()->result();
    }

    /**
     * ============================================================
     * SAVE EDIT CONFIGURATION
     * ============================================================
     */
    public function save_edit_configuration(
        $id_virtual_machine,
        $status_referensi,
        $db,
        $ha,
        $slave,
        $standby,
        $id_need_backup_reason,
        $pairs = array()
    ) {
        $id_virtual_machine = (int) $id_virtual_machine;

        /**
         * Validasi VM utama.
         */
        if ($id_virtual_machine <= 0) {
            return false;
        }

        /**
         * ============================================================
         * VALIDASI VM + KONTEKS NEED BACKUP
         * ============================================================
         *
         * Reason ditentukan berdasarkan:
         * - Site VM dari master_virtual_machine
         * - actual status dari virtual_machine_backup
         *
         * PENTING:
         * status_referensi TIDAK digunakan untuk menentukan
         * kewajiban Need Backup Reason.
         */
        $vm_context = $this->db
            ->select("
                vm.id_site,
                b.status AS backup_status
            ")
            ->from(
                $this->table_vm . " vm"
            )
            ->join(
                $this->table_backup . " b",
                "b.id_virtual_machine = vm.id_virtual_machine",
                "left"
            )
            ->where(
                "vm.id_virtual_machine",
                $id_virtual_machine
            )
            ->where(
                "vm.is_active",
                1
            )
            ->limit(1)
            ->get()
            ->row();

        if (!$vm_context) {
            return false;
        }


        $id_site = strtoupper(
            trim(
                (string) $vm_context->id_site
            )
        );

        $backup_status = strtoupper(
            trim(
                (string) $vm_context->backup_status
            )
        );


        /**
         * Reason hanya WAJIB untuk:
         *
         * Site   = GTI
         * Status = NEED BACKUP
         */
        $is_need_backup_reason_required =
            $id_site === "GTI" &&
            $backup_status === "NEED BACKUP";

        /**
         * ============================================================
         * VALIDASI NEED BACKUP REASON
         * ============================================================
         */
        if ($is_need_backup_reason_required) {

            $id_need_backup_reason =
                (int) $id_need_backup_reason;

            /**
             * GTI + NEED BACKUP wajib mempunyai reason.
             */
            if ($id_need_backup_reason <= 0) {
                return false;
            }


            /**
             * Reason yang dipilih harus:
             * - benar-benar ada
             * - masih aktif
             */
            $reason_exists = $this->db
                ->where(
                    "id_need_backup_reason",
                    $id_need_backup_reason
                )
                ->where(
                    "is_active",
                    1
                )
                ->count_all_results(
                    "master_need_backup_reason"
                );

            if ($reason_exists <= 0) {
                return false;
            }
        }

        /**
         * Validasi Status Referensi.
         */
        $status_referensi = strtoupper(
            trim((string) $status_referensi)
        );

        $allowed_status = array(
            "NEED BACKUP",
            "NO NEED BACKUP"
        );

        if (!in_array($status_referensi, $allowed_status, true)) {
            return false;
        }

        /**
         * Validasi flag manual.
         *
         * Hanya boleh:
         * YES = 1
         * NO  = 0
         */
        if (
            !in_array((string) $db, array("0", "1"), true) ||
            !in_array((string) $ha, array("0", "1"), true) ||
            !in_array((string) $slave, array("0", "1"), true) ||
            !in_array((string) $standby, array("0", "1"), true)
        ) {
            return false;
        }

        $db = (int) $db;
        $ha = (int) $ha;
        $slave = (int) $slave;
        $standby = (int) $standby;

        /**
         * ============================================================
         * NORMALISASI VM PASANGAN
         * ============================================================
         */
        $allowed_pair_types = array(
            "DB",
            "HA",
            "SLAVE",
            "STANDBY"
        );

        $pair_rows = array();
        $all_pair_ids = array();

        foreach ($allowed_pair_types as $pair_type) {

            $pair_ids = isset($pairs[$pair_type])
                && is_array($pairs[$pair_type])
                    ? $pairs[$pair_type]
                    : array();

            foreach ($pair_ids as $id_vm_pair) {

                $id_vm_pair = (int) $id_vm_pair;

                /**
                 * Abaikan ID invalid dan VM dirinya sendiri.
                 */
                if (
                    $id_vm_pair <= 0 ||
                    $id_vm_pair === $id_virtual_machine
                ) {
                    continue;
                }

                /**
                 * Hindari pasangan duplicate
                 * pada pair type yang sama.
                 */
                $unique_key =
                    $pair_type . "_" . $id_vm_pair;

                if (isset($pair_rows[$unique_key])) {
                    continue;
                }

                $pair_rows[$unique_key] = array(
                    "id_virtual_machine" => $id_virtual_machine,
                    "pair_type" => $pair_type,
                    "id_vm_pair" => $id_vm_pair
                );

                $all_pair_ids[$id_vm_pair] = $id_vm_pair;
            }
        }

        /**
         * ============================================================
         * VALIDASI VM PASANGAN
         * ============================================================
         *
         * Semua pasangan harus:
         * - ada di master_virtual_machine
         * - masih aktif
         */
        if (!empty($all_pair_ids)) {

            $valid_vm = $this->db
                ->select("id_virtual_machine")
                ->from($this->table_vm)
                ->where("is_active", 1)
                ->where_in(
                    "id_virtual_machine",
                    array_values($all_pair_ids)
                )
                ->get()
                ->result();

            $valid_ids = array();

            foreach ($valid_vm as $vm) {
                $valid_ids[(int) $vm->id_virtual_machine] = true;
            }

            foreach ($all_pair_ids as $id_vm_pair) {

                if (!isset($valid_ids[(int) $id_vm_pair])) {
                    return false;
                }
            }
        }

        /**
         * ============================================================
         * TRANSACTION
         * ============================================================
         */
        $this->db->trans_begin();

        /**
         * Cek apakah VM sudah mempunyai row
         * virtual_machine_backup.
         */
        $backup_exists = $this->db
            ->where(
                "id_virtual_machine",
                $id_virtual_machine
            )
            ->count_all_results($this->table_backup);

        /**
         * Data yang BOLEH diubah manual dari dashboard.
         *
         * PENTING:
         * status, vrep, rubrik TIDAK disentuh.
         */
        $backup_data = array(
            "status_referensi" => $status_referensi,
            "db" => $db,
            "ha" => $ha,
            "slave" => $slave,
            "standby" => $standby
        );

        /**
         * Reason hanya diubah ketika VM saat ini memang:
         *
         * GTI + NEED BACKUP
         *
         * Untuk kondisi lainnya field ini TIDAK disentuh,
         * sehingga reason historis tidak otomatis hilang
         * hanya karena status dari external script berubah.
         */
        if ($is_need_backup_reason_required) {

            $backup_data["id_need_backup_reason"] =
                $id_need_backup_reason;
        }

        if ($backup_exists > 0) {

            $this->db
                ->where(
                    "id_virtual_machine",
                    $id_virtual_machine
                )
                ->update(
                    $this->table_backup,
                    $backup_data
                );

        } else {

            $backup_data["id_virtual_machine"] =
                $id_virtual_machine;

            $this->db->insert(
                $this->table_backup,
                $backup_data
            );
        }

        /**
         * ============================================================
         * SYNC VM PASANGAN
         * ============================================================
         *
         * Hapus pasangan existing VM tersebut,
         * kemudian insert ulang sesuai form Edit.
         */
        $this->db
            ->where(
                "id_virtual_machine",
                $id_virtual_machine
            )
            ->delete(
                "virtual_machine_backup_pair"
            );

        if (!empty($pair_rows)) {

            $this->db->insert_batch(
                "virtual_machine_backup_pair",
                array_values($pair_rows)
            );
        }

        /**
         * ============================================================
         * TRANSACTION RESULT
         * ============================================================
         */
        if ($this->db->trans_status() === false) {

            $this->db->trans_rollback();

            return false;
        }

        $this->db->trans_commit();

        return true;
    }
}