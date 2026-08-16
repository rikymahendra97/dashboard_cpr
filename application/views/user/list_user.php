<?php
/**
 * ========================================================================
 * File Name    : list_user.php
 * Modul        : User Management
 * Architecture : Enterprise CP-05 (Linter-Safe P1008, CSRF POST Delete)
 * ========================================================================
 */

// [ENTERPRISE FIX]: Linter Guard (P1008)
$list_user = $list_user ?? []; ?>

<section class="scrollable wrapper">
    <div class="right_col" role="main">
        <div class="">
            <div class="clearfix"></div>

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

            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel" style="border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <div class="x_title">
                            <h2 style="font-weight: bold; color: #2A3F54;"><i class="fa fa-users"></i> Daftar Pengguna <small>Manajemen Akun</small></h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <div style="margin-bottom: 15px;">
                                <a href="<?= site_url(
                                    "user/tambah_user",
                                ) ?>" class="btn btn-success btn-sm font-bold" style="border-radius: 4px;">
                                    <i class="fa fa-plus"></i> Tambah Pengguna Baru
                                </a>
                            </div>

                            <table id="datatable-users" class="table table-striped responsive-utilities jambo_table" style="width: 100%;">
                                <thead>
                                    <tr class="headings">
                                        <th style="width: 5%; text-align: center;">No</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th style="text-align: center;">Role Akses</th>
                                        <th style="width: 15%; text-align: center;" class="no-link last"><span class="nobr">Aksi</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    foreach ($list_user as $list):

                                        $id_target = html_escape($list["id_user"]);
                                        $nama = html_escape($list["nama_lengkap"]);
                                        ?>
                                    <tr class="even pointer">
                                        <td style="text-align: center; vertical-align: middle;"><?= $no++ ?></td>
                                        <td style="vertical-align: middle;"><strong><?= $nama ?></strong></td>
                                        <td style="vertical-align: middle;"><?= html_escape(
                                            $list["username"],
                                        ) ?></td>
                                        <td style="vertical-align: middle;"><?= html_escape(
                                            $list["email"] ?? "-",
                                        ) ?></td>
                                        <td style="text-align: center; vertical-align: middle;">
                                            <span class="label label-info" style="font-size: 11px; padding: 4px 8px;"><?= html_escape(
                                                $list["nama_role"] ?? "Unassigned",
                                            ) ?></span>
                                        </td>
                                        <td style="text-align: center; vertical-align: middle;" class="last">
                                            <a href="<?= site_url(
                                                "user/edit_user/" . $id_target,
                                            ) ?>" class="btn btn-xs btn-warning" data-toggle="tooltip" title="Edit Password" style="border-radius: 3px;"><i class="fa fa-key"></i></a>
                                            <a href="<?= site_url(
                                                "user/edit_user_detail/" . $id_target,
                                            ) ?>" class="btn btn-xs btn-info" data-toggle="tooltip" title="Edit Profil" style="border-radius: 3px;"><i class="fa fa-edit"></i></a>
                                            <button type="button" class="btn btn-xs btn-danger btn-delete-user" data-id="<?= $id_target ?>" data-nama="<?= $nama ?>" data-toggle="tooltip" title="Hapus Permanen" style="border-radius: 3px;"><i class="fa fa-trash-o"></i></button>
                                        </td>
                                    </tr>
                                    <?php
                                    endforeach;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- [ENTERPRISE FIX]: Form Hapus Rahasia (Anti-CSRF POST Method) -->
<form action="<?= site_url(
    "user/hapus",
) ?>" method="post" id="formDeleteUser" style="display:none;">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
    <input type="hidden" name="id_user" id="delete_id_user">
</form>

<script src="<?= base_url("asset/js/datatables/js/jquery.dataTables.js") ?>"></script>
<script>
    $(document).ready(function () {
        // Notifikasi SweetAlert Flashdata
        var $flashElem = $('#swal-flash-data');
        if ($flashElem.length > 0) {
            var swalType = $flashElem.data('type');
            var swalMessage = $flashElem.data('message');
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: swalType, title: swalType === 'error' ? 'Gagal' : 'Berhasil', text: swalMessage, timer: 3000, showConfirmButton: false });
            }
        }

        $('[data-toggle="tooltip"]').tooltip();

        $('#datatable-users').DataTable({
            "oLanguage": { "sSearch": "Cari Pengguna:", "sLengthMenu": "Tampilkan _MENU_ Baris" },
            "iDisplayLength": 25,
            "sPaginationType": "full_numbers",
            "columnDefs": [ { "orderable": false, "targets": [0, 5] } ]
        });

        // [ENTERPRISE FIX]: SweetAlert Confirmation to Hidden POST Form
        $('.btn-delete-user').on('click', function(e) {
            e.preventDefault();
            var id_user = $(this).data('id');
            var nama_user = $(this).data('nama');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus Pengguna?',
                    html: "Apakah Anda yakin ingin menghapus akun <b>" + nama_user + "</b> secara permanen?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#73879C',
                    confirmButtonText: '<i class="fa fa-trash"></i> Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#delete_id_user').val(id_user);
                        $('#formDeleteUser').submit();
                    }
                });
            } else {
                if(confirm('Hapus pengguna ' + nama_user + '?')) {
                    $('#delete_id_user').val(id_user);
                    $('#formDeleteUser').submit();
                }
            }
        });
    });
</script>
