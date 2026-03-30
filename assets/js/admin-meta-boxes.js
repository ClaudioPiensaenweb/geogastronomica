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

	// Media Library picker — upload (imagen o video).
	$(document).on('click', '.geo-upload-btn', function (e) {
		e.preventDefault();
		var $field = $(this).closest('.geo-image-field');
		var $input = $field.find('.geo-image-id');
		var $preview = $field.find('.geo-image-preview');
		var $removeBtn = $field.find('.geo-remove-btn');

		var frame = wp.media({
			title: 'Seleccionar imagen o video',
			button: { text: 'Usar este archivo' },
			multiple: false,
			library: { type: ['image', 'video'] }
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			var isVideo = attachment.type === 'video';

			var previewHtml;
			if (isVideo) {
				// Miniatura del video o icono de fallback.
				var thumb = attachment.image && attachment.image.src
					? attachment.image.src
					: '';
				previewHtml = thumb
					? '<div class="geo-video-preview"><img src="' + thumb + '" alt=""><span class="geo-video-badge">&#9654; Video</span></div>'
					: '<div class="geo-video-preview geo-video-preview--icon"><span class="dashicons dashicons-video-alt3"></span><span>' + attachment.filename + '</span></div>';
			} else {
				var url = attachment.sizes && attachment.sizes.medium
					? attachment.sizes.medium.url
					: attachment.url;
				previewHtml = '<img src="' + url + '" alt="">';
			}

			$input.val(attachment.id);
			$preview.html(previewHtml);
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
