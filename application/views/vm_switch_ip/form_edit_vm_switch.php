<?php
/**
 * =============================================================================
 * File Name    : form_edit_vm_switch.php
 * Modul        : VM Switch IP
 * Purpose      : Halaman form untuk mengedit/koreksi data Master Request Switch IP.
 * Architecture : Pure SweetAlert2, BFCache Safe, Safe JSON Injection, ID Sync
 * =============================================================================
 */

$id = $id ?? [];
$user_session = $user_session ?? [];
$detail = $detail ?? [];
$vm_details = $vm_details ?? [];
$list_vm = $list_vm ?? [];
$master_team = $master_team ?? [];

$vm1 = isset($vm_details[0]) ? $vm_details[0] : null;
$vm2 = isset($vm_details[1]) ? $vm_details[1] : null;
?>

<div class="right_col" role="main">
    <div class="">
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">

                <div style="margin-bottom: 15px;">
                    <a href="<?= site_url(
                        "vm_switch_ip",
                    ) ?>" class="btn btn-default btn-sm" style="border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); font-weight: bold; color: #555;">
                        <i class="fa fa-arrow-left"></i> Kembali ke Daftar Log
                    </a>
                </div>

                <!-- [ENTERPRISE FIX]: SWEETALERT BFCACHE-SAFE DATA INJECTION -->
                <div id="alert-container">
                    <?php
                    $alerts = $this->session->flashdata("alerts") ?? [];
                    if (empty($alerts) && $this->session->flashdata("error")) {
                        $alerts = [["error", $this->session->flashdata("error")]];
                    }
                    $this->session->unset_userdata("alerts");
                    $this->session->unset_userdata("error");

                    if (!empty($alerts) && is_array($alerts) && isset($alerts[0])):

                        $tipe = $alerts[0][0] === "error" ? "error" : "success";
                        $pesan = html_escape($alerts[0][1]);
                        ?>
                        <div id="swal-flash-data" data-type="<?= $tipe ?>" data-message="<?= $pesan ?>" style="display: none;"></div>
                    <?php
                    endif;
                    ?>
                </div>

                <section class="panel" style="box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2a632; border-radius: 8px;">
                    <header class="panel-heading" style="background-color: #fcf8e3; padding: 18px 20px; border-bottom: 1px solid #faebcc; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                        <h3 style="margin: 0; font-weight: bold; color: #8a6d3b; font-size: 18px;">
                            <i class="fa fa-edit"></i> Edit Request Switch IP
                        </h3>
                    </header>

                    <div class="panel-body" style="padding: 30px;">
                        <form action="<?php echo site_url(
                            "vm_switch_ip/update_data",
                        ); ?>" method="post" id="formSwitchIP" novalidate>
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" id="csrf_token">
                            <input type="hidden" name="id_switch" value="<?= html_escape(
                                $detail["id_switch"] ?? "",
                            ) ?>">
                            <input type="hidden" name="resolve_incident_id" id="resolve_incident_id" value="<?= html_escape(
                                $detail["id_incident"] ?? "",
                            ) ?>">

                            <h4 style="font-weight: bold; color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 20px; font-size: 15px;">
                                <i class="fa fa-file-text-o"></i> Informasi Request & Peminta
                            </h4>

                            <div class="row">
                                <div class="col-md-6 col-sm-12" style="border-right: 1px solid #edf2f7; padding-right: 20px;">
                                    <h5 class="font-bold text-primary" style="margin-top: 0; margin-bottom: 15px;"><i class="fa fa-ticket"></i> A. Data Tiket & Skenario</h5>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold">No Tiket <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control required-field" name="no_tiket" required value="<?= html_escape(
                                                    $detail["no_tiket_eksternal"] ?? "",
                                                ) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-bold">Jenis Skenario <span class="text-danger">*</span></label>
                                                <select class="form-control required-field" name="jenis_switch" id="jenis_switch" required>
                                                    <option value="Ganti IP (Single VM)" <?= ($detail[
                                                        "jenis_switch"
                                                    ] ??
                                                        "") ==
                                                    "Ganti IP (Single VM)"
                                                        ? "selected"
                                                        : "" ?>>Ganti IP (Single VM)</option>
                                                    <option value="Tukar Silang (Dual VM)" <?= ($detail[
                                                        "jenis_switch"
                                                    ] ??
                                                        "") ==
                                                    "Tukar Silang (Dual VM)"
                                                        ? "selected"
                                                        : "" ?>>Tukar Silang (Dual VM)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="font-bold">Link Tiket Eksternal</label>
                                        <input type="url" class="form-control" name="link_tiket" value="<?= html_escape(
                                            $detail["link_tiket_eksternal"] ?? "",
                                        ) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="font-bold">Deskripsi Permintaan <span class="text-danger">*</span></label>
                                        <textarea class="form-control required-field" name="deskripsi_permintaan" rows="3" required><?= html_escape(
                                            $detail["deskripsi_permintaan"] ?? "",
                                        ) ?></textarea>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-12" style="padding-left: 20px;">
                                    <h5 class="font-bold text-success" style="margin-top: 0; margin-bottom: 15px;"><i class="fa fa-user"></i> B. Informasi Requestor</h5>

                                    <div style="background-color: #f9fbfd; padding: 20px; border: 1px solid #dae1e7; border-radius: 6px;">
                                        <div class="form-group">
                                            <label class="font-bold text-primary">Fungsi / Departemen <span class="text-danger">*</span></label>
                                            <select class="form-control" id="selectTeamGroup" style="width: 100%;" required>
                                                <option value="">-- Pilih Fungsi / Departemen --</option>
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-7">
                                                <div class="form-group" style="margin-bottom: 10px;">
                                                    <label class="font-bold text-info">Requestor / PIC <span class="text-danger">*</span></label>
                                                    <select class="form-control required-field" name="id_team_requestor" id="id_team_requestor" style="width: 100%;" required disabled>
                                                        <option value="">-- Pilih Requestor / PIC --</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group" style="margin-bottom: 10px;">
                                                    <label class="text-muted font-bold" style="font-size: 12px;">Kontak PIC</label>
                                                    <input type="text" id="info_kontak_pic" class="form-control input-sm" readonly placeholder="-" style="background-color: #eef2f5; font-size: 12px; color: #475569; font-weight: bold; border-color: #d1e0ec;">
                                                </div>
                                            </div>
                                        </div>

                                        <div style="margin-top: 10px; padding-top: 12px; border-top: 1px dashed #cbd5e1;">
                                            <button type="button" class="btn btn-default btn-xs" id="btn-quick-add-team" style="margin:0; font-weight:bold; background-color: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                                <i class="fa fa-plus text-success"></i> Tambah Data
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group" style="background-color: #fcf8e3; border-left: 3px solid #f0ad4e; padding: 15px; margin-top: 5px; margin-bottom: 20px; border-radius: 4px;">
                                        <div class="checkbox" style="margin: 0;">
                                            <label style="font-weight: bold; color: #8a6d3b;">
                                                <input type="checkbox" id="toggle_backdate" value="1">
                                                <i class="fa fa-clock-o"></i> Penyesuaian Waktu Log Pembuatan (Backdate)
                                            </label>
                                        </div>
                                        <div id="backdate_container" style="display: none; padding-left: 20px; margin-top: 15px;">
                                            <label class="font-bold text-warning">Tanggal Dibuat (Create)</label>
                                            <input type="datetime-local" class="form-control" name="created_at" id="input_created_at" value="<?= date(
                                                "Y-m-d\TH:i",
                                                strtotime($detail["created_at"] ?? "now"),
                                            ) ?>" style="max-width: 250px;">
                                            <small class="text-muted" style="display:block; margin-top:5px; font-style:italic;">* Ubah jika ingin mengoreksi waktu pembuatan tiket historis.</small>
                                        </div>

                                        <?php if (
                                            in_array($detail["status_eksekusi"] ?? "", [
                                                "Telah Dieksekusi",
                                                "Selesai Verified",
                                            ])
                                        ): ?>
                                            <div class="col-md-6 col-sm-12" style="margin-top: 15px; padding-left: 0;">
                                                <label class="font-bold text-warning">Tanggal Dieksekusi</label>
                                                <input type="datetime-local" class="form-control" name="tanggal_eksekusi" style="max-width: 250px;" value="<?= !empty(
                                                    $detail["tanggal_eksekusi"]
                                                )
                                                    ? date(
                                                        "Y-m-d\TH:i",
                                                        strtotime($detail["tanggal_eksekusi"]),
                                                    )
                                                    : "" ?>">
                                                <small class="text-muted" style="display:block; margin-top:5px; font-style:italic;">* Ubah ini untuk penyesuaian laporan eksekusi.</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <h4 style="font-weight: bold; color: #2A3F54; border-bottom: 2px solid #e5e5e5; padding-bottom: 8px; margin-bottom: 20px; margin-top: 30px; font-size: 15px;">
                                <i class="fa fa-desktop"></i> Konfigurasi Virtual Machine 1
                            </h4>

                            <div style="background-color: #fdfdfd; padding: 20px; border: 1px solid #eee; border-radius: 6px; margin-bottom: 20px;">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="font-bold text-primary">Cari & Pilih VM 1 <span class="text-danger">*</span></label>
                                            <select class="form-control select2-vm required-field" name="id_vm_1" id="id_vm_1" style="width: 100%;" required>
                                                <option value="">-- Ketik Nama VM atau IP Address --</option>
                                                <optgroup label="🏢 SITE TBN">
                                                    <?php
                                                    $is_found_1 = false;
                                                    foreach ($list_vm as $vm):

                                                        $vm_name = $vm["virtual_machine_name"];
                                                        $primary_ip = isset($vm["ip_address"])
                                                            ? trim($vm["ip_address"])
                                                            : "";

                                                        if (
                                                            empty($primary_ip) ||
                                                            $primary_ip === "-"
                                                        ) {
                                                            $display_text =
                                                                html_escape($vm_name) .
                                                                " | ⚠️ (Tidak ada IP)";
                                                        } elseif (
                                                            strpos($vm_name, $primary_ip) !== false
                                                        ) {
                                                            $display_text = html_escape($vm_name);
                                                        } else {
                                                            $display_text =
                                                                html_escape($vm_name) .
                                                                " | IP: " .
                                                                html_escape($primary_ip);
                                                        }

                                                        $selected1 = "";
                                                        if (
                                                            $vm1 &&
                                                            $vm["id_virtual_machine"] ==
                                                                $vm1["id_virtual_machine"]
                                                        ) {
                                                            $selected1 = "selected";
                                                            $is_found_1 = true;
                                                        }
                                                        ?>
                                                        <option value="<?= $vm[
                                                            "id_virtual_machine"
                                                        ] ?>" data-ips='<?= html_escape(
    $vm["ip_list_json"] ?? "[]",
) ?>' data-name="<?= html_escape($vm_name) ?>" <?= $selected1 ?>>
                                                            <?= $display_text ?>
                                                        </option>
                                                    <?php
                                                    endforeach;
                                                    ?>

                                                    <?php if (
                                                        !$is_found_1 &&
                                                        $vm1 &&
                                                        !empty($vm1["id_virtual_machine"])
                                                    ): ?>
                                                        <option value="<?= html_escape(
                                                            $vm1["id_virtual_machine"],
                                                        ) ?>" data-ips='["<?= html_escape(
    $vm1["ip_lama"],
) ?>"]' data-name="<?= html_escape($vm1["nama_vm_lama"]) ?>" selected>
                                                            <?= html_escape(
                                                                $vm1["nama_vm_lama"],
                                                            ) ?> | IP: <?= html_escape(
     $vm1["ip_lama"],
 ) ?> (Legacy/Non-Aktif)
                                                        </option>
                                                    <?php endif; ?>
                                                </optgroup>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" style="margin-top: 10px;">
                                    <div class="col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label class="text-muted">Kondisi Saat Ini: IP Address</label>
                                            <div id="container_ip_lama_1">
                                                <input type="text" class="form-control" name="ip_lama_1" id="ip_lama_1" readonly placeholder="-" value="<?= $vm1
                                                    ? html_escape($vm1["ip_lama"])
                                                    : "" ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label class="text-muted">Kondisi Saat Ini: Nama VM</label>
                                            <input type="text" class="form-control" name="nama_lama_1" id="nama_lama_1" readonly placeholder="-" value="<?= $vm1
                                                ? html_escape($vm1["nama_vm_lama"])
                                                : "" ?>">
                                        </div>
                                    </div>
                                </div>

                                <hr style="border-top: 1px dashed #ccc; margin: 10px 0 20px;">

                                <div class="row">
                                    <div class="col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label class="font-bold text-success">Target IP Baru 1 <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control required-field" name="ip_baru_1" id="ip_baru_1" required placeholder="10.10.10.5" value="<?= $vm1
                                                ? html_escape($vm1["ip_baru"])
                                                : "" ?>">
                                            <small class="text-muted" style="font-style:italic; margin-top:4px; display:block;">
                                                <i class="fa fa-info-circle"></i> Jika IP tidak tersedia atau belum dialokasikan, silakan isi dengan tanda strip <b>"-"</b>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6">
                                        <div class="form-group">
                                            <label class="font-bold text-success">Target Nama Baru 1 <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control required-field" name="nama_baru_1" id="nama_baru_1" required placeholder="vm-app-prod-01" value="<?= $vm1
                                                ? html_escape($vm1["nama_vm_baru"])
                                                : "" ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="section_vm_2" style="<?= ($detail["jenis_switch"] ?? "") ==
                            "Ganti IP (Single VM)"
                                ? "display: none;"
                                : "" ?>">
                                <h4 style="font-weight: bold; color: #f0ad4e; border-bottom: 2px solid #faebcc; padding-bottom: 8px; margin-bottom: 20px; margin-top: 30px; font-size: 15px;">
                                    <i class="fa fa-desktop"></i> Konfigurasi Virtual Machine 2 (Swap Target)
                                </h4>

                                <div style="background-color: #fffaf0; padding: 20px; border: 1px solid #faebcc; border-radius: 6px;">
                                    <div class="alert alert-info" style="background-color: #e9f7fe; border-color: #bce8f1; color: #31708f; padding: 10px; margin-bottom: 15px;">
                                        <i class="fa fa-info-circle"></i> <strong>Info Skenario Tukar Silang:</strong> Memilih VM 2 akan otomatis menyilangkan konfigurasi Target IP antara VM 1 dan VM 2.
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="font-bold text-warning">Cari & Pilih VM 2 <span class="text-danger">*</span></label>
                                                <select class="form-control select2-vm" name="id_vm_2" id="id_vm_2" style="width: 100%;">
                                                    <option value="">-- Ketik Nama VM atau IP Address --</option>
                                                    <optgroup label="🏢 SITE TBN">
                                                        <?php
                                                        $is_found_2 = false;
                                                        foreach ($list_vm as $vm):

                                                            $vm_name = $vm["virtual_machine_name"];
                                                            $primary_ip = isset($vm["ip_address"])
                                                                ? trim($vm["ip_address"])
                                                                : "";

                                                            if (
                                                                empty($primary_ip) ||
                                                                $primary_ip === "-"
                                                            ) {
                                                                $display_text =
                                                                    html_escape($vm_name) .
                                                                    " | ⚠️ (Tidak ada IP)";
                                                            } elseif (
                                                                strpos($vm_name, $primary_ip) !==
                                                                false
                                                            ) {
                                                                $display_text = html_escape(
                                                                    $vm_name,
                                                                );
                                                            } else {
                                                                $display_text =
                                                                    html_escape($vm_name) .
                                                                    " | IP: " .
                                                                    html_escape($primary_ip);
                                                            }

                                                            $selected2 = "";
                                                            if (
                                                                $vm2 &&
                                                                $vm["id_virtual_machine"] ==
                                                                    $vm2["id_virtual_machine"]
                                                            ) {
                                                                $selected2 = "selected";
                                                                $is_found_2 = true;
                                                            }
                                                            ?>
                                                            <option value="<?= $vm[
                                                                "id_virtual_machine"
                                                            ] ?>" data-ips='<?= html_escape(
    $vm["ip_list_json"] ?? "[]",
) ?>' data-name="<?= html_escape($vm_name) ?>" <?= $selected2 ?>>
                                                                <?= $display_text ?>
                                                            </option>
                                                        <?php
                                                        endforeach;
                                                        ?>

                                                        <?php if (
                                                            !$is_found_2 &&
                                                            $vm2 &&
                                                            !empty($vm2["id_virtual_machine"])
                                                        ): ?>
                                                            <option value="<?= html_escape(
                                                                $vm2["id_virtual_machine"],
                                                            ) ?>" data-ips='["<?= html_escape(
    $vm2["ip_lama"],
) ?>"]' data-name="<?= html_escape($vm2["nama_vm_lama"]) ?>" selected>
                                                                <?= html_escape(
                                                                    $vm2["nama_vm_lama"],
                                                                ) ?> | IP: <?= html_escape(
     $vm2["ip_lama"],
 ) ?> (Legacy/Non-Aktif)
                                                            </option>
                                                        <?php endif; ?>
                                                    </optgroup>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row" style="margin-top: 10px;">
                                        <div class="col-md-6 col-sm-6">
                                            <div class="form-group">
                                                <label class="text-muted">Kondisi Saat Ini: IP Address</label>
                                                <div id="container_ip_lama_2">
                                                    <input type="text" class="form-control" name="ip_lama_2" id="ip_lama_2" readonly placeholder="-" value="<?= $vm2
                                                        ? html_escape($vm2["ip_lama"])
                                                        : "" ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="form-group">
                                                <label class="text-muted">Kondisi Saat Ini: Nama VM</label>
                                                <input type="text" class="form-control" name="nama_lama_2" id="nama_lama_2" readonly placeholder="-" value="<?= $vm2
                                                    ? html_escape($vm2["nama_vm_lama"])
                                                    : "" ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <hr style="border-top: 1px dashed #e8d0a0; margin: 10px 0 20px;">

                                    <div class="row">
                                        <div class="col-md-6 col-sm-6">
                                            <div class="form-group">
                                                <label class="font-bold text-success">Target IP Baru 2 <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="ip_baru_2" id="ip_baru_2" placeholder="Auto fill dari IP VM 1" value="<?= $vm2
                                                    ? html_escape($vm2["ip_baru"])
                                                    : "" ?>">
                                                <small class="text-muted" style="font-style:italic; margin-top:4px; display:block;">
                                                    <i class="fa fa-info-circle"></i> Jika IP tidak tersedia atau belum dialokasikan, silakan isi dengan tanda strip <b>"-"</b>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="form-group">
                                                <label class="font-bold text-success">Target Nama Baru 2 <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="nama_baru_2" id="nama_baru_2" placeholder="Auto fill dari Nama VM 1" value="<?= $vm2
                                                    ? html_escape($vm2["nama_vm_baru"])
                                                    : "" ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row" style="margin-top: 40px;">
                                <div class="col-md-12 text-right">
                                    <hr style="border-top: 1px solid #e5e5e5; margin-bottom: 20px;">
                                    <a href="<?= site_url(
                                        "vm_switch_ip",
                                    ) ?>" class="btn btn-default font-bold btn-lg" style="border-radius: 4px; margin-right: 10px;">
                                        <i class="fa fa-times"></i> Batal Edit
                                    </a>
                                    <!-- [ENTERPRISE FIX]: Penahan Klik Ganda Form Submit -->
                                    <button type="submit" class="btn btn-primary font-bold btn-lg" id="btnSubmitAdd" style="border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">
                                        <i class="fa fa-save"></i> Update Data
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalQuickAddTeam" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 8px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
            <form id="formQuickAddTeam">
                <div class="modal-header" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef; border-top-left-radius: 8px; border-top-right-radius: 8px; padding: 18px 25px;">
                    <button type="button" class="close" data-dismiss="modal" style="color: #666; opacity: 0.7; margin-top: 2px;"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title font-bold" style="color: #2c3e50; font-size: 16px;">
                        <i class="fa fa-plus-square text-primary" style="margin-right: 8px;"></i> Tambah Master Fungsi/Tim
                    </h4>
                </div>

                <div class="modal-body" style="padding: 25px 30px; background-color: #fff;">
                    <div style="background-color: #fdfdfd; border: 1px solid #e6e9ed; padding: 15px 15px 5px 15px; border-radius: 6px; margin-bottom: 20px;">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label style="font-size: 12px; font-weight:bold; color: #444;">Nama Team / Fungsi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="qa_team_name" placeholder="Contoh: DIVISI IT APP" required style="border-radius: 4px; text-transform: uppercase;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label style="font-size: 12px; font-weight:bold; color: #444;">Kode <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="qa_team_code" placeholder="Maks 4" required maxlength="4" style="border-radius: 4px; text-transform: uppercase; text-align: center; font-weight: bold; letter-spacing: 1px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-size: 12px; font-weight:bold; color: #777;">Nama Requestor/PIC <small class="text-muted" style="font-weight: normal;">(Opsional)</small></label>
                                <input type="text" class="form-control" id="qa_pic_name" placeholder="Nama lengkap PIC..." style="border-radius: 4px;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-size: 12px; font-weight:bold; color: #777;">Kontak PIC <small class="text-muted" style="font-weight: normal;">(Opsional)</small></label>
                                <input type="text" class="form-control" id="qa_pic_contact" placeholder="Telepon / Email..." style="border-radius: 4px;">
                            </div>
                        </div>
                    </div>
                    <div id="qa_error_container" style="margin-top: 5px;"></div>
                </div>

                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef; padding: 15px 25px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                    <button type="button" class="btn btn-default font-bold" data-dismiss="modal" style="border-radius: 4px;">Batal</button>
                    <button type="submit" class="btn btn-primary font-bold" id="btnSaveQuickAdd" style="border-radius: 4px;"><i class="fa fa-save"></i> Simpan ke Master</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- [ENTERPRISE FIX]: Injeksi JS Variabel Linter-Safe & JSON XSS Safe -->
<script>
    var TEAM_DATA_JSON_STRING = '<?= json_encode(
        $master_team ?? [],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
    ) ?>';
    var URL_AJAX_DUPLICATE    = '<?= site_url("vm_switch_ip/ajax_check_duplicate") ?>';
    var CSRF_NAME_VAL         = '<?= $this->security->get_csrf_token_name() ?>';
    var CURRENT_ID            = '<?= html_escape($detail["id_switch"] ?? "0") ?>';
</script>

<script>
$(document).ready(function() {

    // [ENTERPRISE FIX]: Penanganan Flashdata SweetAlert (Anti-BFCache Leak)
    var $flashElem = $('#swal-flash-data');
    if ($flashElem.length > 0) {
        var swalType = $flashElem.data('type');
        var swalMessage = $flashElem.data('message');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: swalType, title: swalType === 'error' ? 'Gagal' : 'Informasi',
                text: swalMessage, timer: 3500, showConfirmButton: false
            });
        }
        if (window.history.replaceState) { window.history.replaceState(null, null, window.location.href); }
        $flashElem.remove();
    }

    // --- 1. Engine Grouping JSON Dropdown Team ---
    var rawTeamData = [];
    var groupedTeams = {};
    try { rawTeamData = JSON.parse(TEAM_DATA_JSON_STRING); } catch(e) { console.error(e); }

    rawTeamData.forEach(function(item) {
        var keyStr = item.team_code ? item.team_code : item.team_name;
        var labelStr = (item.team_code ? '[' + item.team_code + '] ' : '') + item.team_name;
        if (!groupedTeams[keyStr]) { groupedTeams[keyStr] = { label: labelStr, pics: [] }; }
        groupedTeams[keyStr].pics.push(item);
    });

    var savedIdTeam = "<?= html_escape($detail["id_team_requestor"] ?? "") ?>";
    var initialGroupKey = '';

    if (savedIdTeam !== '') {
        rawTeamData.forEach(function(item) {
            if (item.id_team == savedIdTeam) initialGroupKey = item.team_code ? item.team_code : item.team_name;
        });
    }

    var $groupSelect = $('#selectTeamGroup');
    var $picSelect = $('#id_team_requestor');

    $.each(groupedTeams, function(key, groupObj) {
        var isSelected = (key === initialGroupKey) ? 'selected' : '';
        $groupSelect.append('<option value="' + key + '" ' + isSelected + '>' + groupObj.label + '</option>');
    });

    $groupSelect.select2({ placeholder: '-- Pilih Fungsi / Departemen --', width: '100%' });

    $groupSelect.on('change', function() {
        var selectedKey = $(this).val();

        if ($picSelect.hasClass("select2-hidden-accessible")) { $picSelect.select2('destroy'); }
        $picSelect.empty().append('<option value="">-- Pilih Requestor / PIC --</option>');
        $('#info_kontak_pic').val('');

        if (selectedKey && groupedTeams[selectedKey]) {
            var picList = groupedTeams[selectedKey].pics;
            $picSelect.prop('disabled', false);

            picList.forEach(function(p) {
                var picNameDisplay = (p.pic_name && p.pic_name !== '-') ? p.pic_name : 'Tim Umum (Tanpa Spesifik PIC)';
                var isSelected = (p.id_team == savedIdTeam) ? 'selected' : '';
                $picSelect.append('<option value="' + p.id_team + '" data-contact="' + (p.pic_contact || '-') + '" ' + isSelected + '>' + picNameDisplay + '</option>');
            });

            $picSelect.select2({ placeholder: '-- Pilih Requestor / PIC --', width: '100%' });

            if (picList.length === 1 && !savedIdTeam) {
                $picSelect.val(picList[0].id_team).trigger('change');
            } else if (savedIdTeam) {
                $picSelect.val(savedIdTeam).trigger('change');
            }
        } else {
            $picSelect.prop('disabled', true);
            $picSelect.select2({ placeholder: '-- Pilih Requestor / PIC --', width: '100%' });
        }
    });

    $picSelect.on('change', function() {
        var selectedDOM = $(this).find('option:selected')[0];
        if (selectedDOM && $(this).val() !== '') {
            var picContact = selectedDOM.getAttribute('data-contact');
            $('#info_kontak_pic').val(picContact && picContact !== '-' ? picContact : 'Tidak Ada Kontak');
        } else {
            $('#info_kontak_pic').val('');
        }
    });

    if (initialGroupKey !== '') {
        $groupSelect.trigger('change');
    } else {
        $picSelect.select2({ placeholder: '-- Pilih Requestor / PIC --', width: '100%' }).prop('disabled', true);
    }

    // --- 2. Engine Master Data Modal Quick Add ---
    function showEnterpriseToast(type, title, message) {
        $('.enterprise-floating-toast').remove();
        var borderColors = type === 'success' ? '#26b99a' : '#e74c3c';
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var titleColor = type === 'success' ? '#169f85' : '#c0392b';

        var toastHtml = '<div class="enterprise-floating-toast" style="position: fixed; top: 80px; right: 20px; z-index: 999999; background: #fff; border-left: 4px solid ' + borderColors + '; border-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); padding: 15px 20px; min-width: 280px; max-width: 380px; color: #333;">' +
            '<div style="font-size: 13px; line-height: 1.4;"><i class="fa ' + icon + '" style="color: ' + borderColors + '; font-size: 20px; margin-right: 12px; float: left; margin-top: 2px;"></i>' +
            '<div style="overflow: hidden; Margins: 0;"><strong style="color: ' + titleColor + '; font-size: 14px;">' + title + '</strong><br><span style="color: #555; display: inline-block; margin-top: 4px;">' + message + '</span></div></div></div>';

        $('body').append(toastHtml);
        setTimeout(function() { $('.enterprise-floating-toast').fadeOut(400, function() { $(this).remove(); }); }, 5000);
    }

    $('#btn-quick-add-team').on('click', function(e) {
        e.preventDefault();
        $('#formQuickAddTeam')[0].reset();
        $('#qa_team_name, #qa_team_code').css('border', '');
        $('#qa_error_container').html('');
        $('#modalQuickAddTeam').modal('show');
        setTimeout(function() { $('#qa_team_name').focus(); }, 500);
    });

    $('#qa_team_code').on('input', function() { $(this).val($(this).val().toUpperCase()); });

    $('#formQuickAddTeam').on('submit', function(e) {
        e.preventDefault();
        var teamName = $('#qa_team_name').val().trim();
        var teamCode = $('#qa_team_code').val().trim().toUpperCase();
        var picName = $('#qa_pic_name').val().trim();
        var picContact = $('#qa_pic_contact').val().trim();

        $('#qa_team_name, #qa_team_code').css('border', '');
        $('#qa_error_container').html('');

        if (teamName === '' || teamCode === '') {
            if (teamName === '') $('#qa_team_name').css('border', '1px solid #e74c3c');
            if (teamCode === '') $('#qa_team_code').css('border', '1px solid #e74c3c');
            return;
        }

        var btnSave = $('#btnSaveQuickAdd');
        btnSave.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');

        var payload = { team_name: teamName, team_code: teamCode, pic_name: picName, pic_contact: picContact };
        payload[$('#csrf_token').attr('name')] = $('#csrf_token').val();

        $.ajax({
            url: '<?= site_url("vm_switch_ip/ajax_quick_add_team") ?>',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    var newItem = { id_team: res.id_team, team_code: res.team_code, team_name: res.team_name, pic_name: picName, pic_contact: picContact };
                    rawTeamData.push(newItem);

                    var keyStr = res.team_code ? res.team_code : res.team_name;
                    var labelStr = (res.team_code ? '[' + res.team_code + '] ' : '') + res.team_name;

                    if (!groupedTeams[keyStr]) {
                        groupedTeams[keyStr] = { label: labelStr, pics: [] };
                        var newOption = new Option(labelStr, keyStr, false, false);
                        $groupSelect.append(newOption).trigger('change');
                    }
                    groupedTeams[keyStr].pics.push(newItem);

                    savedIdTeam = res.id_team;
                    $groupSelect.val(keyStr).trigger('change');

                    $('#modalQuickAddTeam').modal('hide');
                    btnSave.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan ke Master');
                    showEnterpriseToast('success', 'Penyimpanan Berhasil', res.message);
                } else {
                    $('#qa_error_container').html('<div class="alert alert-danger" style="padding:8px; margin:0; font-size:12px;"><i class="fa fa-times-circle"></i> ' + res.message + '</div>');
                    btnSave.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan ke Master');
                    showEnterpriseToast('error', 'Gagal Menyimpan', res.message);
                }
            },
            error: function() {
                $('#qa_error_container').html('<div class="alert alert-danger" style="padding:8px; margin:0; font-size:12px;"><i class="fa fa-wifi"></i> Gagal terhubung ke server.</div>');
                btnSave.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan ke Master');
                showEnterpriseToast('error', 'Gangguan Jaringan', 'Gagal berkomunikasi dengan server pusat.');
            }
        });
    });

    // --- 3. Engine Helpers ---
    function renderIpContainer(suffix, ipsArray, selectedIp) {
        selectedIp = selectedIp || '';
        var container = $('#container_ip_lama_' + suffix);
        var html = ''; var note = '';

        if (ipsArray && ipsArray.length > 1) {
            html = '<select name="ip_lama_' + suffix + '" id="ip_lama_' + suffix + '" class="form-control" style="border: 2px solid #3498DB; box-shadow: 0 0 5px rgba(52,152,219,0.2); font-weight:bold; cursor:pointer;" required>';
            var isLegacyIncluded = false;
            if (selectedIp && selectedIp !== '-' && !ipsArray.includes(selectedIp)) {
                html += '<option value="' + selectedIp + '" selected>' + selectedIp + ' (Saved/Legacy)</option>';
                isLegacyIncluded = true;
            }
            ipsArray.forEach(function(ip, index) {
                var label = (index === 0) ? ' (Primary)' : ' (NIC ' + (index + 1) + ')';
                var isSelected = (ip === selectedIp && !isLegacyIncluded) ? 'selected' : '';
                html += '<option value="' + ip + '" ' + isSelected + '>' + ip + label + '</option>';
            });
            html += '</select>';
            note = '<small class="text-primary" style="display:block; margin-top:5px; font-weight:600;"><i class="fa fa-info-circle"></i> <strong>Multi-NIC Terdeteksi:</strong> Silakan pilih spesifik IP yang digantikan.</small>';
        } else if (ipsArray && ipsArray.length === 1) {
            var valToUse = (selectedIp && selectedIp !== '-') ? selectedIp : ipsArray[0];
            html = '<input type="text" class="form-control" name="ip_lama_' + suffix + '" id="ip_lama_' + suffix + '" readonly value="' + valToUse + '">';
            note = '<small class="text-success" style="display:block; margin-top:5px;"><i class="fa fa-check-circle"></i> Menggunakan IP tunggal (Primary Interface).</small>';
        } else {
            var valToUse = (selectedIp) ? selectedIp : '-';
            html = '<input type="text" class="form-control" name="ip_lama_' + suffix + '" id="ip_lama_' + suffix + '" readonly value="' + valToUse + '">';
            if (valToUse === '-') note = '<small class="text-warning" style="display:block; margin-top:5px; font-weight:600;"><i class="fa fa-exclamation-triangle"></i> Tidak ada IP yang tercatat untuk VM ini.</small>';
            else note = '<small class="text-warning" style="display:block; margin-top:5px; font-weight:600;"><i class="fa fa-info-circle"></i> Menggunakan IP tersimpan sebelumnya.</small>';
        }
        container.html(html + note);
    }

    function extractBaseName(vmName) {
        if (!vmName) return '';
        var ipPattern = /_((?:[0-9]{1,3}\.){3}[0-9]{1,3})$/;
        return vmName.replace(ipPattern, '');
    }

    function updateStandardTarget(suffix) {
        if ($('#jenis_switch').val() === 'Ganti IP (Single VM)') {
            var namaLama = $('#nama_lama_' + suffix).val();
            var ipBaru = $('#ip_baru_' + suffix).val();

            if (namaLama) {
                var prefix = extractBaseName(namaLama);
                if (ipBaru && ipBaru !== '-') {
                    $('#nama_baru_' + suffix).val(prefix + '_' + ipBaru);
                } else {
                    $('#nama_baru_' + suffix).val(prefix);
                }
            }
        }
    }

    function calculateSwapLogic() {
        if ($('#jenis_switch').val() !== 'Tukar Silang (Dual VM)') return;

        var ip1 = $('#ip_lama_1').val();
        var ip2 = $('#ip_lama_2').val();
        var name1 = $('#nama_lama_1').val();
        var name2 = $('#nama_lama_2').val();

        if (ip1 && ip2) {
            $('#ip_baru_1').val(ip2).css({'border': '', 'background-color': ''});
            $('#ip_baru_2').val(ip1).css({'border': '', 'background-color': ''});

            if (name1) {
                var prefix1 = extractBaseName(name1);
                var finalName1 = (ip2 && ip2 !== '-') ? (prefix1 + '_' + ip2) : prefix1;
                $('#nama_baru_1').val(finalName1);
            }
            if (name2) {
                var prefix2 = extractBaseName(name2);
                var finalName2 = (ip1 && ip1 !== '-') ? (prefix2 + '_' + ip1) : prefix2;
                $('#nama_baru_2').val(finalName2);
            }
        }
    }

    // Logic Form Events
    $('.select2-vm').select2({ placeholder: "-- Ketik Nama VM atau IP Address --", allowClear: true });

    $('#toggle_backdate').on('change', function() {
        if ($(this).is(':checked')) {
            $('#backdate_container').slideDown();
        } else {
            $('#backdate_container').slideUp();
        }
    });

    if ($('#jenis_switch').val() === 'Tukar Silang (Dual VM)') {
        $('#id_vm_2, #ip_baru_2, #nama_baru_2').addClass('required-field').prop('required', true);
    }

    $('#jenis_switch').on('change', function() {
        if ($(this).val() === 'Tukar Silang (Dual VM)') {
            $('#section_vm_2').slideDown();
            $('#id_vm_2, #ip_baru_2, #nama_baru_2').addClass('required-field').prop('required', true);
            calculateSwapLogic();
        } else {
            $('#section_vm_2').slideUp();
            $('#id_vm_2, #ip_baru_2, #nama_baru_2').removeClass('required-field').prop('required', false);
        }
    });

    $(document).on('input propertychange', '#ip_baru_1', function() { updateStandardTarget(1); });
    $(document).on('input propertychange', '#ip_baru_2', function() { updateStandardTarget(2); });
    $(document).on('change', '#ip_lama_1, #ip_lama_2', function() {
        if ($('#jenis_switch').val() === 'Tukar Silang (Dual VM)') calculateSwapLogic();
    });

    // Initialize Render for Edit Mode
    var savedIp1 = "<?= $vm1 ? html_escape($vm1["ip_lama"]) : "" ?>";
    var savedIp2 = "<?= $vm2 ? html_escape($vm2["ip_lama"]) : "" ?>";

    if ($('#id_vm_1').val()) {
        var ipsRaw1 = $('#id_vm_1').find(':selected').attr('data-ips');
        var ipsArray1 = ipsRaw1 ? JSON.parse(ipsRaw1) : [];
        renderIpContainer(1, ipsArray1, savedIp1);
    }
    if ($('#id_vm_2').val()) {
        var ipsRaw2 = $('#id_vm_2').find(':selected').attr('data-ips');
        var ipsArray2 = ipsRaw2 ? JSON.parse(ipsRaw2) : [];
        renderIpContainer(2, ipsArray2, savedIp2);
    }

    $('#id_vm_1').on('change', function() {
        var opt = $(this).find(':selected');
        var ipsRaw = opt.attr('data-ips');
        var ipsArray = ipsRaw ? JSON.parse(ipsRaw) : [];
        renderIpContainer(1, ipsArray, null);

        $('#nama_lama_1').val(opt.data('name'));
        $(this).next('.select2-container').find('.select2-selection').css({'border': '', 'background-color': ''});

        if ($('#jenis_switch').val() === 'Tukar Silang (Dual VM)') calculateSwapLogic();
        else updateStandardTarget(1);
    });

    $('#id_vm_2').on('change', function() {
        var opt = $(this).find(':selected');
        var ipsRaw = opt.attr('data-ips');
        var ipsArray = ipsRaw ? JSON.parse(ipsRaw) : [];
        renderIpContainer(2, ipsArray, null);

        $('#nama_lama_2').val(opt.data('name'));
        $(this).next('.select2-container').find('.select2-selection').css({'border': '', 'background-color': ''});

        calculateSwapLogic();
    });

    // --- 4. Form Submission Guard & AJAX Duplicate Check ---
    var isSubmitting = false;

    $('#formSwitchIP').on('submit', function(e) {
        if (isSubmitting) return false;

        var isValid = true;
        var firstInvalidField = null;
        var $form = $(this);

        $('.error-inline').remove();
        $('.required-field').css({'border': '', 'background-color': ''});

        $form.find('.required-field').each(function() {
            if ($(this).val() === '' || $(this).val() === null) {
                isValid = false;
                if (!firstInvalidField) firstInvalidField = $(this);

                if ($(this).hasClass('select2-vm') || $(this).hasClass('select2-hidden-accessible')) {
                    $(this).next('.select2-container').find('.select2-selection').css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                } else {
                    $(this).css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                }
            }
        });

        if ($('#jenis_switch').val() === 'Tukar Silang (Dual VM)') {
            if ($('#id_vm_1').val() === $('#id_vm_2').val() && $('#id_vm_1').val() !== '') {
                isValid = false;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Bentrokan Data', text: 'Target VM 1 dan VM 2 tidak boleh sama!' });
                }
                $('#id_vm_2').next('.select2-container').find('.select2-selection').css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
            }
        }

        if (!isValid) {
            e.preventDefault();
            if ($('#custom-validation-toast').length === 0) {
                var toastBox = '<div id="custom-validation-toast" style="position: fixed; top: 80px; right: 20px; z-index: 999999; background: #fff; border-left: 4px solid #e74c3c; border-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); padding: 15px 20px; min-width: 250px; max-width: 350px; display: none; color: #333;"></div>';
                $('body').append(toastBox);
            }
            var msgHtml = "<div style='font-size: 13px; line-height: 1.4;'><i class='fa fa-times-circle' style='color: #e74c3c; font-size: 20px; margin-right: 12px; float: left; margin-top: 2px;'></i><div style='overflow: hidden;'><strong style='color: #c0392b; font-size: 14px;'>Validasi Form Gagal</strong><br><span style='color: #555; display: inline-block; margin-top: 4px;'>Harap lengkapi semua kolom yang ditandai merah sebelum menyimpan update data.</span></div></div>";

            $('#custom-validation-toast').html(msgHtml).stop(true, true).fadeIn(300);
            clearTimeout(window.valToastTimer);
            window.valToastTimer = setTimeout(function() { $('#custom-validation-toast').fadeOut(400); }, 5000);

            if (firstInvalidField) $('html, body').animate({ scrollTop: firstInvalidField.offset().top - 120 }, 400);
        } else {
            e.preventDefault();
            var btnTarget = $('#btnSubmitAdd');
            var originalText = btnTarget.html();
            // [ENTERPRISE FIX]: Lock the button safely
            btnTarget.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memvalidasi Data...');
            isSubmitting = true;

            // [ENTERPRISE FIX]: Send the CURRENT_ID variable from Server Binding
            var payloadData = {
                no_tiket: $('input[name="no_tiket"]').val().trim(),
                id_vm_1: $('#id_vm_1').val(),
                id_vm_2: $('#jenis_switch').val() === 'Tukar Silang (Dual VM)' ? $('#id_vm_2').val() : 0,
                id_change: CURRENT_ID
            };
            payloadData[CSRF_NAME_VAL] = $('#csrf_token').val();

            $.ajax({
                url: URL_AJAX_DUPLICATE,
                type: 'POST',
                data: payloadData,
                dataType: 'json',
                success: function(res) {
                    if(res.csrf_hash) { $('#csrf_token').val(res.csrf_hash); }

                    if (res.status === 'duplicate') {
                        isSubmitting = false;
                        btnTarget.html(originalText).prop('disabled', false);

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error', title: 'Update Ditolak',
                                html: '<span style="font-size: 14px;">' + res.message + '</span>',
                                confirmButtonColor: '#d33', confirmButtonText: '<i class="fa fa-times"></i> Tutup'
                            });
                        } else {
                            alert('Data Ditolak: \n' + res.message.replace(/(<([^>]+)>)/gi, ""));
                        }

                        $('input[name="no_tiket"]').css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                        $('#id_vm_1').next('.select2-container').find('.select2-selection').css({'border': '1px solid #e74c3c', 'background-color': '#fadbd8'});
                        $('html, body').animate({ scrollTop: $('input[name="no_tiket"]').offset().top - 120 }, 400);
                    } else {
                        btnTarget.html('<i class="fa fa-spinner fa-spin"></i> Memperbarui Request...');
                        $form[0].submit();
                    }
                },
                error: function() {
                    if (typeof Swal !== 'undefined') Swal.fire({icon: 'error', title: 'Gangguan Jaringan', text: 'Gagal memvalidasi data ganda. Coba lagi.'});
                    isSubmitting = false;
                    btnTarget.html(originalText).prop('disabled', false);
                }
            });
        }
    });

    $(document).on('input change', '.required-field', function() {
        if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).next('.select2-container').find('.select2-selection').css({'border': '', 'background-color': ''});
        } else {
            $(this).css({'border': '', 'background-color': ''});
        }
    });
});
</script>
