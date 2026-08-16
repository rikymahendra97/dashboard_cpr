<?php
/**
 * ============================================================================
 * File Name    : form_add_os.php
 * Modul        : Master OS
 * Purpose      : Form tambah data Master Operating System.
 * Architecture : Enterprise Standard CP-05
 * ============================================================================
 */
?>
<section class="content">
    <div class="right_col" role="main">
        <div class="clearfix"></div>

        <div style="margin-bottom: 15px;">
            <a href="<?= site_url(
                "master_os",
            ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); font-weight: bold; color: #555;">
                <i class="fa fa-arrow-left"></i> Kembali ke Daftar OS
            </a>
        </div>

        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="x_panel" style="border-radius:8px;">
                    <div class="x_title">
                        <h2 style="font-weight:bold; color:#2A3F54;"><i class="fa fa-plus-square"></i> Tambah Master OS Baru</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <br />
                        <form action="<?= site_url("master_os/simpan") ?>" method="post">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                            <div class="form-group" style="margin-bottom:20px;">
                                <label style="font-size: 13px;">OS Family (Grup Dasar) <span class="text-danger">*</span></label>
                                <select class="form-control" name="os_family" required style="border-radius:4px;">
                                    <option value="">-- Pilih OS Family --</option>
                                    <option value="Red Hat Enterprise Linux">Red Hat Enterprise Linux</option>
                                    <option value="Ubuntu Linux">Ubuntu Linux</option>
                                    <option value="Windows Server">Windows Server</option>
                                    <option value="Other">Other / Custom Appliance</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom:20px;">
                                <label style="font-size: 13px;">Nama Spesifik OS <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="os_name" required placeholder="Contoh: Ubuntu 24.04 LTS x64" style="border-radius:4px;">
                            </div>

                            <div class="form-group" style="margin-bottom:30px;">
                                <label style="font-size: 13px;">Status Penggunaan <span class="text-danger">*</span></label>
                                <select class="form-control" name="is_active" required style="border-radius:4px;">
                                    <option value="1">Aktif</option>
                                    <option value="0">Non-Aktif</option>
                                </select>
                            </div>

                            <div class="ln_solid"></div>
                            <div class="form-group text-right">
                                <a href="<?= site_url(
                                    "master_os",
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
