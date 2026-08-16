<?php
/**
 * ========================================================================
 * File Name    : form_tambah_user.php
 * Modul        : User Management
 * Purpose      : Antarmuka Registrasi Akun Pengguna Baru
 * Architecture : Enterprise CP-05 (Debounce AJAX, Linter-Safe, Modern UX)
 * ========================================================================
 */

// [ENTERPRISE FIX]: Linter Guard (P1008)
$role = $role ?? []; ?>

<div class="right_col" role="main">
    <div class="">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">

                <!-- Tombol Kembali -->
                <div style="margin-bottom: 15px;">
                    <a href="<?= site_url(
                        "user",
                    ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); font-weight: bold; color: #555;">
                        <i class="fa fa-arrow-left"></i> Kembali ke Daftar Pengguna
                    </a>
                </div>

                <!-- Flashdata Alerts -->
                <div id="alert-container">
                    <?php
                    $alerts = $this->session->flashdata("alerts") ?? [];
                    if (!empty($alerts) && is_array($alerts) && isset($alerts[0])):

                        $tipe = $alerts[0][0] === "error" ? "error" : "success";
                        $pesan = html_escape($alerts[0][1]);
                        ?>
                        <div id="swal-flash-data" data-type="<?= $tipe ?>" data-message="<?= $pesan ?>" style="display: none;"></div>
                    <?php
                    endif;
                    ?>
                </div>

                <!-- Panel Form -->
                <div class="x_panel" style="border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 3px solid #1ABB9C;">
                    <div class="x_title">
                        <h2 style="font-weight: bold; color: #2A3F54;"><i class="fa fa-user-plus"></i> Tambah Pengguna Baru</h2>
                        <div class="clearfix"></div>
                    </div>

                    <div class="x_content">
                        <form class="form-horizontal form-label-left" action="<?= site_url(
                            "user/simpan_data",
                        ) ?>" method="post" id="formAddUser" novalidate style="margin-top: 20px;">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                            <!-- BAGIAN 1: IDENTITAS UTAMA -->
                            <h4 style="font-weight: bold; color: #2A3F54; border-bottom: 1px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 20px; font-size: 15px;">
                                A. Identitas Pengguna
                            </h4>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Nama Lengkap <span class="text-danger">*</span></label>
                                <div class="col-md-6 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" name="nama_lengkap" required placeholder="Contoh: John Doe">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Role / Hak Akses <span class="text-danger">*</span></label>
                                <div class="col-md-6 col-sm-9 col-xs-12">
                                    <select name="id_role" id="id_role" class="form-control" required>
                                        <option value="">-- Pilih Role Akses --</option>
                                        <?php foreach ($role as $r): ?>
                                            <option value="<?= html_escape(
                                                $r["id_role"],
                                            ) ?>"><?= html_escape($r["nama_role"]) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Kartu ID (KTP/SIM)</label>
                                <div class="col-md-6 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" name="id_kartu" placeholder="Nomor identitas (Opsional)">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">No Telepon</label>
                                <div class="col-md-6 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" name="no_phone" placeholder="08123456789">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Email</label>
                                <div class="col-md-6 col-sm-9 col-xs-12">
                                    <input type="email" class="form-control" name="email" placeholder="email@domain.com">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Alamat</label>
                                <div class="col-md-6 col-sm-9 col-xs-12">
                                    <textarea class="form-control" name="alamat" rows="3" placeholder="Alamat lengkap (Opsional)"></textarea>
                                </div>
                            </div>

                            <br>
                            <!-- BAGIAN 2: AUTENTIKASI -->
                            <h4 style="font-weight: bold; color: #2A3F54; border-bottom: 1px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 20px; font-size: 15px;">
                                B. Kredensial Login
                            </h4>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Username <span class="text-danger">*</span></label>
                                <div class="col-md-6 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" name="nama_user" id="inputUsername" required placeholder="Gunakan huruf kecil tanpa spasi">
                                    <div id="usernameFeedback" style="margin-top: 5px; font-size: 12px; font-weight: bold; display: none;"></div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Password <span class="text-danger">*</span></label>
                                <div class="col-md-6 col-sm-9 col-xs-12">
                                    <input type="password" class="form-control" name="password1_dummy" id="inputPass1" required minlength="6" placeholder="Minimal 6 karakter">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Konfirmasi Password <span class="text-danger">*</span></label>
                                <div class="col-md-6 col-sm-9 col-xs-12">
                                    <input type="password" class="form-control" name="password" id="inputPass2" required minlength="6" placeholder="Ketik ulang password">
                                    <div id="passMessage" style="margin-top: 5px; font-size: 12px; font-weight: bold; display: none;"></div>
                                </div>
                            </div>

                            <div class="ln_solid"></div>

                            <div class="form-group">
                                <div class="col-md-6 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" class="btn btn-default font-bold" onclick="window.location.href='<?= site_url(
                                        "user",
                                    ) ?>'">Batal</button>
                                    <button type="submit" class="btn btn-primary font-bold" id="btnSubmitAdd" disabled><i class="fa fa-save"></i> Simpan Pengguna</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const URL_CEK_USERNAME = '<?= site_url("user/cek_username") ?>';

    $(document).ready(function() {
        // [ENTERPRISE FIX]: SweetAlert Flashdata
        var $flashElem = $('#swal-flash-data');
        if ($flashElem.length > 0) {
            var swalType = $flashElem.data('type');
            var swalMessage = $flashElem.data('message');
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: swalType, title: swalType === 'error' ? 'Gagal' : 'Berhasil', text: swalMessage, timer: 3000, showConfirmButton: false });
            }
        }

        // Variabel global state validasi (Harus true semua untuk enable submit)
        let isUsernameValid = false;
        let isPasswordValid = false;
        let usernameTimer; // Untuk Debounce AJAX

        function validateFormState() {
            if (isUsernameValid && isPasswordValid) {
                $('#btnSubmitAdd').prop('disabled', false);
            } else {
                $('#btnSubmitAdd').prop('disabled', true);
            }
        }

        // ====================================================
        // 1. Debounce AJAX Cek Ketersediaan Username
        // ====================================================
        $('#inputUsername').on('keyup', function() {
            clearTimeout(usernameTimer);
            var currentUsername = $(this).val().trim();
            var $msg = $('#usernameFeedback');

            if (currentUsername.length < 3) {
                $msg.show().css('color', '#e74c3c').html('<i class="fa fa-times-circle"></i> Username minimal 3 karakter.');
                $('#inputUsername').css('border-color', '#e74c3c');
                isUsernameValid = false;
                validateFormState();
                return;
            }

            $msg.show().css('color', '#f39c12').html('<i class="fa fa-spinner fa-spin"></i> Mengecek ketersediaan...');

            usernameTimer = setTimeout(function() {
                $.getJSON(URL_CEK_USERNAME, { username: currentUsername }, function(data) {
                    // Jika data null atau kosong, berarti username belum dipakai
                    if (!data || data.length === 0) {
                        $msg.css('color', '#2ecc71').html('<i class="fa fa-check-circle"></i> Username <b>' + currentUsername + '</b> tersedia.');
                        $('#inputUsername').css('border-color', '#2ecc71');
                        isUsernameValid = true;
                    } else {
                        $msg.css('color', '#e74c3c').html('<i class="fa fa-times-circle"></i> Username <b>' + currentUsername + '</b> sudah dipakai.');
                        $('#inputUsername').css('border-color', '#e74c3c');
                        isUsernameValid = false;
                    }
                    validateFormState();
                }).fail(function() {
                    $msg.css('color', '#e74c3c').html('<i class="fa fa-wifi"></i> Gagal menghubungi server.');
                    isUsernameValid = false;
                    validateFormState();
                });
            }, 600); // Delay 600ms (Debounce)
        });

        // ====================================================
        // 2. Real-time Password Matcher
        // ====================================================
        $('#inputPass1, #inputPass2').on('keyup', function () {
            var pass1 = $('#inputPass1').val();
            var pass2 = $('#inputPass2').val();
            var $msg = $('#passMessage');

            if (pass2.length > 0) {
                $msg.show();
                if (pass1 === pass2 && pass1.length >= 6) {
                    $msg.css('color', '#2ecc71').html('<i class="fa fa-check-circle"></i> Password cocok.');
                    $('#inputPass2').css('border-color', '#2ecc71');
                    isPasswordValid = true;
                } else if (pass1 !== pass2) {
                    $msg.css('color', '#e74c3c').html('<i class="fa fa-times-circle"></i> Password tidak sama!');
                    $('#inputPass2').css('border-color', '#e74c3c');
                    isPasswordValid = false;
                } else {
                    $msg.css('color', '#e74c3c').html('<i class="fa fa-times-circle"></i> Password minimal 6 karakter.');
                    isPasswordValid = false;
                }
            } else {
                $msg.hide();
                $('#inputPass2').css('border-color', '');
                isPasswordValid = false;
            }
            validateFormState();
        });

        // ====================================================
        // 3. Anti-Spam Submit Guard
        // ====================================================
        $('#formAddUser').on('submit', function() {
            $('#btnSubmitAdd').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');
        });
    });
</script>
