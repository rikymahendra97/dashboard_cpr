<?php if (!defined("BASEPATH")) {
    exit("No direct script access allowed");
}

/**
 * ========================================================================
 * File Name    : User_model.php
 * Modul        : User Management & Profile
 * Purpose      : Operasi Basis Data untuk entitas Master User.
 * Architecture : Enterprise CP-05 (Decoupled from HTTP POST, Clean AR)
 * ========================================================================
 */
class User_model extends CI_Model
{
    public function get_all_user()
    {
        $this->db->select("*")->from("master_user");
        return $this->db->get()->result_array();
    }

    public function get_all_user_complete()
    {
        $this->db->select("mu.*, ur.nama_role");
        $this->db->from("master_user as mu");
        $this->db->join("user_role as ur", "ur.id_role = mu.id_role", "left");
        return $this->db->get()->result_array();
    }

    public function get_log_user()
    {
        // Limit ditambah agar tidak terjadi Out-Of-Memory (OOM)
        // jika tabel log berisi ratusan ribu baris.
        $this->db->select("mu.*, ur.nama_role, l.date_created");
        $this->db->from("loginlog as l");
        $this->db->join("master_user as mu", "l.user_id = mu.id_user", "left");
        $this->db->join("user_role as ur", "ur.id_role = mu.id_role", "left");
        $this->db->order_by("l.date_created", "DESC");
        $this->db->limit(1500);
        return $this->db->get()->result_array();
    }

    public function get(int $id_user)
    {
        $this->db->select("mu.*, ur.nama_role");
        $this->db->from("master_user as mu");
        $this->db->join("user_role as ur", "ur.id_role = mu.id_role", "left");
        $this->db->where("mu.id_user", $id_user);
        return $this->db->get()->row_array();
    }

    // Menerima array $data dari Controller. Bebas dari $_POST.
    public function simpan_data(array $data): bool
    {
        return $this->db->insert("master_user", $data);
    }

    // Penggabungan fungsi update, menerima array dinamis dari Controller.
    public function update_data(int $id_user, array $data): bool
    {
        $this->db->where("id_user", $id_user);
        return $this->db->update("master_user", $data);
    }

    // Mencegah SQL Injection dengan Active Record.
    public function hapus(int $id_user): bool
    {
        $this->db->where("id_user", $id_user);
        return $this->db->delete("master_user");
    }

    public function get_all_role()
    {
        $this->db->select("*");
        $this->db->from("user_role");
        return $this->db->get()->result_array();
    }

    public function cek_username(string $username)
    {
        $this->db->select("id_user, username");
        $this->db->from("master_user");
        $this->db->where("username", $username);
        return $this->db->get()->row_array();
    }
}
