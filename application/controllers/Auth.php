<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

class Auth extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        // Load library dan model standar
        $this->load->library('session');
        $this->load->library('csrf');
        $this->load->model('account_model');
    }

    public function index()
    {
        $this->load->view('login_page');
    }

    public function login()
    {
        // 1. Pengecekan method POST yang lebih bersih
        if (!$this->input->post()) {
            $this->load->view('login_page');
            return;
        }

        // 2. XSS Filtering aktif untuk username
        $username = $this->input->post('username', TRUE);
        $password = $this->input->post('pwd');

        // 3. Menggunakan logika eksisting (Legacy Hash) agar tidak merusak Production
        $user_data = $this->account_model->login($username, "xx-" . $password . "-xx");

        if (!empty($user_data)) {

            // --- LOGIN SUKSES ---
            unset($user_data['password']);

            // TAMBAHAN: Sapu bersih sisa notifikasi error dari percobaan login yang gagal
            $this->session->unset_userdata('alerts');

            // Mencegah Session Fixation (Best Practice tanpa mengganggu fitur)
            session_regenerate_id(TRUE);
            $this->session->set_userdata('user_data', $user_data);

            // Mempertahankan Audit Trail/Log eksisting
            $this->account_model->login_log("1");

            // Routing sesuai kebutuhan fitur baru
            $dst = $this->input->get('dst');
            if ($dst && $dst !== 'vm') {
                redirect(rawurldecode($dst));
            } else {
                redirect('dashboard');
            }
        } else {
            // --- LOGIN GAGAL ---

            // [UX FIX]: Simpan inputan username ke dalam session sementara (Flashdata)
            // agar bisa ditampilkan kembali di halaman form tanpa harus diketik ulang.
            $this->session->set_flashdata('old_username', $username);

            $alerts = [];
            // [SECURITY FIX]: Pesan error digabung untuk mencegah Username Enumeration
            $alerts[] = array('error', 'Username atau Password salah!');
            $this->session->set_flashdata('alerts', $alerts);

            $dst = $this->input->get('dst');
            redirect($dst ? 'auth/login?dst=' . $dst : 'auth/login');
        }
    }

    public function logout()
    {
        $this->session->sess_destroy();
        redirect('auth/login');
    }

    public function check_sess_ajax()
    {
        // 4. Implementasi Output Class CI3 yang profesional untuk API/AJAX
        $is_logged_in = $this->session->userdata('user_data') ? true : false;

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => $is_logged_in]));
    }

    public function cek_login()
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(false));
    }
}

/* End of file auth.php */
/* Location: ./application/controllers/auth.php */
