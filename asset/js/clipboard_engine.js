/**
 * nama file: clipboard_engine.js
 * tujuan file: Modernisasi Fitur "Copy to Clipboard" Lintas Modul (Async & Legacy Fallback)
 * catatan: Termasuk optimalisasi block finally untuk jaminan garbage collection
 */
$(document).ready(function () {
	$(document).on("click", ".btn-copy-inline, .btn-copy-ip", function (e) {
		e.preventDefault();
		var $btn = $(this);
		var textToCopy = $btn.data("text") || $btn.data("ip");
		var $icon = $btn.find("i");

		var showSuccessVisuals = function () {
			if ($("#toast-copy-success").length === 0) {
				var miniToast =
					'<div id="toast-copy-success" style="position: fixed; top: 70px; right: 20px; z-index: 999999; background: #2A3F54; color: #ffffff; padding: 12px 20px; border-left: 4px solid #2ecc71; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-size: 13px; display: none;"><i class="fa fa-check-circle" style="color: #2ecc71; margin-right: 8px; font-size: 15px; vertical-align: middle;"></i><strong style="vertical-align: middle;">Tersalin:</strong> <span id="toast-copy-text" style="vertical-align: middle; font-weight: normal;"></span></div>';
				$("body").append(miniToast);
			}
			$("#toast-copy-text").text(textToCopy);
			$("#toast-copy-success")
				.stop(true, true)
				.fadeIn(300)
				.delay(2000)
				.fadeOut(400);

			var originalColor = $btn.css("color");
			var originalClass = $icon.attr("class").match(/fa-copy|fa-clipboard/)[0];

			$btn.css("color", "#2ecc71");
			$icon.removeClass(originalClass).addClass("fa-check");
			setTimeout(function () {
				$icon.removeClass("fa-check").addClass(originalClass);
				$btn.css("color", originalColor);
			}, 1500);
		};

		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard
				.writeText(textToCopy)
				.then(function () {
					showSuccessVisuals();
				})
				.catch(function (err) {
					console.error("Gagal menyalin text (Modern API): ", err);
					executeLegacyCopy();
				});
		} else {
			executeLegacyCopy();
		}

		function executeLegacyCopy() {
			var textArea = document.createElement("textarea");
			textArea.value = textToCopy;
			textArea.style.top = "0";
			textArea.style.left = "0";
			textArea.style.position = "fixed";
			document.body.appendChild(textArea);
			textArea.focus();
			textArea.select();

			try {
				var successful = document.execCommand("copy");
				if (successful) {
					showSuccessVisuals();
				} else {
					console.error("Gagal menyalin text (Legacy Fallback)");
				}
			} catch (err) {
				console.error("Kesalahan sistem saat menyalin: ", err);
			} finally {
				// Eksekusi pembersihan mutlak untuk mencegah penumpukan elemen DOM
				if (document.body.contains(textArea)) {
					document.body.removeChild(textArea);
				}
			}
		}
	});
});
