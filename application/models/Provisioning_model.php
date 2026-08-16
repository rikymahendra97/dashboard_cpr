<?php
/**
 * ============================================================================
 * File Name    : Provisioning_model.php
 * Modul        : VM Provisioning
 * Purpose      : Engine ORM & Query Builder untuk modul Provisioning
 * Architecture : Sub-Query Server-Side DataTables (RAM Optimize), FOR UPDATE Lock
 * ============================================================================
 */
if (!defined("BASEPATH")) {
    exit("No direct script access allowed");
}

class Provisioning_model extends CI_Model
{
    public $table = "tiket_provisioning";

    public $column_order = [
        null,
        "tanggal_masuk_tiket",
        "no_tiket",
        "nama_server",
        "kritikalitas",
        "environment",
        "tipe_request",
        null,
        "detail_disk",
        "keterangan",
        "source_clone",
        "created_by",
        "setup_by",
        "closed_by",
        "progres_tiket",
        null,
    ];

    public $column_search = ["no_tiket", "nama_server", "environment", "source_clone", "ip"];
    public $order = ["tanggal_masuk_tiket" => "asc"];

    public function __construct()
    {
        parent::__construct();
    }

    private function _get_datatables_query()
    {
        $this->db->select("
            id_tiket, no_tiket, link_tiket, nama_server, ip,
            kritikalitas, environment, tipe_request, detail_disk,
            keterangan, source_clone, created_by, setup_by,
            closed_by, progres_tiket, tanggal_masuk_tiket, cpu, ram, disk
        ");
        $this->db->from($this->table);

        $filter_kpi = $this->input->post("filter_kpi");
        if (!empty($filter_kpi)) {
            $closed_status = ["Done", "Cancel"];
            if ($filter_kpi === "pending") {
                $this->db->where("progres_tiket", "Pending");
            } elseif ($filter_kpi === "in_progress") {
                $this->db->where("progres_tiket", "In Progress");
            } elseif ($filter_kpi === "waiting_sync") {
                $this->db->where("progres_tiket", "Waiting Sync");
            } elseif ($filter_kpi === "selesai") {
                $this->db->where_in("progres_tiket", ["Done", "Cancel"]);
            } elseif ($filter_kpi === "kurang_7") {
                $this->db->where_not_in("progres_tiket", $closed_status);
                $this->db->where("DATEDIFF(NOW(), tanggal_masuk_tiket) <=", 7, false);
            } elseif ($filter_kpi === "lewat_7") {
                $this->db->where_not_in("progres_tiket", $closed_status);
                $this->db->where("DATEDIFF(NOW(), tanggal_masuk_tiket) >", 7, false);
                $this->db->where("DATEDIFF(NOW(), tanggal_masuk_tiket) <=", 14, false);
            } elseif ($filter_kpi === "lewat_14") {
                $this->db->where_not_in("progres_tiket", $closed_status);
                $this->db->where("DATEDIFF(NOW(), tanggal_masuk_tiket) >", 14, false);
            }
        }

        $search_value = $this->input->post("search")["value"] ?? "";
        if (!empty($search_value)) {
            $this->db->group_start();
            foreach ($this->column_search as $i => $item) {
                if ($i === 0) {
                    $this->db->like($item, $search_value);
                } else {
                    $this->db->or_like($item, $search_value);
                }
            }
            $this->db->group_end();
        }

        $order = $this->input->post("order");
        if (isset($order)) {
            $col_idx = $order["0"]["column"];
            $dir = $order["0"]["dir"];

            if ($col_idx == 14) {
                if ($dir === "asc") {
                    $this->db->order_by(
                        "FIELD(progres_tiket, 'Pending', 'In Progress', 'Waiting Sync', 'Done', 'Cancel')",
                        "",
                        false,
                    );
                } else {
                    $this->db->order_by(
                        "FIELD(progres_tiket, 'Cancel', 'Done', 'Waiting Sync', 'In Progress', 'Pending')",
                        "",
                        false,
                    );
                }
            } elseif (isset($this->column_order[$col_idx])) {
                $this->db->order_by($this->column_order[$col_idx], $dir);
            }
        } else {
            $this->db->order_by(
                "CASE WHEN progres_tiket = 'Pending' THEN 1 WHEN progres_tiket = 'In Progress' THEN 2 WHEN progres_tiket = 'Waiting Sync' THEN 3 WHEN progres_tiket = 'Done' THEN 4 WHEN progres_tiket = 'Cancel' THEN 5 ELSE 6 END",
                "ASC",
                false,
            );
            $this->db->order_by("tanggal_masuk_tiket", "ASC");
        }
    }

    public function get_datatables()
    {
        $this->_get_datatables_query();
        $length = $this->input->post("length") ?? 10;
        $start = $this->input->post("start") ?? 0;
        if ($length != -1) {
            $this->db->limit($length, $start);
        }
        return $this->db->get()->result();
    }

    /**
     * [ENTERPRISE FIX] DataTables Memory Leak Prevention
     * Menghitung sub-query menggunakan DB Engine murni
     */
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

    public function insert_data(array $data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function get_by_id(int $id)
    {
        $this->db->where("id_tiket", $id);
        return $this->db->get($this->table)->row();
    }

    /**
     * [ENTERPRISE FIX] Row-Level Locking (Pessimistic)
     * Memaksa antrean query saat transaksi Bind CMDB
     */
    public function get_by_id_for_update(int $id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id_tiket = ? FOR UPDATE";
        return $this->db->query($sql, [$id])->row();
    }

    public function update_data(int $id, array $data)
    {
        $this->db->where("id_tiket", $id);
        return $this->db->update($this->table, $data);
    }

    public function delete(int $id)
    {
        $this->db->where("id_tiket", $id);
        return $this->db->delete($this->table);
    }

    public function get_kpi_summary(): array
    {
        $kpi = [
            "pending" => 0,
            "in_progress" => 0,
            "waiting_sync" => 0,
            "done" => 0,
            "kurang_7" => 0,
            "lewat_7" => 0,
            "lewat_14" => 0,
            "total_tiket" => 0,
        ];

        $this->db->select("progres_tiket, tanggal_masuk_tiket");
        $query = $this->db->get($this->table)->result();
        $now = new DateTime();

        foreach ($query as $row) {
            $kpi["total_tiket"]++;
            $status = strtolower(trim($row->progres_tiket));

            if ($status === "pending") {
                $kpi["pending"]++;
            } elseif ($status === "in progress") {
                $kpi["in_progress"]++;
            } elseif ($status === "waiting sync") {
                $kpi["waiting_sync"]++;
            } elseif ($status === "done" || $status === "cancel") {
                $kpi["done"]++;
            }

            if ($status !== "done" && $status !== "cancel") {
                if (
                    !empty($row->tanggal_masuk_tiket) &&
                    $row->tanggal_masuk_tiket != "0000-00-00 00:00:00"
                ) {
                    $start = new DateTime($row->tanggal_masuk_tiket);
                    $days = $start->diff($now)->days;
                    if ($days >= 14) {
                        $kpi["lewat_14"]++;
                    } elseif ($days >= 7) {
                        $kpi["lewat_7"]++;
                    } else {
                        $kpi["kurang_7"]++;
                    }
                }
            }
        }
        return $kpi;
    }

    public function check_duplicate_vm(
        string $no_tiket,
        string $nama_server,
        string $hostname,
        int $exclude_id_tiket = 0,
    ): bool {
        $nama_server = trim($nama_server);
        $hostname = trim($hostname);
        $no_tiket = trim($no_tiket);

        if (empty($nama_server) && empty($hostname)) {
            return false;
        }

        $this->db->group_start();
        $this->db->where("no_tiket", $no_tiket);

        $this->db->group_start();
        if (!empty($nama_server)) {
            $this->db->where("nama_server", $nama_server);
        }
        if (!empty($hostname)) {
            if (empty($nama_server)) {
                $this->db->where("hostname", $hostname);
            } else {
                $this->db->or_where("hostname", $hostname);
            }
        }
        $this->db->group_end();
        $this->db->group_end();

        $this->db->where("progres_tiket !=", "Cancel");

        if ($exclude_id_tiket > 0) {
            $this->db->where("id_tiket !=", $exclude_id_tiket);
        }

        return $this->db->get($this->table)->num_rows() > 0;
    }

    // ========================================================================
    // [ENTERPRISE FIX]: RAM-Safe Unbuffered Streaming Query for Export
    // ========================================================================
    public function get_data_export_query(?string $start_date = null, ?string $end_date = null)
    {
        $this->db->select("*");
        $this->db->from($this->table);

        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where(
                "DATE(COALESCE(tanggal_masuk_vcenter, tanggal_masuk_tiket)) >=",
                $start_date,
            );
            $this->db->where(
                "DATE(COALESCE(tanggal_masuk_vcenter, tanggal_masuk_tiket)) <=",
                $end_date,
            );
        }

        $this->db->order_by("tanggal_masuk_tiket", "ASC");

        // MENGEMBALIKAN OBJECT QUERY, BUKAN RESULT ARRAY (UNTUK STREAMING)
        return $this->db->get();
    }

    // Fungsi fetch_data_for_export tetap dipertahankan untuk backward compatibility (Preview JSON)
    public function fetch_data_for_export(
        ?string $start_date = null,
        ?string $end_date = null,
    ): array {
        $query = $this->get_data_export_query($start_date, $end_date);
        return $query->result();
    }

    public function search_vm(string $keyword)
    {
        $this->db->select(
            "id_virtual_machine, virtual_machine_name, cpu_count, memory_mb, provisioned_gb",
        );
        $this->db->from("master_virtual_machine");
        $this->db->where("is_active", 1);
        $this->db->like("virtual_machine_name", $keyword, "after");
        $this->db->limit(10);
        return $this->db->get()->result();
    }

    public function search_master_datastore(string $keyword): array
    {
        $this->db->select("*");
        $this->db->from("master_datastore");
        $this->db->like("datastore_name", $keyword);
        $this->db->order_by("free_gb", "DESC");
        $this->db->limit(20);
        return $this->db->get()->result();
    }

    public function get_master_os()
    {
        $this->db->where("is_active", 1);
        $this->db->order_by("os_family", "ASC");
        $this->db->order_by("os_name", "ASC");
        return $this->db->get("master_os")->result();
    }

    public function get_master_template()
    {
        $this->db->where("is_active", 1);
        $this->db->order_by("template_family", "ASC");
        $this->db->order_by("template_name", "ASC");
        return $this->db->get("master_template_vm")->result();
    }

    public function get_master_team()
    {
        $this->db->order_by("team_name", "ASC");
        return $this->db->get("master_team")->result();
    }
}
