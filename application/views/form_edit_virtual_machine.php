<!-- page content -->
<div class="right_col" role="main">
    <div class="">
        <div class="row">
            <div class="col-sm-12">
                <section class="panel">
                    <header class="panel-heading font-bold">Edit Virtual Machine</header>
                    <div class="panel-body">
                        <form class="bs-example form-horizontal" action="<?php echo base_url('index.php/virtual_machine/update_data_complex'); ?>" method="post">
                            <?php
                              if (isset($this->csrf) && is_object($this->csrf) && method_exists($this->csrf, 'get_html')) {
                                  echo $this->csrf->get_html();
                              }
                            ?>
                            <input type="hidden" name="id_virtual_machine" value="<?php echo $query['id_virtual_machine']; ?>">

                            <!-- Virtual Machine Info -->
                              <div class="form-group">
                                <label class="col-lg-2 control-label">uuid</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" name="uuid" readonly value="<?php echo $query['uuid']; ?>" required>
                                </div>
                              </div>

                            <!-- Virtual Machine Info -->
                              <div class="form-group">
                                <label class="col-lg-2 control-label">VM Name</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" name="virtual_machine_name" value="<?php echo $query['virtual_machine_name']; ?>" required>
                                </div>
                              </div>
  								
  							  <div class="form-group">
                                <label class="col-lg-2 control-label">No Tiket IRIS</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" name="no_tiket_iris" value="<?php echo $query['no_tiket_iris']; ?>" >
                                </div>
                              </div>

                              <div class="form-group">
                                <label class="col-lg-2 control-label">vCenter</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" required name="vcenter_name" value="<?php echo $query['vcenter_name']; ?>">
                                </div>
                              </div>

                              <div class="form-group">
                                <label class="col-lg-2 control-label">Cluster</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" name="cluster_name" required value="<?php echo $query['cluster_name']; ?>">
                                </div>
                              </div>

                              <div class="form-group"  style="display:none;">
                                <label class="col-lg-2 control-label">Host</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" name="host_name" required value="<?php echo $query['host_name']; ?>">
                                </div>
                              </div>

                              <div class="form-group"  style="display:none;">
                                <label class="col-lg-2 control-label">Folder Path</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" name="folder_path" value="<?php echo $query['folder_path']; ?>">
                                </div>
                              </div>

                              <div class="form-group">
                                <label class="col-lg-2 control-label">Power State</label>
                                <div class="col-lg-10">
                                  <select class="form-control" name="power_state">
                                    <option value="ON" <?php if($query['power_state']=="ON") echo "selected"; ?>>ON</option>
                                    <option value="OFF" <?php if($query['power_state']=="OFF") echo "selected"; ?>>OFF</option>
                                  </select>
                                </div>
                              </div>

                              <div class="form-group">
                                <label class="col-lg-2 control-label">Guest OS</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" name="guest_os" value="<?php echo $query['guest_os']; ?>">
                                </div>
                              </div>

                              <div class="form-group">
                                <label class="col-lg-2 control-label">Guest OS Manual</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" name="guest_os_manual" value="<?php echo $query['guest_os_manual']; ?>">
                                </div>
                              </div>

                              <div class="form-group"  style="display:none;">
                                <label class="col-lg-2 control-label">CPU Count</label>
                                <div class="col-lg-10">
                                  <input type="number" class="form-control" name="cpu_count" value="<?php echo $query['cpu_count']; ?>">
                                </div>
                              </div>

                              <div class="form-group"  style="display:none;">
                                <label class="col-lg-2 control-label">Memory (MB)</label>
                                <div class="col-lg-10">
                                  <input type="number" class="form-control" name="memory_mb" value="<?php echo $query['memory_mb']; ?>">
                                </div>
                              </div>

                              <div class="form-group"  style="display:none;">
                                <label class="col-lg-2 control-label">Provisioned (GB)</label>
                                <div class="col-lg-10">
                                  <input type="number" class="form-control" name="provisioned_gb" value="<?php echo $query['provisioned_gb']; ?>">
                                </div>
                              </div>
                                      
                              <div class="form-group">
                                <label class="col-lg-2 control-label">Environment</label>
                                <div class="col-lg-10">
                                  <select class="form-control" name="id_env" required>
                                    <option value="">-- Pilih Environment --</option>
                                    <?php foreach($list_env as $env): ?>
                                      <option value="<?= $env['id_env']; ?>"
                                        <?= ($query['id_env'] == $env['id_env']) ? 'selected' : ''; ?>>
                                        <?= $env['env_name']; ?>
                                      </option>
                                    <?php endforeach; ?>
                                  </select>
                                </div>
                              </div>

                              <div class="form-group">
                                <label class="col-lg-2 control-label">IP Address</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" name="ip_address" value="<?php echo $query['ip_address']; ?>">
                                </div>
                              </div>

                              <div class="form-group" style="display:none;">
                                <label class="col-lg-2 control-label">IP Address 2</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" name="ip_address_2" value="<?php echo $query['ip_address_2']; ?>">
                                </div>
                              </div>

                              <div class="form-group" style="display:none;">
                                <label class="col-lg-2 control-label">IP Address 3</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" name="ip_address_3" value="<?php echo $query['ip_address_3']; ?>">
                                </div>
                              </div>

                              <div class="form-group">
                                <label class="col-lg-2 control-label">IP Rubrik</label>
                                <div class="col-lg-10">
                                  <input type="text" class="form-control" name="ip_rubrik" value="<?php echo $query['ip_rubrik']; ?>">
                                </div>
                              </div>

                              <div class="form-group">
                                <label class="col-lg-2 control-label">Status</label>
                                <div class="col-lg-10">
                                  <select class="form-control" name="is_active">
                                    <option value="1" <?php if($query['is_active']=="1") echo "selected"; ?>>Active</option>
                                    <option value="0" <?php if($query['is_active']=="0") echo "selected"; ?>>Inactive</option>
                                  </select>
                                </div>
                              </div>

                            <!-- Site -->
                            <div class="form-group">
                                <label class="col-lg-2 control-label">Site</label>
                                <div class="col-lg-10">
                                    <input type="text" class="form-control" required name="site" value="<?php echo $query['id_site']; ?>">
                                </div>
                            </div>



                            <!-- Developer -->
                            <div class="form-group">
                              <label class="col-lg-2 control-label">Developer</label>
                              <div class="col-lg-10">
                                <select class="form-control" name="id_dev">
                                  <option value="">-- Pilih Developer --</option>
                                  <?php foreach($list_team as $team): ?>
                                    <option value="<?= $team['id_team']; ?>"
                                      <?= ($query['id_dev'] == $team['id_team']) ? 'selected' : ''; ?>>
                                      <?= $team['team_name']; ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                            </div>

                            <!-- Ops -->
                            <div class="form-group">
                              <label class="col-lg-2 control-label">Ops</label>
                              <div class="col-lg-10">
                                <select class="form-control" name="id_ops">
                                  <option value="">-- Pilih Ops --</option>
                                  <?php foreach($list_team as $team): ?>
                                    <option value="<?= $team['id_team']; ?>"
                                      <?= ($query['id_ops'] == $team['id_team']) ? 'selected' : ''; ?>>
                                      <?= $team['team_name']; ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                            </div>

                            <!-- Owner -->
                            <div class="form-group">
                              <label class="col-lg-2 control-label">Owner</label>
                              <div class="col-lg-10">
                                <select class="form-control" name="id_owner">
                                  <option value="">-- Pilih Owner --</option>
                                  <?php foreach($list_team as $team): ?>
                                    <option value="<?= $team['id_team']; ?>"
                                      <?= ($query['id_owner'] == $team['id_team']) ? 'selected' : ''; ?>>
                                      <?= $team['team_name']; ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                            </div>

                            <!-- Requestor -->
                            <div class="form-group">
                              <label class="col-lg-2 control-label">Requestor</label>
                              <div class="col-lg-10">
                                <select class="form-control" name="id_requestor">
                                  <option value="">-- Pilih Requestor --</option>
                                  <?php foreach($list_team as $team): ?>
                                    <option value="<?= $team['id_team']; ?>"
                                      <?= ($query['id_requestor'] == $team['id_team']) ? 'selected' : ''; ?>>
                                      <?= $team['team_name']; ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                            </div>


                            <!-- SLA -->
                            <div class="form-group" style="display:none;">
                              <label class="col-lg-2 control-label">SLA</label>
                              <div class="col-lg-10">
                                <select class="form-control" name="sla_rubriks[]" multiple>
                                  <?php foreach($list_sla as $sla): ?>
                                    <option value="<?= $sla['id_sla_rubrik']; ?>"
                                      <?= (isset($selected_sla) && in_array($sla['id_sla_rubrik'], $selected_sla)) ? 'selected' : ''; ?>>
                                      <?= $sla['sla_name']; ?> - 
                                      (<?= $sla['sla_type']; ?>)
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                                <small class="help-block">* Tekan Ctrl/Command untuk memilih lebih dari satu SLA</small>
                              </div>
                            </div>
                                    
                            <!-- VM Relation -->
                            <div class="form-group">
                              <label class="col-lg-2 control-label">VM Relation</label>
                              <div class="col-lg-10">
                                <select class="form-control" name="id_vm_relation">
                                  <option value="">-- Pilih VM --</option>
                                  <?php foreach($list_vm_relation as $vm_relation): ?>
                                    <option value="<?= $vm_relation['id_virtual_machine']; ?>"
                                      <?= ($query['id_vm_relation'] == $vm_relation['id_virtual_machine']) ? 'selected' : ''; ?>>
                                      <?= $vm_relation['virtual_machine_name']; ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                            </div>
                                    
                            <div class="form-group">
                              <label class="col-lg-2 control-label">Last Verify</label>
                              <div class="col-lg-10">
                                <input type="text" class="form-control" 
                                       value="<?php echo !empty($query['last_verify']) ? date('d-m-Y H:i:s', strtotime($query['last_verify'])) : '-'; ?> UTC" 
                                       readonly> 
                              </div>
                            </div>


                            <!-- Relation: Application System ↔ Component -->
                            <div class="form-group">
                                <label class="col-lg-2 control-label">Application ↔ Component</label>
                                <div class="col-lg-10">
                                    <div id="relation-rows">
                                        <?php if(!empty($relation_pairs)): ?>
                                            <?php foreach($relation_pairs as $pair): ?>
                                                <div class="relation-row" style="margin-bottom:8px; display:flex; gap:8px;">
                                                    <select class="form-control" name="relation_app[]">
                                                        <option value="">-- Pilih App --</option>
                                                        <?php foreach($list_application_system as $app): ?>
                                                            <option value="<?= $app['id_application_system']; ?>"
                                                                <?= ($pair['id_application_system'] == $app['id_application_system']) ? 'selected' : ''; ?>>
                                                                <?= $app['application_system_name']; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>

                                                    <select class="form-control" name="relation_component[]">
                                                        <option value="">-- Pilih Component --</option>
                                                        <?php foreach($list_component as $comp): ?>
                                                            <option value="<?= $comp['id_component']; ?>"
                                                                <?= ($pair['id_component'] == $comp['id_component']) ? 'selected' : ''; ?>>
                                                                <?= $comp['component_name']; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>

                                                    <button type="button" class="btn btn-danger btn-sm remove-row">X</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <!-- jika kosong, tampilkan 1 baris kosong -->
                                            <div class="relation-row" style="margin-bottom:8px; display:flex; gap:8px;">
                                                <select class="form-control" name="relation_app[]">
                                                    <option value="">-- Pilih App --</option>
                                                    <?php foreach($list_application_system as $app): ?>
                                                        <option value="<?= $app['id_application_system']; ?>"><?= $app['application_system_name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <select class="form-control" name="relation_component[]">
                                                    <option value="">-- Pilih Component --</option>
                                                    <?php foreach($list_component as $comp): ?>
                                                        <option value="<?= $comp['id_component']; ?>"><?= $comp['component_name']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <button type="button" class="btn btn-danger btn-sm remove-row">X</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm" id="add-row">+ Tambah Baris</button>
                                </div>
                            </div>

                            <!-- Tombol -->
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <!-- <button type="submit" class="btn btn-primary">Simpan</button> 
                                    <button type="button" class="btn btn-warning" id="btn-last-verify">Update Last Verify</button> -->
                                    <button type="submit" name="action" value="save" class="btn btn-primary">Simpan</button>
                                    <button type="submit" name="action" value="update_last_verify" class="btn btn-warning">Update Last Verify</button> 
                                    <button type="button" class="btn btn-white" onclick="window.history.back()">Batal</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
<!-- /page content -->

<script>
document.getElementById('add-row').addEventListener('click', function() {
    let container = document.getElementById('relation-rows');
    let newRow = container.querySelector('.relation-row').cloneNode(true);

    // reset pilihan
    newRow.querySelectorAll('select').forEach(sel => sel.selectedIndex = 0);

    // attach event remove ke tombol baru
    newRow.querySelector('.remove-row').addEventListener('click', function() {
        this.parentNode.remove();
    });

    container.appendChild(newRow);
});

// pasang event remove ke semua tombol yang ada
document.querySelectorAll('.remove-row').forEach(btn => {
    btn.addEventListener('click', function() {
        this.parentNode.remove();
    });
});



</script>
