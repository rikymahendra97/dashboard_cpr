<section class="scrollable wrapper">
<!-- page content -->
<div class="right_col" role="main">
<div class="">
    
    <div class="clearfix"></div>

    <div class="row">

        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Application_system <small>list</small></h2>
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
                    <a href="#" class="btn btn-success btn-sm" id="btnAddApplication_system"><i class="fa fa-plus"></i>Tambah</a>
                    <table id="example" class="table table-striped responsive-utilities jambo_table">
                        <thead>
                            <tr class="headings">
                                <th>Num </th>
                                <th>Nama Application_system </th>
                                <th>Criticality </th>
                                <th class=" no-link last"><span class="nobr">Action</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php 
                            $no=1;
                                foreach($list_application_system as $list){
                                    
                            ?>
                            <tr class="even pointer">
                                <td class=" "><?php echo $no++; ?></td>
                                <td class=" "><?php echo $list['application_system_name']?></td>
                                <td class=" "><?php echo $list['criticality_name']?></td>
                                <td class=" last">
                                    <!-- ganti link edit lama dengan tombol yang buka modal -->
                                      <a href="#"
                                         class="btn btn-info btn-xs btn_edit"
                                         data-id_application_system="<?= $list['id_application_system']; ?>"
                                         data-nama="<?= html_escape($list['application_system_name']); ?>"
                                         data-criticality="<?= $list['id_criticality']; ?>">
                                         <i class="fa fa-edit"></i> Edit
                                      </a>
||
                                    <a href="<?php echo site_url('application_system/hapus/'.$list['id_application_system']); ?>" class="btn detail_icon btn-xs btn-danger btn_delete" data-toggle="tooltip" data-original-title="Delete"><i class="fa fa-trash-o"></i></a>
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
                                
                <div class="modal fade" id="modalEditApplication_system" tabindex="-1" role="dialog" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">

                      <form id="formEditApplication_system" class="bs-example form-horizontal"
                            method="post"
                            action="<?= base_url('index.php/application_system/simpan_data'); ?>"><!-- default: Tambah -->

                        <!-- CSRF (samakan dengan yang lama) -->
                        <?php echo $this->csrf->get_html(); ?>

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                          <h4 class="modal-title">Tambah Application_system</h4>
                        </div>

                        <div class="modal-body">
                          <input id="id_application_system" name="id_application_system" type="hidden">

                          <div class="form-group">
                            <label class="col-lg-2 control-label" for="application_system_name">Nama Application_system</label>
                            <div class="col-lg-10">
                              <input type="text" class="form-control" name="application_system_name" id="application_system_name" required>
                            </div>
                          </div>

                          <div class="form-group">
                            <label class="col-lg-2 control-label" for="id_criticality">Criticality</label>
                              <div class="col-lg-10">
                                <select class="form-control" name="id_criticality" id="id_criticality" required>
                                  <option value="">-- Pilih Criticality --</option>
                                  <?php foreach($list_criticality as $c): ?>
                                    <option value="<?= $c['id_criticality']; ?>"><?= $c['criticality_name']; ?></option>
                                  <?php endforeach; ?>
                                </select>
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
$(document).on('click', '#btnAddApplication_system', function(e){
  e.preventDefault();
  $('#modalEditApplication_system .modal-title').text('Tambah Application_system');
  $('#formEditApplication_system').attr('action', '<?= base_url("index.php/application_system/simpan_data"); ?>');
  $('#id_application_system').val('');              // kosongkan id
  $('#application_system_name').val('');            // kosongkan nama
  $('#id_criticality').val('');            // kosongkan id_criticality
  $('#modalEditApplication_system').modal('show');
});

// Tombol "Edit"
$(document).on('click', '.btn_edit', function(e){
  e.preventDefault();
  $('#modalEditApplication_system .modal-title').text('Edit Application_system');
  $('#formEditApplication_system').attr('action', '<?= base_url("index.php/application_system/update_data"); ?>');
  $('#id_application_system').val($(this).data('id_application_system'));
  $('#application_system_name').val($(this).data('nama'));
  $('#id_criticality').val($(this).data('criticality')); // <-- set dropdown
  $('#modalEditApplication_system').modal('show');
});

</script>