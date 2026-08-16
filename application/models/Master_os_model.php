<?php
/**
 * ============================================================================
 * File Name    : Master_os_model.php
 * Modul        : Master OS
 * Purpose      : Model untuk manajemen data Master Operating System.
 * Architecture : Enterprise Standard CP-05
 * ============================================================================
 */
defined("BASEPATH") or exit("No direct script access allowed");

class Master_os_model extends CI_Model
{
    var $table = "master_os";
    var $column_order = [null, "os_family", "os_name", "is_active", null];
    var $column_search = ["os_family", "os_name"];
    var $order = ["os_family" => "asc", "os_name" => "asc"];

    private function _get_datatables_query()
    {
        $this->db->from($this->table);

        $i = 0;
        foreach ($this->column_search as $item) {
            if ($_POST["search"]["value"]) {
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
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    public function get_datatables()
    {
        $this->_get_datatables_query();
        if ($_POST["length"] != -1) {
            $this->db->limit($_POST["length"], $_POST["start"]);
        }
        return $this->db->get()->result();
    }

    public function count_filtered()
    {
        $this->_get_datatables_query();
        return $this->db->get()->num_rows();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    public function get_by_id(int $id_os)
    {
        return $this->db->where("id_os", $id_os)->get($this->table)->row_array();
    }

    public function insert_data(array $data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function update_data(int $id_os, array $data)
    {
        return $this->db->where("id_os", $id_os)->update($this->table, $data);
    }

    public function delete_data(int $id_os)
    {
        return $this->db->where("id_os", $id_os)->delete($this->table);
    }
}
