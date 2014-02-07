<?php
/**
 * PNP Post Formats
 *
 * Main class to create custom meta boxes for post formats
 */
if(!class_exists('PNP_Post_Formats')) {

	class PNP_Post_Formats {

		protected $options    = array();
		protected $post_type  = 'post';
		public    $post_types = array();

		function __construct( $options = array() ) {

			$this->register_post_formats();
			$this->options = $options;

			if(is_admin()) {
				$this->post_types = $this->get_post_types();
				add_action('add_meta_boxes', array($this, 'add_box'));
				add_action('admin_enqueue_scripts', array($this, 'enqueue_cssjs') );
				add_action('save_post', array($this, 'save_box'));
				add_action('wp_ajax_pnpcg_get_thumbnail', array($this, 'get_thumbnail'));
			}

			add_image_size('pnp-gallery-thumb', 200, 200, true);

		}

		function register_post_formats() {
			/** Post formats */
			$post_formats = array (
				'gallery',
				'link',
				'quote',
				'video',
				'audio'
			);

			add_theme_support( 'post-formats', $post_formats );
		}

		/**
		 * Register custom meta boxes
		 */
		function add_box() {

			foreach($this->options as $option) {

				$id        = isset($option['id']) ? $option['id'] : 'pnp_blank';
				$title     = isset($option['title']) ? $option['title'] : '';
				$callback  = array($this, 'show_box');
				$post_type = isset($option['post_type']) ? $option['post_type'] : '';
				$context   = isset($option['context']) ? $option['context'] : '';
				$priority  = isset($option['priority']) ? $option['priority'] : '';
				$fields    = ( isset($option['fields']) && is_array($option['fields']) ) ? $option['fields'] : array('kokoo');

				if(!empty($id) && !empty($title) && !empty($post_type) && !empty($context) && !empty($priority) ) {
					add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $fields );		
				}

			}

		}

		/** 
		 * Display meta boxes 
		 */
		function show_box($post, $fields) {

			// Use nonce for verification
			echo '<input type="hidden" id="pnppf-nonce" name="pnp_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';

			$fields = $fields['args'];

			foreach($fields as $field) {

				echo pnp_post_format_field($field);

			}

		}

		/** 
		 * Save meta boxes fields
		 */
		function save_box( $post_id ) {

			global $post;

			if(!isset($_POST['pnp_meta_box_nonce'])) $_POST['pnp_meta_box_nonce'] = null;
 
			// verify nonce
			if (!wp_verify_nonce($_POST['pnp_meta_box_nonce'], basename(__FILE__) ))
				return $post_id;
		 
			// check autosave
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
				return $post_id;

			// check permissions
			if ('page' == $_POST['post_type']) {
				if (!current_user_can('edit_page', $post_id)) {
					return $post_id;
				}
			} elseif (!current_user_can('edit_post', $post_id)) {
				return $post_id;
			}

			// save meta values
			foreach($this->options as $option) {
				if($option['post_type'] == $post->post_type) {
					foreach($option['fields'] as $field) {
						$old = get_post_meta($post_id, $field['id'], true);

						if($field['id'] == 'pnppf_custom_gallery') {
							$new = $_POST['pnpcg_images'];
						} else {
							$new = $_POST[$field['id']];
						}

						if ($new && $new != $old) {
							if(is_array($new)) {

								$_new = array();
								foreach($new as $key => $value){
									$_new[$key] = stripslashes(htmlspecialchars($value));
								}

								update_post_meta($post_id, $field['id'], $_new);
								
							} else {
								update_post_meta($post_id, $field['id'], stripslashes(htmlspecialchars($new)));
							}
						} elseif ('' == $new && $old) {
							delete_post_meta($post_id, $field['id'], $old);
						}
					}
				}
			}


		}

		/**
		 * Get post types where custom meta boxes are used
		 */
		function get_post_types() {
			$post_types = array('post', 'page');
			return $post_types;
		}

		function enqueue_cssjs( $hook ) {
			global $post;
			if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
				if ( in_array($post->post_type, $this->post_types) ) {
					wp_enqueue_script('pnp-post-formats-js', PNPPF_DIR_URL . 'post-formats/post-formats.js' );
					wp_enqueue_style('pnp-post-formats-css', PNPPF_DIR_URL . 'post-formats/post-formats.css');
				}
			}
		}

		// ajax call to get thumbnail url
		function get_thumbnail() {

			$nonce = $_POST['security'];	

			if (! wp_verify_nonce($nonce, basename(__FILE__)) ) die('Invalid nonce');

			$attachment_id = $_POST['id'];

			$image = wp_get_attachment_image_src( $attachment_id, 'pnp-gallery-thumb' );

			echo $image[0];

			exit();

		}

	}

}

/**
 * Custom Meta fields
 */
function pnp_post_format_field($field) {

	global $post;

	if(!is_array($field)) return false;
	$meta = get_post_meta($post->ID, $field['id'], true);

	switch ($field['type']) {

		case 'input_number':

			$step = isset($field['options']['step']) ? $field['options']['step'] : 'any';

			echo '<div id="'. $field['id'] .'_container"><p>'.
			    	'<label for="'. $field['id'] .'">'.
			    	    '<span style=" display:block; color:#777; margin:0 0 10px;">'. $field['desc'].'</span>'.
			    	'</label>';

				echo '<input type="number" step="'. $step .'" name="', $field['id'], '" id="', $field['id'], '" value="', ($meta !== false) ? $meta : $field['std'], '" size="8" />';

			echo '</p></div>';

		break;

		case 'text':

			echo '<div id="'.$field['id'].'_container"><p>'.
			    	'<label for="', $field['id'], '">'.
			    	    '<span style=" display:block; color:#777; margin:0 0 10px;">'. $field['desc'].'</span>'.
			    	'</label>';

				echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : stripslashes(htmlspecialchars(( $field['std']), ENT_QUOTES)), '" size="30" />';

			echo '</p></div>';

		break;

		case 'textarea':

			echo '<div id="'.$field['id'].'_container"><p>'.
			    	'<label for="', $field['id'], '">'.
			    	    '<span style=" display:block; color:#777; margin:0 0 10px;">'. $field['desc'].'</span>'.
			    	'</label>';

				echo '<textarea name="', $field['id'], '" id="', $field['id'], '" rows="2" cols="40" style="width: 98%">', $meta ? $meta : $field['std'] ,'</textarea>';

			echo '</p></div>';

		break;

		case 'editor':
			
			echo '<div id="'.$field['id'].'_container"><p>'.
			    	'<label for="', $field['id'], '">'.
			    	    '<span style=" display:block; color:#777; margin:0 0 10px;">'. $field['desc'].'</span>'.
			    	'</label>';
				
					$settings = array(
						'textarea_name' => $field['id'],
						'media_buttons' => false,
						'textarea_rows' => '40',
					);
					
					wp_editor( $meta ? htmlspecialchars_decode($meta) : stripslashes(htmlspecialchars(( $field['std']), ENT_QUOTES)) , $field['id'], $settings );
				
			echo '</p></div>';
			
		break;

		case 'select':
			
			$meta = $meta ? $meta : $field['std'];

			echo '<div id="'.$field['id'].'_container"><p>'.
			    	'<label for="'. $field['id'] .'">'.
			    	    '<span style=" display:block; color:#777; margin:0 0 10px;">'. $field['desc'].'</span>'.
			    	'</label>';
		
				echo'<select name="'.$field['id'].'">';
			
				foreach ($field['options'] as $key => $option) {
					
					echo '<option value="' . $key .'"';
						if ($meta == $key ) { 
							echo ' selected="selected"'; 
						}
					echo'>'. $option .'</option>';
				
				} 
				
				echo'</select>';

			echo '</p></div>';
		
		break;

		case 'checkbox':

			$meta = $meta ? $meta : $field['std'];

			echo '<div id="'. $field['id'] .'_container"><p>';

			echo '<label><input value="1" type="checkbox" name="'. $field['id'] .'" id="'. $field['id'] .'" ',checked( $meta, 1, false ),'> '. $field['desc'] .'</label>';

			echo '</p></div>';


		break;

		case 'gallery':

			require 'custom-gallery.php';

		break;


	}// end switch

}

$metaboxes = array();

// Standard
$metaboxes[] = array(
	'id'        => 'pnppf-standard',
	'title'     =>  __('Standard Format Settings', 'p12r'),
	'post_type' => 'post',
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		array(
			'id'      => 'pnppf_standard',
			'desc'    => __('Select yes to display featured thumbnail.','p12r'),
			'type'    => 'select',
			'std'     => '1',
			'options' => array('yes' => 'Yes', 'no' => 'No')
		)
	)
);

// Link
$metaboxes[] = array(
	'id'        => 'pnppf-link',
	'title'     =>  __('Link Format Settings', 'p12r'),
	'post_type' => 'post',
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		array(
			'id'   => 'pnppf_link',
			'desc' => __('Enter the link','p12r'),
			'type' => 'text',
			'std'  => ''
		)
	)
);

// Gallery
$metaboxes[] = array(
	'id'        => 'pnppf-gallery',
	'title'     =>  __('Gallery Format Settings', 'p12r'),
	'post_type' => 'post',
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		array(
			'id'      => 'pnppf_gallery_0',
			'desc'    => __('Enter crop height, or leave empty to not crop (recommended: 400)','p12r'),
			'type'    => 'input_number',
			'std'     => 400,
			'options' => array('step' => 10)
		),
		array(
			'id'      => 'pnppf_custom_gallery',
			'desc'    => __('Drag & drop to reorder images','p12r'),
			'type'    => 'gallery',
			'std'     => array()
		),
	)
);

// Quote
$metaboxes[] = array(
	'id'        => 'pnppf-quote',
	'title'     =>  __('Quote Format Settings', 'p12r'),
	'post_type' => 'post',
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		array(
			'id'      => 'pnppf_quote',
			'desc'    => __('Enter the quote author or reference to the quote (optional)','p12r'),
			'type'    => 'text',
			'std'     => ''
		),
	)
);

// Video
$metaboxes[] = array(
	'id'        => 'pnppf-video',
	'title'     =>  __('Video Format Settings', 'p12r'),
	'post_type' => 'post',
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		array(
			'id'   => 'pnppf_video',
			'desc' => __('Enter embed code from e.g. Youtube or Vimeo', 'p12r'),
			'type' => 'textarea',
			'std' => '',
		),
	)
);

// Audio
$metaboxes[] = array(
	'id'        => 'pnppf-audio',
	'title'     =>  __('Audio Format Settings', 'p12r'),
	'post_type' => 'post',
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		array(
			'id'   => 'pnppf_audio',
			'desc' => __('Enter embed code from e.g. Soundcloud', 'p12r'),
			'type' => 'textarea',
			'std' => '',
		)
	)
);

new PNP_Post_Formats($metaboxes);

