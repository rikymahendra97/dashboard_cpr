<section class="scrollable wrapper">
<!-- page content -->
<div class="right_col" role="main">
<div class="">
    
    <div class="clearfix"></div>

    <div class="row">

        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Component <small>list</small></h2>
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
                    <a href="#" class="btn btn-success btn-sm" id="btnAddComponent"><i class="fa fa-plus"></i>Tambah</a>
                    <table id="example" class="table table-striped responsive-utilities jambo_table">
                        <thead>
                            <tr class="headings">
                                <th>Num </th>
                                <th>Nama Component </th>
                                <th class=" no-link last"><span class="nobr">Action</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php 
                            $no=1;
                                foreach($list_component as $list){
                                    
                            ?>
                            <tr class="even pointer">
                                <td class=" "><?php echo $no++; ?></td>
                                <td class=" "><?php echo $list['component_name']?></td>
                                <td class=" last">
                                    <!-- ganti link edit lama dengan tombol yang buka modal -->
                                      <a href="#"
                                         class="btn btn-info btn-xs btn_edit"
                                         data-id="<?= $list['id_component']; ?>"
                                         data-nama="<?= html_escape($list['component_name']); ?>">
                                         <i class="fa fa-edit"></i> Edit
                                      </a>
||
                                    <a href="<?php echo site_url('component/hapus/'.$list['id_component']); ?>" class="btn detail_icon btn-xs btn-danger btn_delete" data-toggle="tooltip" data-original-title="Delete"><i class="fa fa-trash-o"></i></a>
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
                                
                <div class="modal fade" id="modalEditComponent" tabindex="-1" role="dialog" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">

                      <form id="formEditComponent" class="bs-example form-horizontal"
                            method="post"
                            action="<?= base_url('index.php/component/simpan_data'); ?>"><!-- default: Tambah -->

                        <!-- CSRF (samakan dengan yang lama) -->
                        <?php echo $this->csrf->get_html(); ?>

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                          <h4 class="modal-title">Tambah Component</h4>
                        </div>

                        <div class="modal-body">
                          <input id="id_component" name="id_component" type="hidden">

                          <div class="form-group">
                            <label class="col-lg-2 control-label" for="component_name">Nama Component</label>
                            <div class="col-lg-10">
                              <input type="text" class="form-control" name="component_name" id="component_name" required>
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
$(document).on('click', '#btnAddComponent', function(e){
  e.preventDefault();
  $('#modalEditComponent .modal-title').text('Tambah Component');
  $('#formEditComponent').attr('action', '<?= base_url("index.php/component/simpan_data"); ?>');
  $('#id_component').val('');              // kosongkan id
  $('#component_name').val('');            // kosongkan nama
  $('#modalEditComponent').modal('show');
});

// Tombol "Edit"
$(document).on('click', '.btn_edit', function(e){
  e.preventDefault();
  $('#modalEditComponent .modal-title').text('Edit Component');
  $('#formEditComponent').attr('action', '<?= base_url("index.php/component/update_data"); ?>');
  $('#id_component').val($(this).data('id'));
  $('#component_name').val($(this).data('nama'));
  $('#modalEditComponent').modal('show');
});

</script>