<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Application_system extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		if(is_null($this->session->userdata('user_data'))) {
			redirect(site_url()."/login");
		}
		
		$this->load->library('csrf');
		$this->load->model('application_system_model');
		$this->load->model('criticality_model');
		$this->load->model('user_model');
		$this->load->library('Mobile_Detect');
    }
	
	public function index()
	{
			redirect(site_url()."/application_system/get_list_application_system");
	}
	public function get_list_application_system()
	{	

		$time = date('Y-m-d H:i:s');
		$data['list_application_system'] = $this->application_system_model->get_all_application_system_with_criticality();

		$data['list_criticality'] = $this->criticality_model->get_all_criticality();
        

		$id = $this->session->userdata('user_data');
		
		$data['page_title'] = 'Daftar Application_system';
		$data['css_arr'] = array('datatables.css');
		$data['js_arr'] = array('datatables/jquery.dataTables.min.js');
		$data['id']= $this->session->userdata('user_data');

		$data['user_session']=  $this->user_model->get($id['id_user']);
			
		$this->load->view('main/1head', $data);
		$this->load->view('main/2sidebar', $data);
		$this->load->view('main/3topnavigation', $data);
		$this->load->view('list_application_system', $data);
		$this->load->view('main/5footer', $data);
		$this->load->view('main/6bottom', $data);
	
	}
			
    public function simpan_data() {
		$nama = $this->input->post('application_system_name', true);
		if ($nama === '') { redirect(site_url()."/application_system"); }
		$this->load->model('application_system_model');
		$this->application_system_model->simpan_data();
		redirect(site_url()."/application_system");
    }

	function update_data()
    {
		
	    $this->load->model('application_system_model');
	    $this->application_system_model->update_data();
	    $alerts[] = array('message', 'Data berhasil disimpan!');
	    $this->session->set_flashdata('alerts', $alerts);
	    //$data['judul']='Insert Data Berhasil';
	    redirect(site_url()."/application_system");
	    //$this->load->view('input/tambah',$data);
		
	}
		 
	function hapus($id_application_system)
    {
		$this->application_system_model->hapus($id_application_system);			
		//$data['notifikasi']='Data Berhasil Disimpan';
		//$data['judul']='Insert Data Berhasil';
		redirect(site_url()."/application_system");
    }
		
		
}

