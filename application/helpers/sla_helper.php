<?php

/**
 * File: sla_helper.php
 * Tujuan: Core Logic Engine untuk Service Level Agreement (SLA) & Tracking Hari Kerja.
 * Modul: VM Utilization Incident Management (Global Reusable)
 * Catatan: Mengabaikan hari Sabtu dan Minggu. Kompatibel penuh dengan skema ENUM 'Done/Close'.
 */

// ==========================================================
// SECTION: Inisialisasi & Proteksi Akses Berkas
// ==========================================================
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Fungsi: add_working_days
 * Menambahkan jumlah hari kerja tertentu dengan meleompati hari Sabtu dan Minggu.
 * * @param string $start_date Waktu mulai pencatatan (Format: Y-m-d H:i:s)
 * @param int $days_to_add Jumlah hari kerja yang dialokasikan (e.g., 5 atau 2)
 * @return string Format Datetime MySQL (Y-m-d H:i:s)
 */
if (!function_exists('add_working_days')) {
    function add_working_days($start_date, $days_to_add)
    {
        $date = new DateTime($start_date);
        $remaining_days = (int)$days_to_add;

        while ($remaining_days > 0) {
            $date->modify('+1 day');
            // Format 'N' menghasilkan nilai 1 (Senin) s.d 7 (Minggu).
            // Jika di bawah 6, berarti hari kerja (Senin - Jumat).
            if ((int)$date->format('N') < 6) {
                $remaining_days--;
            }
        }

        return $date->format('Y-m-d H:i:s');
    }
}

/**
 * Fungsi: calculate_sla_deadline
 * Menghitung tenggat waktu insiden berdasarkan jenis urgensi atau fallback T+5 hari kerja.
 * * @param string $created_at Waktu tiket dibuat (Format: Y-m-d H:i:s)
 * @param string $urgensi Tingkat keparahan (Critical, High, Medium, Low)
 * @return string Format Datetime MySQL (Y-m-d H:i:s)
 */
if (!function_exists('calculate_sla_deadline')) {
    function calculate_sla_deadline($created_at, $urgensi = 'Medium')
    {
        // Implementasi Roadmap Utama: Default Menggunakan Penambahan Hari Kerja Sesuai Aturan Kepatuhan Audit
        switch (strtoupper($urgensi)) {
            case 'CRITICAL':
                // Jika kritis butuh penanganan sangat cepat, bisa diset dalam hitungan jam langsung
                $start_time = strtotime($created_at);
                $deadline_time = $start_time + (4 * 3600);
                return date('Y-m-d H:i:s', $deadline_time);
            case 'HIGH':
                return add_working_days($created_at, 3);
            case 'MEDIUM':
            case 'LOW':
            default:
                // Standar Roadmap Tata Kelola Insiden Utama: T+5 Hari Kerja
                return add_working_days($created_at, 5);
        }
    }
}

/**
 * Fungsi: get_sla_status_badge
 * Memproduksi HTML Badge untuk UI Dashboard berdasarkan sisa waktu riil (Countdown).
 * * @param string $deadline_date Waktu jatuh tempo penyelesaian
 * @param string $status_insiden Status tiket aktual dari DB ('Open Tiket', 'Done/Close', dll)
 * @return string Kode komponen HTML Span Badge (AdminLTE / Bootstrap Stack Compatible)
 */
if (!function_exists('get_sla_status_badge')) {
    function get_sla_status_badge($deadline_date, $status_insiden)
    {
        // 1. Sinkronisasi Mutlak Terhadap ENUM Database Eksisting
        if (in_array($status_insiden, ['Done/Close', 'Selesai Verified'])) {
            return '<span class="label label-success" style="font-size:11px; padding:4px 8px;"><i class="fa fa-check-circle"></i> Tuntas (SLA Terpenuhi)</span>';
        }

        // 2. Kalkulasi Selisih Waktu Detik Saat Ini dengan Tenggat Waktu
        $now = time();
        $deadline = strtotime($deadline_date);
        $diff_seconds = $deadline - $now;

        // 3. Render Kondisional Berdasarkan Ambang Batas Waktu Kritis
        if ($diff_seconds < 0) {
            // Kondisi SLA Terlewati (Breached)
            $telat_jam = floor(abs($diff_seconds) / 3600);
            $telat_hari = floor($telat_jam / 24);

            if ($telat_hari > 0) {
                return '<span class="label label-danger" style="font-size:11px; padding:4px 8px;"><i class="fa fa-warning"></i> Breached (-' . $telat_hari . ' Hari)</span>';
            }
            return '<span class="label label-danger" style="font-size:11px; padding:4px 8px;"><i class="fa fa-warning"></i> Breached (-' . $telat_jam . ' Jam)</span>';
        } elseif ($diff_seconds <= (24 * 3600)) {
            // Sisa Waktu Kritis: Kurang dari atau sama dengan 24 Jam (Warna Kuning/Oranye Peringatan)
            $sisa_jam = floor($diff_seconds / 3600);
            return '<span class="label label-warning" style="font-size:11px; padding:4px 8px;"><i class="fa fa-clock-o"></i> Kritis (' . $sisa_jam . ' Jam Lagi)</span>';
        } else {
            // Kondisi Aman: Sisa waktu di atas 1 Hari (Warna Biru Utama)
            $sisa_hari = floor($diff_seconds / 86400);
            $sisa_jam = floor(($diff_seconds % 86400) / 3600);
            return '<span class="label label-primary" style="font-size:11px; padding:4px 8px;"><i class="fa fa-shield"></i> Sisa ' . $sisa_hari . 'h ' . $sisa_jam . 'j</span>';
        }
    }
}
