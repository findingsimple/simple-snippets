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

	static $capability_type = 'snippet';

	static $snippets = array();

	public static function init() {
		global $wp_version;

		self::$text_domain = apply_filters( 'simple_snippets_text_domain', 'Simple_Snippets' );

		self::$post_type_name = apply_filters( 'simple_snippets_post_type_name', 'snippet' );

		self::$admin_screen_id = apply_filters( 'simple_snippets_admin_screen_id', 'snippet' );

		add_action( 'init', array( __CLASS__, 'register_post_type' ) );

		self::create_shortcodes();

		add_filter( 'mce_external_plugins', array( __CLASS__, 'register_tinymce_plugin' ) );

		add_filter( 'mce_buttons', array( __CLASS__, 'add_remove_tinymce_buttons' ) );

		add_filter( 'post_row_actions', array( __CLASS__, 'remove_inline_actions' ), 10, 2 );

		add_filter( 'post_updated_messages', array( __CLASS__, 'set_correct_snippet_messages' ), 11 );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_styles_and_scripts' ) );

		add_action( 'admin_footer', array( __CLASS__, 'snippet_dialog_markup' ) );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_remove_meta_boxes' ) );

		add_action( 'save_post', array( __CLASS__, 'save_snippet_meta' ) );

		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );

		add_action( 'admin_init', array( __CLASS__, 'set_and_save_settings' ) );

		// In context help 
		add_action( 'load-post.php', array( __CLASS__, 'add_help_tabs' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'add_help_tabs' ) );
	}

	/**
	 * Adds a settings page for administrators under the default WordPress Settings menu.
	 * 
	 * @since 1.0
	 */
	function add_settings_page() {
		if ( function_exists( 'add_options_page' ) )
			$page = add_options_page( 'Snippet Settings', 'Snippets', 'manage_options', 'snippet_settings', array( __CLASS__, 'settings_page'  ) );

	}

	/**
	 * Site admins may want to allow or disallow users to create, edit and delete snippets.
	 *
	 * This function provides an admin menu for selecting which roles can do what with snippets.
	 *
	 * @since 1.0
	 */
	function settings_page() { 
		global $wp_roles;

		$role_names = $wp_roles->get_names();
		$roles = array();

		foreach ( $role_names as $role_name => $display_name ) {
			$roles[$role_name] = get_role( $role_name );
			$roles[$role_name]->display_name = $display_name;
		}

		$snippet_post_type    = get_post_type_object( self::$post_type_name );
		$snippet_capabilities = $snippet_post_type->cap;

?>
<div class="wrap snippet-settings">
	<?php screen_icon(); ?>
	<h2><?php _e( 'Snippet Settings', self::$text_domain ) ?></h2>
	<form id="snippet-setting-form" method="post" action="">
		<?php wp_nonce_field( __FILE__, 'snippet_settings_nonce' ); ?>

		<h3><?php printf( __( '%s Capabilities', self::$text_domain ), $snippet_post_type->labels->name ); ?></h3>
		<p><?php _e( 'Control who create, edit and manage snippets.', self::$text_domain ); ?></p>

		<?php // Allow editing own posts ?>
		<div class="snippet-settings">
			<h4><?php printf( __( "Create %s", self::$text_domain ), $snippet_post_type->labels->name  ); ?></h4>
			<p><?php _e( 'Permit roles to create, edit and delete their own snippets.', self::$text_domain ); ?></p>
			<?php foreach ( $roles as $role ): ?>
			<label for="create-<?php echo self::$post_type_name . '-' . $role->name; ?>">
				<input type="checkbox" id="create-<?php echo self::$post_type_name . '-' . $role->name; ?>" name="create-<?php echo self::$post_type_name . '-' . $role->name; ?>"<?php checked( isset( $role->capabilities[$snippet_capabilities->edit_published_posts] ), 1 ); ?> />
				<?php echo $role->display_name; ?>
			</label>
			<?php endforeach; ?>
		</div>

		<?php // Allow editing others posts ?>
		<div class="snippet-settings">
			<h4><?php printf( __( "Manage %s", self::$text_domain ), $snippet_post_type->labels->name  ); ?></h4>
			<p><?php _e( 'Permit role to create, edit and delete their own snippets as well as edit the snippets created by others. Allowing a role to manage snippets will also allow them to create snippets.', self::$text_domain ); ?></p>
			<?php foreach ( $roles as $role ): ?>
			<label for="manage-<?php echo self::$post_type_name . '-' . $role->name; ?>">
				<input type="checkbox" id="manage-<?php echo self::$post_type_name . '-' . $role->name; ?>" name="manage-<?php echo self::$post_type_name . '-' . $role->name; ?>"<?php checked( isset( $role->capabilities[$snippet_capabilities->edit_others_posts] ), 1 ); ?> />
				<?php echo $role->display_name; ?>
			</label>
			<?php endforeach; ?>
		</div>

		<p class="submit">
			<input type="submit" name="submit" class="button button-primary" value="<?php _e( 'Save', self::$text_domain ); ?>" />
		</p>
	</form>
</div>
<?php
	}

	/**
	 * Save snippet settings when the admin page is submitted page by adding the capability to the appropriate roles.
	 *
	 * @since 1.0
	 **/
	function set_and_save_settings() {
		global $wp_roles;

		// Bit of a hack to set defaults
		if ( get_option( 'fs_capabilities_set_for_' . self::$post_type_name, false ) === false ) {

			$_POST['snippet_settings_nonce']       = wp_create_nonce( __FILE__ );
			$_POST['manage-snippet-administrator'] = 'on';
			$_POST['manage-snippet-editor']        = 'on';
			$_POST['create-snippet-administrator'] = 'on';
			$_POST['create-snippet-editor']        = 'on';
			$_POST['create-snippet-author']        = 'on';
			$_POST['create-snippet-contributor']   = 'on';

			add_option( 'fs_capabilities_set_for_' . self::$post_type_name, 'true' );
		}

	    if ( ! isset( $_POST['snippet_settings_nonce'] ) || ! wp_verify_nonce( $_POST['snippet_settings_nonce'], __FILE__ ) || ! current_user_can( 'manage_options' ) )
			return;

		$role_names = $wp_roles->get_names();
		$roles = array();

		foreach ( $role_names as $role_name => $display_name ) {
			$roles[$role_name] = get_role( $role_name );
			$roles[$role_name]->display_name = $display_name;
		}

		$snippet_post_type    = get_post_type_object( self::$post_type_name );
		$snippet_capabilities = $snippet_post_type->cap;

		foreach ( $roles as $role_name => $role ) {

			$snippet_role_tag = self::$post_type_name . '-' . $role_name;

			if ( ( isset( $_POST['create-'.$snippet_role_tag] ) && $_POST['create-'.$snippet_role_tag] == 'on' ) || ( isset( $_POST['manage-'.$snippet_role_tag] ) && $_POST['manage-'.$snippet_role_tag] == 'on' ) ) {

				// Shared capability required to see post's menu & publish posts
				$role->add_cap( $snippet_capabilities->edit_posts );

				// Shared capability required to delete posts
				$role->add_cap( $snippet_capabilities->delete_posts );

				// Allow publish
				$role->add_cap( $snippet_capabilities->publish_posts );

				// Allow editing own posts
				$role->add_cap( $snippet_capabilities->edit_published_posts );
				$role->add_cap( $snippet_capabilities->edit_private_posts );
				$role->add_cap( $snippet_capabilities->delete_published_posts );
				$role->add_cap( $snippet_capabilities->delete_private_posts );
			} else {

				// Shared capability required to see post's menu & publish posts
				$role->remove_cap( $snippet_capabilities->edit_posts );

				// Shared capability required to delete posts
				$role->remove_cap( $snippet_capabilities->delete_posts );

				// Allow publish
				$role->remove_cap( $snippet_capabilities->publish_posts );

				// Allow editing own posts
				$role->remove_cap( $snippet_capabilities->edit_published_posts );
				$role->remove_cap( $snippet_capabilities->edit_private_posts );
				$role->remove_cap( $snippet_capabilities->delete_published_posts );
				$role->remove_cap( $snippet_capabilities->delete_private_posts );
			}

			// Allow editing other's posts
			if ( isset( $_POST['manage-'.$snippet_role_tag] ) && $_POST['manage-'.$snippet_role_tag] == 'on' ) {
				$role->add_cap( $snippet_capabilities->edit_others_posts );
				$role->add_cap( $snippet_capabilities->delete_others_posts );
			} else {
				$role->remove_cap( $snippet_capabilities->edit_others_posts );
				$role->remove_cap( $snippet_capabilities->delete_others_posts );
			}

		}

		// Redirect so that new capabilities are applied correctly
		$location = admin_url( 'options-general.php?page=snippet_settings&updated=1' );
		wp_safe_redirect( $location );
		exit;
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
		$snippet_use_content  = ( isset( $_POST['_snippet_use_content'] ) && 'on' == $_POST['_snippet_use_content'] ) ? 'true' : 'false';

		/* Clear any empty variables (variables need a name but not a default value) */
		foreach ( $snippet_variables as $key => $variable_array ) {
			if ( empty( $variable_array['variable_name'] ) )
				unset( $snippet_variables[$key] );
			else
				$snippet_variables[$key]['variable_name'] = self::sanitize_variable_name( $variable_array['variable_name'] );
		}

		update_post_meta( $post_id, '_snippet_variables', $snippet_variables );
		update_post_meta( $post_id, '_snippet_is_shortcode', $snippet_is_shortcode );
		update_post_meta( $post_id, '_snippet_use_content', $snippet_use_content );

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

		$use_content = ( in_array( get_post_meta( $post->ID, '_snippet_use_content', true ), array( '', 'true' ) ) ) ? 'true' : 'false';

		wp_nonce_field( __FILE__, '_snippets_nonce' );

		$snippet_variables = get_post_meta( $post->ID, '_snippet_variables', true );

		if ( empty( $snippet_variables ) )
			$snippet_variables = array( array( 'variable_name' => '', 'variable_default' => '' ) );

?>
		<p><?php _e( 'An optional description to display when inserting this snippet.', self::$text_domain ); ?></p>
		<label class="screen-reader-text" for="excerpt"><?php _e( 'Description', self::$text_domain ) ?></label>
		<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt"><?php echo esc_attr( $post->post_excerpt ); ?></textarea>

		<h4><?php _e( 'Shortcode' ); ?></h4>

		<label for="_snippet_is_shortcode">
			<input type="checkbox" name="_snippet_is_shortcode" id="_snippet_is_shortcode" <?php checked( $is_shortcode, 'true' ); ?>/>
			<?php _e( 'Use snippet as a shortcode', self::$text_domain ) ?>
		</label>

		<div class="use-snippet-content">
			<h4><?php _e( 'Content' ); ?></h4>

			<label for="_snippet_use_content">
				<input type="checkbox" name="_snippet_use_content" id="_snippet_use_content" <?php checked( $use_content, 'true' ); ?>/>
				<?php _e( 'Allow content in the snippet shortcode', self::$text_domain ) ?>
			</label>
		</div>

		<h4><?php _e( 'Variables' ); ?></h4>

		<fieldset id="snippet-variables">
			<?php foreach ( $snippet_variables as $index => $snippet_variable ) : ?>
			<fieldset class="snippet-variable">
				<label for="_snippet_variables[<?php echo $index; ?>][variable_name]"><?php _e( 'Variable Name:', self::$text_domain ) ?>
					<input type="text" name="_snippet_variables[<?php echo $index; ?>][variable_name]" id="_snippet_variables[<?php echo $index; ?>][variable_name]" value="<?php echo $snippet_variable['variable_name']; ?>" />
				</label>
				<label for="_snippet_variables[<?php echo $index; ?>][variable_default]"><?php _e( 'Default Value/s:', self::$text_domain ) ?>
					<input type="text" name="_snippet_variables[<?php echo $index; ?>][variable_default]" id="_snippet_variables[<?php echo $index; ?>][variable_default]" value="<?php esc_attr_e( $snippet_variable['variable_default'] ); ?>" />
				</label>
			</fieldset>
			<?php endforeach; ?>
		</fieldset>

		<input type="button" id="snippet_variable_adder" value="<?php _e( 'Add a Variable', self::$text_domain ); ?>" class="button"/>
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
			'add_new'            => _x( 'Add New', self::$text_domain ),
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
			'capability_type'    => self::$capability_type,
			'map_meta_cap'       => true,
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
		
		if ( is_admin() )
			wp_enqueue_style( 'snippets', self::get_url( '/css/admin.css' ) );

		if ( $screen->base == 'post' ) {

			wp_enqueue_style( 'wp-jquery-ui-dialog' );

			/* Prepare the snippets and shortcodes into javascript variables so they can be inserted into the editor and get the variables replaced with user defined strings. */
			$snippets = self::get_snippets();

			$snippet_data = array();

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

					if ( $snippet->use_content != 'false' )
						$snippet_data['variables'][$snippet->post_name]['_content'] = '';

				}

				if ( $snippet->is_shortcode != 'false' ) {

					$snippet_data['snippetsToInsert'][$snippet->post_name] = '[' . $snippet->post_name . $variable_string . ']';

					if ( $snippet->use_content != 'false' )
						$snippet_data['snippetsToInsert'][$snippet->post_name] .= '{_content}';

					// Always close shortcode to future proof against a shortcode being changed to include content (and breaking existing shortcodes without closing tag)
					$snippet_data['snippetsToInsert'][$snippet->post_name] .= '[/' . $snippet->post_name . ']';

				} else {

					$snippet_data['snippetsToInsert'][$snippet->post_name] = str_replace( ']]>', ']]&gt;', apply_filters( 'the_content', $snippet->post_content ) );

				}

				$snippet_data['snippetsToInsert'][$snippet->post_name] = json_encode( $snippet_data['snippetsToInsert'][$snippet->post_name] );

			}

			wp_enqueue_script( 'snippets', self::get_url( '/js/snippets.js' ), array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-tabs' ) );

			wp_localize_script( 'snippets', 'SnippetData', $snippet_data );

		}

	}


	/* TinyMCE */

	/**
	 * Register a TinyMCE button.
	 *
	 * @see wp-includes/class-wp-editor.php
	 * @link http://www.tinymce.com/wiki.php/Buttons/controls
	 * @since 1.0
	 */
	public static function add_remove_tinymce_buttons( $buttons ) {

		$screen = get_current_screen();

		// Snippet TinyMCE Editor, remove "read more" & "next page" buttons
		if ( $screen->id == self::$admin_screen_id )
			foreach( $buttons as $key => $name )
				if ( in_array( $name, array( 'wp_more', 'wp_page' ) ) )
					unset( $buttons[$key] );

		// Add the Snippet button to all editors
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
	 * Build jQuery UI Window.
	 *
	 * Creates the jQuery for Post Editor popup window, its snippet tabs and the
	 * form fields to enter variables.
	 *
	 * @since 1.0
	 */
	public static function snippet_dialog_markup() {
		global $post_id;

		$screen = get_current_screen();

		if ( $screen->base != 'post' )
			return;

		$snippets = self::get_snippets();
?>
<div class="hidden">
	<div id="snippets-dialog" title="Insert Snippet">
		<div id="snippets-tabs">
			<nav id="snippet-selector">
				<label for="snippet-select">
					<?php _e( 'Please select a snippet to insert:', self::$text_domain ); ?>
					<select id="snippet-select" name="snippet-select">
						<?php foreach ( $snippets as $key => $snippet ) : ?>
							<?php if ( $screen->id == self::$admin_screen_id && $post_id == $snippet->ID ) continue; // don't add the snippet to itself ?>
							<option value="#snippet-tab-<?php echo $snippet->post_name; ?>"><?php echo $snippet->post_title; ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</nav>
			<ul>
			<?php foreach ( $snippets as $key => $snippet ) : ?>
				<?php if ( $screen->id == self::$admin_screen_id && $post_id == $snippet->ID ) continue; // don't add the snippet to itself ?>
				<li><a href="#snippet-tab-<?php echo $snippet->post_name; ?>"><?php echo $snippet->post_title; ?></a></li>
			<?php endforeach; ?>
			</ul>

			<?php foreach ( $snippets as $key => $snippet ) : ?>
			<?php if ( $screen->id == self::$admin_screen_id && $post_id == $snippet->ID ) continue; // don't add the snippet to itself ?>
			<div id="snippet-tab-<?php echo $snippet->post_name; ?>" class="snippet-tab">

				<?php if ( ! empty( $snippet->post_excerpt ) ) : ?>
				<p class="howto"><?php echo $snippet->post_excerpt; ?></p>
				<?php endif; ?>

				<?php foreach ( $snippet->variables as $key_2 => $variable ) : ?>

				<?php $variable_id    = $snippet->post_name . '_' . self::sanitize_variable_name( $variable['variable_name'] ); ?>
				<?php $variable_label = ucwords( str_replace( '_', ' ', $variable['variable_name'] ) ); ?>

				<?php if ( 1 == preg_match( '/^\{.*?\}$/', $variable['variable_default'] ) ) : // We want a select box ?>
				<div class="select-wrap">
				<label for="<?php echo $variable_id; ?>" class="select"><?php echo $variable_label; ?>:</label>
				<select id="<?php echo $variable_id; ?>" name="<?php echo $variable_id; ?>">
					<?php foreach ( explode( ',', substr( $variable['variable_default'], 1, -1 ) ) as $value ) : ?>
					<option value="<?php echo $value; ?>"><?php echo $value; ?></option>
					<?php endforeach; ?>
				</select>
				</div>
				<?php else : ?>
				<label for="<?php echo $variable_id; ?>"><?php echo $variable_label; ?>:
					<input type="text" id="<?php echo $variable_id; ?>" name="<?php echo $variable_id; ?>" value="<?php esc_attr_e( $variable['variable_default'] ); ?>" />
				</label>
				<?php endif; ?>
				<?php endforeach; ?>
				<?php if ( $snippet->use_content != 'false' ) : ?>
				<label for="<?php echo $snippet->post_name . '__content'; ?>" class="snippet-content"><?php _e( 'Content:', self::$text_domain ); ?>
					<textarea id="<?php echo $snippet->post_name . '__content'; ?>" name="<?php echo $snippet->post_name . '__content'; ?>"></textarea>
				</label>
				<?php endif; ?>
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
	public static function shortcode_callback( $atts, $_content = '', $callback ) {

		$snippets = self::get_snippets();

		$shortcode_symbols = array();

		foreach( $snippets[$callback]->variables as $variable )
			$shortcode_symbols[$variable['variable_name']] = $variable['variable_default'];

		extract( shortcode_atts( $shortcode_symbols, $atts ) );

		$attributes = compact( array_keys( $shortcode_symbols ) );

		$snippet = str_replace( "&", "&amp;", addslashes( $snippets[$callback]->post_content ) );

		$_content = remove_wpautop( $_content );

		// Get the first <p> tag to make sure it's an opening tag, if it is a closing tag, prepend an opening tag, works around a bug in wpautop()
		if ( preg_match( '/<(\/?)p("[^"]*"|\'[^\']*\'|[^\'">r])*>/', $_content, $matches ) )
			if ( strpos( $matches[0], '/' ) == 1 )
				$_content = '<p>' . $_content;

		// Get all the <p> tags and make sure the last tag is a closing tag, if it is an opening tag, append a closing tag, works around a bug in wpautop()
		if ( preg_match_all( '/<(\/?)p("[^"]*"|\'[^\']*\'|[^\'">r])*?>/', $_content, $matches ) )
			if ( end( $matches[1] ) != '/' ) // We didn't have a closing /, so we have an opening <p> tag
				$_content = $_content . '</p>';

		// Add enclosed content to variables
		$attributes['_content'] = $_content;

		foreach ( $attributes as $variable_name => $variable_value )
			$snippet = str_replace( "{".$variable_name."}", $variable_value, $snippet );

		// Strip escaping and execute nested shortcodes
		$snippet = do_shortcode( stripslashes( $snippet ) );

		$snippet = wpautop( $snippet );

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

			foreach ( $snippet_posts as $key => $snippet ) {

				self::$snippets[$snippet->post_name] = $snippet;

				self::$snippets[$snippet->post_name]->variables    = get_post_meta( $snippet->ID, '_snippet_variables', true );
				self::$snippets[$snippet->post_name]->is_shortcode = get_post_meta( $snippet->ID, '_snippet_is_shortcode', true );
				self::$snippets[$snippet->post_name]->use_content  = get_post_meta( $snippet->ID, '_snippet_use_content', true );
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

	/**
	 * Remove "Quick Edit" and irrelevant actions from snippets list table.
	 *
	 * @since 1.0
	 */
	public static function remove_inline_actions( $actions, $post ) {

		if( $post->post_type == self::$post_type_name )
			unset( $actions['inline hide-if-no-js'] );

		return $actions;
	}

	/**
	 * Customise the messsages for all custom post types in a WordPress install.
	 *
	 * @author Brent Shepherd <brent@findingsimple.com>
	 * @since 1.0
	 */
	function set_correct_snippet_messages( $messages ) {
		global $post, $post_ID;

		$snippet_object = get_post_type_object( self::$post_type_name );

		$messages[self::$post_type_name] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( __( '%s updated.' ), $snippet_object->labels->singular_name ),
			2  => __( 'Custom field updated.' ),
			3  => __( 'Custom field deleted.' ),
			4  => sprintf( __( '%s updated.' ), $snippet_object->labels->singular_name ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( '%s restored to revision from %s' ), $snippet_object->labels->singular_name, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( '%s published.' ), $snippet_object->labels->singular_name ),
			7  => sprintf( __( '%s saved.' ), $snippet_object->labels->singular_name ),
			8  => sprintf( __( '%s submitted.' ), $snippet_object->labels->singular_name ),
			9  => sprintf( __( '%s scheduled for: <strong>%s</strong>.' ), $snippet_object->labels->singular_name, date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
			10 => sprintf( __( '%s draft updated.' ), $snippet_object->labels->singular_name ),
		);

		return $messages;
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
				'title'   => __( 'Basic', self::$text_domain ),
				'content' => self::help_tab_basic()
			) );

			$screen->add_help_tab( array(
				'id'      => 'variables-plugin-help',
				'title'   => __( 'Variables', self::$text_domain ),
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
<h2><?php _e( 'Title', self::$text_domain ); ?></h2>
<p><?php _e( 'The snippet title is used to identify the snippet. This will also become the name of the shortcode if you enable that option.', self::$text_domain ); ?></p>

<h2><?php _e( 'Content', self::$text_domain ); ?></h2>
<p><?php _e( 'Create HTML content for your snippet just as you would create content for a post or page. You can use shortcodes and even other snippets within your snippet\'s content. To use variables in the content, reference them between curly braces e.g. <code>{variable_name}</code>.', self::$text_domain ); ?></p>

<h2><?php _e( 'Description', self::$text_domain ); ?></h2>
<p><?php _e( 'Include an optional explanation or description of the snippet. If filled out, the description will be displayed in insert snippet window of the post editor.', self::$text_domain); ?></p>

<h2><?php _e( 'Shortcode', self::$text_domain ); ?></h2>
<p><?php _e( 'When the shortcode checkbox is checked, the snippet is no longer inserted into a post as HTML, instead it is inserted as a shortcode. The advantage of a shortcode is that you can insert a snippet in many places on the site, and the snippet content will update dynamically whenever the snippet is changed.', self::$text_domain ); ?></p>
<p><?php _e( 'The name to use the shortcode is the same as the title of the snippet (with spaces replaced by hypehens). When inserting a snippet as a shortcode, the shortcode tag will be inserted into the post instead of the HTML content i.e. [snippet-name].', self::$text_domain ); ?></p>
<p><?php _e( 'If changing a snippet from a shortcode to HTML or vice versa, you will also need to change where the snippet has been inserted.', self::$text_domain ); ?></p>

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
<h2><?php _e( 'Variables', self::$text_domain ); ?></h2>
<p><?php _e( 'You can use variables to dynamically change certain values in your snippet. A variable can also be assigned a default value.', self::$text_domain ); ?></p>

<h3><?php _e( 'Variable Names', self::$text_domain ); ?></h3>
<p><?php _e( 'Variable names must be unique and should contain only letters (a-z), numbers (0-9) and underscores (_).', self::$text_domain ); ?></p>
<p><?php _e( 'If you change the name of a variable, you will also need to change the variable name where you have inserted the snippet (so try not to change the name of a variable).', self::$text_domain ); ?></p>

<h3><?php _e( 'Variable Values', self::$text_domain ); ?></h3>
<p><?php _e( 'A variable can be assigned a default value which will be used if no other value is provided.', self::$text_domain ); ?></p>

<h3><?php _e( 'Using a Variable', self::$text_domain ); ?></h3>
<p><?php _e( 'To use a variable in a snippet, insert the variable name enclosed in curly braces. For example, to use a variable named <code>var_one</code>, add <code>{var_one}</code> to your snippet.', self::$text_domain ); ?></p>

<h3><?php _e( 'Variable Select Box', self::$text_domain ); ?></h3>
<p><?php _e( 'To constrain the available values for a variable to a specific list of items, insert a comma separated list of values enclosed in curly braces in the <em>Default Value/s</em> field.', self::$text_domain ); ?></p>
<p><?php _e( 'For example, entering <code>{ACT,NSW,NT,QLD,SA,TAS,VIC,WA}</code> in the <em>Default Value/s</em> field would display a select box with States and Territories when inserting a snippet.', self::$text_domain ); ?></p>
	<?php 
		return ob_get_clean();
	}


}

Simple_Snippets::init();

endif;
