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
                    AND LOWER(TRIM(b.status)) = 'done'
                    THEN 1 ELSE 0
                END) AS done_replication,

                SUM(CASE
                    WHEN vm.id_site = 'GTI'
                    AND LOWER(TRIM(b.status)) = 'need'
                    THEN 1 ELSE 0
                END) AS need_replication,

                SUM(CASE
                    WHEN vm.id_site = 'GTI'
                    AND LOWER(TRIM(b.status)) = 'no need'
                    THEN 1 ELSE 0
                END) AS no_need_replication,

                SUM(CASE
                    WHEN vm.id_site = 'TBN'
                    AND LOWER(TRIM(b.status)) = 'done'
                    THEN 1 ELSE 0
                END) AS done_backup,

                SUM(CASE
                    WHEN vm.id_site = 'TBN'
                    AND LOWER(TRIM(b.status)) = 'need'
                    THEN 1 ELSE 0
                END) AS need_backup,

                SUM(CASE
                    WHEN vm.id_site = 'TBN'
                    AND LOWER(TRIM(b.status)) = 'no need'
                    THEN 1 ELSE 0
                END) AS no_need_backup,

                SUM(CASE
                    WHEN b.vrep = 1
                    THEN 1 ELSE 0
                END) AS vrep,

                SUM(CASE
                    WHEN vm.id_site = 'GTI'
                    AND b.rubrik = 1
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

            b.status AS backup_status,
            b.status_referensi,
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
}