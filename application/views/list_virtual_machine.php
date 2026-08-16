<section class="scrollable wrapper">
<!-- page content -->
<div class="right_col" role="main">
  <div class="">
    <div class="clearfix"></div>
    <div class="row">

      <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
          <div class="x_title">
            <h2>Virtual Machine <small>list</small></h2>
            <ul class="nav navbar-right panel_toolbox">
              <li><a href="#"><i class="fa fa-chevron-up"></i></a></li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-wrench"></i></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="#">Settings 1</a></li>
                  <li><a href="#">Settings 2</a></li>
                </ul>
              </li>
              <li><a href="#"><i class="fa fa-close"></i></a></li>
            </ul>
            <div class="clearfix"></div>
          </div>

          <div class="x_content">
            
            <!-- [PERBAIKAN 1]: Menambahkan wrapper div.table-responsive -->
            <div class="table-responsive">
                <!-- [PERBAIKAN 2]: Menambahkan inline style width: 100% dan white-space: nowrap -->
                <table id="example" class="table table-striped jambo_table" style="width: 100%; white-space: nowrap;">
                  <thead>
                    <tr class="headings">
                      <th>No</th>
                      <th>UUID</th>
                      <th>Nama Virtual Machine</th>
                      <th>No Tiket IRIS</th>
                      <th>vCenter</th>
                      <th>Cluster</th>
                      <th>Host</th>
                      <th>IP Address</th>
                      <th>IP Rubrik</th>
                      <th>Site</th>
                      <th>Owner</th>
                      <th>Requestor</th>
                      <th>Application System</th>
                      <th>Component</th>
                      <th>Criticality</th>
                      <th class="no-link last"><span class="nobr">Action</span></th>
                    </tr>
                  </thead>
                  <tbody>                
                  </tbody>
                </table>
            </div>

            <!-- Modal Edit/Tambah -->
            <div class="modal fade" id="modalEditVirtual_machine" tabindex="-1" role="dialog">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">

                  <form id="formEditVirtual_machine" class="bs-example form-horizontal"
                        method="post"
                        action="<?= base_url('index.php/virtual_machine/simpan_data'); ?>">

                    <?php
                      if (isset($this->csrf) && is_object($this->csrf) && method_exists($this->csrf, 'get_html')) {
                          echo $this->csrf->get_html();
                      }
                    ?>

                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                      <h4 class="modal-title">Tambah Virtual Machine</h4>
                    </div>

                    <div class="modal-body">
                      <input id="id_virtual_machine" name="id_virtual_machine" type="hidden">

                      <div class="form-group">
                        <label class="col-lg-2 control-label">Nama Virtual Machine</label>
                        <div class="col-lg-10">
                          <input type="text" class="form-control" name="virtual_machine_name" id="virtual_machine_name" required>
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
            <!-- /Modal -->

          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<!-- /page content -->
</section>

<!-- Datatables -->
<script src="<?= base_url('asset/js/datatables/js/jquery.dataTables.js'); ?>"></script>
<script src="<?= base_url('asset/js/datatables/tools/js/dataTables.tableTools.js'); ?>"></script>
<script>
  $(document).ready(function () {
    $('#example').dataTable({
        "bDestroy": true,
        "bProcessing": true,
        "bServerSide": true,
        "bInfo": false,
        "iDisplayLength": 20,
        "aLengthMenu": [[20, 50, 100], [20, 50, 100]],
        "sPaginationType": "full_numbers",
        "sServerMethod": "POST",
        "sAjaxSource": "<?= site_url('virtual_machine/ajax_list'); ?>",
        "aaSorting": [[1, "asc"]],
        "aoColumnDefs": [
            { "bSortable": false, "aTargets": [0, 15] }
        ],
        "fnServerData": function (sSource, aoData, fnCallback) {
            $.ajax({
                "dataType": "json",
                "type": "POST",
                "url": sSource,
                "data": aoData,
                "success": fnCallback,
                "error": function(xhr, status, error) {
                    console.log('AJAX ERROR:', status, error);
                    console.log(xhr.responseText);
                }
            });
        }
    });
  });

  // Tombol Tambah
  $(document).on('click', '#btnAddVirtual_machine', function(e){
    e.preventDefault();
    $('#modalEditVirtual_machine .modal-title').text('Tambah Virtual Machine');
    $('#formEditVirtual_machine').attr('action', '<?= base_url("index.php/virtual_machine/simpan_data"); ?>');
    $('#id_virtual_machine').val('');
    $('#virtual_machine_name').val('');
    $('#modalEditVirtual_machine').modal('show');
  });

  // Tombol Edit
  $(document).on('click', '.btn_edit', function(e){
    e.preventDefault();
    $('#modalEditVirtual_machine .modal-title').text('Edit Virtual Machine');
    $('#formEditVirtual_machine').attr('action', '<?= base_url("index.php/virtual_machine/update_data"); ?>');
    $('#id_virtual_machine').val($(this).data('id_virtual_machine'));
    $('#virtual_machine_name').val($(this).data('virtual_machine_name'));
    $('#modalEditVirtual_machine').modal('show');
  });

  // Tombol Delete dengan konfirmasi
  $(document).on('click', '.btn_delete', function(e){
    e.preventDefault();
    var href = $(this).attr('href');
    if(confirm('Anda yakin ingin menghapus VM ini?')) {
      window.location.href = href;
    }
  });
</script>