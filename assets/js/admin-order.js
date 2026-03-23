/**
 * Admin Order — Drag & drop para reordenar anuncios.
 *
 * @package GeoGastronomica
 */
(function ($) {
	'use strict';

	$(function () {
		var $list = $('#geo-sortable-list');
		if (!$list.length) return;

		$list.sortable({
			handle: '.geo-order-handle',
			placeholder: 'geo-order-placeholder',
			axis: 'y',
			cursor: 'grabbing',
			opacity: 0.8,
			update: function () {
				updatePositions();
				saveOrder();
			}
		});

		function updatePositions() {
			$list.find('.geo-order-item').each(function (i) {
				$(this).find('.geo-order-position').text(i + 1);
			});
		}

		function saveOrder() {
			var order = [];
			$list.find('.geo-order-item').each(function () {
				order.push($(this).data('id'));
			});

			$.ajax({
				url: geoOrderData.ajaxUrl,
				method: 'POST',
				data: {
					action: 'geo_save_order',
					nonce: geoOrderData.nonce,
					order: order
				},
				success: function (res) {
					if (res.success) {
						$('#geo-order-notice').fadeIn(200).delay(2000).fadeOut(200);
					}
				}
			});
		}
	});
})(jQuery);
