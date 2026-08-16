<?php
/**
 * ============================================================================
 * File Name    : 2sidebar.php
 * Modul        : Main Layout
 * Purpose      : Menampilkan sidebar navigasi utama aplikasi SCR System.
 * Architecture : Enterprise Standard CP-05 (Dynamic Menu & Access Control)
 * ============================================================================
 */

$id = $id ?? [];
$segment = $this->uri->segment(1);
?>

<div class="col-md-3 left_col">
    <div class="left_col scroll-view">

        <div class="navbar nav_title" style="border: 0; padding: 10px 0; height: auto;">
            <a href="<?= site_url(
                "dashboard",
            ) ?>" class="site_title" style="height: auto; line-height: 1; padding-left: 15px;">
                <i class="fa fa-laptop" style="border-radius: 4px; padding: 5px; font-size: 18px;"></i>
                <span style="font-weight: bold; letter-spacing: 1px; font-size: 20px;">SCR System</span>
            </a>
        </div>

        <div class="clearfix"></div>
        <br />

        <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">

            <div class="menu_section">
                <h3>Operasional IT</h3>
                <ul class="nav side-menu">
                    <li class="<?= $segment == "dashboard" ? "current-page active" : "" ?>">
                        <a href="<?= site_url(
                            "dashboard",
                        ) ?>"><i class="fa fa-home"></i> Dashboard </a>
                    </li>
                    <li class="<?= $segment == "provisioning" ? "current-page active" : "" ?>">
                        <a href="<?= site_url(
                            "provisioning",
                        ) ?>"><i class="fa fa-ticket"></i> Tiket Provisioning </a>
                    </li>
                    <li class="<?= $segment == "vm_change_resource"
                        ? "current-page active"
                        : "" ?>">
                        <a href="<?= site_url(
                            "vm_change_resource",
                        ) ?>"><i class="fa fa-tasks"></i> Change Resource </a>
                    </li>
                    <li class="<?= $segment == "vm_switch_ip" ? "current-page active" : "" ?>">
                        <a href="<?= site_url(
                            "vm_switch_ip",
                        ) ?>"><i class="fa fa-exchange"></i> Switch IP </a>
                    </li>
                    <li class="<?= $segment == "vm_restart" ? "current-page active" : "" ?>">
                        <a href="<?= site_url(
                            "vm_restart",
                        ) ?>"><i class="fa fa-refresh"></i> Log Restart VM </a>
                    </li>
                    <li class="<?= $segment == "vm_incident" ? "current-page active" : "" ?>">
                        <a href="<?= site_url(
                            "vm_incident",
                        ) ?>"><i class="fa fa-exclamation-triangle"></i> Tiket Utilisasi</a>
                    </li>
                    <li class="<?= $segment == "replication_backup" ? "current-page active" : "" ?>">
                        <a href="<?= site_url(
                            "replication_backup",
                        ) ?>">
                            <i class="fa fa-database"></i> Replication & Backup
                        </a>
                    </li>
                </ul>
            </div>

            <?php if (in_array((int) ($id["id_role"] ?? 99), [0, 1, 2])) { ?>
                <div class="menu_section">
                    <h3>Data Center</h3>
                    <ul class="nav side-menu">
                        <?php
                        $srvActive = in_array($segment, [
                            "virtual_machine",
                            "master_os",
                            "master_template_vm",
                        ]);
                        $dispSrv = $srvActive
                            ? 'style="display: block;"'
                            : 'style="display: none;"';
                        ?>
                        <li class="<?= $srvActive ? "active" : "" ?>">
                            <a href="javascript:void(0);" aria-expanded="<?= $srvActive
                                ? "true"
                                : "false" ?>" aria-haspopup="true">
                                <i class="fa fa-database"></i> Server Inventory <span class="fa fa-chevron-down"></span>
                            </a>
                            <ul class="nav child_menu" <?= $dispSrv ?>>
                                <li class="<?= $segment == "virtual_machine"
                                    ? "current-page"
                                    : "" ?>">
                                    <a href="<?= site_url(
                                        "virtual_machine",
                                    ) ?>">Data Virtual Machine</a>
                                </li>
                                <li class="<?= $segment == "master_os" ? "current-page" : "" ?>">
                                    <a href="<?= site_url(
                                        "master_os",
                                    ) ?>">Master OS (Sistem Operasi)</a>
                                </li>
                                <li class="<?= $segment == "master_template_vm"
                                    ? "current-page"
                                    : "" ?>">
                                    <a href="<?= site_url(
                                        "master_template_vm",
                                    ) ?>">Master Template</a>
                                </li>
                            </ul>
                        </li>

                        <?php
                        $appActive = in_array($segment, [
                            "application_system",
                            "criticality",
                            "component",
                        ]);
                        $dispApp = $appActive
                            ? 'style="display: block;"'
                            : 'style="display: none;"';
                        ?>
                        <li class="<?= $appActive ? "active" : "" ?>">
                            <a href="javascript:void(0);" aria-expanded="<?= $appActive
                                ? "true"
                                : "false" ?>" aria-haspopup="true">
                                <i class="fa fa-cubes"></i> App Component <span class="fa fa-chevron-down"></span>
                            </a>
                            <ul class="nav child_menu" <?= $dispApp ?>>
                                <li class="<?= $segment == "application_system"
                                    ? "current-page"
                                    : "" ?>">
                                    <a href="<?= site_url("application_system") ?>">Data Sistem</a>
                                </li>
                                <li class="<?= $segment == "criticality" ? "current-page" : "" ?>">
                                    <a href="<?= site_url("criticality") ?>">Criticality Level</a>
                                </li>
                                <li class="<?= $segment == "component" ? "current-page" : "" ?>">
                                    <a href="<?= site_url("component") ?>">Tipe Komponen</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            <?php } else { ?>
                <div class="menu_section">
                    <h3>Data Center</h3>
                    <ul class="nav side-menu">
                        <li class="<?= $segment == "virtual_machine"
                            ? "current-page active"
                            : "" ?>">
                            <a href="<?= site_url(
                                "virtual_machine",
                            ) ?>"><i class="fa fa-database"></i> Server Inventory </a>
                        </li>
                    </ul>
                </div>
            <?php } ?>

            <div class="menu_section">
                <h3>Sistem & Audit</h3>
                <ul class="nav side-menu">
                    <li class="<?= $segment == "user" && $this->uri->segment(2) == "get_log_user"
                        ? "current-page active"
                        : "" ?>">
                        <a href="<?= site_url("user/get_log_user") ?>">
                            <i class="fa fa-history"></i> Audit Log User
                        </a>
                    </li>

                    <?php if (in_array((int) ($id["id_role"] ?? 99), [0, 1, 2])) { ?>
                        <?php
                        $admin_master_arr = ["user", "role", "team"];
                        $secActive =
                            in_array($segment, $admin_master_arr) &&
                            $this->uri->segment(2) != "get_log_user";
                        $dispSec = $secActive
                            ? 'style="display: block;"'
                            : 'style="display: none;"';
                        ?>
                        <li class="<?= $secActive ? "active" : "" ?>">
                            <a href="javascript:void(0);" aria-expanded="<?= $secActive
                                ? "true"
                                : "false" ?>" aria-haspopup="true">
                                <i class="fa fa-shield"></i> Security & Akses <span class="fa fa-chevron-down"></span>
                            </a>
                            <ul class="nav child_menu" <?= $dispSec ?>>
                                <li class="<?= $segment == "user" &&
                                $this->uri->segment(2) != "get_log_user"
                                    ? "current-page"
                                    : "" ?>">
                                    <a href="<?= site_url("user") ?>">User Akun</a>
                                </li>
                                <li class="<?= $segment == "role" ? "current-page" : "" ?>">
                                    <a href="<?= site_url("role") ?>">Hak Akses (Role)</a>
                                </li>
                                <li class="<?= $segment == "team" ? "current-page" : "" ?>">
                                    <a href="<?= site_url("team") ?>">Manajemen Tim</a>
                                </li>
                            </ul>
                        </li>
                    <?php } ?>
                </ul>
            </div>

            <div style="height: 65px; width: 100%; clear: both;"></div>
        </div>

        <div class="sidebar-footer hidden-small">
            <a data-toggle="tooltip" data-placement="top" title="Settings">
                <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
            </a>
            <a data-toggle="tooltip" data-placement="top" title="FullScreen" onclick="document.documentElement.requestFullscreen();" style="cursor: pointer;">
                <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
            </a>
            <a data-toggle="tooltip" data-placement="top" title="Lock">
                <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
            </a>
            <a data-toggle="tooltip" data-placement="top" title="Logout" href="<?= site_url(
                "auth/logout",
            ) ?>">
                <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
            </a>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        var $activeChild = $('#sidebar-menu .child_menu > li.current-page');
        if ($activeChild.length) {
            var $parentUl = $activeChild.closest('ul.child_menu');
            var $parentLi = $parentUl.closest('li');

            $parentLi.addClass('active');
            $parentUl.css({ display: 'block', height: 'auto' });
            $parentLi.children('a[aria-haspopup]').attr('aria-expanded', 'true');
        }

        $('#sidebar-menu .side-menu > li > a').on('click', function(e) {
            var href = $(this).attr('href');
            var hasChild = $(this).next('.child_menu').length > 0;
            if (!hasChild && href && href !== '#' && href !== 'javascript:void(0);' && href !== 'javascript:;') {
                e.preventDefault();
                e.stopImmediatePropagation();
                window.location.href = href;
            }
        });

        $('#sidebar-menu .child_menu > li > a').on('click', function(e) {
            var href = $(this).attr('href');
            if (href && href !== '#' && href !== 'javascript:void(0);') {
                e.preventDefault();
                e.stopImmediatePropagation();

                var $parentUl = $(this).closest('ul.child_menu');
                var $parentLi = $parentUl.closest('li');

                $parentLi.addClass('active');
                $parentUl.css('display', 'block');
                $parentLi.children('a[aria-haspopup]').attr('aria-expanded', 'true');

                window.location.href = href;
            }
        });

        $('#sidebar-menu .side-menu > li > a[aria-haspopup]').on('click', function() {
            var $li = $(this).parent();
            var isExpanded = $li.hasClass('active');
            $(this).attr('aria-expanded', isExpanded ? 'true' : 'false');
        });
    });
</script>
