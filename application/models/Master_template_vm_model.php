<?php
/**
 * ============================================================================
 * File Name    : Master_template_vm_model.php
 * Modul        : Master Template VM
 * Purpose      : Model untuk manajemen data Master Template / Source Clone.
 * Architecture : Enterprise Standard CP-05
 * ============================================================================
 */
defined("BASEPATH") or exit("No direct script access allowed");

class Master_template_vm_model extends CI_Model
{
    var $table = "master_template_vm";
    var $column_order = [null, "template_family", "template_name", "is_active", "created_at", null];
    var $column_search = ["template_family", "template_name"];
    var $order = ["template_family" => "asc", "template_name" => "asc"];

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

    public function get_by_id(int $id_template)
    {
        return $this->db->where("id_template", $id_template)->get($this->table)->row_array();
    }

    public function insert_data(array $data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function update_data(int $id_template, array $data)
    {
        return $this->db->where("id_template", $id_template)->update($this->table, $data);
    }

    public function delete_data(int $id_template)
    {
        return $this->db->where("id_template", $id_template)->delete($this->table);
    }
}
