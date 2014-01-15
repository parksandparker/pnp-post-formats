jQuery(document).ready(function($) {
	
	//global post id
	var post_ID = $('#post_ID').val();
	
	//	Post formats change
	function changeFormat(format){

		if(format == 0) format = 'standard';

		jQuery('#pnppf-standard, #pnppf-gallery, #pnppf-link, #pnppf-quote, #pnppf-video, #pnppf-audio').hide();
		jQuery('#pnppf-' + format).show();
	}
	
	var currFormat = jQuery('#post-formats-select').find(':checked').val();
	changeFormat(currFormat);
	
	$('#post-formats-select').change(function() {
		var format = jQuery(this).find(':checked').val();
		changeFormat(format);
	});

	$(document).on('change', '#pnppf_gallery_1', function(){
		toggleCustomGallery();
	});

	// custom gallery
	$(document).on('click', '#pnp-custom-gallery-upload', function(event) {

		var $clicked = $(this), 
			frame,
			input_id = $clicked.prev().attr('id'),
			media_type = 'image';
			
		event.preventDefault();
		
		// If the media frame already exists, reopen it.
		if ( frame ) {
			frame.open();
			return;
		}

		//wp.media.editor.remove('content');
		
		// Create the media frame.
		frame = wp.media.frames.pnpcg_uploader = wp.media({
			// Set the media type
			library: {
				type: media_type
			},
			multiple: true,
			selection: [31]
		});

		// When an image is selected, run a callback.
		frame.on( 'select', function() {
			// Grab the selected attachment.
			var attachments = frame.state().get('selection'),
				selected = {},
				count = _.size(attachments.models);

			$.each( attachments.models, function(index, attachment) {

				var attachment_id = attachment.attributes.id;

				var data = {
					action: 'pnpcg_get_thumbnail',
					security: $('#pnppf-nonce').val(),
					id: attachment_id
				};

				$.post( ajaxurl, data, function(response, textStatus, xhr) {
					//optional stuff to do after success
					attachment_id = attachment.attributes.id;
					
					selected[attachment_id] = response;
					send_image(selected);

				});

			});

			function send_image(selected) {

				var size = _.size(selected);

				if(size == count) {

					var output = document.createElement('div'),
						element;

					output.id = 'pnpcg-temp';
					$(output).hide();

					// build the output
					$.each(selected, function(index, value) {

						element = document.createElement('div');
						element.className = 'pnpcg-image attachment';

						var input = '<input type="hidden" name="pnpcg_images[]" value="'+ index +'">';
						var image = '<img src="'+ value +'"/>';
						var close = '<a class="close media-modal-icon" href="#" title="Remove"></a>';

						$(element).append(input);
						$(element).append(image);
						$(element).append(close);
						$(element).hide();

						$(output).append(element);

					});

					$('#pnpcg-wrapper .pnpcg-images').append(output);
					$('#pnpcg-wrapper #pnpcg-temp').contents().unwrap();
					$('#pnpcg-wrapper .pnpcg-image').fadeIn();
					pnpcg_sortable();

				}

			}
			
		});

		frame.open();
	
	});
	
	/** Make em sortable bro */
	function pnpcg_sortable() {
		$('.pnpcg-images').sortable({
			placeholder: 'placeholder'
		});
	};
	pnpcg_sortable();

	$(document).on('click', '.pnpcg-image .close', function() {
		$(this).parent().remove();
		return false;
	});
	
});
