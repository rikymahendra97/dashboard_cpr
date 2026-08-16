<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Application_system_model extends CI_Model
{
	
	function get_all_application_system()
	{
		$this->db->select('*')->from('master_application_system');
		$query = $this->db->get();
		return $query->result_array();
	}

    function get_all_application_system_with_criticality()
	{
	    $this->db->select('
	        mas.id_application_system,
	        mas.application_system_name,
	        mas.id_criticality,
	        mc.criticality_name,
	        mc.criticality_rate,
	        mas.update_at as application_update_at,
	        mc.update_at as criticality_update_at
	    ');
	    $this->db->from('master_application_system mas');
	    $this->db->join('master_criticality mc', 'mas.id_criticality = mc.id_criticality', 'left');
	    $query = $this->db->get();
	    return $query->result_array();
	}


	function get($id_application_system)
	{
		$this->load->database();
		$query = $this->db->get_where('master_application_system',array('id_application_system'=>$id_application_system));
		return $query->row_array();
	}

	function simpan_data()
	{
		$maxid = $this->get_maxid();
		$tmaxid = 1+$maxid['id_application_system'];
		$simpan_data=array
		(
			//'id_application_system'		=>$tmaxid,
			'application_system_name'		=>$this->input->post('application_system_name'),
			'id_criticality' => $this->input->post('id_criticality')
		);

		$simpan = $this->db->insert('master_application_system',$simpan_data);
		return $simpan;
		
	}
	function update_data()
        {
            $data=array(
				'application_system_name'			=>$this->input->post('application_system_name'),
				'id_criticality' => $this->input->post('id_criticality')
			);
				$this->db->where('id_application_system',$this->input->post('id_application_system'));
                $this->db->update('master_application_system', $data);
        }

    function hapus($id_application_system){
		$this->db->query("delete from master_application_system where id_application_system = $id_application_system");
	}	
	
	function get_maxid(){
		$this->db->select('MAX(id_application_system) AS id_application_system');
		$this->db->from('master_application_system');
		$result = $this->db->get();
		return $result->row_array();
	}


	
}
