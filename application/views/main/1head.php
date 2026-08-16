<!DOCTYPE html>
<html lang="en">

<head>
    <?php date_default_timezone_set("Asia/Jakarta"); ?>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= html_escape($title ?? "SCR Teams") ?></title>

    <link href="<?= base_url("asset/css/bootstrap.min.css") ?>" rel="stylesheet">
    <link href="<?= base_url("asset/fonts/css/font-awesome.min.css") ?>" rel="stylesheet">
    <link href="<?= base_url("asset/css/animate.min.css") ?>" rel="stylesheet">

    <link href="<?= base_url("asset/css/custom.css") ?>" rel="stylesheet">
    <link href="<?= base_url("asset/css/icheck/flat/green.css") ?>" rel="stylesheet">
    <link href="<?= base_url(
        "asset/css/progressbar/bootstrap-progressbar-3.3.0.css",
    ) ?>" rel="stylesheet">

    <!-- ======================================================= -->
    <!-- [ENTERPRISE CORE]: GLOBAL LOCAL CSS ASSETS              -->
    <!-- ======================================================= -->
    <link href="<?= base_url("asset/css/select2/select2.min.css") ?>" rel="stylesheet">

    <!-- ======================================================= -->
    <!-- DATATABLES CSS (Standar Enterprise - Localized)         -->
    <!-- ======================================================= -->
    <link href="<?= base_url(
        "asset/css/datatables/dataTables.bootstrap.min.css",
    ) ?>" rel="stylesheet">
    <link href="<?= base_url(
        "asset/css/datatables/responsive.bootstrap.min.css",
    ) ?>" rel="stylesheet">

    <!-- JQuery Core -->
    <script src="<?= base_url("asset/js/jquery.min.js") ?>"></script>

    <!-- ======================================================= -->
    <!-- CHART.JS V3 & PLUGINS (Standar Enterprise - Localized)  -->
    <!-- ======================================================= -->
    <script src="<?= base_url("asset/js/chartjs_v3/chart.min.js") ?>"></script>
    <script src="<?= base_url("asset/js/chartjs_v3/chartjs-plugin-datalabels.min.js") ?>"></script>

    <script type="text/javascript">
        function zoom() {
            document.body.style.zoom = "80%";
        }
    </script>

    <style>
        /* --- 6.1 Top Navigation & Body Reset --- */
        body { padding-top: 45px; overflow-x: hidden; overflow-y: auto; background: #F7F7F7; }

        /* Nav-MD (Sidebar Terbuka) */
        body.nav-md .top_nav { width: calc(100% - 230px); margin-left: 230px; }
        body.nav-md .right_col, body.nav-md footer { margin-left: 230px; width: calc(100% - 230px); }

        /* Nav-SM (Sidebar Di-minimize) */
        body.nav-sm .top_nav { width: calc(100% - 70px); margin-left: 70px; }
        body.nav-sm .right_col, body.nav-sm footer { margin-left: 70px; width: calc(100% - 70px); }
        body.nav-sm .left_col { width: 70px; }

        .top_nav { position: fixed; top: 0; z-index: 999; transition: width 0.3s ease, margin-left 0.3s ease; overflow: visible !important; }
        .top_nav .nav_menu { width: 100%; min-height: 45px; box-sizing: border-box; background: #EDEDED; margin: 0; display: block; border-bottom: 1px solid #D9DEE4; }
        .top_nav .nav.toggle { float: left; width: auto; margin: 0; padding: 9px 0 0 15px; }
        .top_nav .navbar-nav.navbar-right { float: right !important; width: auto; margin: 0; padding-right: 20px; }
        .top_nav .nav_menu .info-number { padding-top: 12px !important; padding-bottom: 12px !important; }
        .top_nav .nav_menu .user-profile { padding-top: 11px !important; padding-bottom: 11px !important; }

        /* --- 6.2 Sidebar Fixed Layout --- */
        .left_col { position: fixed; top: 0; left: 0; width: 230px; height: 100vh; overflow-y: auto; overflow-x: hidden; z-index: 1000; background-color: #2A3F54; transition: all 0.3s ease; }

        /* --- 6.3 Main Container & Right Column --- */
        .main_container { position: relative; min-height: calc(100vh - 45px); padding-bottom: 52px !important; box-sizing: border-box; }
        .right_col { min-height: unset !important; padding: 25px 20px 20px !important; transition: all 0.3s ease; background: #F7F7F7; }

        /* --- 6.4 Mathematical Absolute Footer --- */
        footer { background: #fff; padding: 15px 20px; display: block; border-top: 1px solid #e5e5e5; position: absolute; bottom: 0; height: 52px; box-sizing: border-box; transition: all 0.3s ease; }

        /* Backdrop */
        .sidebar-backdrop { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(0, 0, 0, 0.6); z-index: 1040 !important; display: none; opacity: 0; transition: opacity 0.3s ease-in-out; }
        .sidebar-backdrop.show { display: block; opacity: 1; }

        /* =======================================================
           Responsive Breakpoints (Mobile/Tablet) - Off Canvas Mode
           ======================================================= */
        @media (max-width: 992px) {
            body { overflow-x: hidden !important; }
            .main_container { overflow-x: hidden !important; }

            /* [PATCH RESPONSIVE]: Memaksa Konten Utama Full-Width 100% di HP */
            body.nav-sm .top_nav, body.nav-md .top_nav, body.nav-sm .right_col, body.nav-md .right_col, body.nav-sm footer, body.nav-md footer {
                width: 100% !important; margin-left: 0 !important;
            }

            /* Sidebar Off-Canvas Mutlak */
            body .col-md-3.left_col {
                position: fixed !important; top: 0; left: 0; width: 230px !important; height: 100vh !important; z-index: 1050 !important; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            body.nav-sm .col-md-3.left_col { transform: translateX(-100%) !important; }
            body.nav-md .col-md-3.left_col { transform: translateX(0) !important; }
        }
    </style>
</head>

<body class="nav-md">
    <script>
        (function() {
            var width = window.innerWidth;
            var savedState = localStorage.getItem('scr_sidebar_state');
            var body = document.body;

            if (width <= 992) {
                body.classList.remove('nav-md');
                body.classList.add('nav-sm');
            } else {
                if (savedState === 'nav-sm') {
                    body.classList.remove('nav-md');
                    body.classList.add('nav-sm');
                } else {
                    body.classList.remove('nav-sm');
                    body.classList.add('nav-md');
                }
            }
        })();
    </script>

    <div class="container body">
        <div class="main_container" style="position: relative; min-height: 100vh;">
