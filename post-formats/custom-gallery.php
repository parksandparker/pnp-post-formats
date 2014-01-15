<p><label style="color:#777;">Drag & drop to reorder images</label></p>

<div id="pnpcg-wrapper">
	
	<div class="pnpcg-images">
		<?php
		// get saved images
		$attachments = get_post_meta($post->ID, 'pnppf_custom_gallery', true);

		if($attachments && is_array($attachments)) {

			foreach($attachments as $attachment) {
				$attachment_id = $attachment;
				$image = wp_get_attachment_image_src( $attachment_id, 'pnp-gallery-thumb' );
				$src = $image[0];
				?>
				<div class="pnpcg-image attachment">
					<input type="hidden" name="pnpcg_images[]" value="<?php echo $attachment_id; ?>">
					<img src="<?php echo $src; ?>" alt="">
					<a class="close media-modal-icon" href="#" title="Remove"></a>
				</div>
				<?php
			}
		}
		?>
	</div>

</div>

<input id="pnp-custom-gallery-upload" type="button" class="button" value="Add Images">