</div>
</div> <!-- /main_container end -->

    <!-- 1. LIBRARY BAWAAN TEMPLATE -->
    <script src="<?= base_url("asset/js/bootstrap.min.js") ?>"></script>

    <!-- bootstrap progress js -->
    <script src="<?= base_url("asset/js/progressbar/bootstrap-progressbar.min.js") ?>"></script>
    <script src="<?= base_url("asset/js/nicescroll/jquery.nicescroll.min.js") ?>"></script>

    <!-- icheck -->
    <script src="<?= base_url("asset/js/icheck/icheck.min.js") ?>"></script>

    <!-- ======================================================= -->
    <!-- [ENTERPRISE CORE]: GLOBAL LOCAL ASSETS                  -->
    <!-- ======================================================= -->
    <script src="<?= base_url("asset/js/sweetalert2.all.min.js") ?>"></script>
    <script src="<?= base_url("asset/js/select2/select2.min.js") ?>"></script>

    <!-- ======================================================= -->
    <!-- DATATABLES CORE ENGINE (Localized)                      -->
    <!-- ======================================================= -->
    <script src="<?= base_url("asset/js/datatables/jquery.dataTables.min.js") ?>"></script>
    <script src="<?= base_url("asset/js/datatables/dataTables.bootstrap.min.js") ?>"></script>
    <script src="<?= base_url("asset/js/datatables/dataTables.responsive.min.js") ?>"></script>

    <!-- Custom Theme Scripts -->
    <script src="<?= base_url("asset/js/custom.js") ?>"></script>

    <script>
      $(document).ready(function() {
        var $body = $('body');
        // Injeksi layar gelap (backdrop)
        var $backdrop = $('<div class="sidebar-backdrop" style="display: none;"></div>').appendTo('.main_container');

        /* ==========================================================
           ENGINE RESPONSIVE & OFF-CANVAS MOBILE MANAGER
           Dilengkapi dengan State Persistence (Memori Penyimpanan)
           ========================================================== */

        function adjustResponsiveLayout() {
          var width = $(window).width();
          var savedState = localStorage.getItem('scr_sidebar_state');

          if (width <= 992) {
            if ($body.hasClass('nav-md')) {
              $body.removeClass('nav-md').addClass('nav-sm');
            }
            $backdrop.removeClass('show').css('display', 'none');
          } else {
            $backdrop.removeClass('show').css('display', 'none');
            if (savedState === 'nav-sm') {
              $body.removeClass('nav-md').addClass('nav-sm');
            } else {
              $body.removeClass('nav-sm').addClass('nav-md');
            }
          }
        }

        adjustResponsiveLayout();
        $(window).on('resize', function() {
          adjustResponsiveLayout();
        });

        $('#menu_toggle').off('click').on('click', function(e) {
          e.preventDefault();
          var width = $(window).width();

          if ($body.hasClass('nav-md')) {
            $body.removeClass('nav-md').addClass('nav-sm');
            localStorage.setItem('scr_sidebar_state', 'nav-sm');

            if (width <= 992) {
              $backdrop.removeClass('show');
              setTimeout(function() {
                $backdrop.css('display', 'none');
              }, 300);
            }
          } else {
            $body.removeClass('nav-sm').addClass('nav-md');
            localStorage.setItem('scr_sidebar_state', 'nav-md');

            if (width <= 992) {
              $backdrop.css('display', 'block');
              setTimeout(function() {
                $backdrop.addClass('show');
              }, 10);
            }
          }
        });

        $('#sidebar-menu a').on('click', function() {
          var url = $(this).attr('href');
          var hasChild = $(this).next('.child_menu').length > 0;

          if ($(window).width() <= 992 && url && url !== '#' && url !== 'javascript:void(0);' && !hasChild) {
            $body.removeClass('nav-md').addClass('nav-sm');
            $backdrop.removeClass('show');
            setTimeout(function() {
              $backdrop.css('display', 'none');
            }, 300);
          }
        });

        $backdrop.on('click', function() {
          if ($body.hasClass('nav-md') && $(window).width() <= 992) {
            $body.removeClass('nav-md').addClass('nav-sm');
            $backdrop.removeClass('show');
            setTimeout(function() {
              $backdrop.css('display', 'none');
            }, 300);
          }
        });

        // ========================================================================
        // GLOBAL AJAX SESSION GUARD
        // Menangkap response HTTP 401 dari seluruh request AJAX jika sesi habis
        // ========================================================================
        $.ajaxSetup({
            error: function(jqXHR, textStatus, errorThrown) {
                if (jqXHR.status === 401) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sesi Berakhir',
                            text: 'Waktu login Anda telah habis demi keamanan. Harap login kembali.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            confirmButtonColor: '#2A3F54',
                            confirmButtonText: '<i class="fa fa-sign-in"></i> Ke Halaman Login'
                        }).then(function() {
                            window.location.href = '<?= site_url("auth/login") ?>';
                        });
                    } else {
                        alert("Sesi berakhir. Mengalihkan ke halaman login...");
                        window.location.href = '<?= site_url("auth/login") ?>';
                    }
                }
            }
        });

      });
    </script>

    <!-- ======================================================= -->
    <!-- DYNAMIC MODULE JS LOADER                                -->
    <!-- Menjamin script lokal dieksekusi paling akhir           -->
    <!-- ======================================================= -->
    <?php if (isset($custom_js) && is_array($custom_js)): ?>
        <?php foreach ($custom_js as $js_file): ?>
            <script src="<?= base_url(
                "asset/js/" . html_escape($js_file),
            ) ?>?v=<?= time() ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
