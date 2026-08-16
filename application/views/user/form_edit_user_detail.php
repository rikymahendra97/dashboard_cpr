<?php
/**
 * ========================================================================
 * File Name    : form_edit_user_detail.php
 * Modul        : User Management
 * Architecture : Enterprise CP-05 (Linter-Safe P1008, Clean Grid Layout)
 * ========================================================================
 */

// [ENTERPRISE FIX]: Linter Guard (P1008)
$query = $query ?? [];
$role = $role ?? [];
?>

<div class="right_col" role="main">
    <div class="">
        <div class="row">
            <!-- Flashdata -->
            <div class="col-md-12">
                <div id="alert-container">
                    <?php
                    // 1. Tangkap isi flashdata ke dalam variabel lokal
                    $alerts = $this->session->flashdata("alerts");

                    // 2. Hard-Kill Session Memory!
                    // Hancurkan memori flashdata secara paksa seketika setelah dibaca.
                    // Ini mengunci rapat kebocoran agar tidak terbawa ke halaman Dashboard akibat AJAX Race Condition.
                    if (isset($_SESSION["alerts"])) {
                        unset($_SESSION["alerts"]);
                    }

                    // 3. Render ke dalam SweetAlert DOM
                    $alerts = $alerts ?? [];
                    if (!empty($alerts) && is_array($alerts) && isset($alerts[0])):

                        $tipe = $alerts[0][0] === "error" ? "error" : "success";
                        $pesan = html_escape($alerts[0][1]);
                        ?>
                        <div id="swal-flash-data" data-type="<?= $tipe ?>" data-message="<?= $pesan ?>" style="display: none;"></div>
                    <?php
                    endif;
                    ?>
                </div>
            </div>

            <!-- BAGIAN KIRI: EDIT DATA PROFIL -->
            <div class="col-md-8 col-sm-12 col-xs-12">
                <div class="x_panel" style="border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <div class="x_title">
                        <h2 style="font-weight: bold; color: #2A3F54;"><i class="fa fa-edit"></i> Edit Profil Pengguna</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <form class="form-horizontal form-label-left" action="<?= site_url(
                            "user/update_data_detail",
                        ) ?>" method="post" id="formEditProfile">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <input type="hidden" name="id_user" value="<?= html_escape(
                                $query["id_user"] ?? "",
                            ) ?>">

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Username</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" value="<?= html_escape(
                                        $query["username"] ?? "",
                                    ) ?>" disabled style="background-color: #f1f5f9; cursor: not-allowed;">
                                    <small class="text-muted">Username tidak dapat diubah.</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Nama Lengkap <span class="text-danger">*</span></label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" name="nama_lengkap" value="<?= html_escape(
                                        $query["nama_lengkap"] ?? "",
                                    ) ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Role / Hak Akses <span class="text-danger">*</span></label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <?php
                                    // Pengecekan aman, asumsikan hanya Role 1 dan 2 (Admin) yang boleh ganti Role
                                    $session_role = (int) ($user_session["id_role"] ?? 99);
                                    $is_admin = in_array($session_role, [1, 2]);
                                    ?>
                                    <select name="id_role" class="form-control" <?= $is_admin
                                        ? ""
                                        : "disabled" ?>>
                                        <?php foreach ($role as $r): ?>
                                            <option value="<?= $r["id_role"] ?>" <?= $r[
    "id_role"
] ==
($query["id_role"] ?? "")
    ? "selected"
    : "" ?>>
                                                <?= html_escape($r["nama_role"]) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!$is_admin): ?>
                                        <small class="text-warning">Hubungi Administrator untuk mengubah Role Anda.</small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Email</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="email" class="form-control" name="email" value="<?= html_escape(
                                        $query["email"] ?? "",
                                    ) ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Kartu ID (KTP/PN)</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" name="id_kartu" value="<?= html_escape(
                                        $query["id_kartu"] ?? "",
                                    ) ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">No Telepon</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input type="text" class="form-control" name="no_phone" value="<?= html_escape(
                                        $query["no_phone"] ?? "",
                                    ) ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Alamat</label>
                                <div class="col-md-9 col-sm-9 col-xs-12">
                                    <textarea class="form-control" name="alamat" rows="3"><?= html_escape(
                                        $query["alamat"] ?? "",
                                    ) ?></textarea>
                                </div>
                            </div>

                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3">
                                    <button type="button" class="btn btn-default font-bold" onclick="window.location.href='<?= site_url(
                                        "user",
                                    ) ?>'">Batal</button>
                                    <button type="submit" class="btn btn-primary font-bold" id="btnUpdateProfile"><i class="fa fa-save"></i> Simpan Profil</button>
                                    <a href="<?= site_url(
                                        "user/edit_user/" . ($query["id_user"] ?? ""),
                                    ) ?>" class="btn btn-warning font-bold"><i class="fa fa-key"></i> Ganti Password</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- BAGIAN KANAN: UPDATE FOTO PROFIL -->
            <div class="col-md-4 col-sm-12 col-xs-12">
                <div class="x_panel" style="border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <div class="x_title">
                        <h2 style="font-weight: bold; color: #2A3F54;"><i class="fa fa-camera"></i> Foto Profil</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content text-center">
                        <form action="<?= site_url(
                            "user/aksi_upload",
                        ) ?>" method="post" enctype="multipart/form-data" id="formUploadPhoto">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <input type="hidden" name="id_user" value="<?= html_escape(
                                $query["id_user"] ?? "",
                            ) ?>">

                            <div style="margin-bottom: 20px;">
                                <?php
                                $file_foto = $query["img_file"] ?? "";
                                $img_url = !empty($file_foto)
                                    ? base_url("images/" . $file_foto)
                                    : base_url("images/avatar_default.jpg");
                                ?>
                                <img src="<?= $img_url ?>" alt="Avatar" class="img-circle" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #edf2f7; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                            </div>

                            <div class="form-group" style="text-align: left;">
                                <label style="font-size: 12px; color: #777;">Upload Foto Baru (Maks 2MB, JPG/PNG)</label>
                                <input type="file" class="form-control" accept=".jpg,.jpeg,.png,.gif" name="berkas" required style="padding-bottom: 40px;">
                            </div>

                            <button type="submit" class="btn btn-success btn-block font-bold" id="btnUploadPhoto"><i class="fa fa-upload"></i> Upload Foto</button>
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

        $('#formEditProfile').on('submit', function() {
            $('#btnUpdateProfile').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
        });

        $('#formUploadPhoto').on('submit', function() {
            $('#btnUploadPhoto').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Mengunggah...');
        });
    });
</script>
