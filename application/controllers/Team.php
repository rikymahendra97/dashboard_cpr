<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Team extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
		if(is_null($this->session->userdata('user_data'))) {
			redirect(site_url()."/login");
		}
		
		$this->load->library('csrf');
		$this->load->model('team_model');
		$this->load->model('user_model');
		$this->load->library('Mobile_Detect');
    }
	
	public function index()
	{
			redirect(site_url()."/team/get_list_team");
	}
	public function get_list_team()
	{	

		$time = date('Y-m-d H:i:s');
		$data['list_team'] = $this->team_model->get_all_team();
		$id = $this->session->userdata('user_data');
		
		$data['page_title'] = 'Daftar Team';
		$data['css_arr'] = array('datatables.css');
		$data['js_arr'] = array('datatables/jquery.dataTables.min.js');
		$data['id']= $this->session->userdata('user_data');

		$data['user_session']=  $this->user_model->get($id['id_user']);
			
		$this->load->view('main/1head', $data);
		$this->load->view('main/2sidebar', $data);
		$this->load->view('main/3topnavigation', $data);
		$this->load->view('list_team', $data);
		$this->load->view('main/5footer', $data);
		$this->load->view('main/6bottom', $data);
	
	}
			
    public function simpan_data() {
		$nama = $this->input->post('team_name', true);
		if ($nama === '') { redirect(site_url()."/team"); }
		$this->load->model('team_model');
		$this->team_model->simpan_data();
		redirect(site_url()."/team");
    }

	function update_data()
    {
		
	    $this->load->model('team_model');
	    $this->team_model->update_data();
	    $alerts[] = array('message', 'Data berhasil disimpan!');
	    $this->session->set_flashdata('alerts', $alerts);
	    //$data['judul']='Insert Data Berhasil';
	    redirect(site_url()."/team");
	    //$this->load->view('input/tambah',$data);
		
	}
		 
	function hapus($id_team)
    {
		$this->team_model->hapus($id_team);			
		//$data['notifikasi']='Data Berhasil Disimpan';
		//$data['judul']='Insert Data Berhasil';
		redirect(site_url()."/team");
    }
		
		
}

