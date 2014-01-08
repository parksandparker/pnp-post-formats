<?php 
/**
 * Custom Gallery
 *
 * Enable users to select custom images from media library
 * for use in their gallery post formats
 */

class PNP_Custom_Gallery {

	var $meta_id = 'pnp_custom_gallery';

	function __construct() {

		add_image_size( 'pnp-gallery-thumb', 200, 200, true );
		add_action('add_meta_boxes', array($this, 'add_box'));
		add_action('save_post', array($this, 'save_box'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_cssjs') );
		add_action('wp_ajax_pnpcg_get_thumbnail', array($this, 'get_thumbnail'));

	}

	function add_box() {

		$id        = 'pnp-custom-gallery';
		$title     = 'Custom Gallery';
		$callback  = array($this, 'show_box');
		$post_type = 'post';
		$context   = 'normal';
		$priority  = 'high';

		add_meta_box( $id, $title, $callback, $post_type, $context, $priority);

	}

	function show_box() {

		global $post;

		// Use nonce for verification
		echo '<input id="pnpcg-nonce" type="hidden" name="pnpcg-nonce" value="'. wp_create_nonce('pnpcg-nonce'). '" />';
	 
		// add media button
		require_once('interface.php');

	}

	function save_box( $post_id ) {

		global $post;

		if(!isset($_POST['pnpcg-nonce'])) $_POST['pnpcg-nonce'] = null;

		// verify nonce
		if (!wp_verify_nonce($_POST['pnpcg-nonce'], 'pnpcg-nonce' ))
			return $post_id;
	 
		// check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return $post_id;

		// First we need to check if the current user is authorised to do this action. 
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
			    return;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) )
			    return;
		}

		// Secondly we need to check if the user intended to change this value.
		if ( ! isset( $_POST['pnpcg-nonce'] ) || ! wp_verify_nonce( $_POST['pnpcg-nonce'], 'pnpcg-nonce' ) )
			return;

		// Save values
		$meta_id = $this->meta_id;
		$old     = get_post_meta($post_id, $meta_id, true);
		$new     = $_POST['pnpcg-images'];

		if ($new && $new != $old) {
			update_post_meta($post_id, $meta_id, $new);
		} elseif ('' == $new && $old) {
			delete_post_meta($post_id, $meta_id, $old);
		}

	}

	function enqueue_cssjs( $hook ) {
		
		global $post;

		if ( $hook == 'post-new.php' || $hook == 'post.php' && 
			in_array($post->post_type, array('post', 'page'))
		) {
			wp_enqueue_style('pnpcg-css', PNPPF_DIR_URL . 'custom-gallery/custom-gallery.css');
			wp_enqueue_script('pnpcg-js', PNPPF_DIR_URL . 'custom-gallery/custom-gallery.js');
		}

	}

	// ajax call to get thumbnail url
	function get_thumbnail() {

		$nonce = $_POST['security'];	
		if (! wp_verify_nonce($nonce, 'pnpcg-nonce') ) die('Invalid nonce');

		$attachment_id = $_POST['id'];

		$image = wp_get_attachment_image_src( $attachment_id, 'pnp-gallery-thumb' );

		echo $image[0];

		exit();

	}

}

new PNP_Custom_Gallery;