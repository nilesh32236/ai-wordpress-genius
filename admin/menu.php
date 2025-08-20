<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register the admin menu for AI WordPress Genius.
 */
function ai_wp_genius_register_admin_menu() {
	add_menu_page(
		__( 'AI Genius', 'ai-wordpress-genius' ), // Page Title
		'AI Genius',                              // Menu Title
		'manage_options',                         // Capability
		'ai-wordpress-genius',                    // Menu Slug
		'ai_wp_genius_render_dashboard_page',     // Callback function
		'dashicons-superhero',                    // Icon
		2                                         // Position
	);

	add_submenu_page(
		'ai-wordpress-genius',                    // Parent slug
		__( 'Settings', 'ai-wordpress-genius' ),  // Page title
		__( 'Settings', 'ai-wordpress-genius' ),  // Menu title
		'manage_options',                         // Capability
		'ai-wordpress-genius-settings',           // Menu slug
		'ai_wp_genius_render_settings_page'       // Callback function
	);
}
add_action( 'admin_menu', 'ai_wp_genius_register_admin_menu' );

/**
 * Render the dashboard page for AI WordPress Genius.
 */
function ai_wp_genius_render_dashboard_page() {
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p><?php _e( 'Welcome to the AI WordPress Genius Dashboard. Here you will be able to generate themes, plugins, and more.', 'ai-wordpress-genius' ); ?></p>

		<hr>

		<h2><?php _e( 'Child Theme Generator', 'ai-wordpress-genius' ); ?></h2>
		<p><?php _e( 'Select a parent theme from the list below to generate a child theme for it.', 'ai-wordpress-genius' ); ?></p>

		<form method="post" action="">
			<?php wp_nonce_field( 'ai_wp_genius_create_child_theme', 'ai_wp_genius_nonce' ); ?>

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Select Parent Theme', 'ai-wordpress-genius' ); ?></th>
					<td>
						<select name="parent_theme" id="parent_theme">
							<?php
							$themes = wp_get_themes();
							foreach ( $themes as $theme ) {
								// We don't want to create a child of a child theme (usually)
								if ( ! $theme->parent() ) {
									echo '<option value="' . esc_attr( $theme->get_stylesheet() ) . '">' . esc_html( $theme->get( 'Name' ) ) . '</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>
			</table>

            <?php submit_button( __( 'Create Child Theme', 'ai-wordpress-genius' ), 'primary', 'submit_child_theme' ); ?>
		</form>

		<hr>

        <h2><?php _e( 'AI Theme Generator', 'ai-wordpress-genius' ); ?></h2>
        <p><?php _e( 'Describe the theme you want to create. Be as specific as possible about the layout, colors, and typography.', 'ai-wordpress-genius' ); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field( 'ai_wp_genius_create_theme', 'ai_wp_genius_theme_nonce' ); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="theme_name"><?php _e( 'Theme Name', 'ai-wordpress-genius' ); ?></label></th>
                    <td>
                        <input type="text" id="theme_name" name="theme_name" class="regular-text" required />
                        <p class="description"><?php _e( 'e.g., My Awesome Blog Theme', 'ai-wordpress-genius' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="theme_description"><?php _e( 'Theme Description', 'ai-wordpress-genius' ); ?></label></th>
                    <td>
                        <textarea id="theme_description" name="theme_description" rows="5" class="large-text" required></textarea>
                        <p class="description"><?php _e( 'e.g., A minimalist, single-column theme with a dark background, white text, and blue links. Use a sans-serif font for headings and a serif font for body text.', 'ai-wordpress-genius' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Generate Theme', 'ai-wordpress-genius' ), 'primary', 'submit_theme' ); ?>
        </form>

		<hr>

        <h2><?php _e( 'AI Plugin Generator', 'ai-wordpress-genius' ); ?></h2>
        <p><?php _e( 'Describe the functionality of the plugin you want to create.', 'ai-wordpress-genius' ); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field( 'ai_wp_genius_create_plugin', 'ai_wp_genius_plugin_nonce' ); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="plugin_name"><?php _e( 'Plugin Name', 'ai-wordpress-genius' ); ?></label></th>
                    <td>
                        <input type="text" id="plugin_name" name="plugin_name" class="regular-text" required />
                        <p class="description"><?php _e( 'e.g., My Current Year Shortcode', 'ai-wordpress-genius' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="plugin_description"><?php _e( 'Plugin Description', 'ai-wordpress-genius' ); ?></label></th>
                    <td>
                        <textarea id="plugin_description" name="plugin_description" rows="5" class="large-text" required></textarea>
                        <p class="description"><?php _e( 'e.g., A simple plugin that creates a shortcode [year] to display the current year.', 'ai-wordpress-genius' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Generate Plugin', 'ai-wordpress-genius' ), 'primary', 'submit_plugin' ); ?>
        </form>

		<hr>

		<h2><?php _e( 'Agentic Bug Finder (Beta)', 'ai-wordpress-genius' ); ?></h2>
		<p><?php _e( 'Describe a bug or issue you are experiencing, select the suspected plugin or theme, and the AI agent will attempt to diagnose and fix it.', 'ai-wordpress-genius' ); ?></p>

		<form method="post" action="">
			<?php wp_nonce_field( 'ai_wp_genius_agent_find_bug', 'ai_wp_genius_agent_find_bug_nonce' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="bug_target_slug"><?php _e( 'Suspected Plugin/Theme', 'ai-wordpress-genius' ); ?></label></th>
					<td>
						<select name="bug_target_slug" id="bug_target_slug" style="min-width: 300px;">
							<optgroup label="<?php esc_attr_e( 'Plugins', 'ai-wordpress-genius' ); ?>">
							<?php
							$plugins = get_plugins();
							foreach ( $plugins as $plugin_file => $plugin_data ) {
								if ( strpos( $plugin_file, 'ai-wordpress-genius.php' ) !== false ) continue;
								echo '<option value="plugin:' . esc_attr( $plugin_file ) . '">' . esc_html( $plugin_data['Name'] ) . '</option>';
							}
							?>
							</optgroup>
							<optgroup label="<?php esc_attr_e( 'Themes', 'ai-wordpress-genius' ); ?>">
							<?php
							$themes = wp_get_themes();
							foreach ( $themes as $theme ) {
								echo '<option value="theme:' . esc_attr( $theme->get_stylesheet() ) . '">' . esc_html( $theme->get( 'Name' ) ) . '</option>';
							}
							?>
							</optgroup>
						</select>
						<p class="description"><?php _e( 'Select the plugin or theme you believe is causing the issue.', 'ai-wordpress-genius' ); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="bug_description"><?php _e( 'Describe the Bug', 'ai-wordpress-genius' ); ?></label></th>
					<td>
						<textarea name="bug_description" id="bug_description" rows="5" class="large-text" required></textarea>
						<p class="description"><?php _e( 'e.g., "When I submit the contact form, I get a fatal error." or "The images in the gallery are not showing up since the last update."', 'ai-wordpress-genius' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Let Agent Diagnose & Fix', 'ai-wordpress-genius' ), 'primary', 'submit_agent_find_bug' ); ?>
		</form>

		<hr>

		<h2><?php _e( 'AI Code Editor (Beta)', 'ai-wordpress-genius' ); ?></h2>
		<p><?php _e( 'Select a plugin or theme, provide an instruction, and the AI will modify the code for you. This feature will only modify the main plugin file or the theme\'s `functions.php` file.', 'ai-wordpress-genius' ); ?></p>

		<?php
		// Display diff and approval form if it exists
		$modification_request = get_transient( 'ai_wp_genius_modification_request' );
		if ( $modification_request ) :
			$diff_table = wp_text_diff( $modification_request['original_content'], $modification_request['new_content'], [
				'title' => __( 'Proposed Changes', 'ai-wordpress-genius' ),
				'title_left' => __( 'Original Code', 'ai-wordpress-genius' ),
				'title_right' => __( 'New Code', 'ai-wordpress-genius' ),
			]);
		?>
			<h3><?php _e( 'Review and Approve Changes', 'ai-wordpress-genius' ); ?></h3>
			<p><?php printf( __( 'AI has proposed the following changes for the file: %s', 'ai-wordpress-genius' ), '<code>' . esc_html( $modification_request['relative_path'] ) . '</code>' ); ?></p>

			<h4><?php _e( 'AI Explanation', 'ai-wordpress-genius' ); ?></h4>
			<div class="ai-explanation-box">
				<?php echo ai_wp_genius_format_ai_response( $modification_request['explanation'] ); ?>
			</div>

			<?php echo $diff_table; ?>

			<div style="display: flex; gap: 10px; align-items: center; margin-top: 20px;">
				<form method="post" action="" style="margin: 0;">
					<?php wp_nonce_field( 'ai_wp_genius_approve_changes', 'ai_wp_genius_approve_changes_nonce' ); ?>
					<input type="hidden" name="modification_key" value="<?php echo esc_attr( $modification_request['key'] ); ?>" />
					<?php submit_button( __( 'Approve & Apply Changes', 'ai-wordpress-genius' ), 'primary', 'submit_approve_changes', false ); ?>
				</form>
				<a href="<?php echo esc_url( add_query_arg( 'ai_action', 'cancel_modification', admin_url( 'admin.php?page=ai-wordpress-genius' ) ) ); ?>" class="button button-secondary"><?php _e( 'Cancel', 'ai-wordpress-genius' ); ?></a>
			</div>

			<hr>
			<h4><?php _e( 'Not quite right? Give the AI a follow-up instruction.', 'ai-wordpress-genius' ); ?></h4>
			<form method="post" action="">
				<?php wp_nonce_field( 'ai_wp_genius_follow_up', 'ai_wp_genius_follow_up_nonce' ); ?>
				<input type="hidden" name="session_id" value="<?php echo esc_attr( $modification_request['session_id'] ); ?>" />
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="follow_up_instruction"><?php _e( 'Follow-up Instruction', 'ai-wordpress-genius' ); ?></label></th>
						<td>
							<textarea name="follow_up_instruction" id="follow_up_instruction" rows="3" class="large-text" required></textarea>
							<p class="description"><?php _e( 'e.g., "That is good, but also add PHPdoc comments to the new function." or "Change the variable name from `$foo` to `$bar`."', 'ai-wordpress-genius' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Send Follow-up', 'ai-wordpress-genius' ), 'secondary', 'submit_follow_up' ); ?>
			</form>
		<?php
		else :
		// Show the editor forms if no approval is pending
		?>
			<h4><?php _e( 'Modify a Plugin', 'ai-wordpress-genius' ); ?></h4>
			<form method="post" action="">
				<?php wp_nonce_field( 'ai_wp_genius_modify_plugin', 'ai_wp_genius_modify_plugin_nonce' ); ?>
				<input type="hidden" name="modify_type" value="plugin" />
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="plugin_to_modify"><?php _e( 'Select Plugin', 'ai-wordpress-genius' ); ?></label></th>
						<td>
							<select name="modify_target" id="plugin_to_modify" style="min-width: 300px;">
								<?php
								$plugins = get_plugins();
								foreach ( $plugins as $plugin_file => $plugin_data ) {
									if ( strpos( $plugin_file, 'ai-wordpress-genius.php' ) !== false ) continue;
									echo '<option value="' . esc_attr( $plugin_file ) . '">' . esc_html( $plugin_data['Name'] ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="plugin_instruction"><?php _e( 'Instructions', 'ai-wordpress-genius' ); ?></label></th>
						<td>
							<textarea name="instruction" id="plugin_instruction" rows="3" class="large-text"></textarea>
							<p class="description"><?php _e( 'e.g., "Change the version number to 2.0.0" or "Add a new shortcode [hello] that returns \'Hello World\'"', 'ai-wordpress-genius' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Generate Proposed Changes', 'ai-wordpress-genius' ), 'secondary', 'submit_modify_plugin' ); ?>
			</form>

			<h4><?php _e( 'Modify a Theme', 'ai-wordpress-genius' ); ?></h4>
			<form method="post" action="">
				<?php wp_nonce_field( 'ai_wp_genius_modify_theme', 'ai_wp_genius_modify_theme_nonce' ); ?>
				<input type="hidden" name="modify_type" value="theme" />
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="theme_to_modify"><?php _e( 'Select Theme', 'ai-wordpress-genius' ); ?></label></th>
						<td>
							<select name="modify_target" id="theme_to_modify" style="min-width: 300px;">
								<?php
								$themes = wp_get_themes();
								foreach ( $themes as $theme ) {
									echo '<option value="' . esc_attr( $theme->get_stylesheet() ) . '">' . esc_html( $theme->get( 'Name' ) ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="theme_instruction"><?php _e( 'Instructions', 'ai-wordpress-genius' ); ?></label></th>
						<td>
							<textarea name="instruction" id="theme_instruction" rows="3" class="large-text"></textarea>
							<p class="description"><?php _e( 'e.g., "Add a function that logs \'Theme is running\' to the error log on init"', 'ai-wordpress-genius' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Generate Proposed Changes', 'ai-wordpress-genius' ), 'secondary', 'submit_modify_theme' ); ?>
			</form>
		<?php endif; ?>

	</div>
	<?php
}
