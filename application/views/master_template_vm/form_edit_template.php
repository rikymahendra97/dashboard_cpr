<?php
/**
 * ============================================================================
 * File Name    : form_edit_template.php
 * Modul        : Master Template VM
 * Purpose      : Form edit data Master Template.
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
                <div class="x_panel" style="border-radius:8px; border: 1px solid #e2a632;">
                    <div class="x_title" style="background-color: #fcf8e3; border-bottom: 1px solid #faebcc; border-top-left-radius: 8px; border-top-right-radius: 8px; margin: -10px -10px 15px -10px; padding: 15px;">
                        <h2 style="font-weight:bold; color:#8a6d3b; margin:0;"><i class="fa fa-edit"></i> Edit Master Template</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <br />
                        <form action="<?= site_url(
                            "master_template_vm/update",
                        ) ?>" method="post" id="formTemplate">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <input type="hidden" name="id_template" value="<?= html_escape(
                                $detail["id_template"],
                            ) ?>">

                            <div class="form-group" style="margin-bottom:20px;">
                                <label style="font-size: 13px;">OS Family (Grup Dasar) <span class="text-danger">*</span></label>
                                <select class="form-control" name="template_family" required style="border-radius:4px;">
                                    <option value="Red Hat Enterprise Linux" <?= $detail[
                                        "template_family"
                                    ] == "Red Hat Enterprise Linux"
                                        ? "selected"
                                        : "" ?>>Red Hat Enterprise Linux</option>
                                    <option value="Ubuntu Linux" <?= $detail["template_family"] ==
                                    "Ubuntu Linux"
                                        ? "selected"
                                        : "" ?>>Ubuntu Linux</option>
                                    <option value="Windows Server" <?= $detail["template_family"] ==
                                    "Windows Server"
                                        ? "selected"
                                        : "" ?>>Windows Server</option>
                                    <option value="Other" <?= $detail["template_family"] == "Other"
                                        ? "selected"
                                        : "" ?>>Other / Custom Appliance</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom:20px;">
                                <label style="font-size: 13px;">Nama Spesifik Template <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="template_name" required value="<?= html_escape(
                                    $detail["template_name"],
                                ) ?>" style="border-radius:4px;">
                            </div>

                            <div class="form-group" style="margin-bottom:30px;">
                                <label style="font-size: 13px;">Status Penggunaan <span class="text-danger">*</span></label>
                                <select class="form-control" name="is_active" required style="border-radius:4px;">
                                    <option value="1" <?= $detail["is_active"] == 1
                                        ? "selected"
                                        : "" ?>>Aktif (Dapat Digunakan di Form Request)</option>
                                    <option value="0" <?= $detail["is_active"] == 0
                                        ? "selected"
                                        : "" ?>>Non-Aktif (Diarsipkan / Disembunyikan)</option>
                                </select>
                            </div>

                            <div class="ln_solid"></div>
                            <div class="form-group text-right">
                                <a href="<?= site_url(
                                    "master_template_vm",
                                ) ?>" class="btn btn-default">Batal</a>
                                <button type="submit" class="btn btn-warning"><i class="fa fa-save"></i> Update Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
