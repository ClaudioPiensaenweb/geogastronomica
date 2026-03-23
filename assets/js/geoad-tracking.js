/**
 * GeoAd Tracking — Impresiones y clicks via Beacon API.
 *
 * GDPR-friendly: sin cookies, sin IP, sin fingerprint.
 * Solo conteos de ad_id + event_type.
 *
 * @package GeoGastronomica
 */
(function () {
	'use strict';

	if (typeof geodTrackingData === 'undefined') {
		return;
	}

	var endpoint = geodTrackingData.endpoint;
	var nonce = geodTrackingData.nonce;

	/**
	 * Enviar evento al servidor.
	 */
	function sendEvent(postId, eventType) {
		var data = new FormData();
		data.append('post_id', postId);
		data.append('event_type', eventType);
		data.append('_nonce', nonce);

		if (navigator.sendBeacon) {
			navigator.sendBeacon(endpoint, data);
		} else {
			fetch(endpoint, {
				method: 'POST',
				body: data,
				keepalive: true
			}).catch(function () {
				// Fallo silencioso — tracking no debe afectar UX.
			});
		}
	}

	/**
	 * Observar impresiones con IntersectionObserver.
	 */
	var tracked = {};

	if ('IntersectionObserver' in window) {
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) {
					return;
				}

				var banner = entry.target;
				var adId = banner.getAttribute('data-ad-id');
				if (!adId || tracked[adId]) {
					return;
				}

				tracked[adId] = true;
				sendEvent(adId, 'impression');
			});
		}, { threshold: 0.5 });

		document.querySelectorAll('.geoad-banner.active').forEach(function (el) {
			observer.observe(el);
		});
	}

	/**
	 * Registrar clicks.
	 */
	document.addEventListener('click', function (e) {
		var banner = e.target.closest('.geoad-banner');
		if (!banner) {
			return;
		}

		var adId = banner.getAttribute('data-ad-id');
		if (adId) {
			sendEvent(adId, 'click');
		}
	});
})();
