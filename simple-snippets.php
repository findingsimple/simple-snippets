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

if ( is_admin() )
	require( plugin_dir_path( __FILE__ ) . 'classes/help.php' );

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
 * Plugin Main Class.
 *
 * @package Simple Snippets
 * @author Johan Steen <artstorm at gmail dot com>
 * @since 1.0
 */
class Simple_Snippets {
	// Constants
	const TINYMCE_PLUGIN_NAME = 'post_snippets';

	const POST_TYPE = 'snippet';

	// -------------------------------------------------------------------------

	public function __construct() {
		global $wp_version;

		// Add TinyMCE button
		add_action( 'init', array( &$this, 'add_tinymce_button' ) );

		add_action( 'init', array( &$this, 'register_post_type' ) );

		$this->create_shortcodes();

		add_action( 'admin_init', array( &$this, 'enqueue_assets' ) );
		add_action( 'admin_head', array( &$this, 'jquery_ui_dialog' ) );
		add_action( 'admin_footer', array( &$this, 'add_jquery_ui_dialog' ) );
		add_action( 'add_meta_boxes', array( &$this, 'add_remove_meta_boxes' ) );

		add_action( 'admin_print_footer_scripts', array( &$this, 'add_quicktag_button' ), 100 );

		add_action( 'save_post', array( &$this, 'save_snippet_variables' ) );
	}

	public function save_snippet_variables( $post_id ) {
		if ( ! current_user_can( 'edit_post', $post_id ) && ! current_user_can( 'edit_page', $post_id ) )
			return $post_id;

		if ( isset( $_POST['_snippet_variables'] ) )
			update_post_meta( $post_id, '_snippet_variables', $_POST['_snippet_variables'] );

	}

	/**
	 * Performs a few metabox related functions for the edit snippet page. 
	 */
	public function add_remove_meta_boxes() {

		// Rename the excerpt metabox
		remove_meta_box( 'postexcerpt', self::POST_TYPE, 'normal' );
		add_meta_box( 'snippet_description', __( 'Description' ), array( &$this, 'snippet_description_meta_box' ), self::POST_TYPE, 'side', 'core' );

		// Metabox for variables
		add_meta_box( 'snippet_variables', __( 'Variables' ), array( &$this, 'snippet_variables_meta_box' ), self::POST_TYPE, 'side', 'low' );
	}

	/**
	 * Adds the description metabox to the edit snippet page
	 */
	public function snippet_description_meta_box( $post ) { ?>
		<p><?php _e( 'A description for this snippet displayed when adding the snippet to a page/post.' ); ?></p>
		<label class="screen-reader-text" for="excerpt"><?php _e( 'Description' ) ?></label>
		<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt"><?php echo esc_attr( $post->post_excerpt ); ?></textarea>
	<?php
	}

	/**
	 * Adds the variables metabox to the edit snippet page
	 */
	public function snippet_variables_meta_box( $post ) { ?>
		<p><?php _e( 'Add variables in the form var_name="var value". Separate variables with a comma. To use a variable in your snippet, add the variable to your snippet between {}.' ); ?></p>
		<label class="screen-reader-text" for="_snippet_variables"><?php _e( 'Variables' ) ?></label>
		<input type="text" name="_snippet_variables" tabindex="8" id="_snippet_variables" value="<?php echo esc_attr( get_post_meta( $post->ID, '_snippet_variables', true ) ); ?>"/>
	<?php
	}

	/**
	 * Registers the Snippet post type
	 */
	function register_post_type() {
		$labels               = array(
			'name'               => _x( 'Snippets', 'post type general name' ),
			'singular_name'      => _x( 'Snippet', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'book' ),
			'add_new_item'       => __( 'Add New Snippet' ),
			'edit_item'          => __( 'Edit Snippet' ),
			'new_item'           => __( 'New Snippet' ),
			'all_items'          => __( 'All Snippets' ),
			'view_item'          => __( 'View Snippet' ),
			'search_items'       => __( 'Search Snippets' ),
			'not_found'          => __( 'No snippets found' ),
			'not_found_in_trash' => __( 'No snippets found in Trash' ), 
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Snippets' )
		);

		$args                 = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true, 
			'show_in_menu'       => true, 
			'query_var'          => false,
			'rewrite'            => true,
			'capability_type'    => 'post',
			'has_archive'        => true, 
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'author', 'excerpt', 'revisions' )
		); 

		register_post_type( self::POST_TYPE, $args );
	}


	/**
	 * Enqueues the necessary scripts and styles for the plugins
	 *
	 * @since 1.0
	 */
	function enqueue_assets() {
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		# Adds the CSS stylesheet for the jQuery UI dialog
		wp_enqueue_style( 'post-snippets', plugins_url( '/assets/post-snippets.css', fs_get_snippet_plugin_file() ) );
	}
	

	// -------------------------------------------------------------------------
	// WordPress Editor Buttons
	// -------------------------------------------------------------------------

	/**
	 * Add TinyMCE button.
	 *
	 * Adds filters to add custom buttons to the TinyMCE editor (Visual Editor)
	 * in WordPress.
	 *
	 * @since 1.0
	 */
	public function add_tinymce_button() {
		// Don't bother doing this stuff if the current user lacks permissions
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) )
			return;

		// Add only in Rich Editor mode
		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'mce_external_plugins', array( &$this, 'register_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( &$this, 'register_tinymce_button' ) );
		}
	}

	/**
	 * Register TinyMCE button.
	 *
	 * Pushes the custom TinyMCE button into the array of with button names.
	 * 'separator' or '|' can be pushed to the array as well. See the link
	 * for all available TinyMCE controls.
	 *
	 * @see		wp-includes/class-wp-editor.php
	 * @link	http://www.tinymce.com/wiki.php/Buttons/controls
	 * @since 1.0
	 *
	 * @param	array	$buttons	Filter supplied array of buttons to modify
	 * @return	array				The modified array with buttons
	 */
	public function register_tinymce_button( $buttons ) {
		array_push( $buttons, 'separator', self::TINYMCE_PLUGIN_NAME );
		return $buttons;
	}

	/**
	 * Register TinyMCE plugin.
	 *
	 * Adds the absolute URL for the TinyMCE plugin to the associative array of
	 * plugins. Array structure: 'plugin_name' => 'plugin_url'
	 *
	 * @see		wp-includes/class-wp-editor.php
	 * @since 1.0
	 *
	 * @param	array	$plugins	Filter supplied array of plugins to modify
	 * @return	array				The modified array with plugins
	 */
	public function register_tinymce_plugin( $plugins ) {
		// Load the TinyMCE plugin, editor_plugin.js, into the array
		$plugins[self::TINYMCE_PLUGIN_NAME] = plugins_url( '/tinymce/editor_plugin.js?ver=1.9', fs_get_snippet_plugin_file() );

		return $plugins;
	}

	/**
	 * Adds a QuickTag button to the HTML editor.
	 *
	 * Compatible with WordPress 3.3 and newer.
	 *
	 * @see			wp-includes/js/quicktags.dev.js -> qt.addButton()
	 * @since 1.0
	 */
	public function add_quicktag_button() {
		// Only run the function on post edit screens
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen->base != 'post')
				return;
		}

		echo "\n<!-- START: Add QuickTag button for Snippets -->\n";
		?>
		<script type="text/javascript" charset="utf-8">
			QTags.addButton( 'post_snippets_id', 'snippet', qt_post_snippets );
			function qt_post_snippets() {
				post_snippets_caller = 'html';
				jQuery( "#post-snippets-dialog" ).dialog( "open" );
			}
		</script>
		<?php
		echo "\n<!-- END: Add QuickTag button for Post Snippets -->\n";
	}


	// -------------------------------------------------------------------------
	// JavaScript / jQuery handling for the post editor
	// -------------------------------------------------------------------------

	/**
	 * jQuery control for the dialog and Javascript needed to insert snippets into the editor
	 *
	 * @since 1.0
	 */
	public function jquery_ui_dialog() {
		// Only run the function on post edit screens
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ($screen->base != 'post')
				return;
		}

		echo "\n<!-- START: Post Snippets jQuery UI and related functions -->\n";
		echo "<script type='text/javascript'>\n";
		
		# Prepare the snippets and shortcodes into javascript variables
		# so they can be inserted into the editor, and get the variables replaced
		# with user defined strings.
		$snippets = $this->get_snippets();
		foreach ($snippets as $key => $snippet) {
			# Build a long string of the variables, ie: varname1={varname1} varname2={varname2} so {varnameX} can be replaced at runtime.
			$var_arr = explode( ",", $snippet->variables );
			$variables = '';
			if ( ! empty( $var_arr ) ) {
				foreach ( $var_arr as $var ) {
					$var = $this->strip_default_val( $var );
					$variables .= ' ' . $var . '="{' . $var . '}"';
				}
			}
			$shortcode = $snippet->post_name . $variables;
			echo "var postsnippet_{$key} = '[" . $shortcode . "]';\n";
		}
		?>
		
		jQuery(document).ready(function($){
			<?php
			# Create js variables for all form fields
			foreach ( $snippets as $key => $snippet ) {
				$var_arr = explode( ",", $snippet->variables );
				if ( ! empty( $var_arr ) ) {
					foreach ($var_arr as $key_2 => $var) {
						$varname = "var_" . $key . "_" . $key_2;
						echo "var {$varname} = $( \"#{$varname}\" );\n";
					}
				}
			}
			?>
			
			var $tabs = $("#post-snippets-tabs").tabs();
			
			$(function() {
				$( "#post-snippets-dialog" ).dialog({
					autoOpen: false,
					modal: true,
					dialogClass: 'wp-dialog',
					buttons: {
						Cancel: function() {
							$( this ).dialog( "close" );
						},
						"Insert": function() {
							$( this ).dialog( "close" );
							var selected = $tabs.tabs('option', 'selected');
							<?php
							foreach ($snippets as $key => $snippet) {
							?>
								if (selected == <?php echo $key; ?>) {
									insert_snippet = postsnippet_<?php echo $key; ?>;
									<?php
									$var_arr = explode(",",$snippet->variables);
									if (!empty($var_arr[0])) {
										foreach ($var_arr as $key_2 => $var) {
											$varname = "var_" . $key . "_" . $key_2; ?>
											insert_snippet = insert_snippet.replace(/\{<?php echo $this->strip_default_val( $var ); ?>\}/g, <?php echo $varname; ?>.val());
									<?php
											echo "\n";
										}
									}
									?>
								}
							<?php
							}
							?>

							// Decide what method to use to insert the snippet depending on which editor the window was opened from
							if (post_snippets_caller == 'html') {
								// HTML editor in WordPress 3.3 and greater
								QTags.insertContent(insert_snippet);
							} else if (post_snippets_caller == 'html_pre33') {
								// HTML editor in WordPress below 3.3.
								edInsertContent(post_snippets_canvas, insert_snippet);
							} else {
								// Visual Editor
								post_snippets_canvas.execCommand('mceInsertContent', false, insert_snippet);
							}

						}
					},
					width: 500,
				});
			});
		});

// Global variables to keep track on the canvas instance and from what editor that opened the Post Snippets popup.
var post_snippets_canvas;
var post_snippets_caller = '';

<?php
		echo "</script>\n";
		echo "\n<!-- END: Post Snippets jQuery UI and related functions -->\n";
	}

	/**
	 * Build jQuery UI Window.
	 *
	 * Creates the jQuery for Post Editor popup window, its snippet tabs and the
	 * form fields to enter variables.
	 *
	 * @since 1.0
	 */
	public function add_jquery_ui_dialog() {
		// Only run the function on post edit screens
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen->base != 'post' )
				return;
		}

		echo "\n<!-- START: Post Snippets UI Dialog -->\n";
		// Setup the dialog divs
		echo "<div class=\"hidden\">\n";
		echo "\t<div id=\"post-snippets-dialog\" title=\"Post Snippets\">\n";
		// Init the tabs div
		echo "\t\t<div id=\"post-snippets-tabs\">\n";
		echo "\t\t\t<ul>\n";

		// Create a tab for each available snippet
		$snippets = $this->get_snippets();
		foreach ($snippets as $key => $snippet) {
			echo "\t\t\t\t";
			echo "<li><a href=\"#ps-tabs-{$key}\">{$snippet->post_title}</a></li>";
			echo "\n";
		}
		echo "\t\t\t</ul>\n";

		// Create a panel with form fields for each available snippet
		foreach ( $snippets as $key => $snippet ) {
			echo "\t\t\t<div id=\"ps-tabs-{$key}\">\n";

			// Print a snippet description if available
			if ( ! empty( $snippet->post_excerpt ) )
				echo "\t\t\t\t<p class=\"howto\">" . $snippet->post_excerpt . "</p>\n";

			// Get all variables defined for the snippet and output them as input fields
			$var_arr = explode( ',', $snippet->variables );
			if (!empty($var_arr[0])) {
				foreach ($var_arr as $key_2 => $var) {
					// Default value exists?
					$def_pos = strpos( $var, '=' );
					if ( $def_pos !== false ) {
						$split = explode( '=', $var );
						$var = $split[0];
						$def = esc_attr( $split[1] );
					} else {
						$def = '';
					}
					echo "\t\t\t\t<label for=\"var_{$key}_{$key_2}\">{$var}:</label>\n";
					echo "\t\t\t\t<input type=\"text\" id=\"var_{$key}_{$key_2}\" name=\"var_{$key}_{$key_2}\" value=\"{$def}\" style=\"width: 190px\" />\n";
					echo "\t\t\t\t<br/>\n";
				}
			} else {
				// If no variables and no description available, output a text
				// to inform the user that it's an insert snippet only.
				if ( empty( $snippet->post_excerpt ) )
					echo "\t\t\t\t<p class=\"howto\">" . __('This snippet is insert only, no variables defined.', 'post-snippets') . "</p>\n";
			}
			echo "\t\t\t</div><!-- #ps-tabs-{$key} -->\n";
		}
		// Close the tabs and dialog divs
		echo "\t\t</div><!-- #post-snippets-tabs -->\n";
		echo "\t</div><!-- #post-snippets-dialog -->\n";
		echo "</div><!-- .hidden -->\n";

		echo "<!-- END: Post Snippets UI Dialog -->\n\n";
	}

	/**
	 * Strip Default Value.
	 *
	 * Checks if a variable string contains a default value, and if it does it 
	 * will strip it away and return the string with only the variable name
	 * kept.
	 *
	 * @since 1.0
	 * @param	string	$variable	The variable to check for default value
	 * @return	string				The variable without any default value
	 */
	public function strip_default_val( $variable ) {
		// Check if variable contains a default defintion
		$def_pos = strpos( $variable, '=' );

		if ( $def_pos !== false ) {
			$split = str_split( $variable, $def_pos );
			$variable = $split[0];
		}
		return $variable;
	}

	// -------------------------------------------------------------------------
	// Shortcode
	// -------------------------------------------------------------------------

	/**
	 * Create the functions for shortcodes dynamically and register them
	 */
	function create_shortcodes() {
		$snippets = $this->get_snippets();
		if (!empty($snippets)) {
			foreach ($snippets as $snippet) {
				// If shortcode is enabled for the snippet, and a snippet has been entered, register it as a shortcode.
				if ( ! empty( $snippet->post_content ) ) {

					$vars = explode( ",", $snippet->variables );

					$vars_str = '';

					foreach ( $vars as $var ) {
						if ( strpos( $var, '=' ) !== false ) {
							$var_and_key = split( '=', $var );
							$key = $var_and_key[0];
							$var = addslashes( $var_and_key[1] );
						} else {
							$key = $var;
							$var = '';
						}
						$vars_str = $vars_str . '"'.$key.'" => "'.$var.'",';
					}

					add_shortcode( $snippet->post_name, create_function( '$atts, $content=null', 
								'$shortcode_symbols = array('.$vars_str.');

								extract( shortcode_atts( $shortcode_symbols, $atts ) );

								$attributes = compact( array_keys( $shortcode_symbols ) );

								// Add enclosed content if available to the attributes array
								if ( $content != null )
									$attributes["content"] = $content;

								$snippet = \''. addslashes( wpautop( $snippet->post_content ) ) .'\';
								$snippet = str_replace( "&", "&amp;", $snippet );

								foreach ( $attributes as $key => $val )
									$snippet = str_replace( "{".$key."}", $val, $snippet );

								// Strip escaping and execute nested shortcodes
								$snippet = do_shortcode( stripslashes( $snippet ) );

								return $snippet;') );
				}
			}
		}
	}


	/**
	 * Returns an associative array of all snippets. 
	 *
	 * @since 1.0
	 * @return array $post_name => $post_content
	 */
	public static function get_snippets(){
		$snippets = get_posts( array( 'post_type' => self::POST_TYPE ) );

		foreach ( $snippets as $key => $snippet )
			$snippets[$key]->variables = get_post_meta( $snippet->ID, '_snippet_variables', true);

		return $snippets;
	}

}
