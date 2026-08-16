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
            "done_replication" => (int) ($row["done_replication"] ?? 0),
            "need_replication" => (int) ($row["need_replication"] ?? 0),
            "no_need_replication" => (int) ($row["no_need_replication"] ?? 0),

            "done_backup" => (int) ($row["done_backup"] ?? 0),
            "need_backup" => (int) ($row["need_backup"] ?? 0),
            "no_need_backup" => (int) ($row["no_need_backup"] ?? 0),

            "vrep" => (int) ($row["vrep"] ?? 0),
            "rubrik" => (int) ($row["rubrik"] ?? 0),
            "ha" => (int) ($row["ha"] ?? 0),
            "db" => (int) ($row["db_count"] ?? 0),
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

        $this->db->join(
            $this->table_backup . " b",
            "b.id_virtual_machine = vm.id_virtual_machine",
            "left"
        );

        $this->db->join(
            "relation_table rt",
            "rt.id_virtual_machine = vm.id_virtual_machine",
            "left"
        );

        $this->db->join(
            "master_application_system app",
            "app.id_application_system = rt.id_application_system",
            "left"
        );

        $this->db->where("vm.is_active", 1);

        $this->db->group_by("vm.id_virtual_machine");

        $this->db->order_by("vm.virtual_machine_name", "ASC");

        return $this->db->get()->result();
    }
}