<!-- page content -->
<div class="right_col" role="main">
  <div class="">
    <div class="row">

      <div class="col-sm-12">
        <section class="panel">
          <header class="panel-heading font-bold">Edit Virtual Machine</header>
          <div class="panel-body">
            <form class="bs-example form-horizontal" action="<?php echo base_url('index.php/virtual_machine/update_data_complex'); ?>" method="post">
              <?php echo $this->csrf->get_html(); ?>
              <input type="hidden" name="id_virtual_machine" value="<?php echo $query['id_virtual_machine']; ?>">

              <!-- Virtual Machine Info -->
              <div class="form-group">
                <label class="col-lg-2 control-label">VM Name</label>
                <div class="col-lg-10">
                  <input type="text" class="form-control" name="virtual_machine_name" value="<?php echo $query['virtual_machine_name']; ?>" required>
                </div>
              </div>

              <div class="form-group">
                <label class="col-lg-2 control-label">vCenter</label>
                <div class="col-lg-10">
                  <input type="text" class="form-control" name="vcenter_name" value="<?php echo $query['vcenter_name']; ?>">
                </div>
              </div>

              <div class="form-group">
                <label class="col-lg-2 control-label">Cluster</label>
                <div class="col-lg-10">
                  <input type="text" class="form-control" name="cluster_name" value="<?php echo $query['cluster_name']; ?>">
                </div>
              </div>

              <div class="form-group">
                <label class="col-lg-2 control-label">Host</label>
                <div class="col-lg-10">
                  <input type="text" class="form-control" name="host_name" value="<?php echo $query['host_name']; ?>">
                </div>
              </div>

              <div class="form-group">
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
                <label class="col-lg-2 control-label">CPU Count</label>
                <div class="col-lg-10">
                  <input type="number" class="form-control" name="cpu_count" value="<?php echo $query['cpu_count']; ?>">
                </div>
              </div>

              <div class="form-group">
                <label class="col-lg-2 control-label">Memory (MB)</label>
                <div class="col-lg-10">
                  <input type="number" class="form-control" name="memory_mb" value="<?php echo $query['memory_mb']; ?>">
                </div>
              </div>

              <div class="form-group">
                <label class="col-lg-2 control-label">Provisioned (GB)</label>
                <div class="col-lg-10">
                  <input type="number" class="form-control" name="provisioned_gb" value="<?php echo $query['provisioned_gb']; ?>">
                </div>
              </div>

              <div class="form-group">
                <label class="col-lg-2 control-label">Environment</label>
                <div class="col-lg-10">
                  <input type="text" class="form-control" name="environment" value="<?php echo $query['environment']; ?>">
                </div>
              </div>

              <div class="form-group">
                <label class="col-lg-2 control-label">IP Address</label>
                <div class="col-lg-10">
                  <input type="text" class="form-control" name="ip_address" value="<?php echo $query['ip_address']; ?>">
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


              <!-- Relation: SLA Rubrik -->
              <div class="form-group">
                <label class="col-lg-2 control-label">SLA Rubrik</label>
                <div class="col-lg-10">
                  <select class="form-control" name="sla_rubrik[]" multiple>
                    <?php foreach($list_sla as $sla): ?>
                      <option value="<?php echo $sla['id_sla_rubrik']; ?>"
                        <?php if(in_array($sla['id_sla_rubrik'], $selected_sla)) echo 'selected'; ?>>
                        <?php echo $sla['sla_name']; ?> (<?php echo $sla['sla_type']; ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- Relation: Application System -->
              <div class="form-group">
                <label class="col-lg-2 control-label">Application Systems</label>
                <div class="col-lg-10">
                  <select class="form-control" name="application_system[]" multiple>
                    <?php foreach($list_application_system as $app): ?>
                      <option value="<?php echo $app['id_application_system']; ?>"
                        <?php if(in_array($app['id_application_system'], $selected_apps)) echo 'selected'; ?>>
                        <?php echo $app['application_system_name']; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- Relation: Component -->
              <div class="form-group">
                <label class="col-lg-2 control-label">Components</label>
                <div class="col-lg-10">
                  <select class="form-control" name="component[]" multiple>
                    <?php foreach($list_component as $comp): ?>
                      <option value="<?php echo $comp['id_component']; ?>"
                        <?php if(in_array($comp['id_component'], $selected_components)) echo 'selected'; ?>>
                        <?php echo $comp['component_name']; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>


              <!-- Buttons -->
              <div class="form-group">
                <div class="col-lg-offset-2 col-lg-10">
                  <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
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
