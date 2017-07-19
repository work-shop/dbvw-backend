<?php

/**
 * Table of Contents ( rooted at ./library/ )
 *
 * [1]. globals.php – defines independent, globally available php functions
 * [2] abstracts – abstract classes that build common structure
 * [2.1]. abstracts/class-abstract-action-set.php – a class that scaffolds the definition of action sets encapsulating functionality
 * [2.2]. abstracts/class-abstract-filter-set.php– a class that scaffolds the definition of filter sets encapsulating functionality
 * [3] classes – classes that encapsulate site functionality and site actions.
 * [3.1] classes/class-init-actions.php, a class that initializes state via actions on theme-load.
 * [3.2] classes/class-init-actions.php, a class that sets up init-specific filters on theme-load.
 *
 */

/* [1] */
require_once('library/globals.php');

/* [2] */
require_once('library/abstracts/abstract_action_set.php');
require_once('library/abstracts/abstract_filter_set.php');

/* [3] */
require_once( 'library/classes/class_init_actions.php' );
require_once( 'library/classes/class_init_filters.php');

require_once( 'library/custom_dashboard_setup.php');

require_once( 'library/search_augmentation.php');


add_action( 'rest_api_init', function () {
	register_rest_route( 'custom', '/relatedprojects', array(
		'methods'   =>  'GET',
		'callback'  =>  'get_related_projects',
		) );
});
function get_related_projects( $request ) {
	$project_category = $_GET['category'];
	$current_project = $_GET['current'];

	$posts = get_posts(
		array(
			'posts_per_page' => 3,
			'post_type' => 'projects',
			'exclude' => array($current_project),
			'ignore_custom_sort' => true,
			'orderby' => 'rand',
			'order'     => 'ASC',
			'tax_query' => array(
				array(
					'taxonomy' => 'project_categories',
					'field' => 'slug',
					'terms' => $project_category,
					)
				)
			)
		);

	if ( empty( $posts ) ) {
		return null;
	}

	foreach( $posts as $post ) {
		$post_id = $post->ID;
		$featured_image = get_the_post_thumbnail( $post_id );
		$post->createProperty('featured_image', $featured_image); 
	}


    //return $posts;

	return new WP_REST_Response( $posts, 200 );

}


// add_action( 'current_screen', 'print_screen' );

// function print_screen( $current_screen ){
// 	write_log($current_screen);
// }

// // require_once(ABSPATH . 'wp-admin/includes/screen.php');
// // $screen = get_current_screen();

// // write_log($screen->id);

// function write_log ( $log )  {
// 		if ( is_array( $log ) || is_object( $log ) ) {
// 			error_log( print_r( $log, true ) );
// 		} else {
// 			error_log( $log );
// 		}
// }

?>