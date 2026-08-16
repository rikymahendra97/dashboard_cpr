<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Errors extends CI_Controller {
    public function e404() {
        // Pakai handler bawaan CI, otomatis set $heading/$message & status 404
        show_404(); // <-- selesai
    }
}
