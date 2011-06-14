<?php
/**
 * 	Epic Taxonomy
 *	@author Andrew Terris <atterris@gmail.com>
 */
class epic_taxonomy
{
	//Member Variables
	public $slug;
	public $singular_name;
	public $icon;
	public $object_type;
	
	
	//Initialize New Custom Post Type
	function init($args)
	{	
		//Setup Defaults
		$defaults = array(
			'name' => 'Custom Taxonomy',
			'singular_name' =>'Custom Taxonomy',
			'slug' => 'custom_tax',
			'icon' => null,
			'object_type' => 'post',
			'tax_args' => array()
		);
		
		//Merge Defaults and Passed Args
		$args = wp_parse_args($args, $defaults);
		extract($args);
			
		//Save Needed Values
		$this->slug = $slug;
		$this->singular_name = $singular_name;
		$this->icon = $icon;
		$this->object_type = $object_type;
		 
		//Create Custom Taxonomy
		epic_taxonomy::create_taxonomy($name, $singular_name, $slug, $object_type, $tax_args);
		
		//Set Custom Icon
		add_action( 'admin_head', array( &$this, 'custom_icon'));
		
		//Setup Template Redirects
		add_filter('template_redirect', array( &$this, 'template_redirection') );
	}
	
	function create_taxonomy($name, $singular_name, $slug, $object_type, $args)
	{
		//Create Labels
		$labels = array(
				'name' => _x( $name, 'taxonomy general name' ) ,
				'singular_name' => _x( $singular_name, 'taxonomy singular name' ),
				'search_items' => __( 'Search '.$singular_name.'s' ),
				'popular_items' => __( 'Popular '.$singular_name.'s' ),
				'all_items' => __( 'All '.$singular_name.'s' ),
				'parent_item' => __( 'Parent '.$singular_name ),
				'parent_item_colon' => __( 'Parent '.$singular_name.':' ),
				'edit_item' => __( 'Edit '.$singular_name ),
				'update_item' => __( 'Update '.$singular_name ),
				'add_new_item' => __( 'Add New '.$singular_name ),
				'new_item_name' => __( 'New '.$singular_name.' Name' ),
		);
		
		//Create Basic Default Options
		$options = array(
				'hierarchical' => true,
				'update_count_callback' => '',
				'rewrite' => true,
				'query_var' => $taxonomy,
				'public' => true,
				'show_ui' => true,
				'show_tagcloud' => null,
				'labels' => $labels,
				'capabilities' => array('assign_terms' => 'edit_posts'),
				'show_in_nav_menus' => true,
		);
		
		//Merge Options With Passed Args
		$args = wp_parse_args($args, $options);
	    
		//Register Custom Post Type
		register_taxonomy( $slug, $object_type, $args );
	}
	
	function custom_icon()
	{
		if ($_GET['taxonomy'] == $this->slug):
			$icon_url = $this->icon;
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
	
	function template_redirection()
	{
		global $wp;
		$taxonomies = array($this->slug);

		if ( in_array( $wp->query_vars['taxonomy'], $taxonomies ) ):
			if ( is_robots() ):
				do_action('do_robots');
				return;
			elseif ( is_feed() ) :
				do_feed();
				return;
			elseif ( is_trackback() ) :
				include( ABSPATH . 'wp-trackback.php' );
				return;
			elseif($wp->query_vars['taxonomy']):
				$inc = STYLESHEETPATH . '/' . $wp->query_vars['taxonomy'] . '/' . $wp->query_vars['term'] . '.php';
				if ( file_exists( $inc ) ):
					include( $inc );
				else:
					include( STYLESHEETPATH . '/archive-'.$wp->query_vars['taxonomy'] . '.php' );
				endif;
				die();
			endif;
		elseif( in_array( $wp->request, $taxonomies ) ):
			include( STYLESHEETPATH . '/' . $wp->request . '.php');
			die();
		endif;
	}
}

?>