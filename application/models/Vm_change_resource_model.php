<?php
/**
 * ============================================================================
 * File Name    : Vm_change_resource_model.php
 * Modul        : VM Change Resource
 * Purpose      : Model database untuk manajemen tiket Change Resource
 * Architecture : Unbuffered Query Streaming, JSON GROUP_CONCAT, For Update Lock
 * ============================================================================
 */
if (!defined("BASEPATH")) {
    exit("No direct script access allowed");
}

class Vm_change_resource_model extends CI_Model
{
    // ========================================================================
    // SECTION 1: READ DATA (Data Retrieval & Audit Trail)
    // ========================================================================
    public function get_all_changes()
    {
        $this->db->select(
            "
            trx.*,
            COALESCE(trx.snapshot_vm_name, vm.virtual_machine_name, 'VM Telah Dihapus') as snapshot_vm_name,
            COALESCE(trx.snapshot_ip_address, vm.ip_address, '-') as snapshot_ip_address,
            u_p.nama_lengkap as nama_pencatat,
            u_e.nama_lengkap as nama_executor,
            u_v.nama_lengkap as nama_verifikator
        ",
            false,
        );
        $this->db->from("trx_vm_change_resource as trx");
        $this->db->join(
            "master_virtual_machine as vm",
            "vm.id_virtual_machine = trx.id_virtual_machine",
            "left",
        );
        $this->db->join("master_user as u_p", "u_p.id_user = trx.id_pencatat", "left");
        $this->db->join("master_user as u_e", "u_e.id_user = trx.id_executor", "left");
        $this->db->join("master_user as u_v", "u_v.id_user = trx.id_verifikator", "left");
        $this->db->order_by("trx.created_at", "DESC");
        return $this->db->get()->result_array();
    }

    public function get_change_detail(int $id_change)
    {
        $this->db->select(
            "
            trx.*,
            COALESCE(trx.snapshot_vm_name, vm.virtual_machine_name, 'VM Telah Dihapus') as snapshot_vm_name,
            COALESCE(trx.snapshot_ip_address, vm.ip_address, '-') as snapshot_ip_address,
            u_p.nama_lengkap as nama_pencatat,
            u_e.nama_lengkap as nama_executor,
            u_v.nama_lengkap as nama_verifikator,
            inc.no_tiket_insiden as no_tiket_insiden_terkait,
            t.team_name, t.team_code, t.pic_name, t.pic_contact
        ",
            false,
        );
        $this->db->from("trx_vm_change_resource as trx");
        $this->db->join(
            "master_virtual_machine as vm",
            "vm.id_virtual_machine = trx.id_virtual_machine",
            "left",
        );
        $this->db->join("master_user as u_p", "u_p.id_user = trx.id_pencatat", "left");
        $this->db->join("master_user as u_e", "u_e.id_user = trx.id_executor", "left");
        $this->db->join("master_user as u_v", "u_v.id_user = trx.id_verifikator", "left");
        $this->db->join(
            "trx_vm_utilization_incident as inc",
            "inc.id_incident = trx.id_incident",
            "left",
        );
        $this->db->join("master_team as t", "t.id_team = trx.id_team_requestor", "left");
        $this->db->where("trx.id_change", $id_change);
        return $this->db->get()->row_array();
    }

    /**
     * ROW-LEVEL LOCKING
     * Mengunci tabel secara pesimis agar tidak terjadi Race Condition saat Verify/Execute.
     */
    public function get_change_detail_for_update(int $id_change)
    {
        // Menggunakan raw SQL untuk memastikan FOR UPDATE tidak ter-sanitize CI
        $sql = "SELECT * FROM trx_vm_change_resource WHERE id_change = ? FOR UPDATE";
        return $this->db->query($sql, [$id_change])->row_array();
    }

    public function get_change_disks(int $id_change)
    {
        $this->db->where("id_change", $id_change);
        return $this->db->get("trx_vm_change_disk")->result_array();
    }

    public function get_active_vms()
    {
        $this->db->select("id_virtual_machine, virtual_machine_name, ip_address, id_site");
        $this->db->where("id_site", "TBN");
        $this->db->order_by("virtual_machine_name", "ASC");
        return $this->db->get("master_virtual_machine")->result_array();
    }

    public function get_vm_detail_ajax(int $id_vm)
    {
        $this->db->select("cpu_count, memory_mb, provisioned_gb, environment");
        $this->db->where("id_virtual_machine", $id_vm);
        return $this->db->get("master_virtual_machine")->row_array();
    }

    public function get_master_team()
    {
        $this->db->select("id_team, team_code, team_name, pic_name, pic_contact");
        $this->db->order_by("team_name", "ASC");
        return $this->db->get("master_team")->result_array();
    }

    public function get_kpi_summary(): array
    {
        $kpi = [
            "menunggu" => 0,
            "dieksekusi" => 0,
            "selesai" => 0,
            "kurang_7" => 0,
            "lewat_7" => 0,
            "lewat_14" => 0,
            "total_tiket" => 0,
        ];

        $this->db->select("status_eksekusi, created_at");
        $query = $this->db->get("trx_vm_change_resource")->result();
        $now = new DateTime();

        foreach ($query as $row) {
            $kpi["total_tiket"]++;
            $status = $row->status_eksekusi;

            if ($status === "Menunggu Eksekusi") {
                $kpi["menunggu"]++;
            } elseif ($status === "Telah Dieksekusi") {
                $kpi["dieksekusi"]++;
            } elseif ($status === "Selesai Verified" || $status === "Cancel by User") {
                $kpi["selesai"]++;
            }

            if ($status === "Menunggu Eksekusi") {
                if (!empty($row->created_at)) {
                    $start = new DateTime($row->created_at);
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

    public function check_duplicate_change(
        string $no_tiket,
        int $id_vm,
        int $exclude_id_change = 0,
    ): bool {
        $this->db->where("no_tiket_eksternal", trim($no_tiket));
        $this->db->where("id_virtual_machine", $id_vm);
        $this->db->where("status_eksekusi !=", "Cancel by User");

        if ($exclude_id_change > 0) {
            $this->db->where("id_change !=", $exclude_id_change);
        }
        return $this->db->get("trx_vm_change_resource")->num_rows() > 0;
    }

    // ========================================================================
    // SECTION 2: CREATE & WORKFLOW PROCESS
    // ========================================================================
    public function simpan_data_awal(int $id_pencatat)
    {
        $this->db->trans_start();
        $id_vm = (int) $this->input->post("id_vm");

        $vm_master = $this->db
            ->select("virtual_machine_name, ip_address, environment, cpu_count, memory_mb")
            ->where("id_virtual_machine", $id_vm)
            ->get("master_virtual_machine")
            ->row();

        $is_susulan = $this->input->post("is_susulan");

        if ($is_susulan == "1") {
            $curr_cpu = (int) $this->input->post("current_cpu");
            $curr_ram_mb = (int) ((float) $this->input->post("current_ram_gb") * 1024);
        } else {
            $curr_cpu = $vm_master
                ? $vm_master->cpu_count
                : (int) $this->input->post("current_cpu");
            $curr_ram_mb = $vm_master
                ? $vm_master->memory_mb
                : (int) ((float) $this->input->post("current_ram_gb") * 1024);
        }

        $raw_incident_id = $this->input->post("resolve_incident_id", true);
        $final_incident_id =
            !empty($raw_incident_id) && is_numeric($raw_incident_id)
                ? (int) $raw_incident_id
                : null;

        $input_team = $this->input->post("id_team_requestor");
        $id_team_final = !empty($input_team) && $input_team != "0" ? (int) $input_team : null;

        $data_parent = [
            "no_tiket_eksternal" => $this->input->post("no_tiket", true),
            "link_tiket_eksternal" => $this->input->post("link_tiket", false),
            "id_virtual_machine" => $id_vm,
            "id_incident" => $final_incident_id,
            "id_team_requestor" => $id_team_final,
            "snapshot_vm_name" => $vm_master ? $vm_master->virtual_machine_name : "Unknown VM",
            "snapshot_ip_address" => $vm_master ? $vm_master->ip_address : "-",
            "snapshot_environment" => $vm_master
                ? $vm_master->environment
                : $this->input->post("snapshot_env", true),
            "id_pencatat" => $id_pencatat,
            "jenis_perubahan" => $this->input->post("jenis_perubahan", true),
            "keterangan_request_asli" => $this->input->post("deskripsi_permintaan", true),
            "current_cpu_count" => $curr_cpu,
            "current_memory_mb" => $curr_ram_mb,
            "target_cpu_count" => (int) $this->input->post("target_cpu"),
            "target_memory_mb" => (int) ((float) $this->input->post("target_ram_gb") * 1024),
            "status_eksekusi" => "Menunggu Eksekusi",
            "created_at" =>
                normalize_mysql_datetime($this->input->post("created_at", true)) ??
                date("Y-m-d H:i:s"),
        ];

        $this->db->insert("trx_vm_change_resource", $data_parent);
        $insert_id_parent = $this->db->insert_id();

        $status_disk = $this->_insert_disks($insert_id_parent);
        if ($status_disk === false) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_complete();
        return $this->db->trans_status() === false ? false : $insert_id_parent;
    }

    private function _insert_disks(int $id_change)
    {
        $disk_tipe = $this->input->post("disk_tipe");
        $disk_nama = $this->input->post("disk_nama");
        $disk_additional = $this->input->post("disk_additional");
        $disk_end_state = $this->input->post("disk_end_state");

        if (empty($disk_tipe)) {
            return true;
        }

        $total_disks = count($disk_tipe);
        $data_disks = [];

        if (
            count($disk_nama ?? []) !== $total_disks ||
            count($disk_additional ?? []) !== $total_disks ||
            count($disk_end_state ?? []) !== $total_disks
        ) {
            return false;
        }

        foreach ($disk_tipe as $i => $tipe) {
            $raw_nama = trim($disk_nama[$i] ?? "");
            $add = trim($disk_additional[$i] ?? "");
            $end = trim($disk_end_state[$i] ?? "");

            if ($tipe === "" || $raw_nama === "" || $add === "" || $end === "") {
                return false;
            }

            if (preg_match('/^[a-zA-Z]$/', $raw_nama)) {
                $formatted_nama = strtoupper($raw_nama) . ":\\";
            } elseif (preg_match('/^[a-zA-Z]:$/', $raw_nama)) {
                $formatted_nama = strtoupper($raw_nama) . "\\";
            } else {
                $formatted_nama =
                    strpos($raw_nama, "/") === 0 ? strtolower($raw_nama) : strtoupper($raw_nama);
            }

            $data_disks[] = [
                "id_change" => $id_change,
                "tipe_eksekusi" => $tipe,
                "nama_drive" => $formatted_nama,
                "additional_gb" => (float) $add,
                "end_state_gb" => (float) $end,
            ];
        }

        if (!empty($data_disks)) {
            $this->db->insert_batch("trx_vm_change_disk", $data_disks);
        }
        return true;
    }

    public function update_kendala(int $id_change, string $kendala, ?string $username)
    {
        return $this->db
            ->where("id_change", $id_change)
            ->update("trx_vm_change_resource", ["catatan_eksekusi" => $kendala]);
    }

    public function update_workflow_status(int $id_change, array $payload)
    {
        $this->db->where("id_change", $id_change);
        return $this->db->update("trx_vm_change_resource", $payload);
    }

    // ========================================================================
    // SECTION 3: UPDATE & DELETE (ADMIN TOOLS)
    // ========================================================================
    public function update_data_awal(int $id_change)
    {
        $this->db->trans_start();
        $id_vm = (int) $this->input->post("id_vm");
        $vm_master = $this->db
            ->select("virtual_machine_name, ip_address, environment, cpu_count, memory_mb")
            ->where("id_virtual_machine", $id_vm)
            ->get("master_virtual_machine")
            ->row();

        $is_susulan = $this->input->post("is_susulan");
        $input_ram_gb = $this->input->post("current_ram_gb");

        if ($is_susulan == "1") {
            $curr_cpu = (int) $this->input->post("current_cpu");
            $curr_ram_mb = (int) ((float) $input_ram_gb * 1024);
        } else {
            $curr_cpu = (int) $this->input->post("current_cpu");
            $curr_ram_mb =
                $input_ram_gb !== null
                    ? (int) ((float) $input_ram_gb * 1024)
                    : (int) $this->input->post("current_ram_mb");
        }

        $raw_incident_id = $this->input->post("resolve_incident_id", true);
        $final_incident_id =
            !empty($raw_incident_id) && is_numeric($raw_incident_id)
                ? (int) $raw_incident_id
                : null;

        $input_team = $this->input->post("id_team_requestor");
        $id_team_final = !empty($input_team) && $input_team != "0" ? (int) $input_team : null;

        $data_parent = [
            "no_tiket_eksternal" => $this->input->post("no_tiket", true),
            "link_tiket_eksternal" => $this->input->post("link_tiket", false),
            "id_virtual_machine" => $id_vm,
            "id_incident" => $final_incident_id,
            "id_team_requestor" => $id_team_final,
            "snapshot_vm_name" => $vm_master ? $vm_master->virtual_machine_name : "Unknown VM",
            "snapshot_ip_address" => $vm_master ? $vm_master->ip_address : "-",
            "snapshot_environment" => $vm_master
                ? $vm_master->environment
                : $this->input->post("snapshot_env", true),
            "jenis_perubahan" => $this->input->post("jenis_perubahan", true),
            "keterangan_request_asli" => $this->input->post("deskripsi_permintaan", true),
            "current_cpu_count" => $curr_cpu,
            "current_memory_mb" => $curr_ram_mb,
            "target_cpu_count" => (int) $this->input->post("target_cpu"),
            "target_memory_mb" => (int) ((float) $this->input->post("target_ram_gb") * 1024),
        ];

        $created_at = $this->input->post("created_at", true);
        $tanggal_eksekusi = $this->input->post("tanggal_eksekusi", true);

        if (!empty($created_at)) {
            $data_parent["created_at"] = normalize_mysql_datetime($created_at);
        }
        if (!empty($tanggal_eksekusi)) {
            $data_parent["tanggal_eksekusi"] = normalize_mysql_datetime($tanggal_eksekusi);
        }

        $this->db->where("id_change", $id_change)->update("trx_vm_change_resource", $data_parent);
        $this->db->where("id_change", $id_change)->delete("trx_vm_change_disk");

        $status_disk = $this->_insert_disks($id_change);
        if ($status_disk === false) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function hapus_data(int $id_change): bool
    {
        $this->db->where("id_change", $id_change);
        return $this->db->delete("trx_vm_change_resource");
    }

    // ========================================================================
    // Unbuffered SQL Data Aggregation for Streaming
    // ========================================================================
    public function get_data_export_query(?string $start_date = null, ?string $end_date = null)
    {
        // JSON_ARRAYAGG & JSON_OBJECT digunakan agar detail Disks ditarik dalam 1 Query N+1 (Safe)
        $this->db->select(
            "
            trx.*,
            COALESCE(trx.snapshot_vm_name, vm.virtual_machine_name, 'VM Telah Dihapus') as snapshot_vm_name,
            COALESCE(trx.snapshot_ip_address, vm.ip_address, '-') as snapshot_ip_address,
            u_p.nama_lengkap as nama_pencatat,
            u_e.nama_lengkap as nama_executor,
            u_v.nama_lengkap as nama_verifikator,
            (
                SELECT CONCAT('[', GROUP_CONCAT(JSON_OBJECT(
                    'nama_drive', d.nama_drive,
                    'additional_gb', d.additional_gb,
                    'end_state_gb', d.end_state_gb
                )), ']')
                FROM trx_vm_change_disk d
                WHERE d.id_change = trx.id_change
            ) as disks_json
        ",
            false,
        );

        $this->db->from("trx_vm_change_resource as trx");
        $this->db->join(
            "master_virtual_machine as vm",
            "vm.id_virtual_machine = trx.id_virtual_machine",
            "left",
        );
        $this->db->join("master_user as u_p", "u_p.id_user = trx.id_pencatat", "left");
        $this->db->join("master_user as u_e", "u_e.id_user = trx.id_executor", "left");
        $this->db->join("master_user as u_v", "u_v.id_user = trx.id_verifikator", "left");

        if (!empty($start_date) && !empty($end_date)) {
            $safe_start = $this->db->escape($start_date);
            $safe_end = $this->db->escape($end_date);
            $this->db->where(
                "DATE(COALESCE(trx.tanggal_eksekusi, trx.created_at)) >= $safe_start",
                null,
                false,
            );
            $this->db->where(
                "DATE(COALESCE(trx.tanggal_eksekusi, trx.created_at)) <= $safe_end",
                null,
                false,
            );
        }
        $this->db->order_by("COALESCE(trx.tanggal_eksekusi, trx.created_at)", "DESC", false);

        return $this->db->get();
    }

    // Retained for backward compatibility / API
    public function get_data_export(?string $start_date = null, ?string $end_date = null)
    {
        $query = $this->get_data_export_query($start_date, $end_date);
        return $query->result_array();
    }

    // ========================================================================
    // SECTION 5: SERVER-SIDE DATATABLES PROCESSING
    // ========================================================================
    var $column_order = [
        null,
        "a.created_at",
        "a.no_tiket_eksternal",
        "m.virtual_machine_name",
        "a.jenis_perubahan",
        "a.status_eksekusi",
        "u_e.nama_lengkap",
        "a.catatan_eksekusi",
        null,
    ];
    var $column_search = [
        "a.no_tiket_eksternal",
        "m.virtual_machine_name",
        "m.ip_address",
        "a.status_eksekusi",
        "u_e.nama_lengkap",
        "a.catatan_eksekusi",
    ];
    var $order = ["a.created_at" => "desc"];

    private function _get_datatables_query()
    {
        $this->db->select(
            "
            a.id_change, a.created_at, a.no_tiket_eksternal, a.link_tiket_eksternal,
            a.jenis_perubahan, a.status_eksekusi, a.catatan_eksekusi, a.keterangan_request_asli,
            a.current_cpu_count, a.target_cpu_count, a.current_memory_mb, a.target_memory_mb, a.tanggal_eksekusi,
            COALESCE(a.snapshot_vm_name, m.virtual_machine_name) AS snapshot_vm_name,
            COALESCE(a.snapshot_ip_address, m.ip_address) AS snapshot_ip_address,
            u_e.nama_lengkap AS nama_executor
        ",
            false,
        );

        $this->db->from("trx_vm_change_resource a");
        $this->db->join(
            "master_virtual_machine m",
            "m.id_virtual_machine = a.id_virtual_machine",
            "left",
        );
        $this->db->join("master_user u_e", "u_e.id_user = a.id_executor", "left");

        $filter_kpi = $this->input->post("filter_kpi");
        if (!empty($filter_kpi)) {
            $closed_status = ["Selesai Verified", "Cancel by User", "Ditolak"];
            if ($filter_kpi === "menunggu") {
                $this->db->where("a.status_eksekusi", "Menunggu Eksekusi");
            } elseif ($filter_kpi === "dieksekusi") {
                $this->db->where("a.status_eksekusi", "Telah Dieksekusi");
            } elseif ($filter_kpi === "selesai") {
                $this->db->where("a.status_eksekusi", "Selesai Verified");
            } elseif ($filter_kpi === "kurang_7") {
                $this->db->where("a.status_eksekusi", "Menunggu Eksekusi");
                $this->db->where("DATEDIFF(NOW(), a.created_at) <=", 7, false);
            } elseif ($filter_kpi === "lewat_7") {
                $this->db->where("a.status_eksekusi", "Menunggu Eksekusi");
                $this->db->where("DATEDIFF(NOW(), a.created_at) >", 7, false);
                $this->db->where("DATEDIFF(NOW(), a.created_at) <=", 14, false);
            } elseif ($filter_kpi === "lewat_14") {
                $this->db->where("a.status_eksekusi", "Menunggu Eksekusi");
                $this->db->where("DATEDIFF(NOW(), a.created_at) >", 14, false);
            }
        }

        $i = 0;
        foreach ($this->column_search as $item) {
            if (isset($_POST["search"]["value"]) && $_POST["search"]["value"]) {
                if ($i === 0) {
                    $this->db->group_start();
                    $this->db->like($item, $_POST["search"]["value"]);
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
            $order = $this->order;
            $this->db->order_by(
                "CASE WHEN a.status_eksekusi = 'Menunggu Eksekusi' THEN 1 ELSE 2 END",
                "ASC",
                false,
            );
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    public function get_datatables()
    {
        $this->_get_datatables_query();
        if (isset($_POST["length"]) && $_POST["length"] != -1) {
            $this->db->limit($_POST["length"], $_POST["start"]);
        }
        $results = $this->db->get()->result_array();

        foreach ($results as $k => $v) {
            $results[$k]["disks"] = $this->get_change_disks((int) $v["id_change"]);
        }
        return $results;
    }

    /**
     * Memory Leak Prevention
     * Menghitung sub-query total record menggunakan Engine DB, BUKAN ditarik ke RAM PHP
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
        $this->db->from("trx_vm_change_resource");
        return $this->db->count_all_results();
    }
}
