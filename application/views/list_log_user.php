<section class="scrollable wrapper">
<!-- page content -->
<div class="right_col" role="main">
<div class="">
    
    <div class="clearfix"></div>

    <div class="row">

        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Log Users <small>list</small></h2>
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
                    
                    <table id="example" class="table table-striped responsive-utilities jambo_table">
                        <thead>
                            <tr class="headings">
                                <th>Num </th>
                                <th>Name </th>
                                <th>Username</th>
                                <th>Role </th>
                                <th>Date </th>
                                
                            </tr>
                        </thead>

                        <tbody>
                            <?php 
                            $no=1;
                                foreach($list_log_user as $list){
                                    
                            ?>
                            <tr class="even pointer">
                                <td class=" "><?php echo $no++; ?></td>
                                <td class=" "><?php echo $list['nama_lengkap']?></td>
                                <td class=" "><?php echo $list['username']?></td>
                                <td class=" "><?php echo $list['nama_role']?></td>
                                <td class=" "><?php echo $list['date_created']?></td>
                                
                            </tr>
                            <?php
                                }
                            ?>
                        </tbody>

                    </table>

                
                <!-- /modals -->
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
                    'iDisplayLength': 8,
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
$('#confirm-delete').on('show.bs.modal', function(e) {
    $(this).find('.danger').attr('href', $(e.relatedTarget).data('href'));
});
</script>