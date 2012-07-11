<?php
/*
Plugin Name: Simple Snippets
Plugin URI: http://plugins.findingsimple.com
Description: Build a library of HTML snippets.
Version: 1.0
Author: Finding Simple ( Jason Conroy & Brent Shepherd)
Author URI: http://findingsimple.com
License: GPL2
*/
/*
Copyright 2012  Finding Simple  (email : plugins@findingsimple.com)

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

if ( ! class_exists( 'Simple_Snippets' ) ) :

/**
 * Plugin Main Class.
 *
 * @package Simple Snippets
 * @author Brent Shepherd <brent@findingsimple.com>
 * @since 1.0
 */
class Simple_Snippets {

	const TINYMCE_PLUGIN_NAME = 'simple_snippets';

	static $text_domain;

	static $post_type_name;

	static $admin_screen_id;

	static $snippets;

	public static function init() {
		global $wp_version;

		self::$text_domain = apply_filters( 'simple_snippets_text_domain', 'Simple_Snippets' );

		self::$post_type_name = apply_filters( 'simple_snippets_post_type_name', 'snippet' );

		self::$admin_screen_id = apply_filters( 'simple_snippets_admin_screen_id', 'snippet' );

		add_action( 'init', array( __CLASS__, 'register_post_type' ) );

		self::create_shortcodes();

		add_action( 'admin_init', array( __CLASS__, 'add_tinymce_button' ) );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_styles_and_scripts' ) );

		add_action( 'admin_footer', array( __CLASS__, 'snippet_dialog_markup' ) );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_remove_meta_boxes' ) );

		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'add_quicktag_button' ), 100 );

		add_action( 'save_post', array( __CLASS__, 'save_snippet_meta' ) );

		// In context help 
		add_action( 'load-post.php', array( __CLASS__, 'add_help_tabs' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'add_help_tabs' ) );
	}

	/**
	 * When a snippet post type is saved, also save the meta associated with the snippet, like the variables 
	 * and whether the snippet should be used as a shortcode or HTML.
	 * 
	 * @param $post_id int The ID of the snippet post the variables should be saved for.
	 * @since 1.0
	 */
	public static function save_snippet_meta( $post_id ) {

		if ( ! current_user_can( 'edit_post', $post_id ) && ! current_user_can( 'edit_page', $post_id ) )
			return $post_id;

		/* Meta not submitted */
		if ( ! isset( $_POST['_snippets_nonce'] ) || ! wp_verify_nonce( $_POST['_snippets_nonce'], __FILE__ ) )
			return $post_id;

		/* Defaults */
		$snippet_variables    = ( isset( $_POST['_snippet_variables'] ) ) ? $_POST['_snippet_variables'] : array();
		$snippet_is_shortcode = ( isset( $_POST['_snippet_is_shortcode'] ) && 'on' == $_POST['_snippet_is_shortcode'] ) ? 'true' : 'false';

		/* Clear any empty variables (variables need a name but not a default value) */
		foreach ( $snippet_variables as $key => $variable_array ) {
			if ( empty( $variable_array['variable_name'] ) )
				unset( $snippet_variables[$key] );
			else
				$snippet_variables[$key]['variable_name'] = self::sanitize_variable_name( $variable_array['variable_name'] );
		}

		update_post_meta( $post_id, '_snippet_variables', $snippet_variables );
		update_post_meta( $post_id, '_snippet_is_shortcode', $snippet_is_shortcode );

	}

	/**
	 * Performs a few metabox related functions for the edit snippet page. 
	 * 
	 * @since 1.0
	 */
	public static function add_remove_meta_boxes() {
		// Rename the excerpt metabox
		remove_meta_box( 'postexcerpt', self::$post_type_name, 'normal' );
		add_meta_box( 'snippet_details', __( 'Snippet Details', self::$text_domain ), array( __CLASS__, 'snippet_details_meta_box' ), self::$post_type_name, 'side', 'core' );
	}

	/**
	 * Adds the description metabox to the edit snippet page
	 * 
	 * @since 1.0
	 */
	public static function snippet_details_meta_box( $post ) { 

		$is_shortcode = ( in_array( get_post_meta( $post->ID, '_snippet_is_shortcode', true ), array( '', 'true' ) ) ) ? 'true' : 'false';

		wp_nonce_field( __FILE__, '_snippets_nonce' );

		$snippet_variables = get_post_meta( $post->ID, '_snippet_variables', true );

		if ( empty( $snippet_variables ) )
			$snippet_variables = array( array( 'variable_name' => '', 'variable_default' => '' ) );

		?>
		<p><?php _e( 'An optional description for display when inserting the snippet.', self::$text_domain ); ?></p>
		<label class="screen-reader-text" for="excerpt"><?php _e( 'Description', self::$text_domain ) ?></label>
		<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt"><?php echo esc_attr( $post->post_excerpt ); ?></textarea>

		<h4><?php _e( 'Shortcode' ); ?></h4>

		<label for="_snippet_is_shortcode">
			<input type="checkbox" name="_snippet_is_shortcode" id="_snippet_is_shortcode" <?php checked( $is_shortcode, 'true' ); ?>/>
			<?php _e( 'Use snippet as a shortcode', self::$text_domain ) ?>
		</label>

		<h4><?php _e( 'Variables' ); ?></h4>

		<fieldset id="snippet-variables">
			<?php foreach ( $snippet_variables as $index => $snippet_variable ) : ?>
			<fieldset class="snippet-variable">
				<label for="_snippet_variables[<?php echo $index; ?>][variable_name]"><?php _e( 'Variable Name:', self::$text_domain ) ?>
					<input type="text" name="_snippet_variables[<?php echo $index; ?>][variable_name]" id="_snippet_variables[<?php echo $index; ?>][variable_name]" value="<?php echo $snippet_variable['variable_name']; ?>" />
				</label>
				<label for="_snippet_variables[<?php echo $index; ?>][variable_default]"><?php _e( 'Default Value/s:', self::$text_domain ) ?>
					<input type="text" name="_snippet_variables[<?php echo $index; ?>][variable_default]" id="_snippet_variables[<?php echo $index; ?>][variable_default]" value="<?php echo $snippet_variable['variable_default']; ?>" />
				</label>
			</fieldset>
			<?php endforeach; ?>
		</fieldset>

		<input type="button" id="snippet_variable_adder" value="<?php _e( 'Add a Variable', self::$text_domain ); ?>" class="button"/>

<script type="text/javascript">
jQuery(document).ready(function($){
	$('#snippet_variable_adder').click(function(){
		var elementCount = $('#snippet-variables fieldset').length,
			oldElementID = elementCount - 1,
			newFieldset  = $('#snippet-variables fieldset:last').clone();

		// Clear values
		newFieldset.find('input').val('');

		// Update the attributes
		$.each(['variable_name','variable_default'],function(index,keyName){
			newFieldset.find('[name="_snippet_variables['+oldElementID+']['+keyName+']"]').attr({
				'id': '_snippet_variables['+elementCount+']['+keyName+']',
				'name': '_snippet_variables['+elementCount+']['+keyName+']'
			});
			newFieldset.find('[for="_snippet_variables['+oldElementID+']['+keyName+']"]').attr({
				'for': '_snippet_variables['+elementCount+']['+keyName+']'
			});
		});

		$('#snippet-variables fieldset:last').after(newFieldset);
	});
});
</script>
<?php
	}

	/**
	 * Registers the Snippet post type
	 * 
	 * @since 1.0
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => _x( 'Snippets', 'post type general name', self::$text_domain ),
			'singular_name'      => _x( 'Snippet', 'post type singular name', self::$text_domain ),
			'add_new'            => _x( 'Add New', 'book' ),
			'add_new_item'       => __( 'Add New Snippet', self::$text_domain ),
			'edit_item'          => __( 'Edit Snippet', self::$text_domain ),
			'new_item'           => __( 'New Snippet', self::$text_domain ),
			'all_items'          => __( 'All Snippets', self::$text_domain ),
			'view_item'          => __( 'View Snippet', self::$text_domain ),
			'search_items'       => __( 'Search Snippets', self::$text_domain ),
			'not_found'          => __( 'No snippets found', self::$text_domain ),
			'not_found_in_trash' => __( 'No snippets found in Trash', self::$text_domain ), 
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Snippets', self::$text_domain )
		);

		$args = array(
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

		register_post_type( self::$post_type_name, $args );
	}

	/**
	 * Enqueues the necessary scripts and styles for the plugins
	 *
	 * @since 1.0
	 */
	public static function enqueue_styles_and_scripts() {

		$screen = get_current_screen();

		if ( $screen->base == 'post' ) {

			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_script( 'jquery-ui-tabs' );

			wp_enqueue_style( 'wp-jquery-ui-dialog' );
			wp_enqueue_style( 'snippets', self::get_url( '/css/admin.css' ) );

			/* Prepare the snippets and shortcodes into javascript variables so they can be inserted into the editor and get the variables replaced with user defined strings. */
			$snippets = self::get_snippets();

			foreach ( $snippets as $key => $snippet ) {

				$variable_string = '';

				if ( empty( $snippet->variables ) ) {

					$snippet_data['variables'][$snippet->post_name] = array();

				} else {
					/* Build an array of variable names, defaults & a shortcode string for each variable. */

					foreach ( $snippet->variables as $key_2 => $variable ) {

						$snippet_data['variables'][$snippet->post_name][self::sanitize_variable_name( $variable['variable_name'] )] = $variable['variable_default'];

						$variable_string .= ' ' . self::sanitize_variable_name( $variable['variable_name'] ) . '="{' . self::sanitize_variable_name( $variable['variable_name'] ) . '}"';
					}
				}

				if ( $snippet->is_shortcode != 'false' ) {

					$snippet_data['contentToInsert'][$snippet->post_name] = '[' . $snippet->post_name . $variable_string . ']';

				} else {

					$snippet_data['contentToInsert'][$snippet->post_name] = str_replace( ']]>', ']]&gt;', apply_filters( 'the_content', $snippet->post_content ) );

				}

				$snippet_data['contentToInsert'][$snippet->post_name] = json_encode( $snippet_data['contentToInsert'][$snippet->post_name] );

			}

			wp_enqueue_script( 'snippets', self::get_url( '/js/snippets.js' ) );

			wp_localize_script( 'snippets', 'SnippetData', $snippet_data );

		}

	}


	/* TinyMCE */

	/**
	 * Add TinyMCE Snippet button.
	 *
	 * @since 1.0
	 */
	public static function add_tinymce_button() {

		// Don't bother doing this stuff if the current user lacks permissions
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) )
			return;

		// Add only in Rich Editor mode
		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'mce_external_plugins', array( __CLASS__, 'register_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( __CLASS__, 'register_tinymce_button' ) );
		}

	}

	/**
	 * Register a TinyMCE button.
	 *
	 * @see wp-includes/class-wp-editor.php
	 * @link http://www.tinymce.com/wiki.php/Buttons/controls
	 * @since 1.0
	 */
	public static function register_tinymce_button( $buttons ) {

		array_push( $buttons, 'separator', self::TINYMCE_PLUGIN_NAME );

		return $buttons;
	}

	/**
	 * Adds the URL of the Snippet TinyMCE plugin to the associative array of all TinyMCE plugins.
	 *
	 * @see wp-includes/class-wp-editor.php
	 * @since 1.0
	 */
	public static function register_tinymce_plugin( $plugins ) {

		$plugins[self::TINYMCE_PLUGIN_NAME] = self::get_url( '/tinymce/editor_plugin.js', __FILE__ );

		return $plugins;
	}

	/**
	 * Adds a QuickTag button to the HTML editor.
	 *
	 * @see wp-includes/js/quicktags.dev.js -> qt.addButton()
	 * @since 1.0
	 */
	public static function add_quicktag_button() {

		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen->base != 'post' )
				return;
		}
?>
<script type="text/javascript" charset="utf-8">
QTags.addButton('post_snippets_id', 'snippet', function() {
	post_snippets_caller = 'html';
	jQuery( "#snippets-dialog" ).dialog( "open" );
});
</script>
<?php
	}

	/**
	 * Build jQuery UI Window.
	 *
	 * Creates the jQuery for Post Editor popup window, its snippet tabs and the
	 * form fields to enter variables.
	 *
	 * @since 1.0
	 */
	public static function snippet_dialog_markup() {

		$screen = get_current_screen();

		if ( $screen->base != 'post' )
			return;
?>
<div class="hidden">
	<div id="snippets-dialog" title="Snippets">
		<div id="snippets-tabs">
			<ul>
			<?php $snippets = self::get_snippets(); ?>
			<?php foreach ( $snippets as $key => $snippet ) : ?>
				<li><a href="#snippet-tab-<?php echo $snippet->post_name; ?>"><?php echo $snippet->post_title; ?></a></li>
			<?php endforeach; ?>
			</ul>

			<?php foreach ( $snippets as $key => $snippet ) : ?>
			<div id="snippet-tab-<?php echo $snippet->post_name; ?>" class="snippet-tab">

				<?php if ( ! empty( $snippet->post_excerpt ) ) : ?>
				<p class="howto"><?php echo $snippet->post_excerpt; ?></p>
				<?php endif; ?>

				<?php foreach ( $snippet->variables as $key_2 => $variable ) : ?>

				<?php $var_name = $snippet->post_name . '_' . self::sanitize_variable_name( $variable['variable_name'] ); ?>

				<?php if ( 1 == preg_match( '/^\{.*?\}$/', $variable['variable_default'] ) ) : // We want a select box ?>
				<label for="<?php echo $var_name; ?>"><?php echo $variable['variable_name'] ?>:</label>
				<select id="<?php echo $var_name; ?>" name="<?php echo $var_name; ?>">
					<?php foreach ( explode( ',', substr( $variable['variable_default'], 1, -1 ) ) as $value ) : ?>
					<option value="<?php echo $value; ?>"><?php echo $value; ?></option>
					<?php endforeach; ?>
				</select>
				<?php else : ?>
				<label for="<?php echo $var_name; ?>"><?php echo $variable['variable_name'] ?>:
					<input type="text" id="<?php echo $var_name; ?>" name="<?php echo $var_name; ?>" value="<?php echo $variable['variable_default'] ?>" />
				</label>
				<?php endif; ?>
				<?php endforeach; ?>
			</div><!-- #ps-tabs-<?php echo $key; ?> -->
			<?php endforeach; ?>

		</div><!-- #snippets-tabs -->
	</div><!-- #snippets-dialog -->
</div><!-- .hidden -->
<?php
	}


	/* Shortcode */

	/**
	 * Create the functions for shortcodes dynamically and register them
	 *
	 * @since 1.0
	 */
	public static function create_shortcodes() {

		$snippets = self::get_snippets();

		foreach ( $snippets as $snippet ) {
			// If shortcode is enabled for the snippet, and a snippet has been entered, register it as a shortcode.
			if ( ! empty( $snippet->post_content ) && $snippet->is_shortcode !== 'false' )
				add_shortcode( $snippet->post_name, array( __CLASS__, 'shortcode_callback' ) );
		}
	}

	/**
	 * Generates the content for a snippet's shortcode
	 *
	 * @since 1.0
	 */
	public static function shortcode_callback( $atts, $content = null, $callback ) {

		$snippets = self::get_snippets();

		$shortcode_symbols = array();

		foreach( $snippets[$callback]->variables as $variable )
			$shortcode_symbols[$variable['variable_name']] = $variable['variable_default'];

		extract( shortcode_atts( $shortcode_symbols, $atts ) );

		$attributes = compact( array_keys( $shortcode_symbols ) );

		// Add enclosed content if available to the attributes array
		if ( $content != null )
			$attributes["content"] = $content;

		$snippet = addslashes( wpautop( $snippets[$callback]->post_content ) );
		$snippet = str_replace( "&", "&amp;", $snippet );

		foreach ( $attributes as $key => $val )
			$snippet = str_replace( "{".$key."}", $val, $snippet );

		// Strip escaping and execute nested shortcodes
		$snippet = do_shortcode( stripslashes( $snippet ) );

		return $snippet;
	}

	/**
	 * Returns an array of all snippets with their variables and post data.
	 *
	 * @since 1.0
	 * @return array $post_name => $post object + variables
	 */
	public static function get_snippets(){

		if ( empty( self::$snippets ) ) {

			$snippet_posts = get_posts( array( 'post_type' => self::$post_type_name ) );

			if ( empty( $snippet_posts ) ) 
				$snippet_posts = array();

			foreach ( $snippet_posts as $key => $snippet ) {

				self::$snippets[$snippet->post_name] = $snippet;

				self::$snippets[$snippet->post_name]->variables = get_post_meta( $snippet->ID, '_snippet_variables', true );

				self::$snippets[$snippet->post_name]->is_shortcode = get_post_meta( $snippet->ID, '_snippet_is_shortcode', true );

			}

		}

		return self::$snippets;
	}

	/**
	 * Returns an array of all snippets with their variables and post data.
	 *
	 * @since 1.0
	 * @return array $post_name => $post_content
	 */
	public static function sanitize_variable_name( $variable_name ) {

		$variable_name = str_replace( array( ' ', '-', '.', '*', '@' ), '_', sanitize_user( strtolower( $variable_name ), true ) );

		return $variable_name;
	}

	/**
	 * Helper function to get the URL of a given file. 
	 * 
	 * As this plugin may be used as both a stand-alone plugin and as a submodule of 
	 * a theme, the standard WP API functions, like plugins_url() can not be used. 
	 *
	 * @since 1.0
	 * @return array $post_name => $post_content
	 */
	public static function get_url( $file ) {

		// Get the path of this file after the WP content directory
		$post_content_path = substr( dirname( __FILE__ ), strpos( __FILE__, basename( WP_CONTENT_DIR ) ) + strlen( basename( WP_CONTENT_DIR ) ) );

		// Return a content URL for this path & the specified file
		return content_url( $post_content_path . $file );
	}


	/* Help tabs */

	/**
	 * Setup the help tabs and sidebar.
	 *
	 * @since 1.0
	 */
	public static function add_help_tabs() {

		$screen = get_current_screen();

		if ( $screen->id == self::$admin_screen_id ) {

			$screen->add_help_tab( array(
				'id'      => 'basic-plugin-help',
				'title'   => __( 'Basic', Simple_Snippets::$text_domain ),
				'content' => self::help_tab_basic()
			) );

			$screen->add_help_tab( array(
				'id'      => 'variables-plugin-help',
				'title'   => __( 'Variables', Simple_Snippets::$text_domain ),
				'content' => self::help_tab_variables()
			) );
		}
	}

	/**
	 * The basic help tab.
	 * 
	 * @since 1.0
	 * @return	string	The help text
	 */
	public static function help_tab_basic() {
		ob_start(); ?>
<h2><?php _e( 'Title', Simple_Snippets::$text_domain ); ?></h2>
<p><?php _e( 'The snippet title is used to identify the snippet. This will also become the name of the shortcode if you enable that option.', Simple_Snippets::$text_domain ); ?></p>

<h2><?php _e( 'Content', Simple_Snippets::$text_domain ); ?></h2>
<p><?php _e( 'Create HTML content for your snippet just as you would create content for a post or page. You can use shortcodes and even other snippets within your snippet\'s content. To use variables in the content, reference them between curly braces e.g. <code>{variable_name}</code>.', Simple_Snippets::$text_domain ); ?></p>

<h2><?php _e( 'Description', Simple_Snippets::$text_domain ); ?></h2>
<p><?php _e( 'Include an optional explanation or description of the snippet. If filled out, the description will be displayed in insert snippet window of the post editor.', Simple_Snippets::$text_domain); ?></p>

<h2><?php _e( 'Shortcode', Simple_Snippets::$text_domain ); ?></h2>
<p><?php _e( 'When enabling the shortcode checkbox, the snippet is no longer inserted into a post as HTML, instead it is inserted as a shortcode. The advantage of a shortcode is that you can insert a block of text or code in many places on the site, and update the content from one single place.', Simple_Snippets::$text_domain ); ?></p>
<p><?php _e( 'The name to use the shortcode is the same as the title of the snippet (with spaces replaced by hypehens). When inserting a snippet as a shortcode, the shortcode tag will be inserted into the post instead of the HTML content i.e. [snippet-name].', Simple_Snippets::$text_domain ); ?></p>

<?php 
		return ob_get_clean();
	}

	/**
	 * The basic help tab.
	 * 
	 * @since 1.0
	 * @return	string	The help text
	 */
	public static function help_tab_variables() {
		ob_start(); ?>
<h2><?php _e( 'Variables', Simple_Snippets::$text_domain ); ?></h2>
<p><?php _e( 'You can use variables to dynamically change certain values in your snippet. A variable can also be assigned a default value.', Simple_Snippets::$text_domain ); ?></p>

<h3><?php _e( 'Variable Names', Simple_Snippets::$text_domain ); ?></h3>
<p><?php _e( 'Variable names should be unique. For best results, a variable name should only contain letters (a-z), numbers (0-9) and underscores (_).', Simple_Snippets::$text_domain ); ?></p>
<p><?php _e( 'If you change the name of a variable, you will also need to change the variable name in all shortcodes (so try not to change the name of a variable).', Simple_Snippets::$text_domain ); ?></p>

<h3><?php _e( 'Variable Values', Simple_Snippets::$text_domain ); ?></h3>
<p><?php _e( 'A variable can be assigned a default value which will be used if no other value is provided.', Simple_Snippets::$text_domain ); ?></p>

<h3><?php _e( 'Using a Variable', Simple_Snippets::$text_domain ); ?></h3>
<p><?php _e( 'To use a variable in a snippet, insert the variable name enclosed in curly braces. For example, to use a variable named <code>var_one</code>, add <code>{var_one}</code> to your snippet.', Simple_Snippets::$text_domain ); ?></p>

<h3><?php _e( 'Variable Select Box', Simple_Snippets::$text_domain ); ?></h3>
<p><?php _e( 'To constrain the available values for a variable to a specific list of items, insert a comma separated list of values enclosed in curly braces in the <em>Default Value/s</em> field.', Simple_Snippets::$text_domain ); ?></p>
<p><?php _e( 'For example, entering <code>{ACT,NSW,NT,QLD,SA,TAS,VIC,WA}</code> in the <em>Default Value/s</em> field would display a select box with States and Territories when inserting a snippet.', Simple_Snippets::$text_domain ); ?></p>
	<?php 
		return ob_get_clean();
	}
}

Simple_Snippets::init();

endif;