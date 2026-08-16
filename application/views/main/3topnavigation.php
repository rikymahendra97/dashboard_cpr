<!-- top navigation -->
<?php
/**
 * @var array $id
 * @var array|object $user_session
 */
$id = $id ?? [];
$user_session = $user_session ?? [];
?>
<div class="top_nav">
    <div class="nav_menu">
        <nav class="" role="navigation">
            <div class="nav toggle">
                <a id="menu_toggle"><i class="fa fa-bars"></i></a>
            </div>

            <ul class="nav navbar-nav navbar-right mr-3">

                <!-- 1. BLOK USER PROFILE -->
                <li class="">
                    <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <img src="<?php
                        $file = $user_session["img_file"] ?? "";
                        $img = "asset/images/avatar_default.jpg";

                        if (!empty($file) && $file !== "avatar_default.jpg") {
                            $img = "images/" . html_escape($file);
                        }
                        echo base_url($img);
                        ?>" alt="<?= html_escape($id["username"] ?? "") ?>"><?= html_escape(
    $id["username"] ?? "",
) ?>
                        <span class="fa fa-angle-down"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-usermenu animated fadeInDown pull-right">
                        <li><a href="<?= site_url(
                            "user/edit_user_detail/" . ($id["id_user"] ?? ""),
                        ) ?>">Edit Profile</a></li>
                        <li><a href="<?= site_url(
                            "auth/logout",
                        ) ?>"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
                    </ul>
                </li>
                <!-- /BLOK USER PROFILE -->

                <!-- 2. BLOK NOTIFIKASI LONCENG -->
                <li role="presentation" class="dropdown">
                    <a href="javascript:;"
                        class="dropdown-toggle info-number"
                        data-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Notifikasi"
                        style="padding-top: 17px; padding-bottom: 17px;">
                        <i class="fa fa-bell-o" style="font-size: 18px;" aria-hidden="true"></i>
                        <span class="badge bg-red"
                            id="notif-badge"
                            style="display:none; position:absolute; top:8px; right:3px; padding: 3px 6px; border-radius: 50%;"
                            aria-label="Jumlah notifikasi">0</span>
                    </a>
                    <ul id="notif-menu"
                        class="dropdown-menu list-unstyled msg_list"
                        role="menu"
                        style="width: 300px; padding: 10px;">
                        <li>
                            <div class="text-center" style="padding: 10px;">
                                <a><strong>Memuat data...</strong> <i class="fa fa-spinner fa-spin"></i></a>
                            </div>
                        </li>
                    </ul>
                </li>
                <!-- /BLOK NOTIFIKASI LONCENG -->

            </ul>
        </nav>
    </div>
</div>
<!-- /top navigation -->

<style>
    .nav .dropdown-menu { z-index: 1060 !important; }
    .modal-backdrop { z-index: 2000 !important; }
    .modal { z-index: 2010 !important; }
    .modal-dialog { z-index: 2011 !important; }
</style>

<script>
    $(document).ready(function() {
        $('.modal').appendTo('body');
    });
</script>

<!-- [ENTERPRISE FIX]: JS Context Injection (Linter-Safe P1132 & VSCode Error) -->
<script>
    const APP_URLS = {
        notifAjax: '<?= site_url("dashboard/ajax_get_notif") ?>',
        provisioning: '<?= site_url("provisioning") ?>',
        changeVm: '<?= site_url("vm_change_resource") ?>',
        switchIp: '<?= site_url("vm_switch_ip") ?>',
        restartVm: '<?= site_url("vm_restart") ?>',
        incidentVm: '<?= site_url("vm_incident") ?>'
    };
</script>

<script>
    $(document).ready(function() {
        var POLL_INTERVAL = 60000; // 60 detik
        var pollTimer = null;

        function fetchNotifications() {
            if (document.visibilityState === 'hidden') {
                return;
            }

            $.ajax({
                url: APP_URLS.notifAjax,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.total > 0) {
                        $('#notif-badge').text(data.total).fadeIn('fast');
                        var html = '';

                        // Menggunakan Object APP_URLS yang sudah Linter-Safe
                        if (data.provisioning > 0) {
                            html += buildNotifItem(APP_URLS.provisioning, 'fa-ticket', 'text-info', 'Tiket Provisioning', data.provisioning);
                        }
                        if (data.change_vm > 0) {
                            html += buildNotifItem(APP_URLS.changeVm, 'fa-tasks', 'text-warning', 'Change Resource', data.change_vm);
                        }
                        if (data.switch_ip > 0) {
                            html += buildNotifItem(APP_URLS.switchIp, 'fa-exchange', 'text-success', 'Switch IP', data.switch_ip);
                        }
                        if (data.restart_vm > 0) {
                            html += buildNotifItem(APP_URLS.restartVm, 'fa-refresh', 'text-danger', 'Log Restart VM', data.restart_vm);
                        }
                        if (data.vm_incident > 0) {
                            html += buildNotifItem(APP_URLS.incidentVm, 'fa-exclamation-triangle', 'text-danger', 'Tiket Insiden VM (SLA)', data.vm_incident);
                        }

                        $('#notif-menu').html(html);

                    } else {
                        $('#notif-badge').fadeOut('fast');
                        $('#notif-menu').html(
                            '<li><div class="text-center" style="padding: 15px; color: #73879C;">' +
                            '<a><i class="fa fa-check-circle fa-2x text-success" style="margin-bottom: 10px;"></i>' +
                            '<br><strong>Semua antrean bersih!</strong></a></div></li>'
                        );
                    }
                },
                error: function() {
                    console.warn('[SCR] Koneksi background ke server notifikasi gagal.');
                }
            });
        }

        function buildNotifItem(url, icon, colorClass, label, count) {
            return '<li style="border-bottom: 1px solid #eee;">' +
                '<a href="' + url + '" style="padding: 10px; display: block;">' +
                '<span style="font-weight: bold;"><i class="fa ' + icon + ' ' + colorClass + '"></i> ' + label + '</span>' +
                ' <span class="badge bg-red pull-right">' + count + '</span>' +
                '</a></li>';
        }

        fetchNotifications();
        pollTimer = setInterval(fetchNotifications, POLL_INTERVAL);

        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                fetchNotifications();
                if (!pollTimer) {
                    pollTimer = setInterval(fetchNotifications, POLL_INTERVAL);
                }
            } else {
                clearInterval(pollTimer);
                pollTimer = null;
            }
        });
    });
</script>
