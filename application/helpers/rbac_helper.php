<?php if (!defined("BASEPATH")) {
    exit("No direct script access allowed");
}

/**
 * ============================================================================
 * File Name    : rbac_helper.php
 * Purpose      : Sentralisasi logika Role-Based Access Control (RBAC)
 *
 * Mapping Level Otorisasi:
 * 0: Superadmin
 * 1: Senior Manager
 * 2: Manager
 * 3: Junior Manager
 * 4: Associate IT-Project Officer
 * 5: Assistant
 * 6: Opensystem
 * ============================================================================
 */

if (!function_exists("can_edit_execute")) {
    /**
     * Memeriksa wewenang untuk melakukan Edit dan Eksekusi pekerjaan.
     * Wewenang: Role 0 sampai dengan 6.
     *
     * @param int|null $role_id
     * @return bool
     */
    function can_edit_execute(?int $role_id): bool
    {
        // Fallback jika session null/expired
        if ($role_id === null) {
            return false;
        }

        return $role_id >= 0 && $role_id <= 6;
    }
}

if (!function_exists("can_verify_delete")) {
    /**
     * Memeriksa wewenang untuk melakukan Verifikasi (Peer-Review) dan Hapus Data.
     * Wewenang: Role 0 sampai dengan 4.
     *
     * @param int|null $role_id
     * @return bool
     */
    function can_verify_delete(?int $role_id): bool
    {
        // Fallback jika session null/expired
        if ($role_id === null) {
            return false;
        }

        return $role_id >= 0 && $role_id <= 4;
    }
}
