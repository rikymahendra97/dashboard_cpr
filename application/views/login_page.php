<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * File: login_page.php
 * Tujuan: Halaman Autentikasi Utama SCR Teams
 * Perbaikan: Implementasi CSRF Guard, Modern UI Card, & Silent Logout Hack
 */
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | SCR Teams</title>

    <link href="<?= base_url('asset/css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?= base_url('asset/fonts/css/font-awesome.min.css'); ?>" rel="stylesheet">
    <link href="<?= base_url('asset/css/animate.min.css'); ?>" rel="stylesheet">

    <style>
        body {
            background: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .login-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-card h1 {
            font-size: 24px;
            font-weight: 700;
            color: #2A3F54;
            margin-bottom: 30px;
            text-align: center;
            letter-spacing: -0.5px;
        }

        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #999;
        }

        .form-control {
            padding-left: 45px;
            height: 45px;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #2A3F54;
            box-shadow: 0 0 8px rgba(42, 63, 84, 0.1);
        }

        .btn-login {
            background: #2A3F54;
            color: white;
            width: 100%;
            height: 45px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            border: none;
            transition: background 0.3s;
        }

        .btn-login:hover {
            background: #1a2a3a;
            color: #fff;
        }

        .footer-brand {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }

        .alert {
            font-size: 13px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body class="animate fadeIn">

    <div class="login-card">
        <h1><i class="fa fa-laptop"></i> SCR TEAMS</h1>

        <?php if ($this->session->flashdata('alerts')): ?>
            <div id="alert-box">
                <?php foreach ($this->session->flashdata('alerts') as $alert):
                    $type = ($alert[0] == 'error') ? 'danger' : (($alert[0] == 'success') ? 'success' : 'warning');
                    $icon = ($alert[0] == 'error') ? 'times-circle' : 'check-circle';
                ?>
                    <div class="alert alert-<?= $type; ?> alert-dismissible fade in" role="alert">
                        <i class="fa fa-<?= $icon; ?>"></i> <?= $alert[1]; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('auth/login'); ?>" method="post">

            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

            <?php
            // Ambil data username lama (jika ada error)
            $old_user = html_escape($this->session->flashdata('old_username'));
            ?>

            <div class="form-group">
                <i class="fa fa-user"></i>
                <input type="text" class="form-control" name="username" placeholder="Username" value="<?= $old_user; ?>" required <?= empty($old_user) ? 'autofocus' : ''; ?>>
            </div>

            <div class="form-group">
                <i class="fa fa-lock"></i>
                <input type="password" class="form-control" name="pwd" placeholder="Password" required <?= !empty($old_user) ? 'autofocus' : ''; ?>>
            </div>

            <button type="submit" name="login" class="btn btn-login">
                <i class="fa fa-sign-in"></i> Login
            </button>

            <div class="footer-brand" style="margin-top: 35px; border-top: 1px solid #eee; padding-top: 20px;">
                <p style="font-size: 13px; font-weight: 700; color: #2A3F54; margin-bottom: 2px;">
                    Surrounding Compute Recovery Operation Team
                </p>
                <br>
                <p style="font-size: 11px; color: #aaa; margin: 0;">&copy; 2025 <?= (date('Y') > 2025) ? '- ' . date('Y') : ''; ?></p>
            </div>
        </form>
    </div>

    <img src="http://log:out@localhost/elog/" style="display:none;" onerror="console.log('Auth Cache Cleared');">

    <script src="<?= base_url('asset/js/jquery.min.js'); ?>"></script>
    <script src="<?= base_url('asset/js/bootstrap.min.js'); ?>"></script>

    <script>
        $(document).ready(function() {
            // Animasi Alert menghilang otomatis dalam 5 detik
            setTimeout(function() {
                $(".alert").fadeOut(500);
            }, 5000);
        });
    </script>

</body>

</html>
