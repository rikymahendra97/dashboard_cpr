<?php if (!defined("BASEPATH")) {
    exit("No direct script access allowed");
}

/**
 * =============================================================================
 * File Name    : Vm_switch_ip_model.php
 * Modul        : VM Switch IP
 * Purpose      : Interaksi Database, Query Builder, dan Pengolahan Metrik.
 * Architecture : Memory-Optimized Datatables, Unbuffered Query Streaming, Locking
 * =============================================================================
 */

class Vm_switch_ip_model extends CI_Model
{
    // ========================================================================
    // SECTION 1: READ DATA (Data Retrieval & Audit Trail)
    // ========================================================================
    public function get_all_switches()
    {
        $this->db->select('
            trx.*,
            u_p.nama_lengkap as nama_pencatat,
            u_e.nama_lengkap as nama_executor,
            u_v.nama_lengkap as nama_verifikator
        ');
        $this->db->from("trx_vm_switch_ip as trx");
        $this->db->join("master_user as u_p", "u_p.id_user = trx.id_pencatat", "left");
        $this->db->join("master_user as u_e", "u_e.id_user = trx.id_executor", "left");
        $this->db->join("master_user as u_v", "u_v.id_user = trx.id_verifikator", "left");
        $this->db->order_by("trx.created_at", "DESC");
        return $this->db->get()->result_array();
    }

    public function get_switch_detail(int $id_switch): ?array
    {
        $this->db->select('
            trx.*,
            t.team_code as fungsi_requestor,
            t.team_name,
            t.pic_name,
            t.pic_contact,
            u_p.nama_lengkap as nama_pencatat,
            u_e.nama_lengkap as nama_executor,
            u_v.nama_lengkap as nama_verifikator
        ');
        $this->db->from("trx_vm_switch_ip as trx");
        $this->db->join("master_team as t", "t.id_team = trx.id_team_requestor", "left");
        $this->db->join("master_user as u_p", "u_p.id_user = trx.id_pencatat", "left");
        $this->db->join("master_user as u_e", "u_e.id_user = trx.id_executor", "left");
        $this->db->join("master_user as u_v", "u_v.id_user = trx.id_verifikator", "left");
        $this->db->where("trx.id_switch", $id_switch);
        return $this->db->get()->row_array();
    }

    /**
     * [ENTERPRISE FIX] ROW-LEVEL LOCKING
     * Mengunci tabel secara pesimis agar tidak terjadi Race Condition saat Verify/Execute.
     */
    public function get_change_detail_for_update(int $id_switch)
    {
        // Menggunakan raw SQL untuk memastikan FOR UPDATE tidak terpotong
        $sql = "SELECT * FROM trx_vm_switch_ip WHERE id_switch = ? FOR UPDATE";
        return $this->db->query($sql, [$id_switch])->row_array();
    }

    public function get_switch_details_vm(int $id_switch): array
    {
        $this->db->select(
            "
            det.*,
            COALESCE(vm.virtual_machine_name, det.nama_vm_lama, 'VM Dihapus') as nama_master_aktual
        ",
            false,
        );
        $this->db->from("trx_vm_switch_ip_detail as det");
        $this->db->join(
            "master_virtual_machine as vm",
            "vm.id_virtual_machine = det.id_virtual_machine",
            "left",
        );
        $this->db->where("det.id_switch", $id_switch);
        return $this->db->get()->result_array();
    }

    // ========================================================================
    // [ENTERPRISE FIX]: DATABASE DUPLICATE CHECKER & KPI ENGINE
    // ========================================================================
    public function check_duplicate_switch(
        string $no_tiket,
        int $id_vm_1,
        int $id_vm_2 = 0,
        int $exclude_id_switch = 0,
    ): bool {
        $this->db->select("trx.id_switch");
        $this->db->from("trx_vm_switch_ip as trx");
        $this->db->join("trx_vm_switch_ip_detail as det", "det.id_switch = trx.id_switch", "left");
        $this->db->where("trx.no_tiket_eksternal", trim($no_tiket));
        $this->db->where("trx.status_eksekusi !=", "Cancel by User");

        if ($exclude_id_switch > 0) {
            $this->db->where("trx.id_switch !=", $exclude_id_switch);
        }

        $this->db->group_start();
        $this->db->where("det.id_virtual_machine", $id_vm_1);
        if ($id_vm_2 > 0) {
            $this->db->or_where("det.id_virtual_machine", $id_vm_2);
        }
        $this->db->group_end();

        return $this->db->get()->num_rows() > 0;
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
        ];

        $query = $this->db
            ->select("status_eksekusi, created_at, tanggal_eksekusi")
            ->get("trx_vm_switch_ip")
            ->result();
        $now = new DateTime();

        foreach ($query as $row) {
            $st = strtolower(trim($row->status_eksekusi));
            if ($st === "menunggu eksekusi") {
                $kpi["menunggu"]++;
            } elseif ($st === "telah dieksekusi") {
                $kpi["dieksekusi"]++;
            } elseif ($st === "selesai verified" || $st === "ditolak" || $st === "cancel by user") {
                $kpi["selesai"]++;
            }

            if ($st === "menunggu eksekusi") {
                $start_date = new DateTime($row->created_at);
                $diff = $start_date->diff($now)->days;

                if ($diff <= 7) {
                    $kpi["kurang_7"]++;
                } elseif ($diff > 7 && $diff <= 14) {
                    $kpi["lewat_7"]++;
                } else {
                    $kpi["lewat_14"]++;
                }
            }
        }
        return $kpi;
    }

    public function check_ip_usage(string $ip_address): array
    {
        $this->db->select("id_virtual_machine, virtual_machine_name");
        $this->db->from("master_virtual_machine");
        $this->db->where("is_active", 1);
        $this->db->where("id_site", "TBN");

        $this->db->group_start();
        $this->db->where("ip_address", $ip_address);
        $this->db->or_where("ip_address_2", $ip_address);
        $this->db->or_where("ip_address_3", $ip_address);
        $this->db->or_where("ip_address_4", $ip_address);
        $this->db->or_where("ip_address_5", $ip_address);
        $this->db->or_where("ip_rubrik", $ip_address);
        $this->db->group_end();

        return $this->db->get()->result_array();
    }

    public function get_active_vms()
    {
        $this->db->select(
            "id_virtual_machine, virtual_machine_name, ip_address, ip_address_2, ip_address_3, ip_address_4, ip_address_5, ip_rubrik, id_site",
        );
        $this->db->where("is_active", 1);
        $this->db->where("id_site", "TBN");
        $this->db->order_by("virtual_machine_name", "ASC");

        $result = $this->db->get("master_virtual_machine")->result_array();

        foreach ($result as &$row) {
            $ips = [];
            if (!empty(trim($row["ip_address"]))) {
                $ips[] = trim($row["ip_address"]);
            }
            if (!empty(trim($row["ip_address_2"]))) {
                $ips[] = trim($row["ip_address_2"]);
            }
            if (!empty(trim($row["ip_address_3"]))) {
                $ips[] = trim($row["ip_address_3"]);
            }
            if (!empty(trim($row["ip_address_4"]))) {
                $ips[] = trim($row["ip_address_4"]);
            }
            if (!empty(trim($row["ip_address_5"]))) {
                $ips[] = trim($row["ip_address_5"]);
            }
            if (!empty(trim($row["ip_rubrik"]))) {
                $ips[] = trim($row["ip_rubrik"]);
            }

            $ips = array_values(array_unique($ips));
            $row["ip_list_json"] = json_encode($ips);
        }
        return $result;
    }

    public function get_master_team()
    {
        $this->db->select("id_team, team_code, team_name, pic_name, pic_contact");
        $this->db->from("master_team");
        $this->db->order_by("team_name", "ASC");
        return $this->db->get()->result_array();
    }

    // ========================================================================
    // SECTION 2: CREATE & WORKFLOW PROCESS
    // ========================================================================
    public function simpan_data_awal(int $id_pencatat)
    {
        $this->db->trans_start();
        $jenis = $this->input->post("jenis_switch", true);
        $created_at = $this->input->post("created_at", true);

        $data_parent = [
            "no_tiket_eksternal" => $this->input->post("no_tiket", true),
            "link_tiket_eksternal" => $this->input->post("link_tiket", false),
            "id_team_requestor" => $this->input->post("id_team_requestor", true),
            "jenis_switch" => $jenis,
            "deskripsi_permintaan" => $this->input->post("deskripsi_permintaan", true),
            "id_pencatat" => $id_pencatat,
            "status_eksekusi" => "Menunggu Eksekusi",
            "created_at" => normalize_mysql_datetime($created_at) ?? date("Y-m-d H:i:s"),
        ];
        $this->db->insert("trx_vm_switch_ip", $data_parent);
        $id_switch = $this->db->insert_id();

        $this->_insert_detail($id_switch, 1);
        if ($jenis == "Tukar Silang (Dual VM)") {
            $this->_insert_detail($id_switch, 2);
        }

        $this->db->trans_complete();
        return $this->db->trans_status() === false ? false : $id_switch;
    }

    private function _insert_detail(int $id_switch, int $suffix): void
    {
        $id_vm = (int) $this->input->post("id_vm_" . $suffix);
        $vm_master = $this->db
            ->select("virtual_machine_name")
            ->where("id_virtual_machine", $id_vm)
            ->get("master_virtual_machine")
            ->row();

        $ip_lama_raw = $this->input->post("ip_lama_" . $suffix, true);
        $ip_lama_final =
            empty(trim($ip_lama_raw)) || trim($ip_lama_raw) === "-" ? null : trim($ip_lama_raw);

        $ip_baru_raw = $this->input->post("ip_baru_" . $suffix, true);
        $ip_baru_final =
            empty(trim($ip_baru_raw)) || trim($ip_baru_raw) === "-" ? null : trim($ip_baru_raw);

        $nama_lama_raw = $vm_master
            ? $vm_master->virtual_machine_name
            : $this->input->post("nama_lama_" . $suffix, true);
        $nama_lama_final =
            empty(trim($nama_lama_raw)) || trim($nama_lama_raw) === "-"
                ? null
                : trim($nama_lama_raw);

        $data = [
            "id_switch" => $id_switch,
            "id_virtual_machine" => $id_vm,
            "ip_lama" => $ip_lama_final,
            "ip_baru" => $ip_baru_final,
            "nama_vm_lama" => $nama_lama_final,
            "nama_vm_baru" => trim($this->input->post("nama_baru_" . $suffix, true)),
        ];
        $this->db->insert("trx_vm_switch_ip_detail", $data);
    }

    public function update_data_awal(int $id_switch): bool
    {
        $this->db->trans_start();
        $jenis = $this->input->post("jenis_switch", true);

        $data_parent = [
            "no_tiket_eksternal" => $this->input->post("no_tiket", true),
            "link_tiket_eksternal" => $this->input->post("link_tiket", false),
            "id_team_requestor" => $this->input->post("id_team_requestor", true),
            "jenis_switch" => $jenis,
            "deskripsi_permintaan" => $this->input->post("deskripsi_permintaan", true),
        ];

        $created_at = $this->input->post("created_at", true);
        $tanggal_eksekusi = $this->input->post("tanggal_eksekusi", true);

        if (!empty($created_at)) {
            $data_parent["created_at"] = normalize_mysql_datetime($created_at);
        }
        if (!empty($tanggal_eksekusi)) {
            $data_parent["tanggal_eksekusi"] = normalize_mysql_datetime($tanggal_eksekusi);
        }

        $this->db->where("id_switch", $id_switch)->update("trx_vm_switch_ip", $data_parent);
        $this->db->where("id_switch", $id_switch)->delete("trx_vm_switch_ip_detail");

        $this->_insert_detail($id_switch, 1);
        if ($jenis == "Tukar Silang (Dual VM)") {
            $this->_insert_detail($id_switch, 2);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function hapus_data(int $id_switch): bool
    {
        $this->db->where("id_switch", $id_switch);
        return $this->db->delete("trx_vm_switch_ip");
    }

    public function update_kendala(int $id_switch, string $kendala): bool
    {
        return $this->db
            ->where("id_switch", $id_switch)
            ->update("trx_vm_switch_ip", ["catatan_eksekusi" => $kendala]);
    }

    public function update_workflow_status(int $id_switch, array $payload)
    {
        $this->db->where("id_switch", $id_switch);
        return $this->db->update("trx_vm_switch_ip", $payload);
    }

    // ========================================================================
    // [ENTERPRISE FIX]: Restorasi SQL Aliases (as no_tiket, as jenis_aksi)
    // ========================================================================
    public function get_data_export_query(?string $start_date = null, ?string $end_date = null)
    {
        $this->db->select(
            "
            trx.id_switch, trx.tanggal_eksekusi,
            trx.no_tiket_eksternal as no_tiket,
            trx.link_tiket_eksternal,
            trx.jenis_switch as jenis_aksi,
            trx.status_eksekusi as status_akhir,
            trx.deskripsi_permintaan,
            trx.catatan_eksekusi,
            u_e.nama_lengkap AS nama_executor,
            (
                SELECT CONCAT('[', GROUP_CONCAT(JSON_OBJECT(
                    'nama_master_aktual', COALESCE(vm.virtual_machine_name, d.nama_vm_lama, '-'),
                    'ip_lama', COALESCE(d.ip_lama, '-'),
                    'nama_vm_baru', COALESCE(d.nama_vm_baru, '-'),
                    'ip_baru', COALESCE(d.ip_baru, '-')
                )), ']')
                FROM trx_vm_switch_ip_detail d
                LEFT JOIN master_virtual_machine vm ON vm.id_virtual_machine = d.id_virtual_machine
                WHERE d.id_switch = trx.id_switch
            ) as vms_json
        ",
            false,
        );

        $this->db->from("trx_vm_switch_ip as trx");
        $this->db->join("master_user u_e", "u_e.id_user = trx.id_executor", "left");

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

    // Retained for backward compatibility
    public function get_data_export(?string $start_date = null, ?string $end_date = null)
    {
        $query = $this->get_data_export_query($start_date, $end_date);
        return $query->result_array();
    }

    // ========================================================================
    // SECTION 6: [SERVER-SIDE] ENGINE DATATABLES (RAM Optimized)
    // ========================================================================
    var $column_order = [
        null,
        "trx.created_at",
        "trx.no_tiket_eksternal",
        "trx.jenis_switch",
        "trx.status_eksekusi",
        "u_e.nama_lengkap",
        "trx.catatan_eksekusi",
        null,
    ];
    var $column_search = [
        "trx.no_tiket_eksternal",
        "trx.status_eksekusi",
        "u_e.nama_lengkap",
        "trx.catatan_eksekusi",
    ];
    var $order = ["trx.created_at" => "desc"];

    private function _get_datatables_query()
    {
        $this->db->select('
            trx.id_switch, trx.created_at, trx.no_tiket_eksternal, trx.link_tiket_eksternal,
            trx.jenis_switch, trx.status_eksekusi, trx.catatan_eksekusi, trx.deskripsi_permintaan,
            t.team_code as fungsi_requestor,
            u_e.nama_lengkap as nama_executor
        ');
        $this->db->from("trx_vm_switch_ip as trx");
        $this->db->join("master_team as t", "t.id_team = trx.id_team_requestor", "left");
        $this->db->join("master_user u_e", "u_e.id_user = trx.id_executor", "left");

        $filter_kpi = $this->input->post("filter_kpi");
        if (!empty($filter_kpi)) {
            $closed_status = ["Selesai Verified", "Cancel by User", "Ditolak"];

            if ($filter_kpi === "menunggu") {
                $this->db->where("trx.status_eksekusi", "Menunggu Eksekusi");
            } elseif ($filter_kpi === "dieksekusi") {
                $this->db->where("trx.status_eksekusi", "Telah Dieksekusi");
            } elseif ($filter_kpi === "selesai") {
                $this->db->where("trx.status_eksekusi", "Selesai Verified");
            } elseif ($filter_kpi === "kurang_7") {
                $this->db->where("trx.status_eksekusi", "Menunggu Eksekusi");
                $this->db->where("DATEDIFF(NOW(), trx.created_at) <=", 7, false);
            } elseif ($filter_kpi === "lewat_7") {
                $this->db->where("trx.status_eksekusi", "Menunggu Eksekusi");
                $this->db->where("DATEDIFF(NOW(), trx.created_at) >", 7, false);
                $this->db->where("DATEDIFF(NOW(), trx.created_at) <=", 14, false);
            } elseif ($filter_kpi === "lewat_14") {
                $this->db->where("trx.status_eksekusi", "Menunggu Eksekusi");
                $this->db->where("DATEDIFF(NOW(), trx.created_at) >", 14, false);
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
                "CASE WHEN trx.status_eksekusi = 'Menunggu Eksekusi' THEN 1 ELSE 2 END",
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
        return $this->db->get()->result_array();
    }

    /**
     * [ENTERPRISE FIX] Memory Leak Prevention
     * Menghitung sub-query total record menggunakan Engine DB murni.
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
        $this->db->from("trx_vm_switch_ip");
        return $this->db->count_all_results();
    }
}
