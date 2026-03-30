/**
 * GeoAd Frontend — Rotacion, lazy video, sticky dismiss.
 *
 * - Rotacion: fade entre banners con pausa en pestanas ocultas.
 * - Lazy video: IntersectionObserver activa <video> al entrar en viewport.
 *   Los <source> usan data-src para que el navegador no descargue nada
 *   hasta que el anuncio sea visible. Ahorro real de ancho de banda.
 * - Sticky: gestiona el boton de cierre y el padding del body.
 *
 * @package GeoGastronomica
 */
(function () {
	'use strict';

	var INTERVAL_MS  = (window.geoAdConfig && window.geoAdConfig.interval) || 5000;
	var DISMISS_KEY  = 'geoad_sticky_dismissed';
	var VIDEO_MARGIN = '300px'; // Precargar 300px antes del viewport.

	// ─── Rotacion de banners ──────────────────────────────────────────────

	document.querySelectorAll('.geoad-zone').forEach(function (zone) {
		var banners = zone.querySelectorAll('.geoad-banner');
		if (banners.length <= 1) return;

		var state = {
			current : 0,
			total   : banners.length,
			timerId : null,
			paused  : false
		};

		function showNext() {
			banners[state.current].classList.remove('active');
			state.current = (state.current + 1) % state.total;
			banners[state.current].classList.add('active');
			scheduleNext();
		}

		function scheduleNext() {
			if (!state.paused) {
				state.timerId = setTimeout(showNext, INTERVAL_MS);
			}
		}

		document.addEventListener('visibilitychange', function () {
			if (document.hidden) {
				state.paused = true;
				clearTimeout(state.timerId);
			} else {
				state.paused = false;
				scheduleNext();
			}
		});

		scheduleNext();
	});

	// ─── Lazy loading de videos ───────────────────────────────────────────
	// Los <source> llevan data-src en lugar de src para que el browser
	// no descargue nada. Al entrar en viewport se copian los data-src a
	// src y se llama a video.load() + video.play().

	function activateVideo(video) {
		video.querySelectorAll('source[data-src]').forEach(function (source) {
			source.src = source.dataset.src;
			source.removeAttribute('data-src');
		});
		video.load();
		video.play().catch(function () {
			// Silenciar errores de autoplay policy (politica del navegador).
		});
	}

	var videos = document.querySelectorAll('.geoad-video-lazy');

	if (videos.length) {
		if ('IntersectionObserver' in window) {
			var videoObserver = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) return;
					activateVideo(entry.target);
					videoObserver.unobserve(entry.target);
				});
			}, { rootMargin: VIDEO_MARGIN });

			videos.forEach(function (v) { videoObserver.observe(v); });
		} else {
			// Fallback sincrono para browsers muy antiguos.
			videos.forEach(activateVideo);
		}
	}

	// ─── Sticky bottom — close/dismiss ───────────────────────────────────

	var stickies = document.querySelectorAll('.geoad-zone--sticky-bottom');
	if (!stickies.length) return;

	// Si el usuario ya cerro el sticky esta sesion, eliminarlo sin mostrarlo.
	try {
		if (sessionStorage.getItem(DISMISS_KEY) === '1') {
			stickies.forEach(function (z) { z.remove(); });
			return;
		}
	} catch (e) { /* sessionStorage bloqueado */ }

	stickies.forEach(function (zone) {
		// Padding en body para que el sticky no tape contenido.
		document.body.classList.add('has-geoad-sticky');

		var btn = zone.querySelector('.geoad-sticky-close');
		if (btn) {
			btn.addEventListener('click', function () {
				zone.remove();
				document.body.classList.remove('has-geoad-sticky');
				try { sessionStorage.setItem(DISMISS_KEY, '1'); } catch (e) {}
			});
		}
	});

})();
