<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Component_model extends CI_Model
{
	
	function get_all_component()
	{
		$this->db->select('*')->from('master_component');
		$query = $this->db->get();
		return $query->result_array();
	}
	function get($id_component)
	{
		$this->load->database();
		$query = $this->db->get_where('master_component',array('id_component'=>$id_component));
		return $query->row_array();
	}

	function simpan_data()
	{
		$maxid = $this->get_maxid();
		$tmaxid = 1+$maxid['id_component'];
		$simpan_data=array
		(
			//'id_component'		=>$tmaxid,
			'component_name'		=>$this->input->post('component_name')
		);

		$simpan = $this->db->insert('master_component',$simpan_data);
		return $simpan;
		
	}
	function update_data()
        {
            $data=array(
				'component_name'			=>$this->input->post('component_name')
			);
				$this->db->where('id_component',$this->input->post('id_component'));
                $this->db->update('master_component', $data);
        }

    function hapus($id_component){
		$this->db->query("delete from master_component where id_component = $id_component");
	}	
	
	function get_maxid(){
		$this->db->select('MAX(id_component) AS id_component');
		$this->db->from('master_component');
		$result = $this->db->get();
		return $result->row_array();
	}


	
}
