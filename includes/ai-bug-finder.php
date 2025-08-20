<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Generates the prompt for the AI Bug Finder analysis.
 *
 * @param string $issue_description The description of the issue found.
 * @param string $code_context The code snippet surrounding the issue.
 * @return string The generated prompt.
 */
function ai_wp_genius_generate_bug_analysis_prompt( $issue_description, $code_context ) {
	return sprintf(
		"You are an expert WordPress developer and code reviewer. Your task is to analyze a piece of PHP code from a WordPress plugin or theme that contains a potential issue, explain the problem, and suggest a fix.

The issue found is: \"%s\"

Here is the relevant code snippet. The line with the issue is marked with `// <<< ISSUE HERE`:
```php
%s
```

You MUST respond with ONLY a valid JSON object and nothing else. Do not include ```json markdown delimiters or any explanatory text before or after the JSON. The JSON object must have the following structure:
{
  \"explanation\": \"A clear, concise explanation of why this is a problem in the context of WordPress development.\",
  \"suggestion\": \"A specific code replacement or modification to fix the issue.\"
}",
		$issue_description,
		$code_context
	);
}

/**
 * Recursively scans a directory for issues in PHP files.
 *
 * @param string $dir_path The path to the directory to scan.
 * @return array An array of findings.
 */
function ai_wp_genius_scan_directory( $dir_path ) {
	$findings = [];
	try {
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir_path, RecursiveDirectoryIterator::SKIP_DOTS ) );

		foreach ( $iterator as $file ) {
			if ( $file->isDir() || $file->getExtension() !== 'php' ) {
				continue;
			}

			$lines = file( $file->getPathname() );
			if ( $lines === false ) {
				continue;
			}

			$rules = [
				'get_bloginfo(\'siteurl\')' => 'Deprecated function `get_bloginfo(\'siteurl\')` found.',
				'add_contextual_help' => 'Deprecated function `add_contextual_help()` found.',
				'wp_oembed_get' => 'Deprecated function `wp_oembed_get()` found.',
				'/__\(\s*\'[^\']*\'\s*\)/' => 'Missing text domain in localization function.',
				'/_e\(\s*\'[^\']*\'\s*\)/' => 'Missing text domain in localization function.',
			];

			foreach ( $lines as $line_num => $line ) {
				foreach ( $rules as $pattern => $issue_description ) {
					$is_regex = ( substr( $pattern, 0, 1 ) === '/' );
					$match_found = $is_regex ? preg_match( $pattern, $line ) : strpos( $line, $pattern ) !== false;

					if ( $match_found ) {
						$context_start = max( 0, $line_num - 2 );
						$context_end = min( count( $lines ) - 1, $line_num + 2 );
						$code_context = '';
						for ( $i = $context_start; $i <= $context_end; $i++ ) {
							$code_context .= rtrim( $lines[$i] );
							if ( $i === $line_num ) {
								$code_context .= ' // <<< ISSUE HERE';
							}
							$code_context .= "\n";
						}

						$prompt = ai_wp_genius_generate_bug_analysis_prompt( $issue_description, $code_context );
						$ai_response_json = ai_wp_genius_get_ai_response( $prompt );

						$ai_suggestion = [
							'explanation' => 'AI analysis failed or returned an invalid format.',
							'suggestion'  => 'Please review the code manually.',
						];

						if ( ! is_wp_error( $ai_response_json ) ) {
							$decoded_response = json_decode( $ai_response_json, true );
							if ( json_last_error() === JSON_ERROR_NONE && isset( $decoded_response['explanation'] ) && isset( $decoded_response['suggestion'] ) ) {
								$ai_suggestion = $decoded_response;
							}
						}

						$findings[] = [
							'file'        => str_replace( WP_CONTENT_DIR, '', $file->getPathname() ),
							'line'        => $line_num + 1,
							'issue'       => $issue_description,
							'ai_suggestion' => $ai_suggestion,
						];

						// Stop checking other rules for this line to avoid duplicate findings on the same line
						break;
					}
				}
			}
		}
	} catch ( Exception $e ) {
		// Could log the error if a logging system was in place.
	}

	return $findings;
}


/**
 * Handles the bug finder scan submission.
 */
function ai_wp_genius_handle_bug_finder_scan() {
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
		return;
	}

	$findings = ai_wp_genius_scan_directory( $target_path );

	$results = [
		'name'     => $target_name,
		'findings' => $findings,
	];

	set_transient( 'ai_wp_genius_scan_results', $results, HOUR_IN_SECONDS );

	$redirect_url = admin_url( 'admin.php?page=ai-wordpress-genius' );
	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_init', 'ai_wp_genius_handle_bug_finder_scan' );

/**
 * Handles the submission for the one-click fix.
 */
function ai_wp_genius_handle_apply_fix() {
	if ( ! isset( $_POST['submit_apply_fix'] ) ) {
		return;
	}

	$file_path = sanitize_text_field( $_POST['file_path'] );
	$line_number = absint( $_POST['line_number'] );
	// The suggestion is code, so we need to be careful with sanitization.
	// We'll rely on the fact that it comes from our AI and is displayed to the user for approval.
	// `wp_unslash` is important because WordPress will add slashes to POST data.
	$suggestion = wp_unslash( $_POST['suggestion'] );

	if ( ! isset( $_POST['ai_wp_genius_apply_fix_nonce'] ) || ! wp_verify_nonce( $_POST['ai_wp_genius_apply_fix_nonce'], 'ai_wp_genius_apply_fix_' . md5( $file_path . $line_number ) ) ) {
		wp_die( __( 'Security check failed.', 'ai-wordpress-genius' ) );
	}

	if ( ! current_user_can( 'edit_plugins' ) && ! current_user_can( 'edit_themes' ) ) {
		wp_die( __( 'You do not have permission to edit files.', 'ai-wordpress-genius' ) );
	}

	$full_path = WP_CONTENT_DIR . $file_path;

	if ( ! file_exists( $full_path ) ) {
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'File to modify not found.', 'ai-wordpress-genius' ) . '</p></div>';
		});
		return;
	}

	$lines = file( $full_path );
	if ( $lines === false || ! isset( $lines[ $line_number - 1 ] ) ) {
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Could not read file or line number is invalid.', 'ai-wordpress-genius' ) . '</p></div>';
		});
		return;
	}

	// Preserve indentation
	$original_line = $lines[ $line_number - 1 ];
	preg_match( '/^(\s*)/', $original_line, $matches );
	$indentation = $matches[1] ?? '';

	$lines[ $line_number - 1 ] = $indentation . $suggestion . "\n";
	$new_content = implode( '', $lines );

	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();
	}

	if ( $wp_filesystem->put_contents( $full_path, $new_content, FS_CHMOD_FILE ) ) {
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-success is-dismissible"><p>' . __( 'File successfully updated!', 'ai-wordpress-genius' ) . '</p></div>';
		});
	} else {
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Could not write to file. Please check file permissions.', 'ai-wordpress-genius' ) . '</p></div>';
		});
	}
}
add_action( 'admin_init', 'ai_wp_genius_handle_apply_fix' );
