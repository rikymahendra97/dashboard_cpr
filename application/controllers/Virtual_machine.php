<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Virtual_machine extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		if(is_null($this->session->userdata('user_data'))) {
			redirect(site_url()."/login");
		}
		
		//$this->load->library('csrf');
		$this->load->model('virtual_machine_model');
		$this->load->model('criticality_model');
		$this->load->model('user_model');
		$this->load->library('Mobile_Detect');
    }
	
	public function index()
	{
			redirect(site_url()."/virtual_machine/get_list_virtual_machine");
	}

	public function ajax_list()
	{
		$list = $this->virtual_machine_model->get_datatables();

		$data = array();
		$no = $this->input->post('iDisplayStart');

		foreach ($list as $vm) {
			$no++;

			$row = array();
			$row[] = $no;
			$row[] = $vm->uuid;
			$row[] = $vm->virtual_machine_name;
			$row[] = $vm->no_tiket_iris;
			$row[] = $vm->vcenter_name;
			$row[] = $vm->cluster_name;
			$row[] = $vm->host_name;
			$row[] = $vm->ip_address;
			$row[] = $vm->ip_rubrik;
			$row[] = $vm->id_site;
			$row[] = $vm->owner_team;
			$row[] = $vm->requestor_team . ' - ' . $vm->requestor_pic;
			$row[] = $vm->application_systems;
			$row[] = $vm->components;
			$row[] = $vm->criticalities;

			$row[] = '
				<a href="'.site_url('virtual_machine/edit_virtual_machine_complex/'.$vm->id_virtual_machine).'" target="_blank" class="btn detail_icon btn-xs btn-info">
					<i class="fa fa-edit"></i>
				</a>
				<a href="'.site_url('virtual_machine/hapus/'.$vm->id_virtual_machine).'" class="btn btn-danger btn-xs btn_delete">
					<i class="fa fa-trash-o"></i>
				</a>
			';

			$data[] = $row;
		}

		echo json_encode(array(
		"sEcho" => intval($this->input->post('sEcho')),
		"iTotalRecords" => $this->virtual_machine_model->count_all(),
		"iTotalDisplayRecords" => $this->virtual_machine_model->count_filtered(),
		"aaData" => $data
	));
	exit;
	}

	public function get_list_virtual_machine()
	{	
	    // ambil waktu saat ini
	    $time = date('Y-m-d H:i:s');

	    // ambil data VM lengkap dengan relasi
	    $data['list_virtual_machine'] = array();

	    // ambil daftar criticality (misal untuk dropdown/filter di view)
	    $data['list_criticality'] = $this->criticality_model->get_all_criticality();

	    // ambil user session
	    $id = $this->session->userdata('user_data');
	    $data['id'] = $id;
	    $data['user_session'] = $this->user_model->get($id['id_user']);

	    // setting untuk view
	    $data['page_title'] = 'Daftar Virtual Machine';
	    $data['css_arr'] = array('datatables.css');
	    $data['js_arr'] = array('datatables/jquery.dataTables.min.js');

	    // load view
	    $this->load->view('main/1head', $data);
	    $this->load->view('main/2sidebar', $data);
	    $this->load->view('main/3topnavigation', $data);
	    $this->load->view('list_virtual_machine', $data);
	    $this->load->view('main/5footer', $data);
	    $this->load->view('main/6bottom', $data);
	}


	public function get_list_vm_need_validation()
	{	
	    // ambil waktu saat ini
	    $time = date('Y-m-d H:i:s');

	    // ambil data VM lengkap dengan relasi
	    $data['list_virtual_machine'] = $this->virtual_machine_model->get_virtual_machine_incomplete();

	    // ambil daftar criticality (misal untuk dropdown/filter di view)
	    $data['list_criticality'] = $this->criticality_model->get_all_criticality();

	    // ambil user session
	    $id = $this->session->userdata('user_data');
	    $data['id'] = $id;
	    $data['user_session'] = $this->user_model->get($id['id_user']);

	    // setting untuk view
	    $data['page_title'] = 'Daftar Virtual Machine Need Validation';
	    $data['css_arr'] = array('datatables.css');
	    $data['js_arr'] = array('datatables/jquery.dataTables.min.js');

	    // load view
	    $this->load->view('main/1head', $data);
	    $this->load->view('main/2sidebar', $data);
	    $this->load->view('main/3topnavigation', $data);
	    $this->load->view('list_virtual_machine', $data);
	    $this->load->view('main/5footer', $data);
	    $this->load->view('main/6bottom', $data);
	}


			
    public function simpan_data() {
		$nama = $this->input->post('virtual_machine_name', true);
		if ($nama === '') { redirect(site_url()."/virtual_machine"); }
		$this->load->model('virtual_machine_model');
		$this->virtual_machine_model->simpan_data();
		redirect(site_url()."/virtual_machine");
    }

	function update_data()
    {
		
	    $this->load->model('virtual_machine_model');
	    $this->virtual_machine_model->update_data();
	    $alerts[] = array('message', 'Data berhasil disimpan!');
	    $this->session->set_flashdata('alerts', $alerts);
	    //$data['judul']='Insert Data Berhasil';
	    redirect(site_url()."/virtual_machine");
	    //$this->load->view('input/tambah',$data);
		
	}


	public function update_data_complex()
    {
        $this->load->model('virtual_machine_model');

        $id_vm = $this->input->post('id_virtual_machine');
        $action = $this->input->post('action');

        // ✅ HANDLE UPDATE LAST VERIFY SAJA
        if ($action == 'update_last_verify') {
            $this->db->where('id_virtual_machine', $id_vm);
            $this->db->update('master_virtual_machine', [
                'last_verify' => date('Y-m-d H:i:s')
            ]);

            $this->session->set_flashdata('success', 'Last Verify berhasil diupdate');
            redirect($_SERVER['HTTP_REFERER']);
            return; // penting biar tidak lanjut ke bawah
        }

        // =========================
        // PROSES NORMAL (SAVE)
        // =========================

        $id_vm_relation = $this->input->post('id_vm_relation');

        $data_vm = array(
            'virtual_machine_name' => $this->input->post('virtual_machine_name'),
            'vcenter_name'         => $this->input->post('vcenter_name'),
            'no_tiket_iris'        => $this->input->post('no_tiket_iris'),
            'cluster_name'         => $this->input->post('cluster_name'),
            'host_name'            => $this->input->post('host_name'),
            'folder_path'          => $this->input->post('folder_path'),
            'power_state'          => $this->input->post('power_state'),
            'guest_os'             => $this->input->post('guest_os'),
            'guest_os_manual'      => $this->input->post('guest_os_manual'),
            'cpu_count'            => $this->input->post('cpu_count'),
            'memory_mb'            => $this->input->post('memory_mb'),
            'provisioned_gb'       => $this->input->post('provisioned_gb'),
            'id_env'               => $this->input->post('id_env'),
            'ip_address'           => $this->input->post('ip_address'),
            'ip_address_2'         => $this->input->post('ip_address_2'),
            'ip_address_3'         => $this->input->post('ip_address_3'),
            'ip_rubrik'            => $this->input->post('ip_rubrik'),
            'is_active'            => $this->input->post('is_active'),
            'id_dev'               => $this->input->post('id_dev'),
            'id_ops'               => $this->input->post('id_ops'),
            'id_owner'             => $this->input->post('id_owner'),
            'id_requestor'         => $this->input->post('id_requestor'),
            'id_vm_relation'       => $id_vm_relation
        );

        // update data utama
        $this->virtual_machine_model->update_vm($id_vm, $data_vm);

        // relation app-component
        $apps  = $this->input->post('relation_app');
        $comps = $this->input->post('relation_component');
        $this->virtual_machine_model->update_relation_pairs($id_vm, $apps, $comps);

        // relation balik
        if (!empty($id_vm_relation)) {
            $this->virtual_machine_model->update_vm_relation_reverse(
                $id_vm_relation,
                $id_vm
            );
        }

        $this->session->set_flashdata('success', 'Data Virtual Machine berhasil diperbarui');
        redirect('virtual_machine');
    }




	public function edit_virtual_machine_complex($id_vm)
	{
	    $this->load->model('virtual_machine_model');

	    // ambil data utama VM
	    $data['query'] = $this->db->get_where('master_virtual_machine', [
	        'id_virtual_machine' => $id_vm
	    ])->row_array();

	    // ambil master list dropdown
	    $data['list_application_system'] = $this->db->get('master_application_system')->result_array();
	    $data['list_sla']                = $this->db->get('sla_rubrik')->result_array();
	    $data['list_component']          = $this->db->get('master_component')->result_array();
        $data['list_env']                = $this->db->get('master_env')->result_array();

	    // ambil pasangan App-Component dari relation_table
	    $data['relation_pairs'] = $this->virtual_machine_model->get_relation_pairs($id_vm);
		$data['selected_sla'] = $this->virtual_machine_model->get_relation_sla($id_vm);

	    // user session
	    $id = $this->session->userdata('user_data');
	    $data['page_title']   = 'Form edit Virtual Machine';
	    $this->load->model('user_model');
	    $data['id']           = $this->session->userdata('user_data');
	    $data['user_session'] = $this->user_model->get($id['id_user']);
	    // list team
		$data['list_team'] = $this->db->get('master_team')->result_array();
	    // list vm untuk vm relation
        //$data['list_vm_relation'] = $this->db->limit(4)->get('master_virtual_machine')->result_array();
      
        
  
        $data['list_vm_relation'] = $this->db
            ->distinct() // ✅ ini yang benar
            ->select('vm.*')
            ->from('master_virtual_machine vm')
            ->join(
                'relation_table rel',
                'rel.id_virtual_machine = vm.id_virtual_machine',
                'inner'
            )
            ->where('rel.id_application_system IN (
                SELECT id_application_system
                FROM relation_table
                WHERE id_virtual_machine = '.$this->db->escape($id_vm).'
            )', null, false)
            ->where('vm.id_virtual_machine !=', $id_vm)
            ->get()
            ->result_array();



	    // load view
	    $this->load->view('main/1head', $data);
	    $this->load->view('main/2sidebar', $data);
	    $this->load->view('main/3topnavigation', $data);
	    $this->load->view('form_edit_virtual_machine', $data);
	    $this->load->view('main/5footer', $data);
	    $this->load->view('main/6bottom', $data);
	}


		 
	function hapus($id_virtual_machine)
    {
		$this->virtual_machine_model->hapus($id_virtual_machine);			
		//$data['notifikasi']='Data Berhasil Disimpan';
		//$data['judul']='Insert Data Berhasil';
		redirect(site_url()."/virtual_machine");
    }
  
    public function update_last_verify() {
      $id = $this->input->post('id_virtual_machine');

      $this->db->where('id_virtual_machine', $id);
      $this->db->update('master_virtual_machine', [
          'last_verify' => date('Y-m-d H:i:s')
      ]);

      echo json_encode(['status' => 'success']);
	}
  
  
  
		
		
}

