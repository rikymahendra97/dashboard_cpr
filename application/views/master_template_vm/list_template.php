<?php
/**
 * ============================================================================
 * File Name    : list_template.php
 * Modul        : Master Template VM
 * Purpose      : Menampilkan list data Master Template.
 * Architecture : Enterprise Standard CP-05 (Linter Safe)
 * ============================================================================
 */
?>
<section class="content">
    <div class="right_col" role="main">
        <div class="clearfix"></div>

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
            <div class="col-md-12">
                <div class="x_panel" style="border-radius:8px;">
                    <div class="x_title">
                        <h2 style="font-weight:bold; color:#2A3F54;"><i class="fa fa-cubes"></i> Master Source Clone / Template VM</h2>
                        <div class="nav navbar-right panel_toolbox">
                            <a href="<?= site_url(
                                "master_template_vm/tambah",
                            ) ?>" class="btn btn-success btn-sm font-bold"><i class="fa fa-plus"></i> Tambah Template Baru</a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table id="tbl-template" class="table table-striped table-bordered" style="width:100%;">
                            <thead style="background:#34495E; color:#fff;">
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th>Grup / OS Family</th>
                                    <th>Nama Spesifik Template</th>
                                    <th class="text-center" width="10%">Status</th>
                                    <th class="text-center" width="15%">Ditambahkan</th>
                                    <th class="text-center" width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Delete POST -->
<div class="modal fade" id="modalDel" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" style="margin-top:15%;">
        <div class="modal-content" style="border-radius:8px;">
            <form action="<?= site_url("master_template_vm/hapus") ?>" method="post">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="id_template" id="del_id_template">

                <div class="modal-header" style="background:#EF4444; color:#fff; border-radius:8px 8px 0 0;">
                    <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                    <h4 class="modal-title font-bold"><i class="fa fa-warning"></i> Hapus Data</h4>
                </div>
                <div class="modal-body text-center">
                    <p>Yakin ingin menghapus Template VM ini secara permanen dari sistem?</p>
                </div>
                <div class="modal-footer text-center" style="background:#f8f9fa;">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var URL_AJAX_LIST = '<?= site_url("master_template_vm/ajax_list") ?>';
    var CSRF_NAME = '<?= $this->security->get_csrf_token_name() ?>';
    var CSRF_HASH = '<?= $this->security->get_csrf_hash() ?>';

    $(document).ready(function() {
        var $flashElem = $('#swal-flash-data');
        if ($flashElem.length > 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: $flashElem.data('type'),
                    title: $flashElem.data('type') === 'error' ? 'Gagal' : 'Berhasil',
                    text: $flashElem.data('message'),
                    timer: 3000,
                    showConfirmButton: false
                });
            }
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            $flashElem.remove();
        }

        var table = $('#tbl-template').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": URL_AJAX_LIST,
                "type": "POST",
                "data": function(d) {
                    d[CSRF_NAME] = CSRF_HASH;
                }
            },
            "columnDefs": [
                { "targets": [0, 5], "orderable": false },
                { "targets": [0, 3, 4, 5], "className": "text-center dt-middle" }
            ]
        });

        $('#tbl-template tbody').on('click', '.btn-delete', function() {
            $('#del_id_template').val($(this).data('id'));
            $('#modalDel').modal('show');
        });
    });
</script>
