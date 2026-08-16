<?php
defined("BASEPATH") or exit("No direct script access allowed");

/**
 * ========================================================================
 * File Name    : Vm_incident_model.php
 * Modul        : VM Utilization Incident
 * Purpose      : Operasi basis data (CRUD, DataTables, KPI Summary)
 * Architecture : Enterprise CP-05 (Memory-Optimized Pure SQL Aggregation)
 * ========================================================================
 */
class Vm_incident_model extends CI_Model
{
    protected $table = "trx_vm_utilization_incident";

    protected $column_order = [
        null,
        "inc.created_at",
        "inc.no_tiket_insiden",
        "m.virtual_machine_name",
        "inc.tipe_insiden",
        "crit.criticality_name",
        "inc.sla_deadline",
        "inc.status_insiden",
        null,
    ];
    protected $column_search = [
        "inc.no_tiket_insiden",
        "m.virtual_machine_name",
        "m.ip_address",
        "inc.tipe_insiden",
        "crit.criticality_name",
    ];
    protected $order = ["inc.created_at" => "DESC"];

    private function _get_datatables_query()
    {
        $this->db->select(
            "
            inc.id_incident, inc.created_at, inc.no_tiket_insiden, inc.link_tiket, inc.tipe_insiden,
            inc.disk_drive_detail, inc.status_insiden, inc.sla_deadline, inc.metrik_tercatat,
            COALESCE(inc.snapshot_vm_name, m.virtual_machine_name, 'VM Dihapus') AS nama_vm,
            COALESCE(m.ip_address, inc.snapshot_ip_address, '-') AS ip_vm,
            m.guest_os,
            COALESCE(fu_summary.total_fu, 0) AS total_fu,
            fu_summary.last_fu_date,
            COALESCE(crit.criticality_name, 'Unrated') AS kritikalitas
        ",
            false,
        );

        $this->db->from($this->table . " inc");
        $this->db->join(
            "master_virtual_machine m",
            "m.id_virtual_machine = inc.id_virtual_machine",
            "left",
        );
        $this->db->join(
            "relation_table rel",
            "rel.id_virtual_machine = m.id_virtual_machine",
            "left",
        );
        $this->db->join(
            "master_application_system app",
            "app.id_application_system = rel.id_application_system",
            "left",
        );
        $this->db->join(
            "master_criticality crit",
            "crit.id_criticality = app.id_criticality",
            "left",
        );

        $this->db->join(
            "(SELECT id_incident, COUNT(id_fu) as total_fu, MAX(created_at) as last_fu_date FROM trx_vm_incident_fu GROUP BY id_incident) fu_summary",
            "fu_summary.id_incident = inc.id_incident",
            "left",
        );

        $filter_kpi = $this->input->post("filter_kpi");
        if (!empty($filter_kpi)) {
            $open_status = ["Open Tiket", "Review by Owner", "Apply Solution by Owner"];

            if ($filter_kpi === "pending") {
                $this->db->where("inc.status_insiden", "Open Tiket");
            } elseif ($filter_kpi === "in_progress") {
                $this->db->where_in("inc.status_insiden", [
                    "Review by Owner",
                    "Apply Solution by Owner",
                ]);
            } elseif ($filter_kpi === "selesai") {
                $this->db->where("inc.status_insiden", "Done/Close");
            } elseif ($filter_kpi === "kurang_7") {
                $this->db->where_in("inc.status_insiden", $open_status);
                $this->db->where("DATEDIFF(NOW(), inc.created_at) <=", 7, false);
            } elseif ($filter_kpi === "lewat_7") {
                $this->db->where_in("inc.status_insiden", $open_status);
                $this->db->where("DATEDIFF(NOW(), inc.created_at) >", 7, false);
                $this->db->where("DATEDIFF(NOW(), inc.created_at) <=", 14, false);
            } elseif ($filter_kpi === "lewat_14") {
                $this->db->where_in("inc.status_insiden", $open_status);
                $this->db->where("DATEDIFF(NOW(), inc.created_at) >", 14, false);
            }
        }

        $i = 0;
        foreach ($this->column_search as $item) {
            if (!empty($_POST["search"]["value"])) {
                if ($i === 0) {
                    $this->db->group_start();
                    $this->db->like($item, $_POST["search"]["value"]);
                    $this->db->or_like("inc.snapshot_vm_name", $_POST["search"]["value"]);
                    $this->db->or_like("inc.snapshot_ip_address", $_POST["search"]["value"]);
                } else {
                    $this->db->or_like($item, $_POST["search"]["value"]);
                }
                if (count($this->column_search) - 1 == $i) {
                    $this->db->group_end();
                }
            }
            $i++;
        }

        if (isset($_POST["order"])) {
            $this->db->order_by(
                $this->column_order[$_POST["order"]["0"]["column"]],
                $_POST["order"]["0"]["dir"],
            );
        } elseif (isset($this->order)) {
            $this->db->order_by(
                "CASE WHEN inc.status_insiden = 'Open Tiket' THEN 1 ELSE 2 END",
                "ASC",
                false,
            );
            foreach ($this->order as $key => $val) {
                $this->db->order_by($key, $val);
            }
        }
    }

    public function get_datatables()
    {
        $this->_get_datatables_query();
        if (isset($_POST["length"]) && $_POST["length"] != -1) {
            $this->db->limit($_POST["length"], $_POST["start"]);
        }
        return $this->db->get()->result_array();
    }

    public function count_filtered()
    {
        $this->_get_datatables_query();
        $query_string = $this->db->get_compiled_select();
        $count_query = $this->db->query(
            "SELECT COUNT(*) as total FROM ({$query_string}) AS combined_table",
        );
        return $count_query->row()->total;
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    /**
     * [ENTERPRISE FIX]: MEMORY LEAK PREVENTION (O(1) Memory Architecture)
     * Mengganti penarikan PHP ->result() dengan MySQL Pure Aggregation
     */
    public function get_kpi_summary(): array
    {
        $sql = "
            SELECT
                SUM(CASE WHEN status_insiden = 'Open Tiket' THEN 1 ELSE 0 END) AS open,
                SUM(CASE WHEN status_insiden IN ('Review by Owner', 'Apply Solution by Owner') THEN 1 ELSE 0 END) AS wip,
                SUM(CASE WHEN status_insiden = 'Done/Close' THEN 1 ELSE 0 END) AS closed,
                SUM(CASE WHEN status_insiden != 'Done/Close' AND DATEDIFF(NOW(), created_at) <= 7 THEN 1 ELSE 0 END) AS kurang_7,
                SUM(CASE WHEN status_insiden != 'Done/Close' AND DATEDIFF(NOW(), created_at) > 7 AND DATEDIFF(NOW(), created_at) <= 14 THEN 1 ELSE 0 END) AS lewat_7,
                SUM(CASE WHEN status_insiden != 'Done/Close' AND DATEDIFF(NOW(), created_at) > 14 THEN 1 ELSE 0 END) AS lewat_14
            FROM {$this->table}
        ";

        $result = $this->db->query($sql)->row_array();

        return [
            "open" => (int) ($result["open"] ?? 0),
            "wip" => (int) ($result["wip"] ?? 0),
            "closed" => (int) ($result["closed"] ?? 0),
            "kurang_7" => (int) ($result["kurang_7"] ?? 0),
            "lewat_7" => (int) ($result["lewat_7"] ?? 0),
            "lewat_14" => (int) ($result["lewat_14"] ?? 0),
        ];
    }

    public function get_incident_detail(string $id_incident)
    {
        $this->db->select(
            "
            inc.*,
            COALESCE(inc.snapshot_vm_name, vm.virtual_machine_name, 'VM Dihapus') AS nama_vm,
            COALESCE(vm.ip_address, inc.snapshot_ip_address, '-') AS ip_vm,
            vm.cpu_count, vm.memory_mb, vm.provisioned_gb, vm.environment,
            u_p.nama_lengkap AS nama_pelapor,
            u_a.nama_lengkap AS nama_assignee
        ",
            false,
        );
        $this->db->from("{$this->table} inc");
        $this->db->join(
            "master_virtual_machine vm",
            "vm.id_virtual_machine = inc.id_virtual_machine",
            "left",
        );
        $this->db->join("master_user u_p", "u_p.id_user = inc.id_pelapor", "left");
        $this->db->join("master_user u_a", "u_a.id_user = inc.id_assignee", "left");
        $this->db->where("inc.id_incident", (int) $id_incident);
        return $this->db->get()->row_array();
    }

    public function insert_incident(array $data)
    {
        $this->db->trans_start();
        $created_at = $data["created_at"] ?? date("Y-m-d H:i:s");
        $urgensi = $data["tingkat_urgensi"] ?? "Medium";

        $data["created_at"] = $created_at;
        $data["sla_deadline"] = calculate_sla_deadline($created_at, $urgensi);

        $this->db->insert($this->table, $data);
        $insert_id = $this->db->insert_id();
        $this->db->trans_complete();

        return $this->db->trans_status() === false ? false : $insert_id;
    }

    public function update_incident_workflow(string $id_incident, array $payload)
    {
        $this->db->trans_start();
        if (isset($payload["status_insiden"]) && $payload["status_insiden"] === "Done/Close") {
            $payload["resolved_at"] = date("Y-m-d H:i:s");
        }
        $this->db->where("id_incident", (int) $id_incident);
        $this->db->update($this->table, $payload);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function update_incident(string $id_incident, array $payload)
    {
        $this->db->trans_start();
        $this->db->where("id_incident", (int) $id_incident);
        $this->db->update($this->table, $payload);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function insert_fu_and_update_incident(
        array $data_fu,
        array $data_incident,
        string $id_incident,
    ) {
        $this->db->trans_start();
        $this->db->insert("trx_vm_incident_fu", $data_fu);
        $this->db->where("id_incident", (int) $id_incident);
        $this->db->update($this->table, $data_incident);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function get_detail_incident(string $id_incident)
    {
        $this->db->select(
            "
            inc.*,
            COALESCE(inc.snapshot_vm_name, vm.virtual_machine_name, 'VM Dihapus') AS nama_vm,
            COALESCE(vm.ip_address, inc.snapshot_ip_address, '-') AS ip_vm,
            vm.guest_os, vm.vmware_tools_status, vm.cpu_count, vm.memory_mb, vm.provisioned_gb, vm.environment,
            COALESCE(crit.criticality_name, inc.tingkat_urgensi, 'Unrated') AS kritikalitas,
            u_p.nama_lengkap AS nama_pelapor
        ",
            false,
        );

        $this->db->from("trx_vm_utilization_incident inc");
        $this->db->join(
            "master_virtual_machine vm",
            "vm.id_virtual_machine = inc.id_virtual_machine",
            "left",
        );
        $this->db->join("master_user u_p", "u_p.id_user = inc.id_pelapor", "left");
        $this->db->join(
            "relation_table rel",
            "rel.id_virtual_machine = vm.id_virtual_machine",
            "left",
        );
        $this->db->join(
            "master_application_system app",
            "app.id_application_system = rel.id_application_system",
            "left",
        );
        $this->db->join(
            "master_criticality crit",
            "crit.id_criticality = app.id_criticality",
            "left",
        );
        $this->db->where("inc.id_incident", (int) $id_incident);
        return $this->db->get()->row_array();
    }

    public function get_fu_history(string $id_incident)
    {
        $this->db->select(
            "fu.*, t.team_name, t.team_code, t.pic_name, t.pic_contact, u.nama_lengkap as nama_engineer",
        );
        $this->db->from("trx_vm_incident_fu fu");
        $this->db->join("master_team t", "t.id_team = fu.id_team_tujuan", "left");
        $this->db->join("master_user u", "u.id_user = fu.id_user", "left");
        $this->db->where("fu.id_incident", (int) $id_incident);
        $this->db->order_by("fu.created_at", "ASC");
        return $this->db->get()->result_array();
    }

    public function get_master_team_list()
    {
        $this->db->select("id_team, team_name, team_code, pic_name, pic_contact");
        $this->db->order_by("team_name", "ASC");
        return $this->db->get("master_team")->result_array();
    }

    public function insert_follow_up_and_status(
        array $data_fu,
        string $status_baru,
        string $id_user,
    ) {
        $this->db->trans_start();
        $this->db->insert("trx_vm_incident_fu", $data_fu);
        if ($status_baru !== "No Change") {
            $payload_incident = ["status_insiden" => $status_baru, "id_assignee" => $id_user];
            if ($status_baru === "Done/Close") {
                $payload_incident["resolved_at"] = date("Y-m-d H:i:s");
                $payload_incident["catatan_resolusi"] =
                    "Diselesaikan Manual tanpa Restart VM. Keterangan akhir: " .
                    $data_fu["catatan_fu"];
            }
            $this->db->where("id_incident", $data_fu["id_incident"]);
            $this->db->update("trx_vm_utilization_incident", $payload_incident);
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function delete_incident(string $incident_id)
    {
        $this->db->trans_start();
        $this->db->where("id_incident", (int) $incident_id)->delete("trx_vm_incident_fu");
        $this->db->where("id_incident", (int) $incident_id)->delete($this->table);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function get_data_export($startDate = null, $endDate = null)
    {
        $this->db->select(
            "
            inc.id_incident, inc.no_tiket_insiden, inc.created_at, inc.tipe_insiden,
            inc.disk_drive_detail, inc.deskripsi_insiden, inc.metrik_tercatat, inc.tingkat_urgensi,
            inc.sla_deadline, inc.status_insiden, inc.resolved_at, inc.catatan_resolusi,
            COALESCE(inc.snapshot_vm_name, vm.virtual_machine_name, 'VM Dihapus') AS nama_vm,
            COALESCE(vm.ip_address, inc.snapshot_ip_address, '-') AS ip_vm,
            COALESCE(vm.guest_os, '-') AS guest_os,
            COALESCE(app.application_system_name, 'Unbound/No App') AS nama_aplikasi,
            COALESCE(crit.criticality_name, 'Unrated') AS kritikalitas,
            COALESCE(u_p.nama_lengkap, 'Sistem') AS nama_pelapor,
            COALESCE(fu.total_fu, 0) AS total_fu,
            fu.last_fu_date, COALESCE(fu.last_action, 'None') AS last_action
        ",
            false,
        );

        $this->db->from("{$this->table} inc");
        $this->db->join(
            "master_virtual_machine vm",
            "vm.id_virtual_machine = inc.id_virtual_machine",
            "left",
        );
        $this->db->join("master_user u_p", "u_p.id_user = inc.id_pelapor", "left");
        $this->db->join(
            "relation_table rel",
            "rel.id_virtual_machine = vm.id_virtual_machine",
            "left",
        );
        $this->db->join(
            "master_application_system app",
            "app.id_application_system = rel.id_application_system",
            "left",
        );
        $this->db->join(
            "master_criticality crit",
            "crit.id_criticality = app.id_criticality",
            "left",
        );

        $this->db->join(
            '(
            SELECT f1.id_incident, COUNT(f1.id_fu) as total_fu, MAX(f1.created_at) as last_fu_date,
                   (SELECT f2.aksi_tindakan FROM trx_vm_incident_fu f2 WHERE f2.id_incident = f1.id_incident ORDER BY f2.created_at DESC, f2.id_fu DESC LIMIT 1) as last_action
            FROM trx_vm_incident_fu f1 GROUP BY f1.id_incident
        ) fu',
            "fu.id_incident = inc.id_incident",
            "left",
        );

        if (!empty($startDate) && !empty($endDate)) {
            $safe_start = $this->db->escape($startDate);
            $safe_end = $this->db->escape($endDate);
            $this->db->where("DATE(inc.created_at) >= $safe_start", null, false);
            $this->db->where("DATE(inc.created_at) <= $safe_end", null, false);
        }
        $this->db->order_by("inc.created_at", "DESC");
        return $this->db->get()->result_array();
    }

    // ========================================================================
    // [ENTERPRISE FEATURE]: RAM-Safe Unbuffered Streaming Query
    // ========================================================================
    public function get_data_export_query($startDate = null, $endDate = null)
    {
        $this->db->select(
            "
            inc.id_incident, inc.no_tiket_insiden, inc.created_at, inc.tipe_insiden,
            inc.disk_drive_detail, inc.deskripsi_insiden, inc.metrik_tercatat, inc.tingkat_urgensi,
            inc.sla_deadline, inc.status_insiden, inc.resolved_at, inc.catatan_resolusi,
            COALESCE(inc.snapshot_vm_name, vm.virtual_machine_name, 'VM Dihapus') AS nama_vm,
            COALESCE(vm.ip_address, inc.snapshot_ip_address, '-') AS ip_vm,
            COALESCE(vm.guest_os, '-') AS guest_os,
            COALESCE(app.application_system_name, 'Unbound/No App') AS nama_aplikasi,
            COALESCE(crit.criticality_name, 'Unrated') AS kritikalitas,
            COALESCE(u_p.nama_lengkap, 'Sistem') AS nama_pelapor,
            COALESCE(fu.total_fu, 0) AS total_fu,
            fu.last_fu_date, COALESCE(fu.last_action, 'None') AS last_action
        ",
            false,
        );

        $this->db->from("{$this->table} inc");
        $this->db->join(
            "master_virtual_machine vm",
            "vm.id_virtual_machine = inc.id_virtual_machine",
            "left",
        );
        $this->db->join("master_user u_p", "u_p.id_user = inc.id_pelapor", "left");
        $this->db->join(
            "relation_table rel",
            "rel.id_virtual_machine = vm.id_virtual_machine",
            "left",
        );
        $this->db->join(
            "master_application_system app",
            "app.id_application_system = rel.id_application_system",
            "left",
        );
        $this->db->join(
            "master_criticality crit",
            "crit.id_criticality = app.id_criticality",
            "left",
        );

        $this->db->join(
            '(
            SELECT f1.id_incident, COUNT(f1.id_fu) as total_fu, MAX(f1.created_at) as last_fu_date,
                   (SELECT f2.aksi_tindakan FROM trx_vm_incident_fu f2 WHERE f2.id_incident = f1.id_incident ORDER BY f2.created_at DESC, f2.id_fu DESC LIMIT 1) as last_action
            FROM trx_vm_incident_fu f1 GROUP BY f1.id_incident
        ) fu',
            "fu.id_incident = inc.id_incident",
            "left",
        );

        if (!empty($startDate) && !empty($endDate)) {
            $safe_start = $this->db->escape($startDate);
            $safe_end = $this->db->escape($endDate);
            $this->db->where("DATE(inc.created_at) >= $safe_start", null, false);
            $this->db->where("DATE(inc.created_at) <= $safe_end", null, false);
        }
        $this->db->order_by("inc.created_at", "DESC");

        // MENGEMBALIKAN OBJECT QUERY, BUKAN RESULT ARRAY (UNTUK DILAKUKAN STREAMING)
        return $this->db->get();
    }
}
