<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Criticality_model extends CI_Model
{
	
	function get_all_criticality()
	{
		$this->db->select('*')->from('master_criticality');
		$query = $this->db->get();
		return $query->result_array();
	}
	function get($id_criticality)
	{
		$this->load->database();
		$query = $this->db->get_where('master_criticality',array('id_criticality'=>$id_criticality));
		return $query->row_array();
	}

	function simpan_data()
	{
		$maxid = $this->get_maxid();
		$tmaxid = 1+$maxid['id_criticality'];
		$simpan_data=array
		(
			//'id_criticality'		=>$tmaxid,
			'criticality_name'		=>$this->input->post('criticality_name')
		);

		$simpan = $this->db->insert('master_criticality',$simpan_data);
		return $simpan;
		
	}
	function update_data()
        {
            $data=array(
				'criticality_name'			=>$this->input->post('criticality_name')
			);
				$this->db->where('id_criticality',$this->input->post('id_criticality'));
                $this->db->update('master_criticality', $data);
        }

    function hapus($id_criticality){
		$this->db->query("delete from master_criticality where id_criticality = $id_criticality");
	}	
	
	function get_maxid(){
		$this->db->select('MAX(id_criticality) AS id_criticality');
		$this->db->from('master_criticality');
		$result = $this->db->get();
		return $result->row_array();
	}


	
}
