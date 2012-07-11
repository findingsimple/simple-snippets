<?php
/**
 * Post Snippets Help.
 *
 * Class to handle the help texts and tabs on the settings screen.
 *
 * @package Simple Snippets
 * @author Johan Steen <artstorm at gmail dot com>
 * @since 1.0
 */
class Simple_Snippets_Help {

	static $screen_id;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 * @param	string	The option page to load the help text on
	 */
	public static function init( $screen_id = 'snippet' ) {
		self::$screen_id = $screen_id;

		add_action( 'load-post.php', array( __CLASS__, 'add_help_tabs' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'add_help_tabs' ) );
	}

	/**
	 * Setup the help tabs and sidebar.
	 *
	 * @since 1.0
	 */
	public static function add_help_tabs() {

		$screen = get_current_screen();

		if ( $screen->id == self::$screen_id ) {

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
<p><?php _e( 'Variable names should be unique. For best results, a variable name should only contain letters (a-z), numbers (0-9) and hyphens.', Simple_Snippets::$text_domain ); ?></p>

<h3><?php _e( 'Variable Values', Simple_Snippets::$text_domain ); ?></h3>
<p><?php _e( 'A variable can be assigned a default value which will be used if no other value is provided.', Simple_Snippets::$text_domain ); ?></p>

<h3><?php _e( 'Variable Value Select Box', Simple_Snippets::$text_domain ); ?></h3>
<p><?php _e( 'To constrain the available values for a variable to a list of items, insert a comma separated list of values enclosed in curly braces in the <em>Default Value/s</em> field.', Simple_Snippets::$text_domain ); ?></p>
<p><?php _e( 'For example, entering <code>{ACT,NSW,NT,QLD,SA,TAS,VIC,WA}</code> in the <em>Default Value/s</em> field would display a select box with States and Territories when inserting a snippet.', Simple_Snippets::$text_domain ); ?></p>

<h3><?php _e( 'Using a Variable', Simple_Snippets::$text_domain ); ?></h3>
<p><?php _e( 'To use a variable in a snippet, insert the variable name enclosed in curly braces. For example, to use a variable named <code>var_one</code>, add <code>{var_one}</code> to your snippet.', Simple_Snippets::$text_domain ); ?></p>
	<?php 
		return ob_get_clean();
	}

}
