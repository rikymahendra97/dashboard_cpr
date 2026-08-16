<?php
/**
 * ========================================================================
 * File Name    : form_edit_user.php
 * Modul        : User Management
 * Purpose      : Form Khusus Edit Password (Menggabungkan file yang redundan)
 * Architecture : Enterprise CP-05 (Real-time JS Matcher)
 * ========================================================================
 */

// [ENTERPRISE FIX]: Linter Guard (P1008)
$query = $query ?? []; ?>

<div class="right_col" role="main">
    <div class="">
        <div class="row">
            <div class="col-md-8 col-sm-12 col-xs-12">

                <!-- Flashdata -->
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

                <div class="x_panel" style="border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2a632;">
                    <div class="x_title" style="background-color: #fcf8e3; padding: 10px 15px; margin: -10px -10px 15px -10px; border-bottom: 1px solid #faebcc; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        <h2 style="font-weight: bold; color: #8a6d3b;"><i class="fa fa-key"></i> Ubah Password Akses</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <form class="form-horizontal form-label-left" action="<?= site_url(
                            "user/update_data",
                        ) ?>" method="post" id="formEditPassword">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <input type="hidden" name="id_user" value="<?= html_escape(
                                $query["id_user"] ?? "",
                            ) ?>">

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">Username</label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <input type="text" class="form-control" value="<?= html_escape(
                                        $query["username"] ?? "",
                                    ) ?>" disabled style="background-color: #f1f5f9; font-weight: bold;">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">Password Baru <span class="text-danger">*</span></label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <input type="password" class="form-control" name="password" id="inputPass1" required minlength="6" placeholder="Minimal 6 karakter">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">Konfirmasi Password <span class="text-danger">*</span></label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <input type="password" class="form-control" id="inputPass2" required minlength="6" placeholder="Ketik ulang password baru">
                                    <div id="passMessage" style="margin-top: 5px; font-size: 12px; font-weight: bold; display: none;"></div>
                                </div>
                            </div>

                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-8 col-sm-8 col-xs-12 col-md-offset-4">
                                    <button type="button" class="btn btn-default font-bold" onclick="window.history.back()">Batal</button>
                                    <button type="submit" class="btn btn-warning font-bold" id="btnSubmitPassword" disabled><i class="fa fa-save"></i> Perbarui Password</button>
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
    $(document).ready(function() {
        var $flashElem = $('#swal-flash-data');
        if ($flashElem.length > 0) {
            var swalType = $flashElem.data('type');
            var swalMessage = $flashElem.data('message');
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: swalType, title: swalType === 'error' ? 'Gagal' : 'Berhasil', text: swalMessage, timer: 3000, showConfirmButton: false });
            }
        }

        // Real-time Password Matcher
        $('#inputPass1, #inputPass2').on('keyup', function () {
            var pass1 = $('#inputPass1').val();
            var pass2 = $('#inputPass2').val();
            var $msg = $('#passMessage');
            var $btn = $('#btnSubmitPassword');

            if (pass2.length > 0) {
                $msg.show();
                if (pass1 === pass2 && pass1.length >= 6) {
                    $msg.css('color', '#2ecc71').html('<i class="fa fa-check-circle"></i> Password cocok.');
                    $('#inputPass2').css('border-color', '#2ecc71');
                    $btn.prop('disabled', false);
                } else if (pass1 !== pass2) {
                    $msg.css('color', '#e74c3c').html('<i class="fa fa-times-circle"></i> Password tidak sama!');
                    $('#inputPass2').css('border-color', '#e74c3c');
                    $btn.prop('disabled', true);
                } else {
                    $msg.css('color', '#e74c3c').html('<i class="fa fa-times-circle"></i> Password terlalu pendek.');
                    $btn.prop('disabled', true);
                }
            } else {
                $msg.hide();
                $('#inputPass2').css('border-color', '');
                $btn.prop('disabled', true);
            }
        });

        $('#formEditPassword').on('submit', function() {
            $('#btnSubmitPassword').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
        });
    });
</script>
