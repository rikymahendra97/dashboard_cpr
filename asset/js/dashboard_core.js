$(document).ready(function () {
    // ==========================================
    // SWEETALERT Z-INDEX GUARD
    // Memaksa SweetAlert agar selalu muncul di atas semua elemen (termasuk Bootstrap Modal)
    // ==========================================
    if ($("style#swal-zindex-fix").length === 0) {
        $("head").append(
            '<style id="swal-zindex-fix">.swal2-container { z-index: 999999 !important; }</style>'
        );
    }
    // ==========================================
    // HELPER: ENTERPRISE TOAST & CSRF UPDATER
    // ==========================================
    window.showToast = function (type, message) {
        if ($("#dashToastContainer").length === 0) {
            $("body").append('<div id="dashToastContainer"></div>');
        }
        var icon =
            type === "success"
                ? "fa-check-circle"
                : type === "error"
                  ? "fa-exclamation-triangle"
                  : "fa-info-circle";
        var bg = type === "success" ? "#2ecc71" : type === "error" ? "#e74c3c" : "#3498db";
        var tid = "toast_" + Math.random().toString(36).substr(2, 9);

        var html = $(
            '<div id="' +
                tid +
                '" class="dash-toast toast-' +
                type +
                '" style="border-left: 5px solid ' +
                bg +
                ';">' +
                '<div style="display:flex; align-items:flex-start;">' +
                '<i class="fa ' +
                icon +
                '" style="color:' +
                bg +
                '; font-size: 20px; margin-right: 12px;"></i>' +
                '<div class="dash-toast-text"><div style="font-weight:bold; margin-bottom:3px; color:#333;">' +
                (type === "success" ? "Berhasil" : "Pemberitahuan Sistem") +
                '</div><div style="font-size:12px; color:#666;">' +
                message +
                "</div></div>" +
                "<button onclick=\"$('#" +
                tid +
                '\').remove()" style="background:none; border:none; color:#ccc; margin-left:10px; cursor:pointer;">&times;</button></div></div>'
        );

        $("#dashToastContainer").append(html);
        setTimeout(function () {
            html.fadeOut(500, function () {
                $(this).remove();
            });
        }, 6500);
    };

    function updateGlobalCSRF(newHash) {
        if (newHash) {
            $("input[type='hidden']").each(function () {
                if ($(this).attr("name") && $(this).attr("name").includes("csrf")) {
                    $(this).val(newHash);
                }
            });
        }
    }

    window.copyIP = function (ip) {
        if (!ip || ip === "-") return;
        navigator.clipboard
            .writeText(ip)
            .then(function () {
                showToast("success", "IP <b>" + ip + "</b> berhasil disalin!");
            })
            .catch(function () {
                showToast("error", "Gagal menyalin IP.");
            });
    };

    // ==========================================
    // 1. ENGINE STATISTIK
    // ==========================================
    function animateValue(id, end) {
        var obj = document.getElementById(id);
        if (!obj) return;
        var current = parseInt(obj.innerHTML) || 0;
        end = parseInt(end) || 0;
        if (current === end) {
            obj.innerHTML = end;
            return;
        }

        $({ Counter: current }).animate(
            { Counter: end },
            {
                duration: 800,
                step: function () {
                    obj.innerHTML = Math.ceil(this.Counter);
                },
                complete: function () {
                    obj.innerHTML = end;
                },
            }
        );
    }

    function loadDashboardMetrics() {
        $.ajax({
            url: DashboardConfig.urlNotif,
            type: "GET",
            dataType: "json",
            success: function (data) {
                if (data) {
                    animateValue("dash-count-prov", data.provisioning);
                    animateValue("dash-count-change", data.change_vm);
                    animateValue("dash-count-switch", data.switch_ip);
                    animateValue("dash-count-restart", data.restart_vm);
                }
            },
        });
    }

    // ==========================================
    // 2. DATA TABLES ENGINE
    // ==========================================
    var dtConfig = {
        language: {
            emptyTable: "Hore! Tidak ada antrean tugas (Clean Desk).",
            processing: '<i class="fa fa-spinner fa-spin"></i> Memuat...',
        },
        ordering: false,
        searching: false,
        paging: true,
        pageLength: 5,
        lengthChange: false,
        processing: true,
        deferRender: true,
    };

    function renderColumns(tipeModul) {
        return [
            {
                data: null,
                render: function (d, t, row) {
                    var l = row.link_tiket
                        ? '<a href="' +
                          row.link_tiket +
                          '" target="_blank" style="color:#3498DB;"><u><i class="fa fa-external-link"></i> ' +
                          row.no_tiket +
                          "</u></a>"
                        : row.no_tiket;
                    return "<strong>" + l + "</strong>";
                },
            },
            {
                data: null,
                render: function (d, t, row) {
                    var ip =
                        row.ip_target && row.ip_target !== "-"
                            ? '<div style="margin-top:3px;"><small class="text-muted"><i class="fa fa-map-marker"></i> ' +
                              row.ip_target +
                              '</small> <a href="javascript:void(0)" onclick="copyIP(\'' +
                              row.ip_target +
                              '\')" title="Salin IP"><i class="fa fa-copy text-info"></i></a></div>'
                            : "";
                    return "<strong>" + row.nama_target + "</strong>" + ip;
                },
            },
            {
                data: null,
                render: function (d, t, row) {
                    var html = row.detail_request;
                    if (row.catatan_eksekusi && row.catatan_eksekusi.trim() !== "") {
                        var st = row.status_eksekusi ? row.status_eksekusi.toLowerCase() : "";
                        var isP =
                            st.includes("pending") ||
                            st.includes("menunggu") ||
                            st.includes("progress");
                        html +=
                            '<div style="margin-top:8px; padding:6px 10px; border-radius:3px; font-size:11px; line-height:1.3; ' +
                            (isP
                                ? "background:#fff5f5; border-left:3px solid #e74c3c; color:#c0392b;"
                                : "color:#777;") +
                            '"><i class="fa ' +
                            (isP ? "fa-info-circle" : "fa-check-square-o") +
                            '"></i> <strong>' +
                            (isP ? "Info Kendala:" : "Catatan:") +
                            "</strong><br>" +
                            row.catatan_eksekusi
                                .replace(/</g, "&lt;")
                                .replace(/>/g, "&gt;")
                                .replace(/\n/g, "<br>") +
                            "</div>";
                    }
                    return html;
                },
            },
            {
                data: "status_eksekusi",
                className: "text-center",
                render: function (d) {
                    var s = d.toUpperCase();
                    var cls =
                        s.includes("MENUNGGU") || s.includes("PENDING")
                            ? "label-danger"
                            : s.includes("PROGRESS")
                              ? "label-warning"
                              : "label-primary";
                    return (
                        '<span class="label ' + cls + '" style="font-size:11px;">' + s + "</span>"
                    );
                },
            },
            {
                data: null,
                className: "text-center",
                render: function (d, t, row) {
                    var sUrl = DashboardConfig.siteUrl.replace(/\/$/, "");
                    if (tipeModul === "provisioning") {
                        return (
                            '<div style="white-space:nowrap;"><a href="' +
                            sUrl +
                            "/provisioning/edit/" +
                            row.id +
                            '" target="_blank" class="btn btn-info btn-xs"><i class="fa fa-search"></i> Detail</a></div>'
                        );
                    }

                    var bp =
                        '<button onclick="prosesTiket(' +
                        row.id +
                        ", '" +
                        row.no_tiket +
                        "', '" +
                        tipeModul +
                        '\')" class="btn btn-primary btn-xs"><i class="fa fa-wrench"></i> Proses</button>';
                    var bk =
                        '<button class="btn btn-warning btn-xs btn-kendala-dash" data-id="' +
                        row.id +
                        '" data-notiket="' +
                        row.no_tiket +
                        '" data-modul="' +
                        tipeModul +
                        '"><i class="fa fa-exclamation-triangle"></i></button>';
                    var p =
                        tipeModul === "change_vm"
                            ? "vm_change_resource"
                            : tipeModul === "switch_ip"
                              ? "vm_switch_ip"
                              : "vm_restart";
                    var bd =
                        '<a href="' +
                        sUrl +
                        "/" +
                        p +
                        "/detail/" +
                        row.id +
                        '" target="_blank" class="btn btn-info btn-xs"><i class="fa fa-eye"></i> Detail</a>';

                    return (
                        '<div style="white-space:nowrap;">' + bp + " " + bd + " " + bk + "</div>"
                    );
                },
            },
        ];
    }

    function initDataTableLazy(tableId, targetUrl, tipeModul) {
        if ($.fn.DataTable.isDataTable("#" + tableId)) {
            $("#" + tableId)
                .DataTable()
                .ajax.reload(null, false);
        } else {
            $("#" + tableId).DataTable(
                $.extend({}, dtConfig, {
                    ajax: { url: targetUrl, dataSrc: "data" },
                    columns: renderColumns(tipeModul),
                })
            );
        }
    }

    initDataTableLazy("table-dash-prov", DashboardConfig.urlProv, "provisioning");

    $('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
        var t = $(e.target).attr("href");
        if (t === "#tab_prov")
            initDataTableLazy("table-dash-prov", DashboardConfig.urlProv, "provisioning");
        else if (t === "#tab_change")
            initDataTableLazy("table-dash-change", DashboardConfig.urlChange, "change_vm");
        else if (t === "#tab_switch")
            initDataTableLazy("table-dash-switch", DashboardConfig.urlSwitch, "switch_ip");
        else if (t === "#tab_restart")
            initDataTableLazy("table-dash-restart", DashboardConfig.urlRestart, "restart_vm");

        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    $(document).on("click", ".btn-kendala-dash", function () {
        var tbl = $(this).closest("table").DataTable();
        var rData = tbl.row($(this).closest("tr")).data();
        $("#h_id_trx").val($(this).data("id"));
        $("#h_no_tiket").val($(this).data("notiket"));
        $("#h_modul").val($(this).data("modul"));
        $("#input_hambatan").val(rData.catatan_eksekusi || "");
        $("#modalHambatan").modal("show");
    });

    // ==========================================
    // 3. SUBMIT WORKFLOW & HAMBATAN
    // ==========================================
    $("#formWorkflow")
        .off("submit")
        .on("submit", function (e) {
            e.preventDefault();
            var btn = $("#btnSubmitWorkflow");

            if (btn.prop("disabled")) return;

            var isEks = $("#wf_action_type").val() === "execute";
            var m = $("#wf_modul").val();
            var $c = $("#wf_catatan");

            if (isEks && $c.val().trim() === "") {
                $c.css({ border: "1px solid #e74c3c", background: "#fadbd8" }).focus();
                Swal.fire({
                    icon: "warning",
                    title: "Perhatian",
                    text: "Keterangan teknis eksekusi WAJIB diisi!",
                });
                return;
            }

            if (isEks && m === "restart_vm") {
                if ($("#wf_start_dt").val() === "" || $("#wf_finish_dt").val() === "") {
                    $("#wf_start_dt, #wf_finish_dt").css({
                        border: "1px solid #e74c3c",
                        background: "#fadbd8",
                    });
                    Swal.fire({
                        icon: "warning",
                        title: "Perhatian",
                        text: "Waktu Start & Finish Downtime WAJIB diisi!",
                    });
                    return;
                }
            }

            if (!btn.data("oriText")) {
                btn.data("oriText", btn.html());
            }

            btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Memproses...');

            $.ajax({
                url: DashboardConfig.urlSubmitWf,
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function (res) {
                    if (res.csrf_hash) updateGlobalCSRF(res.csrf_hash);
                    if (res.status) {
                        $("#modalWorkflow").modal("hide");
                        //  Menggunakan SweetAlert untuk Sukses
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil",
                            text: res.message,
                            timer: 2500,
                            showConfirmButton: false,
                        });
                        $(".dataTable:visible").DataTable().ajax.reload(null, false);
                        loadDashboardMetrics();
                        loadTimelineActivity();
                    } else {
                        // ==========================================
                        // UX AUTO-CLOSE MODAL
                        // Jika ditolak karena aturan Maker-Checker, tutup modalnya paksa!
                        // ==========================================
                        if (res.message.toLowerCase().includes("maker-checker")) {
                            $("#modalWorkflow").modal("hide");
                            Swal.fire({
                                icon: "error",
                                title: "Pelanggaran Otorisasi",
                                text: res.message,
                            });
                        } else {
                            // Jika sekadar error validasi input dari backend, biarkan modal tetap terbuka
                            Swal.fire({ icon: "error", title: "Gagal", text: res.message });
                        }
                    }
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: "error",
                        title: "Koneksi Terputus",
                        text: "HTTP Error " + xhr.status,
                    });
                },
                complete: function () {
                    btn.prop("disabled", false).html(btn.data("oriText"));
                },
            });
        });

    $("#formHambatan")
        .off("submit")
        .on("submit", function (e) {
            e.preventDefault();
            var btn = $(this).find('button[type="submit"]');

            if (btn.prop("disabled")) return;

            if (!btn.data("oriText")) {
                btn.data("oriText", btn.html());
            }

            btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                url: DashboardConfig.urlSubmitHambatan,
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function (res) {
                    if (res.csrf_hash) updateGlobalCSRF(res.csrf_hash);
                    if (res.status) {
                        $("#modalHambatan").modal("hide");
                        Swal.fire({
                            icon: "success",
                            title: "Tersimpan",
                            text: res.message,
                            timer: 2500,
                            showConfirmButton: false,
                        });
                        $(".dataTable:visible").DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire({ icon: "error", title: "Gagal", text: res.message });
                    }
                },
                error: function () {
                    Swal.fire({
                        icon: "error",
                        title: "Koneksi Terputus",
                        text: "Gagal terhubung ke server.",
                    });
                },
                complete: function () {
                    btn.prop("disabled", false).html(btn.data("oriText"));
                },
            });
        });

    // ==========================================
    // 4. TIMELINE ACTIVITY
    // ==========================================
    window.loadTimelineActivity = function () {
        $.ajax({
            url: DashboardConfig.urlRecent,
            type: "GET",
            dataType: "json",
            success: function (res) {
                var html = '<ul class="vertical-timeline">';
                if (!res.data || res.data.length === 0) {
                    html +=
                        '<li class="vertical-timeline-item"><div class="vt-content"><p class="text-muted">Belum ada aktivitas operasional tercatat.</p></div></li>';
                } else {
                    res.data.forEach(function (act) {
                        var st = act.status.toLowerCase();
                        var isW = st.includes("eksekusi") || st.includes("progress");
                        var bg = isW ? "bg-blue" : "bg-green";
                        var fa = isW ? "fa-cog" : "fa-check";
                        var lc = isW ? "label-primary" : "label-success";

                        var t = new Date(act.waktu);
                        var fTime =
                            t.toLocaleDateString("id-ID", {
                                day: "2-digit",
                                month: "short",
                                year: "numeric",
                            }) +
                            ", " +
                            t.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" }) +
                            " WIB";

                        html +=
                            '<li class="vertical-timeline-item" style="margin-bottom:15px;">' +
                            '<span class="vt-icon ' +
                            bg +
                            '" style="color:white; border-radius:50%; width:32px; height:32px; display:inline-flex; justify-content:center; align-items:center;"><i class="fa ' +
                            fa +
                            '"></i></span>' +
                            '<div class="vt-content" style="margin-left:45px; border:1px solid #eee; padding:10px; border-radius:4px; background:#fff;">' +
                            '<span class="vt-time text-muted" style="font-size:11px; float:right;">' +
                            fTime +
                            "</span>" +
                            '<p class="vt-desc" style="margin:0; font-size:13px;"><span class="label label-default" style="margin-right:5px;">' +
                            act.modul +
                            "</span> <strong>" +
                            act.aktor +
                            "</strong> memproses tiket <strong>" +
                            act.no_tiket +
                            '</strong> menjadi <span class="label ' +
                            lc +
                            '">' +
                            st.toUpperCase() +
                            "</span>.</p></div></li>";
                    });
                }
                $("#panel-activity-timeline").html(html + "</ul>");
            },
        });
    };

    // ==========================================
    // 5. MODAL TRIGGER (EKSEKUSI & VERIFIKASI)
    // ==========================================
    window.prosesTiket = function (id_trx, no_tiket, modul) {
        $("#wf_id_trx").val(id_trx);
        $("#wf_no_tiket").val(no_tiket);
        $("#wf_modul").val(modul);
        $("#wf_catatan, #wf_start_dt, #wf_finish_dt")
            .val("")
            .css({ border: "", "background-color": "" });
        $("#wf_restart_downtime_block").hide();
        $("#wf_loading").show();
        $("#wf_content, #wf_footer").hide();
        $("#modalWorkflow").modal("show");

        $.ajax({
            url: DashboardConfig.urlTicketDetail,
            type: "GET",
            data: { id_trx: id_trx, modul: modul },
            dataType: "json",
            success: function (res) {
                $("#wf_loading").hide();

                // ====================================================================
                // GHOST SCRIPT ANNIHILATOR (REGEX SANITIZER)
                // Memotong paksa semua sisa script format lama (Alertify) dari backend
                // agar tidak terjadi collision ganda dengan dashboard_core.js
                // ====================================================================
                var safeHtml = res.html;
                if (typeof safeHtml === "string") {
                    safeHtml = safeHtml.replace(
                        /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
                        ""
                    );
                }
                $("#wf_html_detail").html(safeHtml);

                var isExecute =
                    res.status_tiket && res.status_tiket.toLowerCase().includes("menunggu");

                if (modul === "restart_vm" && isExecute) {
                    $("#wf_restart_downtime_block").show();
                }

                if (isExecute) {
                    $("#wf_label_catatan").html(
                        'Catatan Hasil Eksekusi <span class="text-danger">*</span> :'
                    );
                    $("#wf_catatan")
                        .attr(
                            "placeholder",
                            "Wajib diisi: Masukkan keterangan teknis pelaksanaan..."
                        )
                        .prop("required", true);
                    $("#wf_action_type").val("execute");
                    $("#btnSubmitWorkflow span").text("Selesaikan Eksekusi");
                    $("#btnSubmitWorkflow").removeClass("btn-info").addClass("btn-success");
                } else {
                    $("#wf_label_catatan").html(
                        'Catatan Verifikasi <span class="text-muted" style="font-weight:normal;">(Opsional)</span> :'
                    );
                    $("#wf_catatan")
                        .attr(
                            "placeholder",
                            "Opsional: Tambahkan catatan jika diperlukan, biarkan kosong jika setuju..."
                        )
                        .prop("required", false);
                    $("#wf_action_type").val("verify");
                    $("#btnSubmitWorkflow span").text("Verifikasi & Tutup Tiket");
                    $("#btnSubmitWorkflow").removeClass("btn-success").addClass("btn-info");
                }
                $("#wf_content, #wf_footer").fadeIn();
            },
            error: function () {
                $("#wf_loading").hide();
                showToast("error", "Gagal menarik detail tiket. Sesi mungkin telah berakhir.");
            },
        });
    };

    $(document).on(
        "input",
        "#wf_catatan, #wf_start_dt, #wf_finish_dt, #input_hambatan",
        function () {
            $(this).css({ border: "", "background-color": "" });
        }
    );

    loadDashboardMetrics();
    loadTimelineActivity();
    setInterval(function () {
        loadDashboardMetrics();
        loadTimelineActivity();
        $(".dataTable:visible").DataTable().ajax.reload(null, false);
    }, 60000);

    // ==========================================
    // ENGINE ANALYTICS (CHART.JS V3 ISOLATED)
    // ==========================================
    var ModernChart = window.ChartV3 || window.Chart;

    if (typeof ModernChart !== "undefined" && typeof ModernChart.register === "function") {
        if (typeof ChartDataLabels !== "undefined") {
            ModernChart.register(ChartDataLabels);
        } else {
            console.warn("[Analytics] Plugin ChartDataLabels tidak ditemukan.");
        }
    } else {
        console.warn(
            "[Analytics] Chart.js v3 tidak terdeteksi. Fitur grafik dinonaktifkan sementara."
        );
    }

    var chartInstances = {};
    var globalLabels = [];

    function calculateGrowth(dataArr) {
        var growth = [];
        var lastValidValue = null;

        for (var i = 0; i < dataArr.length; i++) {
            var curr = dataArr[i];
            if (curr === 0 || curr === null) {
                growth.push(null);
                continue;
            }
            if (lastValidValue === null) {
                growth.push(0);
            } else {
                growth.push(((curr - lastValidValue) / lastValidValue) * 100);
            }
            lastValidValue = curr;
        }
        return growth;
    }

    function formatID(num) {
        if (num === null || num === undefined || isNaN(num)) return "";
        return Number(num).toLocaleString("id-ID", { maximumFractionDigits: 2 });
    }

    function renderAdvancedComboChart(
        canvasId,
        labelText,
        dataArr,
        colorBar,
        colorLine,
        unit = ""
    ) {
        var existingChart = ModernChart.getChart(canvasId);
        if (existingChart !== undefined) {
            existingChart.destroy();
        }

        var ctx = document.getElementById(canvasId).getContext("2d");
        var cleanData = dataArr.map(function (v) {
            return v === 0 ? null : v;
        });
        var growthArr = calculateGrowth(dataArr);
        var unitLabel = unit !== "" ? " " + unit : "";

        var config = {
            type: "bar",
            data: {
                labels: globalLabels,
                datasets: [
                    {
                        type: "line",
                        label: "Growth (%)",
                        data: cleanData,
                        borderColor: colorLine,
                        backgroundColor: "transparent",
                        borderWidth: 2.5,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: colorLine,
                        pointBorderColor: "#ffffff",
                        pointBorderWidth: 1.5,
                        tension: 0.1,
                        spanGaps: true,
                        datalabels: {
                            display: true,
                            align: "top",
                            offset: 8,
                            color: "#1A252F",
                            backgroundColor: "rgba(255, 255, 255, 0.85)",
                            borderRadius: 4,
                            font: { size: 10, weight: "bold" },
                            formatter: function (value, context) {
                                var pct = growthArr[context.dataIndex];
                                if (pct === null) return "";
                                return (pct > 0 ? "+" : "") + formatID(pct) + "%";
                            },
                        },
                    },
                    {
                        type: "bar",
                        label: labelText,
                        data: cleanData,
                        backgroundColor: colorBar,
                        borderRadius: { topLeft: 4, topRight: 4 },
                        barPercentage: 0.45,
                        datalabels: {
                            display: true,
                            align: "center",
                            color: "#111111",
                            font: { size: 11, weight: "bold" },
                            formatter: function (value) {
                                return value > 0 ? formatID(value) + unitLabel : "";
                            },
                        },
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 35, bottom: 5 } },
                plugins: {
                    legend: {
                        display: true,
                        position: "bottom",
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 20,
                            font: { weight: "bold", size: 11, color: "#444" },
                        },
                    },
                    tooltip: {
                        backgroundColor: "rgba(26, 37, 47, 0.9)",
                        titleFont: { size: 12, weight: "bold" },
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 4,
                        callbacks: {
                            label: function (context) {
                                if (context.dataset.type === "line") {
                                    return (
                                        " Kenaikan: " +
                                        (growthArr[context.dataIndex] > 0 ? "+" : "") +
                                        formatID(growthArr[context.dataIndex]) +
                                        "%"
                                    );
                                }
                                return " Total: " + formatID(context.raw) + unitLabel;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: "#555",
                            font: { size: 10, weight: "bold" },
                            maxRotation: 45,
                        },
                    },
                    y: {
                        type: "linear",
                        position: "left",
                        beginAtZero: true,
                        grid: { color: "rgba(0,0,0,0.04)", drawBorder: false },
                        ticks: {
                            color: "#777",
                            font: { size: 10, weight: "bold" },
                            precision: "0",
                        },
                        grace: "15%",
                    },
                },
            },
        };

        chartInstances[canvasId] = new ModernChart(ctx, config);
    }

    // ==========================================
    // STATE MANAGEMENT & FILTER DINAMIS
    // ==========================================
    var savedStart = localStorage.getItem("dash_start");
    var savedEnd = localStorage.getItem("dash_end");
    var savedView = localStorage.getItem("dash_view");
    var savedCutoff = localStorage.getItem("dash_cutoff");

    if (savedView) $("#chartViewMode").val(savedView);
    if (savedCutoff) $("#chartCutoff").val(savedCutoff);

    var start = savedStart ? moment(savedStart) : moment().subtract(29, "days");
    var end = savedEnd ? moment(savedEnd) : moment();

    moment.updateLocale("id", { week: { dow: parseInt($("#chartCutoff").val()) } });

    function updateChartByDate(start, end) {
        $("#reportrange").val(start.format("DD MMM YYYY") + " - " + end.format("DD MMM YYYY"));

        var sDate = start.format("YYYY-MM-DD");
        var eDate = end.format("YYYY-MM-DD");
        var viewMode = $("#chartViewMode").val();
        var dowCutoff = $("#chartCutoff").val();

        localStorage.setItem("dash_start", sDate);
        localStorage.setItem("dash_end", eDate);
        localStorage.setItem("dash_view", viewMode);
        localStorage.setItem("dash_cutoff", dowCutoff);

        $.ajax({
            url:
                DashboardConfig.urlChartData +
                "?start_date=" +
                sDate +
                "&end_date=" +
                eDate +
                "&view_mode=" +
                viewMode +
                "&dow=" +
                dowCutoff,
            type: "GET",
            dataType: "json",
            success: function (res) {
                globalLabels = res.labels;

                if (res.summary && res.summary.tickets) {
                    var t = res.summary.tickets;
                    $("#wdgProvSel").text(formatID(t.prov.selesai));
                    $("#wdgProvAnt").text(formatID(t.prov.antre));
                    $("#wdgUrrSel").text(formatID(t.urr.selesai));
                    $("#wdgUrrAnt").text(formatID(t.urr.antre));
                    $("#wdgResSel").text(formatID(t.restart.selesai));
                    $("#wdgResAnt").text(formatID(t.restart.antre));
                    $("#wdgSwiSel").text(formatID(t.switch.selesai));
                    $("#wdgSwiAnt").text(formatID(t.switch.antre));
                }

                if (res.summary) {
                    $("#widgetCpu").text(
                        res.summary.total_cpu > 0
                            ? "+" + formatID(res.summary.total_cpu)
                            : formatID(res.summary.total_cpu)
                    );
                    $("#widgetRam").text(
                        res.summary.total_ram > 0
                            ? "+" + formatID(res.summary.total_ram)
                            : formatID(res.summary.total_ram)
                    );

                    var diskStr = res.summary.total_disk;
                    if (res.summary.total_disk > 1000) {
                        diskStr = formatID(res.summary.total_disk / 1024);
                        $("#widgetDisk").next("small").text("TB");
                    } else {
                        diskStr = formatID(res.summary.total_disk);
                        $("#widgetDisk").next("small").text("GB");
                    }
                    $("#widgetDisk").text(res.summary.total_disk > 0 ? "+" + diskStr : diskStr);
                }

                if (res.vm && res.vm.cumulative) {
                    renderAdvancedComboChart(
                        "chartVmCumulative",
                        "Total VM",
                        res.vm.cumulative,
                        "rgba(41, 128, 185, 0.70)",
                        "#c0392b",
                        "VM"
                    );
                }

                renderAdvancedComboChart(
                    "chartTiketProv",
                    "Total Tiket Provisioning Masuk",
                    res.tiket.prov,
                    "rgba(54, 162, 235, 0.60)",
                    "#1A73E8"
                );
                renderAdvancedComboChart(
                    "chartTiketURR",
                    "Total Tiket URR Dieksekusi",
                    res.tiket.urr,
                    "rgba(38, 166, 154, 0.60)",
                    "#16A085"
                );
                renderAdvancedComboChart(
                    "chartVmProv",
                    "Jumlah Unique VM (Provisioning)",
                    res.vm.prov,
                    "rgba(54, 162, 235, 0.60)",
                    "#1A73E8"
                );
                renderAdvancedComboChart(
                    "chartVmURR",
                    "Jumlah Unique VM (URR)",
                    res.vm.urr,
                    "rgba(38, 166, 154, 0.60)",
                    "#16A085"
                );
                renderAdvancedComboChart(
                    "chartCpuProv",
                    "Jumlah vCPU Alokasi Tiket Provisioning",
                    res.res.cpu_p,
                    "rgba(0, 188, 212, 0.60)",
                    "#00838F",
                    "vCPU"
                );
                renderAdvancedComboChart(
                    "chartCpuURR",
                    "Jumlah vCPU Alokasi Tiket URR",
                    res.res.cpu_u,
                    "rgba(0, 188, 212, 0.60)",
                    "#00838F",
                    "vCPU"
                );
                renderAdvancedComboChart(
                    "chartRamProv",
                    "Jumlah RAM Alokasi Tiket Provisioning",
                    res.res.ram_p,
                    "rgba(171, 71, 188, 0.60)",
                    "#7B1FA2",
                    "GB"
                );
                renderAdvancedComboChart(
                    "chartRamURR",
                    "Jumlah RAM Alokasi Tiket URR",
                    res.res.ram_u,
                    "rgba(171, 71, 188, 0.60)",
                    "#7B1FA2",
                    "GB"
                );
                renderAdvancedComboChart(
                    "chartDiskProv",
                    "Jumlah Disk Alokasi Tiket Provisioning",
                    res.res.disk_p,
                    "rgba(255, 112, 67, 0.60)",
                    "#D84315",
                    "GB"
                );
                renderAdvancedComboChart(
                    "chartDiskURR",
                    "Jumlah Disk Alokasi Tiket URR",
                    res.res.disk_u,
                    "rgba(255, 112, 67, 0.60)",
                    "#D84315",
                    "GB"
                );
            },
            error: function () {
                console.error("Gagal menarik data grafik. Cek response dari server.");
            },
        });
    }

    $("#chartViewMode").on("change", function () {
        var picker = $("#reportrange").data("daterangepicker");
        updateChartByDate(picker.startDate, picker.endDate);
    });

    $("#chartCutoff").on("change", function () {
        var newDow = parseInt($(this).val());
        moment.updateLocale("id", { week: { dow: newDow } });
        var picker = $("#reportrange").data("daterangepicker");
        updateChartByDate(picker.startDate, picker.endDate);
    });

    $("#reportrange").daterangepicker(
        {
            startDate: start,
            endDate: end,
            ranges: {
                "Hari Ini": [moment(), moment()],
                "Minggu Ini": [moment().startOf("week"), moment().endOf("week")],
                "7 Hari Terakhir": [moment().subtract(6, "days"), moment()],
                "Bulan Ini": [moment().startOf("month"), moment().endOf("month")],
                "Bulan Lalu": [
                    moment().subtract(1, "month").startOf("month"),
                    moment().subtract(1, "month").endOf("month"),
                ],
                "Tahun Ini": [moment().startOf("year"), moment().endOf("year")],
            },
            locale: {
                format: "DD MMM YYYY",
                applyLabel: "Terapkan",
                cancelLabel: "Batal",
                customRangeLabel: "Kustom",
            },
        },
        updateChartByDate
    );

    updateChartByDate(start, end);
});
