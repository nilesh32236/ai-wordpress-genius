<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Get a simulated AI analysis for a found issue.
 *
 * @param string $issue The issue text.
 * @return array An array containing the explanation and suggestion.
 */
function ai_wp_genius_get_simulated_ai_analysis( $issue ) {
	$analysis = [
		'explanation' => 'No specific analysis available for this issue.',
		'suggestion'  => 'Please review the WordPress coding standards.',
	];

	if ( strpos( $issue, 'get_bloginfo(\'siteurl\')' ) !== false ) {
		$analysis['explanation'] = "The function `get_bloginfo('siteurl')` is deprecated for retrieving the main site URL. It can be inconsistent in some setups.";
		$analysis['suggestion']  = "It is recommended to use `home_url()` instead for better reliability and consistency.";
	}

	return $analysis;
}

/**
 * Recursively scans a directory for issues in PHP files.
 *
 * @param string $dir_path The path to the directory to scan.
 * @return array An array of findings.
 */
function ai_wp_genius_scan_directory( $dir_path ) {
	$findings = [];
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir_path ) );

	foreach ( $iterator as $file ) {
		if ( $file->isDir() ) {
			continue;
		}

		if ( pathinfo( $file, PATHINFO_EXTENSION ) === 'php' ) {
			$content = file( $file->getPathname() );
			foreach ( $content as $line_num => $line ) {
				// Rule: Find deprecated get_bloginfo('siteurl')
				if ( strpos( $line, 'get_bloginfo(\'siteurl\')' ) !== false ) {
					$findings[] = [
						'file'        => str_replace( WP_CONTENT_DIR, '', $file->getPathname() ), // Relative path
						'line'        => $line_num + 1,
						'issue'       => "Deprecated function found: get_bloginfo('siteurl')",
						'ai_suggestion' => ai_wp_genius_get_simulated_ai_analysis( "get_bloginfo('siteurl')" ),
					];
				}
			}
		}
	}

	return $findings;
}

/**
 * Handles the bug finder scan submission.
 */
function ai_wp_genius_handle_bug_finder_scan() {
	// Check if a scan was submitted
	if ( ! isset( $_POST['submit_scan_plugin'] ) && ! isset( $_POST['submit_scan_theme'] ) ) {
		return;
	}

	$scan_type = sanitize_text_field( $_POST['scan_type'] );
	$target_slug = sanitize_text_field( $_POST['scan_target'] );
	$nonce_action = 'ai_wp_genius_run_scan_' . $scan_type;
	$nonce_name = 'ai_wp_genius_scan_' . $scan_type . '_nonce';

	if ( ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( $_POST[$nonce_name], $nonce_action ) ) {
		wp_die( __( 'Security check failed.', 'ai-wordpress-genius' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'ai-wordpress-genius' ) );
	}

	$target_path = '';
	$target_name = '';

	if ( $scan_type === 'plugin' ) {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $target_slug );
		$target_name = $plugin_data['Name'];
		$target_path = WP_PLUGIN_DIR . '/' . dirname( $target_slug );
	} elseif ( $scan_type === 'theme' ) {
		$theme = wp_get_theme( $target_slug );
		$target_name = $theme->get( 'Name' );
		$target_path = $theme->get_stylesheet_directory();
	}

	if ( ! is_dir( $target_path ) ) {
		// Handle error: directory not found
		return;
	}

	$findings = ai_wp_genius_scan_directory( $target_path );

	$results = [
		'name'     => $target_name,
		'findings' => $findings,
	];

	set_transient( 'ai_wp_genius_scan_results', $results, HOUR_IN_SECONDS );

	// Redirect back to the settings page
	$redirect_url = admin_url( 'admin.php?page=ai-wordpress-genius' );
	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_init', 'ai_wp_genius_handle_bug_finder_scan' );
