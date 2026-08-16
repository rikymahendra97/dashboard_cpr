/*
 * custom.js — Template Base Script (ENTERPRISE OPTIMIZED)
 *
 * Changelog:
 *  v2.0 — BUG FIX: Hapus slideUp() & removeClass('active') pada init
 *           yang membunuh active state PHP setiap page load
 *        — BUG FIX: Selector dipersempit dari '#sidebar-menu li'
 *           menjadi '#sidebar-menu .side-menu > li' agar child menu
 *           tidak memicu parent toggle (double-click bug)
 *        — FEATURE: Mobile overlay open/close via #sidebar-overlay
 *        — FEATURE: aria-expanded update saat toggle child menu
 *        — FEATURE: URL-based active detection hanya sebagai fallback
 *           (tidak berjalan bila PHP sudah menetapkan .current-page)
 *        — FEATURE: Nav-SM child menu flyout pada hover (desktop)
 */

/* ================================================================
 *  SECTION 1 — LEFT MENU (SIDEBAR NAVIGATION)
 *
 *  Root Cause Yang Diperbaiki:
 *  1. $(function(){}) sebelumnya memanggil slideUp() + removeClass()
 *     yang langsung membatalkan active state yang sudah di-set PHP.
 *  2. '#sidebar-menu li' selector terlalu lebar — menangkap klik
 *     child_menu item, bubble ke parent li, memicu slideUp semua menu.
 *  3. Guard `closest('ul.child_menu')` pada versi sebelumnya masih
 *     rentan race-condition pada hit-test. Digantikan dengan strategi
 *     yang jauh lebih solid: stopPropagation() langsung di level child.
 *  4. Inline script 55 baris di 2sidebar.php adalah counter-hack
 *     yang sudah dihapus. Semua logika menu dikelola di sini.
 * ================================================================ */
$(function () {
	/* ────────────────────────────────────────────────────────────
	 * 1A-i. CHILD MENU LINKS — stopPropagation (WAJIB DIDAFTARKAN LEBIH DULU)
	 *
	 * Strategi: hentikan event bubbling TEPAT di level child link,
	 * SEBELUM event sempat naik ke handler parent (.side-menu > li).
	 *
	 * Mengapa ini lebih andal daripada guard di parent?
	 *  - Guard di parent bergantung pada e.target yang nilainya bisa
	 *    bervariasi tergantung posisi klik (teks, pseudo-element, dll).
	 *  - stopPropagation di child adalah "blokir mutlak" — event tidak
	 *    pernah mencapai parent, tidak ada ambiguitas.
	 *
	 * Catatan: preventDefault() TIDAK dipanggil → href link tetap
	 * berjalan normal, halaman berpindah sesuai menu yang diklik.
	 * ──────────────────────────────────────────────────────────── */
	$("#sidebar-menu .child_menu > li > a").on(
		"click.sidebar-child",
		function (e) {
			e.stopPropagation(); /* Blokir bubble ke .side-menu > li */
			/* Default action (navigasi ke href) tetap berjalan — jangan tambah preventDefault() */
		},
	);

	/* ────────────────────────────────────────────────────────────
	 * 1A-ii. PARENT MENU TOGGLE (Items yang Memiliki child_menu)
	 *
	 * Handler ini sekarang AMAN karena klik dari .child_menu tidak
	 * akan pernah sampai ke sini (sudah di-stop oleh 1A-i di atas).
	 *
	 * Hanya mengikat pada .side-menu > li (level pertama).
	 * Untuk li tanpa child_menu (single-link item), handler langsung
	 * return agar navigasi default berjalan tanpa interferensi.
	 * ──────────────────────────────────────────────────────────── */
	$("#sidebar-menu .side-menu > li").on("click.sidebar-parent", function (e) {
		var $this = $(this);
		var $child = $this.children("ul.child_menu");
		var $parentA = $this.children("a");

		/* Single-link item (tidak punya child_menu): biarkan navigasi berjalan */
		if ($child.length === 0) return;

		e.preventDefault();
		e.stopPropagation();

		if ($this.hasClass("active")) {
			/* ── Tutup menu ini ──────────────────────────────── */
			$this.removeClass("active").removeClass("nv").addClass("vn");
			$child.slideUp(200);
			$parentA.attr("aria-expanded", "false");
		} else {
			/* ── Tutup semua menu lain yang terbuka, buka ini ── */
			$("#sidebar-menu .side-menu > li.active").each(function () {
				$(this).removeClass("active").removeClass("nv").addClass("vn");
				$(this).children("ul.child_menu").slideUp(200);
				$(this).children("a").attr("aria-expanded", "false");
			});

			$this.addClass("active").removeClass("vn").addClass("nv");
			$child.slideDown(200);
			$parentA.attr("aria-expanded", "true");
		}
	});

	/* ────────────────────────────────────────────────────────────
	 * 1B. NAV-SM: CHILD MENU FLYOUT PADA HOVER (Desktop only)
	 *
	 * Di mode nav-sm (sidebar minimized), child menu muncul
	 * sebagai flyout dari kanan saat item di-hover.
	 * ──────────────────────────────────────────────────────────── */
	$("#sidebar-menu .side-menu > li")
		.on("mouseenter", function () {
			if (!$("body").hasClass("nav-sm")) return;
			var $child = $(this).children("ul.child_menu");
			if ($child.length) $child.stop(true, true).fadeIn(150);
		})
		.on("mouseleave", function () {
			if (!$("body").hasClass("nav-sm")) return;
			var $child = $(this).children("ul.child_menu");
			if ($child.length) $child.stop(true, true).fadeOut(150);
		});

	/* ────────────────────────────────────────────────────────────
	 * 1C. HAMBURGER TOGGLE (nav-md ↔ nav-sm)
	 * ──────────────────────────────────────────────────────────── */
	$("#menu_toggle").on("click", function () {
		var $body = $("body");
		var $overlay = $("#sidebar-overlay");
		var isMobile = $(window).width() <= 992;

		if ($body.hasClass("nav-md")) {
			/* ── Tutup sidebar ───────────────────────────────── */
			$body.removeClass("nav-md").addClass("nav-sm");
			$(".sidebar-footer").hide();

			/* Simpan active state sebelum minimize */
			if ($("#sidebar-menu li").hasClass("active")) {
				$("#sidebar-menu li.active")
					.addClass("active-sm")
					.removeClass("active");
			}

			/* Mobile: sembunyikan overlay */
			if (isMobile) {
				$overlay.fadeOut(250);
				$overlay.attr("aria-hidden", "true");
				$("body").css("overflow", "");
			}
		} else {
			/* ── Buka sidebar ────────────────────────────────── */
			$body.removeClass("nav-sm").addClass("nav-md");
			$(".sidebar-footer").show();

			/* Restore active state setelah expand */
			if ($("#sidebar-menu li").hasClass("active-sm")) {
				$("#sidebar-menu li.active-sm")
					.addClass("active")
					.removeClass("active-sm");
			}

			/* Mobile: tampilkan overlay + kunci scroll body */
			if (isMobile) {
				$overlay.fadeIn(250);
				$overlay.attr("aria-hidden", "false");
				$("body").css("overflow", "hidden");
			}
		}
	});

	/* ────────────────────────────────────────────────────────────
	 * 1D. TUTUP SIDEBAR DENGAN KLIK OVERLAY (Mobile)
	 * ──────────────────────────────────────────────────────────── */
	$(document).on("click", "#sidebar-overlay", function () {
		if ($("body").hasClass("nav-md")) {
			$("#menu_toggle").trigger("click");
		}
	});

	/* ────────────────────────────────────────────────────────────
	 * 1E. TUTUP SIDEBAR SAAT RESIZE KE DESKTOP (Cleanup)
	 * ──────────────────────────────────────────────────────────── */
	$(window).on("resize", function () {
		if ($(window).width() > 992) {
			$("#sidebar-overlay").hide().attr("aria-hidden", "true");
			$("body").css("overflow", "");
		}
	});
});

/* ================================================================
 *  SECTION 2 — URL-BASED ACTIVE STATE (FALLBACK ONLY)
 *
 *  Hanya dieksekusi bila PHP TIDAK berhasil menandai current-page.
 *  Ini adalah safety-net, bukan sistem utama.
 *  (Misal: AJAX navigation atau edge case routing CI yang tidak
 *   menghasilkan $segment yang benar.)
 * ================================================================ */
$(function () {
	/* Cek apakah PHP sudah menandai current-page */
	if ($("#sidebar-menu .current-page").length > 0) return;

	var currentUrl = window.location.href;

	$("#sidebar-menu a")
		.filter(function () {
			return this.href === currentUrl;
		})
		.each(function () {
			var $link = $(this);
			var $li = $link.parent("li");
			var $childUl = $li.closest("ul.child_menu");

			/* Tandai item aktif */
			$li.addClass("current-page");

			/* Jika berada di dalam child_menu, buka parent-nya */
			if ($childUl.length) {
				var $parentLi = $childUl.parent("li");
				$parentLi.addClass("active");
				$childUl.show();
				$parentLi.children("a").attr("aria-expanded", "true");
			}
		});
});

/* ================================================================
 *  SECTION 3 — TOOLTIP
 * ================================================================ */
$(function () {
	$('[data-toggle="tooltip"]').tooltip();
});

/* ================================================================
 *  SECTION 4 — PROGRESS BAR
 * ================================================================ */
if ($(".progress .progress-bar")[0]) {
	$(".progress .progress-bar").progressbar();
}

/* ================================================================
 *  SECTION 5 — SWITCHERY
 * ================================================================ */
if ($(".js-switch")[0]) {
	var elems = Array.prototype.slice.call(
		document.querySelectorAll(".js-switch"),
	);
	elems.forEach(function (html) {
		new Switchery(html, { color: "#26B99A" });
	});
}

/* ================================================================
 *  SECTION 6 — PANEL COLLAPSE & CLOSE
 * ================================================================ */
$(".close-link").on("click", function () {
	$(this).closest("div.x_panel").remove();
});

$(".collapse-link").on("click", function () {
	var $panel = $(this).closest("div.x_panel");
	var $icon = $(this).find("i");
	var $content = $panel.find("div.x_content");

	$content.slideToggle(200);

	if ($panel.hasClass("fixed_height_390"))
		$panel.toggleClass("fixed_height_390");
	if ($panel.hasClass("fixed_height_320"))
		$panel.toggleClass("fixed_height_320");

	$icon.toggleClass("fa-chevron-up fa-chevron-down");

	setTimeout(function () {
		$panel.resize();
	}, 50);
});

/* ================================================================
 *  SECTION 7 — ICHECK
 * ================================================================ */
if ($("input.flat")[0]) {
	$(document).ready(function () {
		$("input.flat").iCheck({
			checkboxClass: "icheckbox_flat-green",
			radioClass: "iradio_flat-green",
		});
	});
}

/* ================================================================
 *  SECTION 8 — TABLE BULK ACTION
 * ================================================================ */
var check_state = "";

$("table input")
	.on("ifChecked", function () {
		check_state = "";
		$(this).closest("tr").addClass("selected");
		countChecked();
	})
	.on("ifUnchecked", function () {
		check_state = "";
		$(this).closest("tr").removeClass("selected");
		countChecked();
	});

$(".bulk_action input")
	.on("ifChecked", function () {
		check_state = "";
		$(this).closest("tr").addClass("selected");
		countChecked();
	})
	.on("ifUnchecked", function () {
		check_state = "";
		$(this).closest("tr").removeClass("selected");
		countChecked();
	});

$(".bulk_action input#check-all")
	.on("ifChecked", function () {
		check_state = "check_all";
		countChecked();
	})
	.on("ifUnchecked", function () {
		check_state = "uncheck_all";
		countChecked();
	});

function countChecked() {
	if (check_state === "check_all") {
		$(".bulk_action input[name='table_records']").iCheck("check");
	}
	if (check_state === "uncheck_all") {
		$(".bulk_action input[name='table_records']").iCheck("uncheck");
	}

	var n = $(".bulk_action input[name='table_records']:checked").length;
	if (n > 0) {
		$(".column-title").hide();
		$(".bulk-actions").show();
		$(".action-cnt").html(n + " Records Selected");
	} else {
		$(".column-title").show();
		$(".bulk-actions").hide();
	}
}

/* ================================================================
 *  SECTION 9 — STAR RATING (Starrr Plugin)
 * ================================================================ */
var __slice = [].slice;

(function ($, window) {
	var Starrr;

	Starrr = (function () {
		Starrr.prototype.defaults = {
			rating: void 0,
			numStars: 5,
			change: function (e, value) {},
		};

		function Starrr($el, options) {
			var i,
				_,
				_ref,
				_this = this;

			this.options = $.extend({}, this.defaults, options);
			this.$el = $el;
			_ref = this.defaults;

			for (i in _ref) {
				_ = _ref[i];
				if (this.$el.data(i) != null) this.options[i] = this.$el.data(i);
			}

			this.createStars();
			this.syncRating();

			this.$el.on("mouseover.starrr", "span", function (e) {
				return _this.syncRating(
					_this.$el.find("span").index(e.currentTarget) + 1,
				);
			});
			this.$el.on("mouseout.starrr", function () {
				return _this.syncRating();
			});
			this.$el.on("click.starrr", "span", function (e) {
				return _this.setRating(
					_this.$el.find("span").index(e.currentTarget) + 1,
				);
			});
			this.$el.on("starrr:change", this.options.change);
		}

		Starrr.prototype.createStars = function () {
			var _results = [];
			for (var _i = 1, _ref = this.options.numStars; _i <= _ref; _i++) {
				_results.push(
					this.$el.append(
						"<span class='glyphicon .glyphicon-star-empty'></span>",
					),
				);
			}
			return _results;
		};

		Starrr.prototype.setRating = function (rating) {
			if (this.options.rating === rating) rating = void 0;
			this.options.rating = rating;
			this.syncRating();
			return this.$el.trigger("starrr:change", rating);
		};

		Starrr.prototype.syncRating = function (rating) {
			rating || (rating = this.options.rating);
			if (rating) {
				for (var i = 0; i <= rating - 1; i++) {
					this.$el
						.find("span")
						.eq(i)
						.removeClass("glyphicon-star-empty")
						.addClass("glyphicon-star");
				}
			}
			if (rating && rating < 5) {
				for (var j = rating; j <= 4; j++) {
					this.$el
						.find("span")
						.eq(j)
						.removeClass("glyphicon-star")
						.addClass("glyphicon-star-empty");
				}
			}
			if (!rating) {
				this.$el
					.find("span")
					.removeClass("glyphicon-star")
					.addClass("glyphicon-star-empty");
			}
		};

		return Starrr;
	})();

	$.fn.extend({
		starrr: function () {
			var option = arguments[0];
			var args = 2 <= arguments.length ? __slice.call(arguments, 1) : [];
			return this.each(function () {
				var data = $(this).data("star-rating");
				if (!data)
					$(this).data("star-rating", (data = new Starrr($(this), option)));
				if (typeof option === "string") return data[option].apply(data, args);
			});
		},
	});
})(window.jQuery, window);

$(function () {
	$(".starrr").starrr();
});

$(document).ready(function () {
	$("#stars").on("starrr:change", function (e, value) {
		$("#count").html(value);
	});
	$("#stars-existing").on("starrr:change", function (e, value) {
		$("#count-existing").html(value);
	});
});

/* ================================================================
 *  SECTION 10 — ACCORDION
 * ================================================================ */
$(function () {
	$(".expand").on("click", function () {
		$(this).next().slideToggle(200);
		var $icon = $(this).find(">:first-child");
		$icon.text($icon.text() === "+" ? "-" : "+");
	});
});

/* ================================================================
 *  SECTION 11 — NICESCROLL
 * ================================================================ */
$(document).ready(function () {
	if ($.fn.niceScroll) {
		$(".scroll-view").niceScroll({
			touchbehavior: true,
			cursorcolor: "rgba(42, 63, 84, 0.35)",
		});
	}
});
