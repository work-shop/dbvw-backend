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


add_action('save_post', 'populate_the_content');

function populate_the_content($post_id) {
	if(get_post_type( $post_id ) != 'acf'){

	    //Check it's not an auto save routine
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return;

	    //Perform permission checks! For example:
		if ( !current_user_can('edit_post', $post_id) ) 
			return;

	    //Check your nonce!

	    //If calling wp_update_post, unhook this function so it doesn't loop infinitely
		remove_action('save_post', 'populate_the_content');

		$post_content = get_field('location', $post_id);

	    // call wp_update_post update, which calls save_post again. E.g:
		wp_update_post(array('ID' => $post_id, 'post_content' => $post_content));

	    // re-hook this function
		add_action('save_post', 'populate_the_content');

	}
}



?>