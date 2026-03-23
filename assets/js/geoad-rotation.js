/**
 * GeoAd Rotation — Rotacion de banners con fade.
 *
 * Usa setTimeout recursivo (no setInterval) para evitar deriva temporal.
 * Pausa en pestanas ocultas para evitar memory leaks.
 *
 * @package GeoGastronomica
 */
(function () {
	'use strict';

	var INTERVAL_MS = 5000;
	var zones = document.querySelectorAll('.geoad-zone');

	zones.forEach(function (zone) {
		var banners = zone.querySelectorAll('.geoad-banner');
		if (banners.length <= 1) {
			return; // Sin rotacion para un solo banner.
		}

		var state = {
			current: 0,
			total: banners.length,
			timerId: null,
			paused: false
		};

		function showNext() {
			banners[state.current].classList.remove('active');

			state.current = (state.current + 1) % state.total;
			banners[state.current].classList.add('active');

			scheduleNext();
		}

		function scheduleNext() {
			if (state.paused) {
				return;
			}
			state.timerId = setTimeout(showNext, INTERVAL_MS);
		}

		function pause() {
			state.paused = true;
			if (state.timerId) {
				clearTimeout(state.timerId);
				state.timerId = null;
			}
		}

		function resume() {
			if (!state.paused) {
				return;
			}
			state.paused = false;
			scheduleNext();
		}

		// Pausar en pestanas ocultas.
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) {
				pause();
			} else {
				resume();
			}
		});

		// Iniciar rotacion.
		scheduleNext();
	});
})();
