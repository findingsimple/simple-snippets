<?php
/**
 * Post Snippets Settings.
 *
 * Class that renders out the HTML for the settings screen and contains helpful
 * methods to simply the maintainance of the admin screen.
 *
 * @package Simple Snippets
 * @author Johan Steen <artstorm at gmail dot com>
 * @since 1.0
 */
class Simple_Snippets_Settings {
	// -------------------------------------------------------------------------
	// Handle form submissions
	// -------------------------------------------------------------------------

	/**
	 * Add New Snippet.
	 */
	private function add() {
		if ( isset( $_POST['add-snippet'] ) && isset( $_POST['update_snippets_nonce'] ) && wp_verify_nonce( $_POST['update_snippets_nonce'], 'update_snippets' ) ) {

			$snippets = get_option( FS_SNIPPETS_OPTION_KEY, array() );

			array_push( $snippets, array (
				'title'       => 'Untitled',
				'vars'        => '',
				'description' => '',
				'shortcode'   => false,
				'php'         => false,
				'wptexturize' => false,
				'snippet'     => '')
			);

			update_option( FS_SNIPPETS_OPTION_KEY, $snippets );

			$this->message( __( 'A snippet named Untitled has been added.', 'post-snippets' ) );

		}
	}

	/**
	 * Delete Snippet/s.
	 */
	private function delete() {
		if (isset( $_POST['delete-snippets'] ) && isset( $_POST['update_snippets_nonce'] ) && wp_verify_nonce( $_POST['update_snippets_nonce'], 'update_snippets') ) {
			$snippets = get_option( FS_SNIPPETS_OPTION_KEY );

			if ( empty($snippets) || !isset($_POST['checked']) ) {
				$this->message( __( 'Nothing selected to delete.', 'post-snippets' ) );
				return;
			}

			$delete = $_POST['checked'];
			$newsnippets = array();
			foreach ($snippets as $key => $snippet) {
				if (in_array($key,$delete) == false) {
					array_push($newsnippets,$snippet);	
				}
			}

			update_option( FS_SNIPPETS_OPTION_KEY, $newsnippets );
			$this->message( __( 'Selected snippets have been deleted.', 'post-snippets' ) );
		}
	}

	/**
	 * Update Snippet/s.
	 */
	private function update() {
		if (isset( $_POST['update-snippets'] ) && isset( $_POST['update_snippets_nonce'] ) && wp_verify_nonce( $_POST['update_snippets_nonce'], 'update_snippets') ) {
			$snippets = get_option( FS_SNIPPETS_OPTION_KEY );
			if (!empty($snippets)) {
				foreach ($snippets as $key => $value) {
					$new_snippets[$key]['title'] = trim($_POST[$key.'_title']);
					$new_snippets[$key]['vars'] = str_replace(' ', '', trim($_POST[$key.'_vars']) );
					$new_snippets[$key]['shortcode'] = isset($_POST[$key.'_shortcode']) ? true : false;
					$new_snippets[$key]['php'] = isset($_POST[$key.'_php']) ? true : false;
					$new_snippets[$key]['wptexturize'] = isset($_POST[$key.'_wptexturize']) ? true : false;

					$new_snippets[$key]['snippet'] = wp_specialchars_decode( trim(stripslashes($_POST[$key.'_snippet'])), ENT_NOQUOTES);
					$new_snippets[$key]['description'] = wp_specialchars_decode( trim(stripslashes($_POST[$key.'_description'])), ENT_NOQUOTES);
				}
				update_option( FS_SNIPPETS_OPTION_KEY, $new_snippets );
				$this->message( __( 'Snippets have been updated.', 'post-snippets' ) );
			}
		}
	}

	/**
	 * Update User Option.
	 *
	 * Sets the per user option for the read-only overview page.
	 *
	 * @since 1.0
	 */
	private function set_user_options() {
		if ( isset($_POST['post_snippets_user_nonce']) && wp_verify_nonce( $_POST['post_snippets_user_nonce'], 'post_snippets_user_options') ) {
			$id = get_current_user_id();
			$render = isset( $_POST['render'] ) ? true : false;
			update_user_meta( $id, FS_SNIPPETS_OPTION_KEY, $render );
		}
	}

	/**
	 * Get User Option.
	 *
	 * Gets the per user option for the read-only overview page.
	 *
	 * @since 1.0
	 * @return	boolean	If overview should be rendered on output or not
	 */
	private function get_user_options() {
		$id = get_current_user_id();
		$options = get_user_meta( $id, FS_SNIPPETS_OPTION_KEY, true ); 
		return $options;
	}


	// -------------------------------------------------------------------------
	// HTML generation for option pages
	// -------------------------------------------------------------------------

	/**
	 * Render the options page.
	 *
	 * @since 1.0
	 * @param	string	$page	Admin page to render. Default: options
	 */
	public function render( $page ) {
		switch( $page ) {
			case 'options':
				$this->options_page();
				break;
			default:
				$this->overview_page();
				break;
		}
	}

	/**
	 * Display Flashing Message.
	 *
	 * @param	string	$message	Message to display to the user.
	 */
	private function message( $message ) {
		if ( $message )
			echo "<div class='updated'><p><strong>{$message}</strong></p></div>";
	}

	/**
	 * Creates the snippets administration page.
	 *
	 * For users with manage_options capability (admin, super admin).
	 *
	 * @since 1.0
	 */
	private function options_page() {
		// Handle Form Submits
		$this->add();
		$this->delete();
		$this->update();

		// Header
		echo '<div class="wrap">';
		echo '<h2>' . __( 'Manage Snippets', 'post-snippets' ) . '</h2>';

		echo '<p class="description">';
		_e( 'Use the help dropdown button for additional information.', 'post-snippets' );
		echo '</p>';

		echo '<form method="post" action="">';
		wp_nonce_field( 'update_snippets', 'update_snippets_nonce' );
?>

<table class="widefat fixed" cellspacing="0">
	<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" /></th>
			<th scope="col" style="width: 180px;"><?php _e( 'Title', 'post-snippets' ) ?></th>
			<th scope="col" style="width: 180px;"><?php _e( 'Variables', 'post-snippets' ) ?></th>
			<th scope="col"><?php _e( 'Snippet', 'post-snippets' ) ?></th>
		</tr>
	</thead>

	<tfoot>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" /></th>
			<th scope="col"><?php _e( 'Title', 'post-snippets' ) ?></th>
			<th scope="col"><?php _e( 'Variables', 'post-snippets' ) ?></th>
			<th scope="col"><?php _e( 'Snippet', 'post-snippets' ) ?></th>
		</tr>
	</tfoot>

	<tbody> <?php 
	$snippets = get_option( FS_SNIPPETS_OPTION_KEY );
	if ( ! empty( $snippets ) ) {
		foreach ($snippets as $key => $snippet) { ?>
			<tr class='recent'>
				<th scope='row' class='check-column'><input type='checkbox' name='checked[]' value='<?php echo $key; ?>' /></th>
				<td class='row-title'>
					<input type='text' name='<?php echo $key; ?>_title' value='<?php echo $snippet['title']; ?>' />
				</td>
				<td class='name'>
					<input type='text' name='<?php echo $key; ?>_vars' value='<?php echo $snippet['vars']; ?>' /><br/><br/>
					<?php $this->checkbox( __( 'Shortcode', 'post-snippets'), $key.'_shortcode', $snippet['shortcode'] );

					echo '<br/><strong>Shortcode Options:</strong><br/>';

					$wptexturize = isset( $snippet['wptexturize'] ) ? $snippet['wptexturize'] : false;
					$this->checkbox('wptexturize', $key.'_wptexturize',	$wptexturize );
				?>
			</td>
			<td class='desc'>
				<textarea name="<?php echo $key; ?>_snippet" class="large-text" style='width: 100%;' rows="5"><?php echo htmlspecialchars($snippet['snippet'], ENT_NOQUOTES); ?></textarea>
				<?php _e( 'Description', 'post-snippets' ) ?>:
				<input type='text' style='width: 100%;' name='<?php echo $key; ?>_description' value='<?php if (isset( $snippet['description'] ) ) echo esc_html($snippet['description']); ?>' /><br/>
			</td>
		</tr>
		<?php
	}
}
?>
	</tbody>
</table>

<?php
		$this->submit( 'update-snippets', __('Update Snippets', 'post-snippets') );
		$this->submit( 'add-snippet', __('Add New Snippet', 'post-snippets'), 'button-secondary', false );
		$this->submit( 'delete-snippets', __('Delete Selected', 'post-snippets'), 'button-secondary', false );
		echo '</form>';

		// Close it
		echo '</div>';
	}

	/**
	 * Tab to Manage Snippets.
	 *
	 * @since 1.0
	 */
	private function tab_snippets() {
	}


	/**
	 * Creates a read-only overview page.
	 *
	 * For users with edit_posts capability but without manage_options 
	 * capability.
	 *
	 * @since 1.0
	 */
	private function overview_page() {
		// Header
		echo '<div class="wrap">';
		echo '<h2>Post Snippets</h2>';
		echo '<p>';
		_e( 'This is an overview of all snippets defined for this site. These snippets are inserted into posts from the post editor using the Post Snippets button. You can choose to see the snippets here as-is or as they are actually rendered on the website. Enabling rendered snippets for this overview might look strange if the snippet have dependencies on variables, CSS or other parameters only available on the frontend. If that is the case it is recommended to keep this option disabled.', 'post-snippets' );
		echo '</p>';

		// Form
		$this->set_user_options();
		$render = $this->get_user_options();

		echo '<form method="post" action="">';
		wp_nonce_field( 'post_snippets_user_options', 'post_snippets_user_nonce' );

		$this->checkbox(__('Display rendered snippets', 'post-snippets'), 'render', $render  );
		$this->submit( 'update-post-snippets-user', __('Update', 'post-snippets') );
		echo '</form>';

		// Snippet List
		$snippets = get_option( FS_SNIPPETS_OPTION_KEY );
		if (!empty($snippets)) {
			foreach ($snippets as $key => $snippet) {

				echo "<hr style='border: none;border-top:1px dashed #aaa; margin:24px 0;' />";

				echo "<h3>{$snippet['title']}";
				if ($snippet['description'])
					echo "<span class='description'> {$snippet['description']}</span>";
				echo "</h3>";

				if ($snippet['vars'])
					printf( "<strong>%s:</strong> {$snippet['vars']}<br/>", __('Variables', 'post-snippets') );

				// echo "<strong>Variables:</strong> {$snippet['vars']}<br/>";

				$options = array();
				if ($snippet['shortcode'])
					array_push($options, 'Shortcode');
				if ($snippet['php'])
					array_push($options, 'PHP');
				if ($snippet['wptexturize'])
					array_push($options, 'wptexturize');
				if ($options)
					printf ( "<strong>%s:</strong> %s<br/>", __('Options', 'post-snippets'), implode(', ', $options) );

				printf( "<br/><strong>%s:</strong><br/>", __('Snippet', 'post-snippets') );
				if ( $render ) {
					echo do_shortcode( $snippet['snippet'] );
				} else {
					echo "<code>";
					echo nl2br( htmlspecialchars($snippet['snippet'], ENT_NOQUOTES) );
					echo "</code>";
				}
			}
		}

		// Close
		echo '</div>';
	}


	// -------------------------------------------------------------------------
	// HTML and Form element methods
	// -------------------------------------------------------------------------
	
	/**
	 * Checkbox.
	 *
	 * Renders the HTML for an input checkbox.
	 *
	 * @param	string	$label		The label rendered to screen
	 * @param	string	$name		The unique name and id to identify the input
	 * @param	boolean	$checked	If the input is checked or not
	 */
	private function checkbox( $label, $name, $checked ) {
		echo "<label for=\"{$name}\">";
		printf( '<input type="checkbox" name="%1$s" id="%1$s" value="true"', $name );
		if ($checked)
			echo ' checked';
		echo ' />';
		echo " {$label}</label><br/>";
	}

	/**
	 * Submit.
	 *
	 * Renders the HTML for a submit button.
	 *
	 * @since 1.0
	 * @param	string	$name	The name that identifies the button on submit
	 * @param	string	$label	The label rendered on the button
	 * @param	string	$class	Optional. Button class. Default: button-primary
	 * @param	boolean	$wrap	Optional. Wrap in a submit div. Default: true
	 */
	private function submit( $name, $label, $class='button-primary', $wrap=true ) {
		$btn = sprintf( '<input type="submit" name="%s" value="%s" class="%s" />', $name, $label, $class );

		if ($wrap)
			$btn = "<div class=\"submit\">{$btn}</div>";

		echo $btn;
	}
}
