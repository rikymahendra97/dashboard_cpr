<?php

/**
 * =============================================================================
 * File Name    : Notification_queue_model.php
 * Purpose      : Menangani penyisipan data ke tabel antrean notifikasi bot.
 * Inputs       : module_name, message_html
 * Outputs      : boolean (success/failure)
 * Dependencies : CodeIgniter 3 Database Layer
 * Notes        : Bersifat asinkronus abstrak; CI3 hanya insert, Python yang kirim.
 * =============================================================================
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Notification_queue_model extends CI_Model
{

    // --- Configuration Section ---
    private $table_name = 'bot_notification_queue';

    public function __construct()
    {
        parent::__construct();
        // Pastikan database library ter-load
        $this->load->database();
    }

    // --- Main Execution Section ---
    /**
     * Memasukkan pesan baru ke dalam antrean notifikasi Telegram.
     *
     * @param string $module_name Nama modul asal (ex: 'VM PROVISIONING', 'VM RESTART')
     * @param string $message_html Pesan yang sudah diformat dengan HTML Telegram
     * @return bool Status keberhasilan query insert
     */
    public function push_to_queue($module_name, $message_html)
    {
        $data = array(
            'module_name'  => $module_name,
            'message_html' => $message_html,
            'status'       => 'PENDING',
            'created_at'   => date('Y-m-d H:i:s')
        );

        // [PATCH LAPIS 1: ISOLASI DATABASE DEBUG]
        // Simpan state db_debug saat ini, lalu matikan sementara.
        // Ini memastikan jika insert antrean gagal, CI3 tidak mencetak error HTML ke screen.
        $db_debug_state = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        // Eksekusi insert dengan query builder bawaan CI3
        $this->db->insert($this->table_name, $data);
        $insert_status = $this->db->affected_rows() > 0;

        // Kembalikan state db_debug seperti semula agar modul lain tidak terpengaruh
        $this->db->db_debug = $db_debug_state;

        return $insert_status;
    }
}
