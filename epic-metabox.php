<?php
/*
Plugin Name: Epic Metaboxes
Plugin URI: http://blueprintds.com
Description: A Plugin to aide in the creation and implementation of Metaboxes
Version: 0.1
Author: Andrew Terris, Andrew Norcross, Jared Atchison
Author URI: http://blueprintds.com 
*/


/**
 *	Epic Metabox
 *	@author Andrew T
 *	
 *	Based on work by Andrew Norcross, Jared Atchison on Custom Metaboxes
 */
if ( class_exists( 'epic_metabox' ) ):
class epic_metabox {
	protected $_meta_box;

	function __construct( $meta_box )
	{
		if ( !is_admin() ) return;

		$this->_meta_box = $meta_box;

		$upload = false;
		foreach ( $meta_box['fields'] as $field ):
			if ( $field['type'] == 'file' || $field['type'] == 'file_list' ):
				$upload = true;
				break;
			endif;
		endforeach;
		
		$current_page = substr(strrchr($_SERVER['PHP_SELF'], '/'), 1, -4);
		
		if ( $upload && ( $current_page == 'page' || $current_page == 'page-new' || $current_page == 'post' || $current_page == 'post-new' ) ):
			add_action('admin_head', array(&$this, 'add_post_enctype'));
		endif;

		add_action( 'admin_menu', array(&$this, 'add') );
		add_action( 'save_post', array(&$this, 'save') );
	} // end __construct()

	function add_post_enctype()
	{
		echo '
		<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery("#post").attr("enctype", "multipart/form-data");
			jQuery("#post").attr("encoding", "multipart/form-data");
		});
		</script>';
	} // end add_post_enctype()

	/// Add metaboxes
	function add()
	{
		$this->_meta_box['context'] = empty($this->_meta_box['context']) ? 'normal' : $this->_meta_box['context'];
		$this->_meta_box['priority'] = empty($this->_meta_box['priority']) ? 'high' : $this->_meta_box['priority'];
		foreach ( $this->_meta_box['pages'] as $page ):
			add_meta_box($this->_meta_box['id'], $this->_meta_box['title'], array(&$this, 'show'), $page, $this->_meta_box['context'], $this->_meta_box['priority']);
		endforeach;
	} // end add()

	// Show fields
	function show()
	{
		global $post;

		// Use nonce for verification
		echo '<input type="hidden" name="wp_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';
		echo '<table class="form-table epic_metabox_metabox">';

		foreach ( $this->_meta_box['fields'] as $field ):
			// Set up blank values for empty ones
			if ( !isset($field['desc']) ) $field['desc'] = '';
			if ( !isset($field['std']) ) $field['std'] = '';
			
			$meta = get_post_meta( $post->ID, $field['id'], 'multicheck' != $field['type'] /* If multicheck this can be multiple values */ );

			echo '<tr>';
	
			if ( $field['type'] == "title" ):
				echo '<td colspan="2">';
			else:
				if( $this->_meta_box['show_names'] == true ):
					echo '<th style="width:18%"><label for="', $field['id'], '">', $field['name'], '</label></th>';
				endif;
				echo '<td>';
			endif;
			
			switch ( $field['type'] ):
				case 'text':
					echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" style="width:97%" />',
						'<p class="epic_metabox_metabox_description">', $field['desc'], '</p>';
					break;
				case 'text_small':
					echo '<input class="epic_metabox_text_small" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" /><span class="epic_metabox_metabox_description">', $field['desc'], '</span>';
					break;
				case 'text_medium':
					echo '<input class="epic_metabox_text_medium" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" /><span class="epic_metabox_metabox_description">', $field['desc'], '</span>';
					break;
				case 'text_date':
					echo '<input class="epic_metabox_text_small epic_metabox_datepicker" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" /><span class="epic_metabox_metabox_description">', $field['desc'], '</span>';
					break;
				case 'text_money':
					echo '$ <input class="epic_metabox_text_money epic_metabox_numeric_only" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" /><span class="epic_metabox_metabox_description">', $field['desc'], '</span>';
					break;
				case 'textarea':
					echo '<textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="10" style="width:97%">', $meta ? $meta : $field['std'], '</textarea>',
						'<p class="epic_metabox_metabox_description">', $field['desc'], '</p>';
					break;
				case 'textarea_small':
					echo '<textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="4" style="width:97%">', $meta ? $meta : $field['std'], '</textarea>',
						'<p class="epic_metabox_metabox_description">', $field['desc'], '</p>';
					break;
				case 'select':
					echo '<select name="', $field['id'], '" id="', $field['id'], '">';
					foreach ($field['options'] as $option):
						echo '<option value="', $option['value'], '"', $meta == $option['value'] ? ' selected="selected"' : '', '>', $option['name'], '</option>';
					endforeach;
					echo '</select>';
					echo '<p class="epic_metabox_metabox_description">', $field['desc'], '</p>';
					break;
				case 'radio_inline':
					echo '<div class="epic_metabox_radio_inline">';
					foreach ($field['options'] as $option):
						$checked = '';
						if ( $meta == $option['value'] ):
							$checked = 'checked="checked"';
						elseif ( empty($meta) && $option['default'] == true ):
							$checked = 'checked="checked"';
						endif;
						echo '<div class="epic_metabox_radio_inline_option"><input type="radio" name="', $field['id'], '" value="', $option['value'], '"', $checked, ' />', $option['name'], '</div>';
					endforeach;
					echo '</div>';
					echo '<p class="epic_metabox_metabox_description">', $field['desc'], '</p>';
					break;
				case 'radio':
					foreach ($field['options'] as $option) {
						$checked = '';
						if ( $meta == $option['value'] ):
							$checked = 'checked="checked"';
						elseif ( empty($meta) && $option['default'] == true ):
							$checked = 'checked="checked"';
						endif;
						echo '<p><input type="radio" name="', $field['id'], '" value="', $option['value'], '"', $checked, ' />', $option['name'].'</p>';
					endforeach;
					echo '<p class="epic_metabox_metabox_description">', $field['desc'], '</p>';
					break;
				case 'checkbox':
					$checked = '';
					if ( empty($meta) && $option['default'] == true):
						$checked = 'checked="checked"';
					elseif ( $meta == $option['value'] ):
						$checked = 'checked="checked"';
					endif;
					echo '<input type="checkbox" name="', $field['id'], '" id="', $field['id'], '"', $checked, ' />';
					echo '<span class="epic_metabox_metabox_description">', $field['desc'], '</span>';
					break;
				case 'multicheck':
					echo '<ul>';
					foreach ( $field['options'] as $value => $name ):
						// Append `[]` to the name to get multiple values
						// Use in_array() to check whether the current option should be checked
						echo '<li><input type="checkbox" name="', $field['id'], '[]" id="', $field['id'], '" value="', $value, '"', in_array( $value, $meta ) ? ' checked="checked"' : '', ' /><label>', $name, '</label></li>';
					endforeach;
					echo '</ul>';
					echo '<span class="epic_metabox_metabox_description">', $field['desc'], '</span>';
					break;
				case 'title':
					echo '<h5 class="epic_metabox_metabox_title">', $field['name'], '</h5>';
					echo '<p class="epic_metabox_metabox_description">', $field['desc'], '</p>';
					break;
				case 'wysiwyg':
					echo '<div id="poststuff" class="meta_mce">';
					echo '<div class="epic-editor"><textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="7" style="width:97%">', $meta ? $meta : '', '</textarea></div>';
					echo '</div>';
					echo '<p class="epic_metabox_metabox_description">', $field['desc'], '</p>';
				break;
				case 'file_list':
					echo '<input id="upload_file" type="text" size="36" name="', $field['id'], '" value="" />';
					echo '<input class="upload_button button" type="button" value="Upload File" />';
					echo '<p class="epic_metabox_metabox_description">', $field['desc'], '</p>';
						$args = array(
								'post_type' => 'attachment',
								'numberposts' => null,
								'post_status' => null,
								'post_parent' => $post->ID
							);
							$attachments = get_posts($args);
							if ($attachments):
								echo '<ul class="attach_list">';
								foreach ( $attachments as $attachment ):
									echo '<li>'.wp_get_attachment_link($attachment->ID, 'thumbnail', 0, 0, 'Download');
									echo '<span>';
									echo apply_filters('the_title', '&nbsp;'.$attachment->post_title);
									echo '</span></li>';
								endforeach;
								echo '</ul>';
							endif;
						break;
				case 'file':
					echo '<input id="upload_file" type="text" size="45" class="', $field['id'], '" name="', $field['id'], '" value="', $meta, '" />';
					echo '<input class="upload_button button" type="button" value="Upload File" />';
					echo '<p class="epic_metabox_metabox_description">', $field['desc'], '</p>';
					echo '<div id="', $field['id'], '_status" class="epic_metabox_upload_status">';	
						if ( $meta != '' ):
							$check_image = preg_match( '/(^.*\.jpg|jpeg|png|gif*)/i', $meta );
							if ( $check_image ):
								echo '<div class="img_status">';
								echo '<img src="', $meta, '" alt="" />';
								echo '<a href="#" class="remove_file_button" rel="', $field['id'], '">Remove Image</a>';
								echo '</div>';
							else:
								$parts = explode( "/", $meta );
								for( $i = 0; $i < sizeof( $parts ); ++$i ):
									$title = $parts[$i];
								endfor;
								echo 'File: <strong>', $title, '</strong>&nbsp;&nbsp;&nbsp; (<a href="', $meta, '" target="_blank" rel="external">Download</a> / <a href="#" class="remove_file_button" rel="', $field['id'], '">Remove</a>)';
							endif;
						endif;
					echo '</div>'; 
				break;
			endswitch;
			echo '</td>','</tr>';
		endforeach;
		echo '</table>';
	} // end show()

	/**
	 *	Save data from metabox
	 */
	function save( $post_id )
	{
		// verify nonce
		if ( ! isset( $_POST['wp_meta_box_nonce'] ) || !wp_verify_nonce($_POST['wp_meta_box_nonce'], basename(__FILE__))):
			return $post_id;
		endif;

		// check autosave
		if ( defined('DOING_AUTOSAVE' ) && DOING_AUTOSAVE):
			return $post_id;
		endif;

		// check permissions
		if ( 'page' == $_POST['post_type'] ):
			if ( !current_user_can( 'edit_page', $post_id ) ):
				return $post_id;
			endif;
		elseif ( !current_user_can( 'edit_post', $post_id ) ):
			return $post_id;
		endif;

		foreach ( $this->_meta_box['fields'] as $field ):
			$name = $field['id'];
			$old = get_post_meta( $post_id, $name, 'multicheck' != $field['type'] /* If multicheck this can be multiple values */ );
			$new = isset( $_POST[$field['id']] ) ? $_POST[$field['id']] : null;

			if ( $field['type'] == 'wysiwyg' ):
				$new = wpautop($new);
			endif;

			if ( ($field['type'] == 'text') || ($field['type'] == 'text_small') || ($field['type'] == 'text_medium') ):
				$new = htmlspecialchars($new);
			endif;
			
			if ( ($field['type'] == 'textarea') || ($field['type'] == 'textarea_small') ):
				$new = htmlspecialchars($new);
			endif;
			
			// validate meta value
			if ( isset($field['validate_func']) ) {
				$ok = call_user_func(array('epic_metabox_validate', $field['validate_func']), $new);
				if ( $ok === false ): // pass away when meta value is invalid
					continue;
				endif;
			elseif ( 'multicheck' == $field['type'] ):
				// Do the saving in two steps: first get everything we don't have yet
				// Then get everything we should not have anymore
				if ( empty( $new ) ):
					$new = array();
				endif;

				$aNewToAdd = array_diff( $new, $old );
				$aOldToDelete = array_diff( $old, $new );
				foreach ( $aNewToAdd as $newToAdd ):
					add_post_meta( $post_id, $name, $newToAdd, false );
				endforeach;
				
				foreach ( $aOldToDelete as $oldToDelete ):
					delete_post_meta( $post_id, $name, $oldToDelete );
				endforeach;
			elseif ($new && $new != $old):
				update_post_meta($post_id, $name, $new);
			elseif ('' == $new && $old ):
				delete_post_meta($post_id, $name, $old);
			endif;
		endforeach;
	} // end save()
} // end epic_metabox
endif;

if ( class_exists('epic_metabox_validate') ):
/**
 * Epic Metabox Validate
 *	
 *	Pretty Much Unused at this point
 */
class epic_metabox_validate
{
	function check_text( $text )
	{
		if ($text != 'hello'):
			return false;
		endif;
		return true;
	}
}

if ( function_exists('epic_metabox_scripts') ):
function epic_metabox_scripts( $hook )
{
	if ( $hook == 'post.php' OR $hook == 'post-new.php' OR $hook == 'page-new.php' OR $hook == 'page.php' ):
		wp_register_script( 'epic-metabox', plugins_url('/epic-metabox.js',__FILE__), array('jquery','media-upload','thickbox'));
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' ); // Make sure and use elements form the 1.7.3 UI - not 1.8.9
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_script( 'epic-metabox' );
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_style( 'jquery-custom-ui' );
  	endif;
}
add_action( 'admin_enqueue_scripts', 'epic_metabox_scripts',10,1 );
endif;

if ( function_exists('epic_metabox_editor_init') ):
function epic_metabox_editor_init()
{
  wp_enqueue_script('word-count');
  wp_enqueue_script('post');
  wp_enqueue_script('editor');
}
add_action( 'admin_init', 'epic_metabox_editor_init' );
endif;

if ( function_exists('epic_metabox_editor_head') ):
function epic_metabox_editor_head()
{
  wp_tiny_mce();
}
add_action('admin_head', 'epic_metabox_editor_head');
endif;

if ( function_exists('epic_metabox_editor_scripts') ):
function epic_metabox_editor_scripts() { ?>
		<script type="text/javascript">/* <![CDATA[ */
		jQuery(function($) {
			var i=1;
			$('.customEditor textarea').each(function(e) {
				var id = $(this).attr('id');
 				if (!id) {
					id = 'epic-editor' + i++;
					$(this).attr('id',id);
				}
 				tinyMCE.execCommand('mceAddControl', false, id);
 			});
		});
	/* ]]> */</script>
	<?php }
add_action('admin_print_footer_scripts','epic_metabox_editor_scripts',99);
endif;

if ( function_exists('epic_metabox_styles_inline') ):
function epic_metabox_styles_inline()
{ 
	echo '<link rel="stylesheet" type="text/css" href="' . plugins_url('/includes/metabox-style.css',__FILE__) . '" />';
	// For some reason this script doesn't like to register
	?>	
	<style type="text/css">
		table.epic_metabox_metabox td, table.epic_metabox_metabox th { border-bottom: 1px solid #f5f5f5; /* Optional borders between fields */ } 
		table.epic_metabox_metabox th { text-align: right; font-weight:bold;}
		table.epic_metabox_metabox th label { margin-top:6px; display:block;}
		p.epic_metabox_metabox_description { color: #AAA; font-style: italic; margin: 2px 0 !important;}
		span.epic_metabox_metabox_description { color: #AAA; font-style: italic;}
		input.epic_metabox_text_small { width: 100px; margin-right: 15px;}
		input.epic_metabox_text_money { width: 90px; margin-right: 15px;}
		input.epic_metabox_text_medium { width: 230px; margin-right: 15px;}
		table.epic_metabox_metabox input, table.epic_metabox_metabox textarea { font-size:11px; padding: 5px;}
		table.epic_metabox_metabox li { font-size:11px; }
		table.epic_metabox_metabox ul { padding-top:5px; }
		table.epic_metabox_metabox select { font-size:11px; padding: 5px 10px;}
		table.epic_metabox_metabox input:focus, table.epic_metabox_metabox textarea:focus { background: #fffff8;}
		.epic_metabox_metabox_title { margin: 0 0 5px 0; padding: 5px 0 0 0; font: italic 24px/35px Georgia,"Times New Roman","Bitstream Charter",Times,serif;}
		.epic_metabox_radio_inline { padding: 4px 0 0 0;}
		.epic_metabox_radio_inline_option {display: inline; padding-right: 18px;}
		table.epic_metabox_metabox input[type="radio"] { margin-right:3px;}
		table.epic_metabox_metabox input[type="checkbox"] { margin-right:6px;}
		table.epic_metabox_metabox .mceLayout {border:1px solid #DFDFDF !important;}
		table.epic_metabox_metabox .meta_mce {width:97%;}
		table.epic_metabox_metabox .meta_mce textarea {width:100%;}
		table.epic_metabox_metabox .epic_metabox_upload_status {  margin: 10px 0 0 0;}
		table.epic_metabox_metabox .epic_metabox_upload_status .img_status {  position: relative; }
		table.epic_metabox_metabox .epic_metabox_upload_status .img_status img { border:1px solid #DFDFDF; background: #FAFAFA; max-width:350px; padding: 5px; -moz-border-radius: 2px; border-radius: 2px;}
		table.epic_metabox_metabox .epic_metabox_upload_status .img_status .remove_file_button { text-indent: -9999px; background: url(<?php bloginfo('stylesheet_directory'); ?>/lib/metabox/images/ico-delete.png); width: 16px; height: 16px; position: absolute; top: -5px; left: -5px;}
	</style>
<?php
}
add_action( 'admin_head', 'epic_metabox_styles_inline' );
endif;
?>