<?php
defined("BASEPATH") or exit("No direct script access allowed");

/**
 * ============================================================================
 * File Name    : Vm_restart_model.php
 * Modul        : VM Restart
 * Purpose      : Menangani seluruh logika interaksi database untuk modul Restart.
 * Architecture : Memory Optimized Datatables, Unbuffered Query Streaming, Locking
 * ============================================================================
 */
class Vm_restart_model extends CI_Model
{
    // ========================================================================
    // SECTION 1: [READ] PENGAMBILAN DATA MASTER & RELASI
    // ========================================================================
    public function get_all_restart_tickets()
    {
        $this->db->select(
            "
            a.*,
            COALESCE(a.snapshot_vm_name, m.virtual_machine_name, 'VM Dihapus') AS nama_target_vm,
            COALESCE(a.snapshot_ip_address, m.ip_address, 'IP Tidak Diketahui') AS ip_target_vm,
            COALESCE(t.team_code, a.nama_requestor_manual, 'Tidak Diketahui') AS fungsi_requestor,
            u_p.nama_lengkap AS nama_pencatat,
            u_e.nama_lengkap AS nama_executor,
            u_v.nama_lengkap AS nama_verifikator
        ",
            false,
        );
        $this->db->from("trx_vm_restart a");
        $this->db->join(
            "master_virtual_machine m",
            "m.id_virtual_machine = a.id_virtual_machine",
            "left",
        );
        $this->db->join("master_team t", "t.id_team = a.id_team_requestor", "left");
        $this->db->join("master_user u_p", "u_p.id_user = a.id_pencatat", "left");
        $this->db->join("master_user u_e", "u_e.id_user = a.id_executor", "left");
        $this->db->join("master_user u_v", "u_v.id_user = a.id_verifikator", "left");
        $this->db->order_by("a.created_at", "DESC");
        return $this->db->get()->result_array();
    }

    public function get_ticket_detail(int $id_restart): ?array
    {
        // [ENTERPRISE FIX]: JOIN presisi ke tabel trx_vm_utilization_incident
        $this->db->select(
            "
            a.*,
            inc.no_tiket_insiden AS no_tiket_insiden_terkait,
            COALESCE(a.snapshot_vm_name, m.virtual_machine_name, 'VM Dihapus') AS nama_target_vm,
            COALESCE(a.snapshot_ip_address, m.ip_address, 'IP Tidak Diketahui') AS ip_target_vm,
            COALESCE(t.team_code, a.nama_requestor_manual, 'Tidak Diketahui') AS fungsi_requestor,
            t.team_name, t.pic_name, t.pic_contact,
            u_p.nama_lengkap AS nama_pencatat,
            u_e.nama_lengkap AS nama_executor,
            u_v.nama_lengkap AS nama_verifikator
        ",
            false,
        );
        $this->db->from("trx_vm_restart a");
        $this->db->join(
            "master_virtual_machine m",
            "m.id_virtual_machine = a.id_virtual_machine",
            "left",
        );
        $this->db->join("master_team t", "t.id_team = a.id_team_requestor", "left");
        $this->db->join("master_user u_p", "u_p.id_user = a.id_pencatat", "left");
        $this->db->join("master_user u_e", "u_e.id_user = a.id_executor", "left");
        $this->db->join("master_user u_v", "u_v.id_user = a.id_verifikator", "left");
        $this->db->join(
            "trx_vm_utilization_incident inc",
            "inc.id_incident = a.id_incident",
            "left",
        );
        $this->db->where("a.id_restart", $id_restart);
        return $this->db->get()->row_array();
    }

    /**
     * [ENTERPRISE FIX] ROW-LEVEL LOCKING
     * Mengunci tabel secara pesimis (Pessimistic Locking) agar tidak terjadi
     * Race Condition (TOC/TOU) saat Verify/Execute.
     */
    public function get_ticket_detail_for_update(int $id_restart)
    {
        $sql = "SELECT * FROM trx_vm_restart WHERE id_restart = ? FOR UPDATE";
        return $this->db->query($sql, [$id_restart])->row_array();
    }

    public function get_master_vm()
    {
        $this->db->select("id_virtual_machine, virtual_machine_name, ip_address, id_site");
        $this->db->where("id_site", "TBN");
        $this->db->order_by("virtual_machine_name", "ASC");
        return $this->db->get("master_virtual_machine")->result_array();
    }

    public function get_master_team()
    {
        $this->db->select("id_team, team_code, team_name, pic_name, pic_contact");
        $this->db->order_by("team_name", "ASC");
        return $this->db->get("master_team")->result_array();
    }

    public function check_duplicate_restart(
        string $no_tiket,
        int $id_vm,
        int $exclude_id_restart = 0,
    ): bool {
        $no_tiket = trim($no_tiket);
        if (empty($no_tiket) || strpos($no_tiket, "DRAFT-") === 0) {
            return false;
        }

        $this->db->select("id_restart");
        $this->db->from("trx_vm_restart");
        $this->db->where("no_tiket_iris", $no_tiket);
        $this->db->where("id_virtual_machine", $id_vm);
        $this->db->where("status_eksekusi !=", "Cancel by User");

        if ($exclude_id_restart > 0) {
            $this->db->where("id_restart !=", $exclude_id_restart);
        }
        return $this->db->get()->num_rows() > 0;
    }

    // ========================================================================
    // SECTION 2: [CREATE] PEMBUATAN TIKET BARU
    // ========================================================================
    public function insert_restart_request(array $data)
    {
        $vm_info = $this->db
            ->select("virtual_machine_name, ip_address")
            ->get_where("master_virtual_machine", [
                "id_virtual_machine" => $data["id_virtual_machine"],
            ])
            ->row();

        if ($vm_info) {
            $data["snapshot_vm_name"] = $vm_info->virtual_machine_name;
            $data["snapshot_ip_address"] = $vm_info->ip_address;
        }

        $data["created_at"] =
            normalize_mysql_datetime($this->input->post("created_at", true)) ?? date("Y-m-d H:i:s");

        $input_team = $this->input->post("id_team_requestor");
        if ($input_team == "0") {
            $data["id_team_requestor"] = null;
            $data["nama_requestor_manual"] = $this->input->post("nama_requestor_manual", true);
        } else {
            $data["id_team_requestor"] = (int) $input_team;
            $data["nama_requestor_manual"] = null;
        }

        $this->db->insert("trx_vm_restart", $data);
        return $this->db->insert_id();
    }

    // ========================================================================
    // SECTION 3: [UPDATE] EKSEKUSI, EDIT DATA & WORKFLOW
    // ========================================================================
    public function update_restart_request(int $id_restart): bool
    {
        $id_vm = (int) $this->input->post("id_virtual_machine");
        $vm_info = $this->db
            ->select("virtual_machine_name, ip_address")
            ->get_where("master_virtual_machine", ["id_virtual_machine" => $id_vm])
            ->row();

        $input_team = $this->input->post("id_team_requestor");
        $id_team_final = $input_team == "0" ? null : (int) $input_team;
        $nama_manual_final =
            $input_team == "0" ? $this->input->post("nama_requestor_manual", true) : null;

        $start_dt = normalize_mysql_datetime($this->input->post("start_downtime", true));
        $finish_dt = normalize_mysql_datetime($this->input->post("finish_downtime", true));
        $durasi = 0;

        if (!empty($start_dt) && !empty($finish_dt)) {
            $start = strtotime($start_dt);
            $finish = strtotime($finish_dt);

            if ($start > $finish) {
                $temp = $start;
                $start = $finish;
                $finish = $temp;
                $start_dt = date("Y-m-d H:i:s", $start);
                $finish_dt = date("Y-m-d H:i:s", $finish);
            }
            $durasi = ceil(($finish - $start) / 60);
        }

        $id_incident_req = $this->input->post("resolve_incident_id", true);

        $data_update = [
            "no_tiket_iris" => $this->input->post("no_tiket_iris", true),
            "link_tiket" => $this->input->post("link_tiket", false),
            "id_virtual_machine" => $id_vm,
            "id_team_requestor" => $id_team_final,
            "nama_requestor_manual" => $nama_manual_final,
            "jenis_downtime" => $this->input->post("jenis_downtime", true),
            "root_cause" => $this->input->post("root_cause", true),
            "keterangan_request" => $this->input->post("keterangan_request", true),
            "start_downtime" => $start_dt,
            "finish_downtime" => $finish_dt,
            "durasi_downtime_menit" => $durasi,
            "id_incident" => !empty($id_incident_req) ? (int) $id_incident_req : null,
        ];

        $created_at = normalize_mysql_datetime($this->input->post("created_at", true));
        $tanggal_eksekusi = normalize_mysql_datetime($this->input->post("tanggal_eksekusi", true));

        if (!empty($created_at)) {
            $data_update["created_at"] = $created_at;
        }
        if (!empty($tanggal_eksekusi)) {
            $data_update["tanggal_eksekusi"] = $tanggal_eksekusi;
        }

        if ($vm_info) {
            $data_update["snapshot_vm_name"] = $vm_info->virtual_machine_name;
            $data_update["snapshot_ip_address"] = $vm_info->ip_address;
        }

        return $this->db->where("id_restart", $id_restart)->update("trx_vm_restart", $data_update);
    }

    public function update_workflow_status(int $id_restart, array $payload): bool
    {
        return $this->db->where("id_restart", $id_restart)->update("trx_vm_restart", $payload);
    }

    public function update_kendala(int $id_restart, string $kendala): bool
    {
        return $this->db
            ->where("id_restart", $id_restart)
            ->update("trx_vm_restart", ["catatan_eksekusi" => $kendala]);
    }

    public function hapus_data(int $id_restart): bool
    {
        return $this->db->where("id_restart", $id_restart)->delete("trx_vm_restart");
    }

    // ========================================================================
    // SECTION 6: [SERVER-SIDE] ENGINE DATATABLES (RAM OPTIMIZED)
    // ========================================================================
    var $column_order = [
        null,
        "a.created_at",
        "a.no_tiket_iris",
        "m.virtual_machine_name",
        "a.jenis_downtime",
        "a.status_eksekusi",
        "u_e.nama_lengkap",
        "a.catatan_eksekusi",
        null,
    ];
    var $column_search = [
        "a.no_tiket_iris",
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
            a.id_restart, a.no_tiket_iris, a.link_tiket, a.jenis_downtime, a.status_eksekusi,
            a.catatan_eksekusi, a.keterangan_request, a.created_at, a.tanggal_eksekusi,
            COALESCE(a.snapshot_vm_name, m.virtual_machine_name, 'VM Dihapus') AS nama_target_vm,
            COALESCE(a.snapshot_ip_address, m.ip_address, 'IP Tidak Diketahui') AS ip_target_vm,
            u_e.nama_lengkap AS nama_executor
        ",
            false,
        );

        $this->db->from("trx_vm_restart a");
        $this->db->join(
            "master_virtual_machine m",
            "m.id_virtual_machine = a.id_virtual_machine",
            "left",
        );
        $this->db->join("master_user u_e", "u_e.id_user = a.id_executor", "left");

        $filter_kpi = $this->input->post("filter_kpi");
        if (!empty($filter_kpi)) {
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
        $this->db->from("trx_vm_restart");
        return $this->db->count_all_results();
    }

    // ========================================================================
    // [ENTERPRISE FIX]: EXPORT EXCEL DATA (STREAMING UNBUFFERED)
    // ========================================================================
    public function get_data_export_query(?string $start_date = null, ?string $end_date = null)
    {
        $this->db->select(
            "
            a.created_at, a.start_downtime, a.finish_downtime, a.no_tiket_iris AS no_tiket, a.root_cause,
            COALESCE(a.snapshot_vm_name, m.virtual_machine_name, 'VM Dihapus') AS nama_server,
            COALESCE(a.snapshot_ip_address, m.ip_address, 'IP Tidak Diketahui') AS ip_server,
            a.jenis_downtime, a.durasi_downtime_menit, a.status_eksekusi, a.keterangan_request,
            a.catatan_eksekusi, a.catatan_verifikasi, u_e.nama_lengkap AS nama_executor
        ",
            false,
        );

        $this->db->from("trx_vm_restart a");
        $this->db->join(
            "master_virtual_machine m",
            "m.id_virtual_machine = a.id_virtual_machine",
            "left",
        );
        $this->db->join("master_user u_e", "u_e.id_user = a.id_executor", "left");

        if (!empty($start_date) && !empty($end_date)) {
            $safe_start = $this->db->escape($start_date);
            $safe_end = $this->db->escape($end_date);
            $this->db->where(
                "DATE(COALESCE(a.start_downtime, a.created_at)) >= $safe_start",
                null,
                false,
            );
            $this->db->where(
                "DATE(COALESCE(a.start_downtime, a.created_at)) <= $safe_end",
                null,
                false,
            );
        }

        $this->db->order_by("COALESCE(a.start_downtime, a.created_at)", "DESC", false);

        return $this->db->get();
    }

    public function get_data_export(?string $start_date = null, ?string $end_date = null)
    {
        $query = $this->get_data_export_query($start_date, $end_date);
        return $query->result_array();
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
            ->get("trx_vm_restart")
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
}
