jQuery(document).ready(function($) {
	
	//global post id
	var post_ID = $('#post_ID').val();
	
	//	Post formats change
	function changeFormat(format){

		if(format == 0) format = 'standard';

		jQuery('#pnppf-standard, #pnppf-gallery, #pnppf-link, #pnppf-quote, #pnppf-video, #pnppf-audio').hide();
		jQuery('#pnppf-' + format).show();

		if(format == 'gallery') {

			//check if custom gallery checked
			toggleCustomGallery();

		} else {
			$('#pnp-custom-gallery').hide();
		}
	}

	// toggle custom gallery
	function toggleCustomGallery() {
		if( $('#pnppf_gallery_1').is(":checked") ) {
			$('#pnp-custom-gallery').show();
		} else {
			$('#pnp-custom-gallery').hide();
		}
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
	
});
