<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Criticality extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		if(is_null($this->session->userdata('user_data'))) {
			redirect(site_url()."/login");
		}
		
		$this->load->library('csrf');
		$this->load->model('criticality_model');
		$this->load->model('user_model');
		$this->load->library('Mobile_Detect');
    }
	
	public function index()
	{
			redirect(site_url()."/criticality/get_list_criticality");
	}
	public function get_list_criticality()
	{	

		$time = date('Y-m-d H:i:s');
		$data['list_criticality'] = $this->criticality_model->get_all_criticality();
		$id = $this->session->userdata('user_data');
		
		$data['page_title'] = 'Daftar Criticality';
		$data['css_arr'] = array('datatables.css');
		$data['js_arr'] = array('datatables/jquery.dataTables.min.js');
		$data['id']= $this->session->userdata('user_data');

		$data['user_session']=  $this->user_model->get($id['id_user']);
			
		$this->load->view('main/1head', $data);
		$this->load->view('main/2sidebar', $data);
		$this->load->view('main/3topnavigation', $data);
		$this->load->view('list_criticality', $data);
		$this->load->view('main/5footer', $data);
		$this->load->view('main/6bottom', $data);
	
	}
			
    public function simpan_data() {
		$nama = $this->input->post('criticality_name', true);
		if ($nama === '') { redirect(site_url()."/criticality"); }
		$this->load->model('criticality_model');
		$this->criticality_model->simpan_data();
		redirect(site_url()."/criticality");
    }

	function update_data()
    {
		
	    $this->load->model('criticality_model');
	    $this->criticality_model->update_data();
	    $alerts[] = array('message', 'Data berhasil disimpan!');
	    $this->session->set_flashdata('alerts', $alerts);
	    //$data['judul']='Insert Data Berhasil';
	    redirect(site_url()."/criticality");
	    //$this->load->view('input/tambah',$data);
		
	}
		 
	function hapus($id_criticality)
    {
		$this->criticality_model->hapus($id_criticality);			
		//$data['notifikasi']='Data Berhasil Disimpan';
		//$data['judul']='Insert Data Berhasil';
		redirect(site_url()."/criticality");
    }
		
		
}

