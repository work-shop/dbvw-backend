<?php


class WS_Init_Actions extends WS_Action_Set {

	/**
	 * Constructor
	 */
	public function __construct() {
		show_admin_bar(false);

		parent::__construct(
			array(
				'init' 								=> 'setup',
				'wp_enqueue_scripts' 				=> 'enqueue_theme_assets',
				'after_theme_setup'					=> array( 'remove_post_formats', 11, 0 ),
				'login_head'						=> 'login_css',
				'admin_head'						=> 'admin_css'/*,
				'admin_menu'						=> 'remove_menus'*/
		));
	}

	/** POST TYPES */
	public function setup() {

		register_post_type( 'projects',
			array(
				'labels' => array(
					'name' => 'Projects',
					'singular_name' =>'Project',
					'add_new' => 'Add New',
				    'add_new_item' => 'Add New Project',
				    'edit_item' => 'Edit Project',
				    'new_item' => 'New Project',
				    'all_items' => 'All Project',
				    'view_item' => 'View Project',
				    'search_items' => 'Search Projects',
				    'not_found' =>  'No Projects found',
				    'not_found_in_trash' => 'No Projects found in Trash', 				
				),
				'public' => true,
				'has_archive' => true,
				'rewrite' => array('slug' => 'projects'),
				'show_in_rest'       => true,
  				'rest_base'          => 'projects',
  				'rest_controller_class' => 'WP_REST_Posts_Controller',				
				'supports' => array( 'title', 'thumbnail')
			));

		register_post_type( 'people',
			array(
				'labels' => array(
					'name' => 'People',
					'singular_name' =>'Person',
					'add_new' => 'Add New',
				    'add_new_item' => 'Add New Person',
				    'edit_item' => 'Edit Person',
				    'new_item' => 'New Person',
				    'all_items' => 'All Person',
				    'view_item' => 'View Person',
				    'search_items' => 'Search People',
				    'not_found' =>  'No People found',
				    'not_found_in_trash' => 'No People found in Trash', 				
				),
				'public' => true,
				'has_archive' => true,
				'rewrite' => array('slug' => 'people'),
				'show_in_rest'       => true,
  				'rest_base'          => 'people',
  				'rest_controller_class' => 'WP_REST_Posts_Controller',				
				'supports' => array( 'title', 'thumbnail')
			));

		register_post_type( 'about',
			array(
				'labels' => array(
					'name' => 'About',
					'singular_name' =>'About Page',
					'add_new' => 'Add New',
				    'add_new_item' => 'Add New About Page',
				    'edit_item' => 'Edit About Page',
				    'new_item' => 'New About Page',
				    'all_items' => 'All About Page',
				    'view_item' => 'View About Page',
				    'search_items' => 'Search About Pages',
				    'not_found' =>  'No About Pages found',
				    'not_found_in_trash' => 'No About Pages found in Trash', 				
				),
				'public' => true,
				'has_archive' => true,
				'rewrite' => array('slug' => 'about'),
				'show_in_rest'       => true,
  				'rest_base'          => 'about',
  				'rest_controller_class' => 'WP_REST_Posts_Controller',				
				'supports' => array( 'title', 'thumbnail')
			));		


		if ( function_exists( 'add_theme_support' ) ) {
			add_theme_support( 'post-thumbnails' );
		    	set_post_thumbnail_size( 600, 400, true ); 
		}
		if ( function_exists( 'add_image_size' ) ) {  
			add_image_size( 'hero', 1440, 768, true );  					
		}

	}


	/** THEME ASSETS */
	public function enqueue_theme_assets() {
		$this->enqueue_theme_styles();
		$this->enqueue_theme_scripts();
	}

	private function enqueue_theme_scripts() {
		if (!file_exists( dirname( __FILE__ ) . '/env_prod' )){	
		}		
	}

	private function enqueue_theme_styles() { 

	}	


	/** REMOVE NATIVE POST FORMATS */
	public function remove_post_formats() { remove_theme_support('post-formats'); }


	/** ADMIN DASHBOARD ASSETS */
	public function login_css() { wp_enqueue_style( 'login_css', get_template_directory_uri() . '/assets/css/login.css' ); }
	public function admin_css() { wp_enqueue_style( 'admin_css', get_template_directory_uri() . '/assets/css/admin.css' ); }


	/** MENU SETTINGS */
	public function remove_menus () {
		global $menu;

		$restricted = array( __('Appearance'),__('Comments'),__('Posts'),__('Pages'),__('Settings') );
		end ($menu);

		while (prev($menu)){
			$value = explode(' ',$menu[key($menu)][0]);
			if(in_array($value[0] != NULL?$value[0]:"" , $restricted)){unset($menu[key($menu)]);}
		}

		$this->remove_acf_menu();
	}


	private function remove_acf_menu(){
	    // provide a list of usernames who can edit custom field definitions here
	    $admins = array( 
	        'dev','greg','nic'
	    );
	 
	    // get the current user
	    $current_user = wp_get_current_user();
	 
	    // match and remove if needed
	    if( !in_array( $current_user->user_login, $admins ) )
	    {
	        remove_menu_page('edit.php?post_type=acf');
	    }
	 
	}

}


new WS_Init_Actions();



