<?php
defined("BASEPATH") or exit("No direct script access allowed");

/**
 * ========================================================================
 * File Name    : Dashboard_model.php
 * Architecture : Aggregated O(1) Memory Database Query
 * Patch        : Strict Filter (id_site = 'TBN' & is_active = 1) applied matching DDL
 * ========================================================================
 */
class Dashboard_model extends CI_Model
{
    // ========================================================================
    // SECTION 1: [READ] NOTIFIKASI WIDGET
    // ========================================================================
    public function get_notification_counts(int $id_role)
    {
        $status_trx_vm = [];
        $status_provisioning = [];

        if (in_array($id_role, [0, 1, 2, 3, 4, 5], true)) {
            $status_trx_vm = ["Menunggu Eksekusi", "Telah Dieksekusi"];
            $status_provisioning = ["pending", "in progress"];
        } elseif (in_array($id_role, [6, 7], true)) {
            $status_trx_vm = ["Menunggu Eksekusi"];
            $status_provisioning = ["pending", "in progress"];
        } else {
            return [
                "change_vm" => 0,
                "switch_ip" => 0,
                "provisioning" => 0,
                "restart_vm" => 0,
                "vm_incident" => 0,
                "total" => 0,
            ];
        }

        $count_change_vm = $this->db
            ->where_in("status_eksekusi", $status_trx_vm)
            ->count_all_results("trx_vm_change_resource");
        $count_switch_ip = $this->db
            ->where_in("status_eksekusi", $status_trx_vm)
            ->count_all_results("trx_vm_switch_ip");
        $count_provisioning = $this->db
            ->where_in("progres_tiket", $status_provisioning)
            ->count_all_results("tiket_provisioning");
        $count_restart_vm = $this->db
            ->where_in("status_eksekusi", $status_trx_vm)
            ->count_all_results("trx_vm_restart");

        $count_incident = $this->db
            ->where_in("status_insiden", [
                "Open Tiket",
                "Review by Owner",
                "Apply Solution by Owner",
            ])
            ->count_all_results("trx_vm_utilization_incident");

        return [
            "change_vm" => $count_change_vm,
            "switch_ip" => $count_switch_ip,
            "provisioning" => $count_provisioning,
            "restart_vm" => $count_restart_vm,
            "vm_incident" => $count_incident,
            "total" =>
                $count_change_vm +
                $count_switch_ip +
                $count_provisioning +
                $count_restart_vm +
                $count_incident,
        ];
    }

    // ========================================================================
    // SECTION 2: [READ] TABEL ANTREAN
    // ========================================================================
    public function get_pending_tasks(int $id_role, string $tipe_modul)
    {
        $status_trx_vm = [];
        $status_provisioning = [];

        if (in_array($id_role, [0, 1, 2, 3, 4, 5], true)) {
            $status_trx_vm = ["Menunggu Eksekusi", "Telah Dieksekusi"];
            $status_provisioning = ["pending", "in progress"];
        } elseif (in_array($id_role, [6, 7], true)) {
            $status_trx_vm = ["Menunggu Eksekusi"];
            $status_provisioning = ["pending", "in progress"];
        } else {
            return [];
        }

        if ($tipe_modul === "provisioning") {
            return $this->db
                ->select(
                    "id_tiket AS id, no_tiket, link_tiket, nama_server AS nama_target, '-' AS ip_target, CONCAT(environment, ' / ', aplikasi) AS detail_request, progres_tiket AS status_eksekusi, keterangan AS catatan_eksekusi",
                    false,
                )
                ->where_in("progres_tiket", $status_provisioning)
                ->order_by("tanggal_masuk_tiket", "DESC")
                ->get("tiket_provisioning")
                ->result_array();
        }

        if ($tipe_modul === "change_vm") {
            return $this->db
                ->select(
                    "a.id_change AS id, a.no_tiket_eksternal AS no_tiket, a.link_tiket_eksternal AS link_tiket, COALESCE(a.snapshot_vm_name, m.virtual_machine_name, 'VM Dihapus') AS nama_target, COALESCE(a.snapshot_ip_address, m.ip_address, '-') AS ip_target, a.jenis_perubahan AS detail_request, a.status_eksekusi, a.catatan_eksekusi",
                    false,
                )
                ->from("trx_vm_change_resource a")
                ->join(
                    "master_virtual_machine m",
                    "m.id_virtual_machine = a.id_virtual_machine",
                    "left",
                )
                ->where_in("a.status_eksekusi", $status_trx_vm)
                ->order_by("a.created_at", "DESC")
                ->get()
                ->result_array();
        }

        if ($tipe_modul === "switch_ip") {
            return $this->db
                ->select(
                    "a.id_switch AS id, a.no_tiket_eksternal AS no_tiket, a.link_tiket_eksternal AS link_tiket, (SELECT GROUP_CONCAT(b.nama_vm_lama SEPARATOR ', ') FROM trx_vm_switch_ip_detail b WHERE b.id_switch = a.id_switch) AS nama_target, (SELECT b.ip_lama FROM trx_vm_switch_ip_detail b WHERE b.id_switch = a.id_switch LIMIT 1) AS ip_target, a.jenis_switch AS detail_request, a.status_eksekusi, a.catatan_eksekusi",
                    false,
                )
                ->from("trx_vm_switch_ip a")
                ->where_in("a.status_eksekusi", $status_trx_vm)
                ->order_by("a.created_at", "DESC")
                ->get()
                ->result_array();
        }

        if ($tipe_modul === "restart_vm") {
            return $this->db
                ->select(
                    "a.id_restart AS id, a.no_tiket_iris AS no_tiket, a.link_tiket, COALESCE(a.snapshot_vm_name, m.virtual_machine_name, 'VM Dihapus') AS nama_target, COALESCE(a.snapshot_ip_address, m.ip_address, '-') AS ip_target, CONCAT('Tipe: ', a.jenis_downtime) AS detail_request, a.status_eksekusi, a.catatan_eksekusi",
                    false,
                )
                ->from("trx_vm_restart a")
                ->join(
                    "master_virtual_machine m",
                    "m.id_virtual_machine = a.id_virtual_machine",
                    "left",
                )
                ->where_in("a.status_eksekusi", $status_trx_vm)
                ->order_by("a.created_at", "DESC")
                ->get()
                ->result_array();
        }
        return [];
    }

    // ========================================================================
    // SECTION 3: [READ] TICKET DETAIL
    // ========================================================================
    public function get_ticket_detail(int $id_trx, string $tipe_modul)
    {
        if ($tipe_modul === "provisioning") {
            return $this->db
                ->get_where("tiket_provisioning", ["id_tiket" => (int) $id_trx])
                ->row_array();
        } elseif ($tipe_modul === "change_vm") {
            return $this->db
                ->select(
                    "a.*, COALESCE(a.snapshot_vm_name, m.virtual_machine_name, 'VM Dihapus') as virtual_machine_name, COALESCE(a.snapshot_ip_address, m.ip_address, '-') as ip_address",
                    false,
                )
                ->from("trx_vm_change_resource a")
                ->join(
                    "master_virtual_machine m",
                    "m.id_virtual_machine = a.id_virtual_machine",
                    "left",
                )
                ->where("a.id_change", (int) $id_trx)
                ->get()
                ->row_array();
        } elseif ($tipe_modul === "switch_ip") {
            return $this->db
                ->select(
                    "a.*, (SELECT GROUP_CONCAT(CONCAT(b.nama_vm_lama, ' (', b.ip_lama, ')') SEPARATOR ', ') FROM trx_vm_switch_ip_detail b WHERE b.id_switch = a.id_switch) AS info_vm",
                    false,
                )
                ->from("trx_vm_switch_ip a")
                ->where("a.id_switch", (int) $id_trx)
                ->get()
                ->row_array();
        } elseif ($tipe_modul === "restart_vm") {
            return $this->db
                ->select(
                    "a.*, COALESCE(a.snapshot_vm_name, m.virtual_machine_name, 'VM Dihapus') as virtual_machine_name, COALESCE(a.snapshot_ip_address, m.ip_address, '-') as ip_address",
                    false,
                )
                ->from("trx_vm_restart a")
                ->join(
                    "master_virtual_machine m",
                    "m.id_virtual_machine = a.id_virtual_machine",
                    "left",
                )
                ->where("a.id_restart", (int) $id_trx)
                ->get()
                ->row_array();
        }
        return false;
    }

    public function get_change_disk_details(int $id_change)
    {
        return $this->db
            ->get_where("trx_vm_change_disk", ["id_change" => $id_change])
            ->result_array();
    }

    // ========================================================================
    // SECTION 4: [UPDATE] WORKFLOW & HAMBATAN
    // ========================================================================
    public function update_ticket_status(
        int $id_trx,
        string $modul,
        string $action_type,
        string $catatan,
        int $id_user,
        string $username,
    ) {
        $now = date("Y-m-d H:i:s");
        $id_trx = (int) $id_trx;

        $data =
            $action_type === "execute"
                ? [
                    "status_eksekusi" => "Telah Dieksekusi",
                    "catatan_eksekusi" => $catatan,
                    "id_executor" => $id_user,
                    "tanggal_eksekusi" => $now,
                ]
                : [
                    "status_eksekusi" => "Selesai Verified",
                    "catatan_verifikasi" => $catatan,
                    "id_verifikator" => $id_user,
                    "tanggal_verifikasi" => $now,
                ];

        if ($modul === "restart_vm") {
            if ($action_type === "execute") {
                $start = $this->input->post("start_downtime", true);
                $finish = $this->input->post("finish_downtime", true);
                if (!empty($start) && !empty($finish)) {
                    $data["start_downtime"] = str_replace("T", " ", $start) . ":00";
                    $data["finish_downtime"] = str_replace("T", " ", $finish) . ":00";
                    $diff =
                        strtotime($data["finish_downtime"]) - strtotime($data["start_downtime"]);
                    $data["durasi_downtime_menit"] = $diff > 0 ? ceil($diff / 60) : 0;
                }
            }
            return $this->db->where("id_restart", $id_trx)->update("trx_vm_restart", $data);
        }

        if ($modul === "change_vm") {
            return $this->db->where("id_change", $id_trx)->update("trx_vm_change_resource", $data);
        }

        if ($modul === "switch_ip") {
            return $this->db->where("id_switch", $id_trx)->update("trx_vm_switch_ip", $data);
        }

        if ($modul === "provisioning") {
            $tiket = $this->db
                ->select("keterangan")
                ->get_where("tiket_provisioning", ["id_tiket" => $id_trx])
                ->row();
            if (!$tiket) {
                return false;
            }
            $cat_info = $action_type === "execute" ? "Eksekusi" : "Verifikasi";
            $catatan_gabungan =
                $tiket->keterangan . "\n\n[Log Dashboard - $cat_info]:\n" . $catatan;

            $data_prov =
                $action_type === "execute"
                    ? [
                        "progres_tiket" => "In Progress",
                        "setup_by" => $username,
                        "keterangan" => $catatan_gabungan,
                    ]
                    : [
                        "progres_tiket" => "Done",
                        "closed_by" => $username,
                        "tanggal_keluar_tiket" => $now,
                        "keterangan" => $catatan_gabungan,
                    ];

            return $this->db->where("id_tiket", $id_trx)->update("tiket_provisioning", $data_prov);
        }

        return false;
    }

    public function update_hambatan(int $id_trx, string $modul, string $hambatan)
    {
        $table_map = [
            "change_vm" => ["t" => "trx_vm_change_resource", "pk" => "id_change"],
            "switch_ip" => ["t" => "trx_vm_switch_ip", "pk" => "id_switch"],
            "restart_vm" => ["t" => "trx_vm_restart", "pk" => "id_restart"],
            "provisioning" => ["t" => "tiket_provisioning", "pk" => "id_tiket"],
        ];

        if (!isset($table_map[$modul])) {
            return false;
        }
        $conf = $table_map[$modul];

        if ($modul === "provisioning") {
            $tiket = $this->db
                ->select("keterangan")
                ->get_where($conf["t"], [$conf["pk"] => (int) $id_trx])
                ->row();
            return $this->db->where($conf["pk"], (int) $id_trx)->update($conf["t"], [
                "keterangan" => $tiket->keterangan . "\n\n[INFO KENDALA]: " . $hambatan,
            ]);
        }
        return $this->db
            ->where($conf["pk"], (int) $id_trx)
            ->update($conf["t"], ["catatan_eksekusi" => $hambatan]);
    }

    // ========================================================================
    // SECTION 5: [READ] ACTIVITY TIMELINE ENGINE
    // ========================================================================
    public function get_recent_activities(int $limit = 6)
    {
        $query = "
            SELECT no_tiket, modul, status, waktu, target, aktor FROM (
                SELECT CAST(a.no_tiket_eksternal AS CHAR) as no_tiket, 'Change Resource' as modul, CAST(a.status_eksekusi AS CHAR) as status, COALESCE(a.tanggal_verifikasi, a.tanggal_eksekusi, a.created_at) as waktu, CAST(COALESCE(a.snapshot_vm_name, m.virtual_machine_name, 'VM Dihapus') AS CHAR) as target, CAST(COALESCE(u.nama_lengkap, 'Tim Ops') AS CHAR) as aktor
                FROM trx_vm_change_resource a LEFT JOIN master_virtual_machine m ON a.id_virtual_machine = m.id_virtual_machine LEFT JOIN master_user u ON u.id_user = COALESCE(a.id_verifikator, a.id_executor, a.id_pencatat) WHERE LOWER(a.status_eksekusi) IN ('telah dieksekusi', 'selesai verified')
                UNION ALL
                SELECT CAST(a.no_tiket_eksternal AS CHAR) as no_tiket, 'Switch IP' as modul, CAST(a.status_eksekusi AS CHAR) as status, COALESCE(a.tanggal_verifikasi, a.tanggal_eksekusi, a.created_at) as waktu, CAST((SELECT GROUP_CONCAT(b.nama_vm_lama SEPARATOR ', ') FROM trx_vm_switch_ip_detail b WHERE b.id_switch = a.id_switch) AS CHAR) as target, CAST(COALESCE(u.nama_lengkap, 'Tim Ops') AS CHAR) as aktor
                FROM trx_vm_switch_ip a LEFT JOIN master_user u ON u.id_user = COALESCE(a.id_verifikator, a.id_executor, a.id_pencatat) WHERE LOWER(a.status_eksekusi) IN ('telah dieksekusi', 'selesai verified')
                UNION ALL
                SELECT CAST(no_tiket AS CHAR) as no_tiket, 'Provisioning' as modul, CAST(progres_tiket AS CHAR) as status, COALESCE(tanggal_keluar_tiket, tanggal_masuk_tiket, created_at) as waktu, CAST(nama_server AS CHAR) as target, CAST(COALESCE(closed_by, setup_by, created_by, 'Tim Ops') AS CHAR) as aktor
                FROM tiket_provisioning WHERE LOWER(progres_tiket) IN ('in progress', 'done')
                UNION ALL
                SELECT CAST(a.no_tiket_iris AS CHAR) as no_tiket, 'Restart VM' as modul, CAST(a.status_eksekusi AS CHAR) as status, COALESCE(a.tanggal_verifikasi, a.tanggal_eksekusi, a.created_at) as waktu, CAST(COALESCE(a.snapshot_vm_name, m.virtual_machine_name, 'VM Dihapus') AS CHAR) as target, CAST(COALESCE(u.nama_lengkap, 'Tim Ops') AS CHAR) as aktor
                FROM trx_vm_restart a LEFT JOIN master_virtual_machine m ON a.id_virtual_machine = m.id_virtual_machine LEFT JOIN master_user u ON u.id_user = COALESCE(a.id_verifikator, a.id_executor, a.id_pencatat) WHERE LOWER(a.status_eksekusi) IN ('telah dieksekusi', 'selesai verified')
                UNION ALL
                SELECT CAST(a.no_tiket_insiden AS CHAR) as no_tiket, 'Insiden Utilisasi' as modul, CAST(a.status_insiden AS CHAR) as status, COALESCE(a.resolved_at, a.created_at) as waktu, CAST(COALESCE(a.snapshot_vm_name, m.virtual_machine_name, 'VM Dihapus') AS CHAR) as target, CAST(COALESCE(u.nama_lengkap, 'Tim Ops') AS CHAR) as aktor
                FROM trx_vm_utilization_incident a LEFT JOIN master_virtual_machine m ON a.id_virtual_machine = m.id_virtual_machine LEFT JOIN master_user u ON u.id_user = COALESCE(a.id_assignee, a.id_pelapor) WHERE LOWER(a.status_insiden) IN ('done/close')
            ) AS combined_data ORDER BY waktu DESC LIMIT $limit
        ";
        $result = $this->db->query($query);
        return $result ? $result->result_array() : [];
    }

    public function get_chart_stats_by_date(
        string $start_date,
        string $end_date,
        string $table,
        string $date_column,
        string $pk_column,
        string $status_column,
        string $exclude_status,
    ) {
        $query = $this->db->query(
            "
            SELECT DATE($date_column) as tanggal, COUNT($pk_column) as total
            FROM $table
            WHERE DATE($date_column) >= ? AND DATE($date_column) <= ?
              AND $status_column != ?
            GROUP BY DATE($date_column)
            ORDER BY tanggal ASC
        ",
            [$start_date, $end_date, $exclude_status],
        );

        return $query->result_array();
    }

    public function get_resource_growth_stats(string $start_date, string $end_date)
    {
        // [ENTERPRISE FIX]: Penyesuaian nama kolom sesuai DDL yaitu m.id_site = 'TBN'
        $prov = $this->db
            ->query(
                "
            SELECT DATE(t.tanggal_masuk_tiket) as tgl, SUM(t.cpu) as cpu, SUM(t.ram) as ram, SUM(t.disk) as disk
            FROM tiket_provisioning t
            LEFT JOIN master_virtual_machine m ON t.id_virtual_machine = m.id_virtual_machine
            WHERE DATE(t.tanggal_masuk_tiket) >= ? AND DATE(t.tanggal_masuk_tiket) <= ?
            AND t.progres_tiket IN ('done', 'in progress')
            AND (m.id_site = 'TBN' AND m.is_active = 1 OR t.id_virtual_machine IS NULL OR t.id_virtual_machine = 0)
            GROUP BY DATE(t.tanggal_masuk_tiket)
        ",
                [$start_date, $end_date],
            )
            ->result_array();

        $urr_res = $this->db
            ->query(
                "
            SELECT DATE(a.created_at) as tgl,
                   SUM(a.target_cpu_count - a.current_cpu_count) as cpu,
                   SUM((a.target_memory_mb - a.current_memory_mb) / 1024) as ram
            FROM trx_vm_change_resource a
            JOIN master_virtual_machine m ON a.id_virtual_machine = m.id_virtual_machine
            WHERE DATE(a.created_at) >= ? AND DATE(a.created_at) <= ?
            AND a.status_eksekusi IN ('Telah Dieksekusi', 'Selesai Verified')
            AND m.id_site = 'TBN' AND m.is_active = 1
            GROUP BY DATE(a.created_at)
        ",
                [$start_date, $end_date],
            )
            ->result_array();

        $urr_disk = $this->db
            ->query(
                "
            SELECT DATE(r.created_at) as tgl, SUM(d.additional_gb) as disk
            FROM trx_vm_change_disk d
            JOIN trx_vm_change_resource r ON d.id_change = r.id_change
            JOIN master_virtual_machine m ON r.id_virtual_machine = m.id_virtual_machine
            WHERE DATE(r.created_at) >= ? AND DATE(r.created_at) <= ?
            AND r.status_eksekusi IN ('Telah Dieksekusi', 'Selesai Verified')
            AND m.id_site = 'TBN' AND m.is_active = 1
            GROUP BY DATE(r.created_at)
        ",
                [$start_date, $end_date],
            )
            ->result_array();

        return [
            "prov" => $prov,
            "urr_res" => $urr_res,
            "urr_disk" => $urr_disk,
        ];
    }

    public function get_strict_executed_tickets(
        string $start_date,
        string $end_date,
        string $table,
        string $date_col,
        string $pk_col,
        string $status_col,
        array $valid_statuses,
    ) {
        $in_clause = "'" . implode("','", $valid_statuses) . "'";

        // [ENTERPRISE FIX]: Penyesuaian nama kolom sesuai DDL yaitu m.id_site = 'TBN'
        $query = $this->db->query(
            "
            SELECT DATE(a.$date_col) as tgl, a.$pk_col, a.id_virtual_machine
            FROM $table a
            LEFT JOIN master_virtual_machine m ON a.id_virtual_machine = m.id_virtual_machine
            WHERE DATE(a.$date_col) >= ? AND DATE(a.$date_col) <= ?
            AND a.$status_col IN ($in_clause)
            AND (m.id_site = 'TBN' AND m.is_active = 1 OR a.id_virtual_machine IS NULL OR a.id_virtual_machine = 0)
        ",
            [$start_date, $end_date],
        );

        return $query->result_array();
    }

    public function get_ticket_status_summary(string $start_date, string $end_date)
    {
        $prov = $this->db
            ->query(
                "
            SELECT
                SUM(CASE WHEN progres_tiket IN ('done', 'in progress') THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN progres_tiket = 'pending' THEN 1 ELSE 0 END) as antre
            FROM tiket_provisioning
            WHERE DATE(tanggal_masuk_tiket) >= ? AND DATE(tanggal_masuk_tiket) <= ?
        ",
                [$start_date, $end_date],
            )
            ->row_array();

        $urr = $this->db
            ->query(
                "
            SELECT
                SUM(CASE WHEN status_eksekusi IN ('Telah Dieksekusi', 'Selesai Verified') THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN status_eksekusi = 'Menunggu Eksekusi' THEN 1 ELSE 0 END) as antre
            FROM trx_vm_change_resource
            WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
        ",
                [$start_date, $end_date],
            )
            ->row_array();

        $restart = $this->db
            ->query(
                "
            SELECT
                SUM(CASE WHEN status_eksekusi IN ('Telah Dieksekusi', 'Selesai Verified') THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN status_eksekusi = 'Menunggu Eksekusi' THEN 1 ELSE 0 END) as antre
            FROM trx_vm_restart
            WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
        ",
                [$start_date, $end_date],
            )
            ->row_array();

        $switch = $this->db
            ->query(
                "
            SELECT
                SUM(CASE WHEN status_eksekusi IN ('Telah Dieksekusi', 'Selesai Verified') THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN status_eksekusi = 'Menunggu Eksekusi' THEN 1 ELSE 0 END) as antre
            FROM trx_vm_switch_ip
            WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
        ",
                [$start_date, $end_date],
            )
            ->row_array();

        return [
            "prov" => ["selesai" => (int) $prov["selesai"], "antre" => (int) $prov["antre"]],
            "urr" => ["selesai" => (int) $urr["selesai"], "antre" => (int) $urr["antre"]],
            "restart" => [
                "selesai" => (int) $restart["selesai"],
                "antre" => (int) $restart["antre"],
            ],
            "switch" => ["selesai" => (int) $switch["selesai"], "antre" => (int) $switch["antre"]],
        ];
    }

    // ========================================================================
    // [ENTERPRISE FIX]: AREA MASTER VM DENGAN FILTER NAMA KOLOM `id_site`
    // ========================================================================
    public function get_anchor_active_vms()
    {
        $query = $this->db->query(
            "SELECT COUNT(id_virtual_machine) as total FROM master_virtual_machine WHERE is_active = 1 AND id_site = 'TBN'",
        );
        return (int) $query->row()->total;
    }

    public function get_daily_creations(string $start_date)
    {
        return $this->db
            ->query(
                "
            SELECT DATE(created_at) as tgl, COUNT(id_virtual_machine) as qty
            FROM master_virtual_machine
            WHERE DATE(created_at) >= ? AND is_active = 1 AND id_site = 'TBN'
            GROUP BY DATE(created_at)
        ",
                [$start_date],
            )
            ->result_array();
    }

    public function get_daily_deletions(string $start_date)
    {
        return $this->db
            ->query(
                "
            SELECT DATE(h.changed_at) as tgl, COUNT(h.history_id) as qty
            FROM history_virtual_machine h
            JOIN master_virtual_machine m ON h.id_virtual_machine = m.id_virtual_machine
            WHERE h.change_type = 'DELETE' AND DATE(h.changed_at) >= ? AND m.id_site = 'TBN'
            GROUP BY DATE(h.changed_at)
        ",
                [$start_date],
            )
            ->result_array();
    }
}
