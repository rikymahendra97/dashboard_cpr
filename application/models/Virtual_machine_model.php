<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Virtual_machine_model extends CI_Model
{
	
	    /* =========================================================
     * DATATABLES SERVER SIDE - LIST VIRTUAL MACHINE
     * ========================================================= */

    var $column_order = array(
        null,
        'mas.uuid',
        'mas.virtual_machine_name',
        'mas.no_tiket_iris',
        'mas.vcenter_name',
        'mas.cluster_name',
        'mas.host_name',
        'mas.ip_address',
        'mas.ip_rubrik',
        'mas.id_site',
        'owner.team_code',
        'requestor.team_code',
        'application_systems',
        'components',
        'criticalities',
        null
    );

    var $column_search = array(
        'mas.uuid',
        'mas.virtual_machine_name',
        'mas.no_tiket_iris',
        'mas.vcenter_name',
        'mas.cluster_name',
        'mas.host_name',
        'mas.ip_address',
        'mas.ip_rubrik',
        'mas.id_site',
        'owner.team_code',
        'requestor.team_code',
        'requestor.pic_name',
        'app.application_system_name',
        'comp.component_name',
        'mc.criticality_name'
    );

    var $order = array('mas.virtual_machine_name' => 'asc');

    private function _get_datatables_query()
    {
        $this->db->select("
            mas.id_virtual_machine,
            mas.uuid,
            mas.virtual_machine_name,
            mas.no_tiket_iris,
            mas.vcenter_name,
            mas.cluster_name,
            mas.host_name,
            mas.ip_address,
            mas.ip_rubrik,
            mas.id_site,

            owner.team_code AS owner_team,
            requestor.team_code AS requestor_team,
            requestor.pic_name AS requestor_pic,

            GROUP_CONCAT(DISTINCT app.application_system_name SEPARATOR ', ') AS application_systems,
            GROUP_CONCAT(DISTINCT comp.component_name SEPARATOR ', ') AS components,
            GROUP_CONCAT(DISTINCT mc.criticality_name SEPARATOR ', ') AS criticalities
        ", false);

        $this->db->from('master_virtual_machine mas');

        $this->db->join('master_team owner', 'owner.id_team = mas.id_owner', 'left');
        $this->db->join('master_team requestor', 'requestor.id_team = mas.id_requestor', 'left');

        $this->db->join('relation_table rt', 'rt.id_virtual_machine = mas.id_virtual_machine', 'left');
        $this->db->join('master_application_system app', 'app.id_application_system = rt.id_application_system', 'left');
        $this->db->join('master_component comp', 'comp.id_component = rt.id_component', 'left');
        $this->db->join('master_criticality mc', 'mc.id_criticality = app.id_criticality', 'left');

        $search = $this->input->post('sSearch');

        if (!empty($search)) {
            $this->db->group_start();

            foreach ($this->column_search as $i => $item) {
                if ($i == 0) {
                    $this->db->like($item, $search);
                } else {
                    $this->db->or_like($item, $search);
                }
            }

            $this->db->group_end();
        }

        $this->db->group_by('mas.id_virtual_machine');

        if ($this->input->post('iSortCol_0') !== null) {
            $col_index = intval($this->input->post('iSortCol_0'));
            $dir = ($this->input->post('sSortDir_0') == 'desc') ? 'desc' : 'asc';

            if (isset($this->column_order[$col_index]) && $this->column_order[$col_index] != null) {
                $this->db->order_by($this->column_order[$col_index], $dir);
            }
        } else {
            $this->db->order_by(key($this->order), $this->order[key($this->order)]);
        }
    }

    public function get_datatables()
    {
        $this->_get_datatables_query();

        if ($this->input->post('iDisplayLength') != -1) {
            $this->db->limit(
                intval($this->input->post('iDisplayLength')),
                intval($this->input->post('iDisplayStart'))
            );
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
        $this->db->from('master_virtual_machine');
        return $this->db->count_all_results();
    }

	
	function get_all_virtual_machine()
	{
		$this->db->select('*')->from('master_virtual_machine');
		$query = $this->db->get();
		return $query->result_array();
	}


	public function get_all_virtual_machine_with_grouping()
	{
	    $this->db->select("
	        mas.id_virtual_machine,
	        mas.virtual_machine_name,
	        mas.no_tiket_iris,
	        mas.id_site,
	        mas.uuid,
	        mas.vcenter_name,
	        mas.cluster_name,
	        mas.host_name,
	        mas.power_state,
	        mas.guest_os,
	        mas.guest_os_manual,
	        mas.environment,
	        mas.ip_address,
	        mas.ip_address_2,
	        mas.ip_address_3,
	        mas.ip_rubrik,
	        mas.created_at,
	        mas.id_owner,
	        owner.team_code AS owner_team,
	        requestor.team_code AS requestor_team,
	        requestor.pic_name AS requestor_pic,

	        -- gabungkan SLA
	        GROUP_CONCAT(DISTINCT CONCAT(sla.sla_name, ' (', sla.sla_type, ')') SEPARATOR ', ') AS sla_list,

	        -- gabungkan Application System
	        GROUP_CONCAT(DISTINCT app.application_system_name SEPARATOR ', ') AS application_systems,

	        -- gabungkan Component
	        GROUP_CONCAT(DISTINCT comp.component_name SEPARATOR ', ') AS components,

	        -- gabungkan Criticality
	        GROUP_CONCAT(DISTINCT mc.criticality_name SEPARATOR ', ') AS criticalities,
	        GROUP_CONCAT(DISTINCT mc.criticality_rate SEPARATOR ', ') AS criticality_rates
	    ", false);

	    $this->db->from('master_virtual_machine mas');

	    // join master_team untuk ambil nama owner
	    $this->db->join('master_team owner', 'owner.id_team = mas.id_owner', 'left');
	    // join master_team untuk ambil nama owner
	    $this->db->join('master_team requestor', 'requestor.id_team = mas.id_requestor', 'left');

	    // join SLA
	    $this->db->join('relation_sla_rubrik rs', 'rs.id_virtual_machine = mas.id_virtual_machine', 'left');
	    $this->db->join('sla_rubrik sla', 'sla.id_sla_rubrik = rs.id_sla_rubrik', 'left');

	    // join Application System & Component
	    $this->db->join('relation_table rt', 'rt.id_virtual_machine = mas.id_virtual_machine', 'left');
	    $this->db->join('master_application_system app', 'app.id_application_system = rt.id_application_system', 'left');
	    $this->db->join('master_component comp', 'comp.id_component = rt.id_component', 'left');

	    // join Criticality
	    $this->db->join('master_criticality mc', 'mc.id_criticality = app.id_criticality', 'left');

	    // group by VM agar tidak duplicate
	    $this->db->group_by('mas.id_virtual_machine');

	    $query = $this->db->get();
	    return $query->result_array();
	}


    public function get_virtual_machine_incomplete()
	{
	    $this->db->select("
	        mas.id_virtual_machine,
	        mas.virtual_machine_name,
	        mas.no_tiket_iris,
	        mas.id_site,
	        mas.uuid,
	        mas.vcenter_name,
	        mas.cluster_name,
	        mas.host_name,
	        mas.power_state,
	        mas.guest_os,
	        mas.cpu_count,
	        mas.memory_mb,
	        mas.provisioned_gb,
	        mas.environment,
	        mas.ip_address,
	        mas.ip_address_2,
	        mas.ip_address_3,
	        mas.ip_rubrik,
	        mas.created_at,

	        owner.team_code AS owner_team,
	        requestor.team_code AS requestor_team,
	        requestor.pic_name AS requestor_pic,

	        -- gabungkan SLA
	        GROUP_CONCAT(DISTINCT CONCAT(sla.sla_name, ' (', sla.sla_type, ')') SEPARATOR ', ') AS sla_list,

	        -- gabungkan Application System
	        GROUP_CONCAT(DISTINCT app.application_system_name SEPARATOR ', ') AS application_systems,

	        -- gabungkan Component
	        GROUP_CONCAT(DISTINCT comp.component_name SEPARATOR ', ') AS components,

	        -- gabungkan Criticality
	        GROUP_CONCAT(DISTINCT mc.criticality_name SEPARATOR ', ') AS criticalities,
	        GROUP_CONCAT(DISTINCT mc.criticality_rate SEPARATOR ', ') AS criticality_rates
	    ", false);

	    $this->db->from('master_virtual_machine mas');
	    
	    // join master_team untuk ambil nama owner
	    $this->db->join('master_team owner', 'owner.id_team = mas.id_owner', 'left');
	    // join master_team untuk ambil nama owner
	    $this->db->join('master_team requestor', 'requestor.id_team = mas.id_requestor', 'left');

	    // join SLA
	    $this->db->join('relation_sla_rubrik rs', 'rs.id_virtual_machine = mas.id_virtual_machine', 'left');
	    $this->db->join('sla_rubrik sla', 'sla.id_sla_rubrik = rs.id_sla_rubrik', 'left');

	    // join Application System & Component
	    $this->db->join('relation_table rt', 'rt.id_virtual_machine = mas.id_virtual_machine', 'left');
	    $this->db->join('master_application_system app', 'app.id_application_system = rt.id_application_system', 'left');
	    $this->db->join('master_component comp', 'comp.id_component = rt.id_component', 'left');

	    // join Criticality (dari application system)
	    $this->db->join('master_criticality mc', 'mc.id_criticality = app.id_criticality', 'left');

	    // Filter kondisi:
	    $this->db->where("
	        rt.id_virtual_machine IS NULL 
	        OR mas.id_ops IS NULL OR mas.id_ops = 0
	        OR mas.id_dev IS NULL OR mas.id_dev = 0
	        OR mas.id_owner IS NULL OR mas.id_owner = 0
	        OR mas.id_requestor IS NULL OR mas.id_requestor = 0
	    ", null, false);

	    // group by VM agar tidak duplicate
	    $this->db->group_by('mas.id_virtual_machine');

	    $query = $this->db->get();
	    return $query->result_array();
	}



	function get($id_virtual_machine)
	{
		$this->load->database();
		$query = $this->db->get_where('master_virtual_machine',array('id_virtual_machine'=>$id_virtual_machine));
		return $query->row_array();
	}

	public function simpan_data()
	{
	    $data = array(
	        'uuid'                  => $this->input->post('uuid'),
	        'virtual_machine_name'  => $this->input->post('virtual_machine_name'),
	        'no_tiket_iris'         => $this->input->post('no_tiket_iris'),
	        'vcenter_name'          => $this->input->post('vcenter_name'),
	        'cluster_name'          => $this->input->post('cluster_name'),
	        'host_name'             => $this->input->post('host_name'),
	        'folder_path'           => $this->input->post('folder_path'),
	        'power_state'           => $this->input->post('power_state'),
	        'guest_os'              => $this->input->post('guest_os'),
	        'cpu_count'             => $this->input->post('cpu_count'),
	        'memory_mb'             => $this->input->post('memory_mb'),
	        'provisioned_gb'        => $this->input->post('provisioned_gb'),
	        'env'          			=> $this->input->post('id_env'),
	        'ip_address'            => $this->input->post('ip_address'),
	        'ip_address_2'          => $this->input->post('ip_address_2'),
	        'ip_address_3'          => $this->input->post('ip_address_3'),
	        'ip_rubrik'             => $this->input->post('ip_rubrik'),
	        'id_site'               => $this->input->post('id_site'),
	        'created_at'            => date('Y-m-d H:i:s'),
	        'updated_at'            => date('Y-m-d H:i:s'),
	        'is_active'             => 1
	    );

	    return $this->db->insert('master_virtual_machine', $data);
	}

	public function update_data()
	{
	    $data = array(
	        'uuid'                  => $this->input->post('uuid'),
	        'virtual_machine_name'  => $this->input->post('virtual_machine_name'),
	        'no_tiket_iris'         => $this->input->post('no_tiket_iris'),
	        'vcenter_name'          => $this->input->post('vcenter_name'),
	        'cluster_name'          => $this->input->post('cluster_name'),
	        'host_name'             => $this->input->post('host_name'),
	        'folder_path'           => $this->input->post('folder_path'),
	        'power_state'           => $this->input->post('power_state'),
	        'guest_os'              => $this->input->post('guest_os'),
	        'cpu_count'             => $this->input->post('cpu_count'),
	        'memory_mb'             => $this->input->post('memory_mb'),
	        'provisioned_gb'        => $this->input->post('provisioned_gb'),
	        'id_env'                => $this->input->post('id_env'),
	        'ip_address'            => $this->input->post('ip_address'),
	        'ip_address_2'          => $this->input->post('ip_address_2'),
	        'ip_address_3'          => $this->input->post('ip_address_3'),
	        'ip_rubrik'             => $this->input->post('ip_rubrik'),
	        'id_site'               => $this->input->post('id_site'),
	        'updated_at'            => date('Y-m-d H:i:s')
	    );

	    $this->db->where('id_virtual_machine', $this->input->post('id_virtual_machine'));
	    return $this->db->update('master_virtual_machine', $data);
	}


    function hapus($id_virtual_machine){
		$this->db->query("delete from master_virtual_machine where id_virtual_machine = $id_virtual_machine");
	}	
	


    function update_vm($id_vm, $data)
    {
        $this->db->where('id_virtual_machine', $id_vm);
        return $this->db->update('master_virtual_machine', $data);
    }

    function update_relation_table($id_vm, $apps, $components)
    {
        // hapus semua relasi lama
        $this->db->where('id_virtual_machine', $id_vm);
        $this->db->delete('relation_table'); 

        // insert ulang relasi aplikasi
        if (!empty($apps)) {
            foreach ($apps as $id_app) {
                $this->db->insert('relation_table', array(
                    'id_virtual_machine'    => $id_vm,
                    'id_application_system' => $id_app,
                    'id_component'          => null
                ));
            }
        }

        // insert ulang relasi component
        if (!empty($components)) {
            foreach ($components as $id_component) {
                $this->db->insert('relation_table', array(
                    'id_virtual_machine'    => $id_vm,
                    'id_application_system' => null,
                    'id_component'          => $id_component
                ));
            }
        }
    }

    function get_relation_apps($id_vm)
    {
        $this->db->select('id_application_system');
        $this->db->from('relation_table');
        $this->db->where('id_virtual_machine', $id_vm);
        $this->db->where('id_application_system IS NOT NULL', null, false);
        $result = $this->db->get()->result_array();

        return array_column($result, 'id_application_system'); 
    }

    function get_relation_components($id_vm)
    {
        $this->db->select('id_component');
        $this->db->from('relation_table');
        $this->db->where('id_virtual_machine', $id_vm);
        $this->db->where('id_component IS NOT NULL', null, false);
        $result = $this->db->get()->result_array();

        return array_column($result, 'id_component'); 
    }

    public function get_relation_sla($id_vm)
	{
	    $this->db->select('id_sla_rubrik');
	    $this->db->from('relation_sla_rubrik');
	    $this->db->where('id_virtual_machine', $id_vm);
	    $result = $this->db->get()->result_array();

	    return array_column($result, 'id_sla_rubrik'); 
	}

	public function update_relation_sla($id_vm, $sla_ids)
	{
	    // hapus SLA lama
	    $this->db->where('id_virtual_machine', $id_vm);
	    $this->db->delete('relation_sla_rubrik');

	    // insert SLA baru
	    if (!empty($sla_ids)) {
	        foreach ($sla_ids as $id_sla_rubrik) {
	            $this->db->insert('relation_sla_rubrik', [
	                'id_virtual_machine' => $id_vm,
	                'id_sla_rubrik'      => $id_sla_rubrik
	            ]);
	        }
	    }
	}

    function get_relation_pairs($id_vm)
	{
	    $this->db->select('id_application_system, id_component');
	    $this->db->from('relation_table');
	    $this->db->where('id_virtual_machine', $id_vm);
	    return $this->db->get()->result_array();
	}

	function update_relation_pairs($id_vm, $apps, $comps)
	{
	    $this->db->where('id_virtual_machine', $id_vm);
	    $this->db->delete('relation_table');

	    if (!empty($apps) && !empty($comps)) {
	        foreach ($apps as $i => $id_app) {
	            $id_comp = $comps[$i] ?? null;
	            if ($id_app && $id_comp) {
	                $this->db->insert('relation_table', [
	                    'id_virtual_machine'    => $id_vm,
	                    'id_application_system' => $id_app,
	                    'id_component'          => $id_comp
	                ]);
	            }
	        }
	    }
	}
  
    public function update_vm_relation_reverse($target_vm_id, $source_vm_id)
    {
        $this->db->where('id_virtual_machine', $target_vm_id);
        return $this->db->update('master_virtual_machine', [
            'id_vm_relation' => $source_vm_id
        ]);
    }



    


	
}
