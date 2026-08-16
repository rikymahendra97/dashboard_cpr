<section class="scrollable wrapper">
<!-- page content -->
<div class="right_col" role="main">
<div class="">
    
    <div class="clearfix"></div>

    <div class="row">

        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Team <small>list</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="#"><i class="fa fa-chevron-up"></i></a>
                        </li>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="#">Settings 1</a>
                                </li>
                                <li><a href="#">Settings 2</a>
                                </li>
                            </ul>
                        </li>
                        <li><a href="#"><i class="fa fa-close"></i></a>
                        </li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <a href="#" class="btn btn-success btn-sm" id="btnAddTeam"><i class="fa fa-plus"></i>Tambah</a>
                    <table id="example" class="table table-striped responsive-utilities jambo_table">
                        <thead>
                            <tr class="headings">
                                <th>Num </th>
                                <th>Nama Team </th>
                                <th>Kode Team </th>
                                <th>PIC Name </th>
                                <th>PIC Contact </th>
                                <th class=" no-link last"><span class="nobr">Action</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php 
                            $no=1;
                                foreach($list_team as $list){
                                    
                            ?>
                            <tr class="even pointer">
                                <td class=" "><?php echo $no++; ?></td>
                                <td class=" "><?php echo $list['team_name']?></td>
                                <td class=" "><?php echo $list['team_code']?></td>
                                <td class=" "><?php echo $list['pic_name']?></td>
                                <td class=" "><?php echo $list['pic_contact']?></td>
                                <td class=" last">
                                    <!-- ganti link edit lama dengan tombol yang buka modal -->
                                      <a href="#"
                                         class="btn btn-info btn-xs btn_edit"
                                         data-id="<?= $list['id_team']; ?>"
                                         data-team_name="<?= html_escape($list['team_name']); ?>"
                                         data-team_code="<?= html_escape($list['team_code']); ?>"
                                         data-pic_name="<?= html_escape($list['pic_name']); ?>"
                                         data-pic_contact="<?= html_escape($list['pic_contact']); ?>">
                                         <i class="fa fa-edit"></i> Edit
                                      </a>
||
                                    <a href="<?php echo site_url('team/hapus/'.$list['id_team']); ?>" class="btn detail_icon btn-xs btn-danger btn_delete" data-toggle="tooltip" data-original-title="Delete"><i class="fa fa-trash-o"></i></a>
                                </td>
                            </tr>
                            <?php
                                }
                            ?>
                        </tbody>

                    </table>

                <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                Konfirmasi Hapus!
                            </div>
                            <div class="modal-body">
                                Apakah Anda Yakin Akan Menghapus User?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                <a href="#" class="btn btn-danger danger">Delete</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /modals -->
                <!-- modals -->
                <!-- Large modal -->
                                
                <div class="modal fade" id="modalEditTeam" tabindex="-1" role="dialog" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">

                      <form id="formEditTeam" class="bs-example form-horizontal"
                            method="post"
                            action="<?= base_url('index.php/team/simpan_data'); ?>"><!-- default: Tambah -->

                        <!-- CSRF (samakan dengan yang lama) -->
                        <?php echo $this->csrf->get_html(); ?>

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                          <h4 class="modal-title">Tambah Team</h4>
                        </div>

                        <div class="modal-body">
                          <input id="id_team" name="id_team" type="hidden">

                          <div class="form-group">
                            <label class="col-lg-2 control-label" for="team_name">Nama Team</label>
                            <div class="col-lg-10">
                              <input type="text" class="form-control" name="team_name" id="team_name" required>
                            </div>
                          </div>

                          <div class="form-group">
                            <label class="col-lg-2 control-label" for="team_code">Kode Team</label>
                            <div class="col-lg-10">
                              <input type="text" class="form-control" name="team_code" id="team_code" required>
                            </div>
                          </div>

                          <div class="form-group">
                            <label class="col-lg-2 control-label" for="pic_name">PIC Name</label>
                            <div class="col-lg-10">
                              <input type="text" class="form-control" name="pic_name" id="pic_name" required>
                            </div>
                          </div>
                          <div class="form-group">
                            <label class="col-lg-2 control-label" for="pic_contact">PIC Contact</label>
                            <div class="col-lg-10">
                              <input type="text" class="form-control" name="pic_contact" id="pic_contact" required>
                            </div>
                          </div>
                        </div>

                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                          <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                      </form>

                    </div>
                  </div>
                </div>





                </div>
            </div>
        </div>

   </div>
</div>

</div>
<!-- /page content -->

</section>

<!-- Datatables -->
<script src="<?php echo base_url('asset'); ?>/js/datatables/js/jquery.dataTables.js"></script>
<script src="<?php echo base_url('asset'); ?>/js/datatables/tools/js/dataTables.tableTools.js"></script>
<script>
            $(document).ready(function () {
                $('input.tableflat').iCheck({
                    checkboxClass: 'icheckbox_flat-green',
                    radioClass: 'iradio_flat-green'
                });
            });

            var asInitVals = new Array();
            $(document).ready(function () {
                var oTable = $('#example').dataTable({
                    "oLanguage": {
                        "sSearch": "Search all columns:"
                    },
                    "aoColumnDefs": [
                        {
                            'bSortable': true,
                            'aTargets': [0]
                        } //disables sorting for column one
            ],
                    'iDisplayLength': 12,
                    "sPaginationType": "full_numbers"
                });
                $("tfoot input").keyup(function () {
                    /* Filter on the column based on the index of this element's parent <th> */
                    oTable.fnFilter(this.value, $("tfoot th").index($(this).parent()));
                });
                $("tfoot input").each(function (i) {
                    asInitVals[i] = this.value;
                });
                $("tfoot input").focus(function () {
                    if (this.className == "search_init") {
                        this.className = "";
                        this.value = "";
                    }
                });
                $("tfoot input").blur(function (i) {
                    if (this.value == "") {
                        this.className = "search_init";
                        this.value = asInitVals[$("tfoot input").index(this)];
                    }
                });
            });
</script>

<script type="text/javascript">       
$('.btn_delete').click(function(e){
    e.preventDefault();
                    var c = alertify.confirm('Anda akan menghapus data ini, Lanjutkan?').set('onok', function(){ window.location.href = $(e.delegateTarget).attr('href');} );
});
</script>
<script type="text/javascript">
$('#confirm-delete').on('show.bs.modal', function(e) {
    $(this).find('.danger').attr('href', $(e.relatedTarget).data('href'));
});
</script>


<script type="text/javascript">

// Tombol "Tambah" (gunakan id sesuai punyamu)
$(document).on('click', '#btnAddTeam', function(e){
  e.preventDefault();
  $('#modalEditTeam .modal-title').text('Tambah Team');
  $('#formEditTeam').attr('action', '<?= base_url("index.php/team/simpan_data"); ?>');
  $('#id_team').val('');              // kosongkan id
  $('#team_name').val('');            // kosongkan nama
  $('#team_code').val('');            // kosongkan code
  $('#pic_name').val('');            // kosongkan pic
  $('#pic_contact').val('');            // kosongkan pic
  $('#modalEditTeam').modal('show');
});

// Tombol "Edit"
$(document).on('click', '.btn_edit', function(e){
  e.preventDefault();
  $('#modalEditTeam .modal-title').text('Edit Team');
  $('#formEditTeam').attr('action', '<?= base_url("index.php/team/update_data"); ?>');
  $('#id_team').val($(this).data('id'));
  $('#team_name').val($(this).data('team_name'));
  $('#team_code').val($(this).data('team_code'));
  $('#pic_name').val($(this).data('pic_name'));
  $('#pic_contact').val($(this).data('pic_contact'));
  $('#modalEditTeam').modal('show');
});

</script>