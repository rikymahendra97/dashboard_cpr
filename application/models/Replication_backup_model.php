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
        $pairs = array()
    ) {
        $id_virtual_machine = (int) $id_virtual_machine;

        /**
         * Validasi VM utama.
         */
        if ($id_virtual_machine <= 0) {
            return false;
        }

        $vm_exists = $this->db
            ->where("id_virtual_machine", $id_virtual_machine)
            ->where("is_active", 1)
            ->count_all_results($this->table_vm);

        if ($vm_exists <= 0) {
            return false;
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