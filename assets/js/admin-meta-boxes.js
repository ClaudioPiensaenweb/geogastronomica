/**
 * Admin Meta Boxes — Tabs y Media Library picker.
 *
 * @package GeoGastronomica
 */
(function ($) {
	'use strict';

	// Tabs navigation.
	$(document).on('click', '.geo-tab-link', function (e) {
		e.preventDefault();
		var tab = $(this).data('tab');

		$('.geo-tab-link').removeClass('active');
		$(this).addClass('active');

		$('.geo-tab-content').removeClass('active');
		$('#geo-tab-' + tab).addClass('active');
	});

	// Media Library picker — upload.
	$(document).on('click', '.geo-upload-btn', function (e) {
		e.preventDefault();
		var $field = $(this).closest('.geo-image-field');
		var $input = $field.find('.geo-image-id');
		var $preview = $field.find('.geo-image-preview');
		var $removeBtn = $field.find('.geo-remove-btn');

		var frame = wp.media({
			title: 'Seleccionar imagen',
			button: { text: 'Usar esta imagen' },
			multiple: false,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			var url = attachment.sizes && attachment.sizes.medium
				? attachment.sizes.medium.url
				: attachment.url;

			$input.val(attachment.id);
			$preview.html('<img src="' + url + '" alt="">');
			$removeBtn.show();
		});

		frame.open();
	});

	// Media Library picker — remove.
	$(document).on('click', '.geo-remove-btn', function (e) {
		e.preventDefault();
		var $field = $(this).closest('.geo-image-field');
		$field.find('.geo-image-id').val('');
		$field.find('.geo-image-preview').html('');
		$(this).hide();
	});
})(jQuery);
