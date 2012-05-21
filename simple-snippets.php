<?php
/*
Plugin Name: Simple Snippets
Plugin URI: http://plugins.findingsimple.com
Description: Build a library of HTML snippets.
Version: 1.0
Author: _FindingSimple
Author URI: http://findingsimple.com
License: GPL2
*/
/*
Copyright 2012  Jason Conroy  (email : plugins@findingsimple.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/*
 * Based on the Post Snippets plugin by Johan Steen: http://wordpress.org/extend/plugins/post-snippets/
 */

if ( ! defined( 'FS_SNIPPETS_OPTION_KEY' ) )
	define( 'FS_SNIPPETS_OPTION_KEY', 'simple_snippets' );

require plugin_dir_path( __FILE__ ) . 'classes/snippets.php';

if ( is_admin() ) {
	require plugin_dir_path( __FILE__ ) . 'classes/settings.php';
	require plugin_dir_path( __FILE__ ) . 'classes/help.php';
}

function fs_init_post_snippets(){
	global $post_snippets;

	$post_snippets = new Simple_Snippets();

}
add_action( 'plugins_loaded', 'fs_init_post_snippets' );


/**
 * Get __FILE__ with no symlinks.
 *
 * @since 1.0
 * @return The __FILE__ constant without resolved symlinks.
 */
function fs_get_snippet_plugin_file(){
	return __FILE__;
}


/**
 * Allow snippets to be retrieved directly from PHP.
 * This function is a wrapper for Simple_Snippets::get_snippet().
 *
 * @since 1.0
 * @param string $snippet_name The name of the snippet to retrieve
 * @param string $snippet_vars The variables to pass to the snippet, formatted as a query string.
 * @return string The Snippet
 */
function fs_get_post_snippet( $snippet_name, $snippet_vars = '' ) {
	global $post_snippets;
	return $post_snippets->get_snippet( $snippet_name, $snippet_vars );
}
