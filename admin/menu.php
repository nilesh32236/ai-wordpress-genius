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

		<h2><?php _e( 'AI Bug Finder', 'ai-wordpress-genius' ); ?></h2>
        <p><?php _e( 'Select a plugin or theme to scan for common issues and deprecated code.', 'ai-wordpress-genius' ); ?></p>

        <?php
        $scan_results = get_transient( 'ai_wp_genius_scan_results' );
        if ( $scan_results ) :
            // Display results if they exist
            ?>
            <div id="ai-scan-results">
                <h3><?php printf( __( 'Scan Results for %s', 'ai-wordpress-genius' ), '<code>' . esc_html( $scan_results['name'] ) . '</code>' ); ?></h3>
                <?php if ( ! empty( $scan_results['findings'] ) ) : ?>
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e( 'File', 'ai-wordpress-genius' ); ?></th>
                                <th><?php _e( 'Line', 'ai-wordpress-genius' ); ?></th>
                                <th><?php _e( 'Issue Found', 'ai-wordpress-genius' ); ?></th>
                                <th><?php _e( 'AI Suggestion', 'ai-wordpress-genius' ); ?></th>
                                <th><?php _e( 'Action', 'ai-wordpress-genius' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $scan_results['findings'] as $finding ) : ?>
                                <tr>
                                    <td><code><?php echo esc_html( $finding['file'] ); ?></code></td>
                                    <td><?php echo esc_html( $finding['line'] ); ?></td>
                                    <td><code><?php echo esc_html( $finding['issue'] ); ?></code></td>
                                    <td>
                                        <p><?php echo esc_html( $finding['ai_suggestion']['explanation'] ); ?></p>
                                        <p><strong><?php _e( 'Suggestion:', 'ai-wordpress-genius' ); ?></strong></p>
                                        <pre><code><?php echo esc_html( $finding['ai_suggestion']['suggestion'] ); ?></code></pre>
                                    </td>
                                    <td>
                                        <form method="post" action="">
                                            <?php wp_nonce_field( 'ai_wp_genius_apply_fix_' . md5( $finding['file'] . $finding['line'] ), 'ai_wp_genius_apply_fix_nonce' ); ?>
                                            <input type="hidden" name="file_path" value="<?php echo esc_attr( $finding['file'] ); ?>" />
                                            <input type="hidden" name="line_number" value="<?php echo esc_attr( $finding['line'] ); ?>" />
                                            <input type="hidden" name="suggestion" value="<?php echo esc_attr( $finding['ai_suggestion']['suggestion'] ); ?>" />
                                            <?php submit_button( __( 'Apply Fix', 'ai-wordpress-genius' ), 'small', 'submit_apply_fix', false ); ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div class="notice notice-success is-dismissible"><p><?php _e( 'No issues found!', 'ai-wordpress-genius' ); ?></p></div>
                <?php endif; ?>
            </div>
            <hr>
            <?php
            delete_transient( 'ai_wp_genius_scan_results' ); // Delete after displaying
        endif;
        ?>

        <h4><?php _e( 'Scan a Plugin', 'ai-wordpress-genius' ); ?></h4>
        <form method="post" action="">
            <?php wp_nonce_field( 'ai_wp_genius_run_scan_plugin', 'ai_wp_genius_scan_plugin_nonce' ); ?>
            <input type="hidden" name="scan_type" value="plugin" />
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="plugin_to_scan"><?php _e( 'Select Plugin', 'ai-wordpress-genius' ); ?></label></th>
                    <td>
                        <select name="scan_target" id="plugin_to_scan" style="min-width: 300px;">
                            <?php
                            $plugins = get_plugins();
                            foreach ( $plugins as $plugin_file => $plugin_data ) {
                                if ( strpos( $plugin_file, 'ai-wordpress-genius.php' ) !== false ) continue; // Don't allow scanning self
                                echo '<option value="' . esc_attr( $plugin_file ) . '">' . esc_html( $plugin_data['Name'] ) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Scan Plugin', 'ai-wordpress-genius' ), 'secondary', 'submit_scan_plugin' ); ?>
        </form>

        <h4><?php _e( 'Scan a Theme', 'ai-wordpress-genius' ); ?></h4>
        <form method="post" action="">
            <?php wp_nonce_field( 'ai_wp_genius_run_scan_theme', 'ai_wp_genius_scan_theme_nonce' ); ?>
            <input type="hidden" name="scan_type" value="theme" />
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="theme_to_scan"><?php _e( 'Select Theme', 'ai-wordpress-genius' ); ?></label></th>
                    <td>
                        <select name="scan_target" id="theme_to_scan" style="min-width: 300px;">
                            <?php
                            $themes = wp_get_themes();
                            foreach ( $themes as $theme ) {
                                echo '<option value="' . esc_attr( $theme->get_stylesheet() ) . '">' . esc_html( $theme->get( 'Name' ) ) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Scan Theme', 'ai-wordpress-genius' ), 'secondary', 'submit_scan_theme' ); ?>
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
			<?php echo $diff_table; ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'ai_wp_genius_approve_changes', 'ai_wp_genius_approve_changes_nonce' ); ?>
				<input type="hidden" name="modification_key" value="<?php echo esc_attr( $modification_request['key'] ); ?>" />
				<?php submit_button( __( 'Approve & Apply Changes', 'ai-wordpress-genius' ), 'primary', 'submit_approve_changes' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-wordpress-genius' ) ); ?>" class="button button-secondary"><?php _e( 'Cancel', 'ai-wordpress-genius' ); ?></a>
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
