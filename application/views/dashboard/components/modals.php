<style>
    #dashToastContainer {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 999999;
        /* Z-Index absolut tertinggi */
        pointer-events: none;
    }

    .dash-toast {
        min-width: 300px;
        max-width: 400px;
        background: #fff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        border-radius: 6px;
        padding: 15px 20px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        border-left: 5px solid #3498db;
        pointer-events: auto;
        animation: slideInRight 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
    }

    .dash-toast.toast-success {
        border-left-color: #2ecc71;
    }

    .dash-toast.toast-error {
        border-left-color: #e74c3c;
    }

    .dash-toast.toast-warning {
        border-left-color: #f1c40f;
    }

    .dash-toast i {
        font-size: 24px;
        margin-right: 15px;
    }

    .dash-toast.toast-success i {
        color: #2ecc71;
    }

    .dash-toast.toast-error i {
        color: #e74c3c;
    }

    .dash-toast.toast-warning i {
        color: #f1c40f;
    }

    .dash-toast-text {
        flex-grow: 1;
        font-size: 13px;
        color: #444;
        line-height: 1.4;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(120%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes fadeOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }

        to {
            transform: translateX(120%);
            opacity: 0;
        }
    }
</style>
<div id="dashToastContainer"></div>
<div class="modal fade" id="modalWorkflow" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dynamic">
        <div class="modal-content">
            <div class="modal-header" style="background: #2c3e50; color: #fff;">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true" style="color:#fff;">×</span></button>
                <h4 class="modal-title" id="wf_title"><i class="fa fa-wrench"></i> Panel Eksekusi & Verifikasi</h4>
            </div>

            <form id="formWorkflow">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                <div class="modal-body">
                    <input type="hidden" id="wf_id_trx" name="id_trx">
                    <input type="hidden" id="wf_no_tiket" name="no_tiket">
                    <input type="hidden" id="wf_modul" name="modul">
                    <input type="hidden" id="wf_action_type" name="action_type">

                    <div id="wf_loading" class="text-center" style="padding: 40px;">
                        <i class="fa fa-spinner fa-spin fa-3x text-primary"></i>
                        <p style="margin-top: 15px; font-weight: bold; color: #73879C;">Mengambil informasi tiket...</p>
                    </div>

                    <div id="wf_content" style="display: none;">
                        <div id="wf_html_detail" style="margin-bottom: 20px;"></div>

                        <div id="wf_restart_downtime_block" style="display: none; margin-bottom: 20px; padding: 15px; background: #fff5f5; border: 1px solid #ebccd1; border-radius: 4px;">
                            <h5 class="font-bold text-danger" style="margin-top:0;"><i class="fa fa-clock-o"></i> Input Waktu Downtime (Wajib)</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="wf_start_dt" style="font-size:11px;">Start Downtime:</label>
                                    <input type="datetime-local" name="start_downtime" id="wf_start_dt" class="form-control input-sm">
                                </div>
                                <div class="col-md-6">
                                    <label for="wf_finish_dt" style="font-size:11px;">Finish Downtime:</label>
                                    <input type="datetime-local" name="finish_downtime" id="wf_finish_dt" class="form-control input-sm">
                                </div>
                            </div>
                        </div>

                        <div class="well" style="background: #fffdf2; border-left: 5px solid #f1c40f; padding: 20px;">
                            <label id="wf_label_catatan" for="wf_catatan" style="font-size: 14px; font-weight: bold; display: block; margin-bottom: 10px;">
                                Catatan Tindak Lanjut:
                            </label>
                            <textarea class="form-control" name="catatan" id="wf_catatan" rows="4" style="resize: none; border: 1px solid #ccc;"></textarea>
                            <span class="help-block" style="font-size: 11px; margin-top: 8px;">
                                <i class="fa fa-lock"></i> Catatan ini bersifat permanen pada <strong>Audit Trail</strong> dan tidak dapat diubah setelah dikonfirmasi.
                            </span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" id="wf_footer" style="display:none;">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="font-weight: bold; min-width: 100px;">Batal</button>
                    <button type="submit" class="btn btn-success px-4" id="btnSubmitWorkflow" style="min-width: 150px; font-weight: bold;">
                        <i class="fa fa-check"></i> <span>Konfirmasi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHambatan" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: #f39c12; color: #fff;">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true" style="color:#fff;">×</span></button>
                <h4 class="modal-title"><i class="fa fa-exclamation-triangle"></i> Catat Hambatan / Kendala</h4>
            </div>

            <form id="formHambatan">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                <div class="modal-body">
                    <input type="hidden" id="h_id_trx" name="id_trx">
                    <input type="hidden" id="h_no_tiket" name="no_tiket">
                    <input type="hidden" id="h_modul" name="modul">

                    <div class="form-group">
                        <label for="input_hambatan" style="font-weight: bold; color: #333;">Penyebab Tiket Terhambat:</label>
                        <textarea class="form-control" name="hambatan" id="input_hambatan" rows="5" required placeholder="Contoh: Terkendala koordinasi dengan user, menunggu approval TL Requestor, atau perbaikan pada resource ..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning" style="font-weight: bold;"><i class="fa fa-save"></i> Simpan Kendala</button>
                </div>
            </form>
        </div>
    </div>
</div>
