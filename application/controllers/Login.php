<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

    public function index()
    {
        // pastikan session library sudah autoload atau diload manual
        if ($this->session->userdata('user_data')) {
            // jika sudah login, redirect ke controller user
            redirect(site_url('user'));
        }

        // jika belum login, tampilkan halaman login
        $this->load->view('login_page');
    }
}
