<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Team_model extends CI_Model
{
	
	function get_all_team()
	{
		$this->db->select('*')->from('master_team');
		$query = $this->db->get();
		return $query->result_array();
	}
	function get($id_team)
	{
		$this->load->database();
		$query = $this->db->get_where('master_team',array('id_team'=>$id_team));
		return $query->row_array();
	}

	function simpan_data()
	{
		$simpan_data=array
		(
			'team_name'		=>$this->input->post('team_name'),
			'team_code'		=>$this->input->post('team_code'),
			'pic_name'		=>$this->input->post('pic_name'),
			'pic_contact'		=>$this->input->post('pic_contact')
		);

		$simpan = $this->db->insert('master_team',$simpan_data);
		return $simpan;
		
	}
	function update_data()
        {
            $data=array(
				'team_name'			=>$this->input->post('team_name'),
				'team_code'		=>$this->input->post('team_code'),
				'pic_name'		=>$this->input->post('pic_name'),
				'pic_contact'		=>$this->input->post('pic_contact')
			);
				$this->db->where('id_team',$this->input->post('id_team'));
                $this->db->update('master_team', $data);
        }

    function hapus($id_team){
		$this->db->query("delete from master_team where id_team = $id_team");
	}	



	
}
