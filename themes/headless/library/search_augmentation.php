<?php


// add_action('save_post', 'populate_the_content');

// function populate_the_content($post_id) {
// 	if(get_post_type( $post_id ) != 'acf'){

// 	    //Check it's not an auto save routine
// 		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
// 			return;

// 	    //Perform permission checks! For example:
// 		if ( !current_user_can('edit_post', $post_id) ) 
// 			return;

// 	    //Check your nonce!

// 	    //If calling wp_update_post, unhook this function so it doesn't loop infinitely
// 		remove_action('save_post', 'populate_the_content');


// 		$post_content = get_field('location', $post_id);


// 	    // call wp_update_post update, which calls save_post again. E.g:
// 		wp_update_post(array('ID' => $post_id, 'post_content' => $post_content));

// 	    // re-hook this function
// 		add_action('save_post', 'populate_the_content');

// 	}
// }


// function project(){
	
// }


?>