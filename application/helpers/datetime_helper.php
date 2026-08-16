<?php
defined("BASEPATH") or exit("No direct script access allowed");

/**
 * ========================================================================
 * ENTERPRISE HELPER: Datetime Utility
 * ========================================================================
 */
if (!function_exists("normalize_mysql_datetime")) {
    /**
     * Mengkonversi format datetime-local HTML5 (ISO 8601) menjadi format MySQL.
     * Mengubah format "YYYY-MM-DDTHH:mm" menjadi "YYYY-MM-DD HH:mm:00".
     *
     * @param string|null $datetime_html5 Input dari view
     * @return string|null Format standar database
     */
    function normalize_mysql_datetime(?string $datetime_html5): ?string
    {
        if (empty($datetime_html5)) {
            return null;
        }

        $clean_date = str_replace("T", " ", trim($datetime_html5));

        // Memastikan detik (:00) ditambahkan jika string hanya berisi sampai menit
        if (strlen($clean_date) === 16) {
            $clean_date .= ":00";
        }

        return date("Y-m-d H:i:s", strtotime($clean_date));
    }
}
