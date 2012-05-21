<?php
/**
 * Uninstall.
 * Clean up the WP DB by deleting the options created by the plugin.
 */
function post_snippets_uninstall()
{
	// Delete all snippets
	delete_option('post_snippets_options');

	// Delete any per user settings 
	global $wpdb;
	$wpdb->query(
		"
		DELETE FROM $wpdb->usermeta 
		WHERE meta_key = 'post_snippets'
		"
	);
}

post_snippets_uninstall();
