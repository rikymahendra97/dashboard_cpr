<?php
/**
 * ============================================================================
 * File Name    : form_add_template.php
 * Modul        : Master Template VM
 * Purpose      : Form tambah data Master Template.
 * Architecture : Enterprise Standard CP-05
 * ============================================================================
 */
?>
<section class="content">
    <div class="right_col" role="main">
        <div class="clearfix"></div>

        <div style="margin-bottom: 15px;">
            <a href="<?= site_url(
                "master_template_vm",
            ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); font-weight: bold; color: #555;">
                <i class="fa fa-arrow-left"></i> Kembali ke Daftar Template
            </a>
        </div>

        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="x_panel" style="border-radius:8px;">
                    <div class="x_title">
                        <h2 style="font-weight:bold; color:#2A3F54;"><i class="fa fa-plus-square"></i> Tambah Master Template Baru</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <br />
                        <form action="<?= site_url(
                            "master_template_vm/simpan",
                        ) ?>" method="post" id="formTemplate">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                            <div class="form-group" style="margin-bottom:20px;">
                                <label style="font-size: 13px;">OS Family (Grup Dasar) <span class="text-danger">*</span></label>
                                <select class="form-control" name="template_family" required style="border-radius:4px;">
                                    <option value="">-- Pilih OS Family --</option>
                                    <option value="Red Hat Enterprise Linux">Red Hat Enterprise Linux</option>
                                    <option value="Ubuntu Linux">Ubuntu Linux</option>
                                    <option value="Windows Server">Windows Server</option>
                                    <option value="Other">Other / Custom Appliance</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom:20px;">
                                <label style="font-size: 13px;">Nama Spesifik Template <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="template_name" required placeholder="Contoh: Template RHEL 9.4 MySQL 8.0" style="border-radius:4px;">
                            </div>

                            <div class="form-group" style="margin-bottom:30px;">
                                <label style="font-size: 13px;">Status Penggunaan <span class="text-danger">*</span></label>
                                <select class="form-control" name="is_active" required style="border-radius:4px;">
                                    <option value="1">Aktif (Dapat Digunakan di Form Request)</option>
                                    <option value="0">Non-Aktif (Diarsipkan / Disembunyikan)</option>
                                </select>
                            </div>

                            <div class="ln_solid"></div>
                            <div class="form-group text-right">
                                <a href="<?= site_url(
                                    "master_template_vm",
                                ) ?>" class="btn btn-default">Batal</a>
                                <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Simpan Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
