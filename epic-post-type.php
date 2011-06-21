<?php
/*
Plugin Name: Epic Post Type
Plugin URI: https://github.com/aterris/epic-post-type
Description: A library to create Custom Post Types on an EPIC scale.
Version: 0.5
Author: Andrew Terris, Eric Marden
Author URI: 
*/

if ( !class_exists('epic_post_type') ):
/**
 *	Custom Post Type Class
 *	@author Andrew Terris <atterris@gmail.com>
 *	@author Eric Marden <wp@xentek.net>
 */
class epic_post_type
{
	//Member Variables
	public $slug;
	public $singular_name;
	public $icon;
	
	//Initialize New Custom Post Type
	function init($args)
	{	
		//Setup Defaults
		$defaults = array(
			'name' => 'Custom Posts',
			'singular_name' =>'Custom Post',
			'slug' => 'custom_post',
			'icon' => null,
			'has_archive' => true,
			'post_args' => array()
		);
		
		//Merge Defaults and Passed Args
		$args = wp_parse_args($args, $defaults);
		extract($args);
			
		//Save Needed Values
		$this->slug = $slug;
		$this->singular_name = $singular_name;
		$this->icon = $icon;
		
		//Create Post Type
		epic_post_type::create_post_type($name, $singular_name, $slug, $icon, $post_args);
		
		//Set Custom Alert Messages
		add_filter('post_updated_messages',array( &$this, 'custom_messages'));
		
		//Set Custom Icon
		add_action( 'admin_head', array( &$this, 'custom_icon'));
		
		//Setup Template Redirects
		add_filter('template_redirect', array( &$this, 'template_redirection') );
		
		//Manage Rewrite Rules
		add_filter('rewrite_rules_array',array( &$this, 'rewrite_rules'));
		
		return $this;
	}
	
	
	//Create Custom Post Type
	function create_post_type($name, $singular_name, $slug, $icon, $args)
	{
		//Create Labels
		$labels = array(
				'name' => _x($name,'post type general name'),
				'singular_name' => _x($singular_name,'post type singular name'),
				'add_new' => _x('Add New '.$singular_name,'post'),
				'add_new_item' => __('Add New '.$singular_name),
				'edit' => __('Edit '.$singular_name),
				'edit_item' => __('Edit '.$singular_name),
				'new_item' => __('New '.$singular_name),
				'view' => __('View '.$singular_name),
				'view_item' => __('View '.$singular_name),
				'search_items' => __('Search '.$singular_name.'s'),
				'not_found' => __('No '.$singular_name.'s Found'),
				'not_found_in_trash' => __('No '.$singular_name.'s found in Trash'),
				'parent' => __('Parent '.$singular_name)
		);
		
		//Create Basic Default Options
		$options = array(
				 	'labels' => $labels,
					'description' => '',
					'publicly_queryable' => true,
					'exclude_from_search' => false,
					'capability_type' => 'post',
					'hierarchical' => false,
					'public' => true,
					'rewrite' => array('slug'=>$slug),
					'query_var' => $slug,
					'supports' => array('title','editor','excerpt','thumbnail'),
					'register_meta_box_cb' => null,
					'show_ui' => true,
					'menu_position' => null,
					'menu_icon' => $icon,
					'permalink_epmask' => EP_PERMALINK,
					'can_export' => true,
					'show_in_nav_menus' => true
		);
		
		//Merge Options With Passed Args
		$args = wp_parse_args($args, $options);

		//Register Custom Post Type
		register_post_type( $slug , $args );
	}
	
	
	//Customize Alert Messages
	function custom_messages($messages)
	{
		$messages[$this->slug] = array(
		    0 => '', // Unused. Messages start at index 1.
		    1 => sprintf( __('%s updated. <a href="%s">View %s</a>'),$this->singular_name, esc_url( get_permalink($post_ID) ),$this->singular_name ),
		    2 => __('Custom field updated.'),
		    3 => __('Custom field deleted.'),
		    4 => __($this->singular_name.' updated.'),
		    /* translators: %s: date and time of the revision */
		    5 => isset($_GET['revision']) ? sprintf( __('%s restored to revision from %s'),$this->singular_name, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		    6 => sprintf( __('%s published. <a href="%s">View %s</a>'),$this->singular_name, esc_url( get_permalink($post_ID) ),$this->singular_name ),
		    7 => __($this->singular_name . ' saved.'),
		    8 => sprintf( __('%s submitted. <a target="_blank" href="%s">Preview %s</a>'),$this->singular_name, esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ),$this->singular_name ),
		    9 => sprintf( __('%3$s scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview %3$s</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ), $this->singular_name ),
		    10 => sprintf( __('%s draft updated. <a target="_blank" href="%s">Preview %s</a>'),$this->singular_name, esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ),$this->singular_name ),
		  );
		
		return $messages;
	}
	
	//Customize Icon
	function custom_icon()
	{
		global $post_type;
		$icon_url = false;
		
		if ($_GET['post_type'] == $this->slug || $post_type == $this->slug )
			$icon_url = $this->icon;

		if ($icon_url) :
		?>
			<style type="text/css" media="screen">
			/*<![CDATA[*/
				#icon-edit {
					background: url(<?php echo $icon_url; ?>) no-repeat 6px !important;
				}
			/*]]>*/
			</style>
		<?php
		endif;
	}
	
	
	//Setup Template Redirects
	function template_redirection()
	{
		global $wp;
		$post_types = array($this->slug);

		if ( in_array( $wp->query_vars['post_type'], $post_types ) ):
			if ( is_robots() ):
				do_action('do_robots');
				return;
			elseif ( is_feed() ) :
				do_feed();
				return;
			elseif ( is_trackback() ) :
				include( ABSPATH . 'wp-trackback.php' );
				return;
			elseif($wp->query_vars['name']):
				locate_template( array(
						'single-' . $wp->query_vars['post_type'] . '.php',
						'single.php',
						'index.php'
					), true);
				die();
			endif;
		elseif( in_array( $wp->request, $post_types ) ):
			locate_template( array(
					$wp->request . '.php',
					'archive'-$wp->request . '.php',
					'archive.php',
					'index.php'
				), true);
			exit;
		endif;
	}
	
	
	//Manage Rewrite Rules
	function rewrite_rules( $rules )
	{
		global $wp_rewrite;

		$post_type = $this->slug;
		$rewrite_rules = $wp_rewrite->generate_rewrite_rules($post_type.'/');
		$rewrite_rules[$post_type.'/?$'] = 'index.php?paged=1&post_type=' . $post_type;

		foreach($rewrite_rules as $regex => $redirect):
			if ( strpos($redirect, 'attachment=') === false ):
					$redirect .= '&post_type='.$post_type;
			endif;

			if ( 0 < preg_match_all( '@\$([0-9])@', $redirect, $matches ) ):
				for($i = 0; $i < count($matches[0]); $i++):
					$redirect = str_replace($matches[0][$i], '$matches['.$matches[1][$i].']', $redirect);	
				endfor;
			endif;
		endforeach;
		return $rewrite_rules + $rules;
	}
	
	
	//Display Posts
	function get_posts($args)
	{
		$defaults = array(
			'post_type' => $this->slug
		);
		$args = wp_parse_args($args, $defaults);
		
		return get_posts($args);
	}
}
endif;
?>