<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Generates the prompt for the AI diagnosis.
 *
 * @param string $bug_description The user's description of the bug.
 * @return string The generated prompt.
 */
function ai_wp_genius_generate_diagnosis_prompt( $bug_description ) {
	// Get environment data to provide context to the AI.
	if ( ! function_exists( 'wp_get_environment_info' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}
	$env_info = wp_get_environment_info();
	// We only need a subset of the environment info to avoid making the prompt too large.
	$context = [
		'wordpress' => $env_info['wp'],
		'server'    => $env_info['server'],
		'theme'     => $env_info['theme'],
		'plugins'   => array_keys( $env_info['plugins']['active'] ),
	];

	return sprintf(
		"You are an expert WordPress developer and diagnostician. Your task is to analyze a user's bug report and a snapshot of their WordPress environment, then determine which PHP files are the most likely source of the problem.

The user's bug report is: \"%s\"

Here is the environment information:
%s

Based on the user's report and the environment, identify the most relevant files to inspect for the bug.

You MUST respond with ONLY a valid JSON object and nothing else. Do not include any explanatory text before or after the JSON. The JSON object must contain a single key, \"files\", which is an array of strings. Each string should be a full path to a file within the WordPress installation that needs to be inspected.

Example response:
{
  \"files\": [
    \"/var/www/html/wp-content/plugins/some-plugin/includes/class-form-handler.php\",
    \"/var/www/html/wp-content/themes/some-theme/functions.php\"
  ]
}",
		$bug_description,
		json_encode( $context, JSON_PRETTY_PRINT )
	);
}

/**
 * Handles the bug finder agent submission.
 */
function ai_wp_genius_handle_agent_bug_finder() {
	if ( ! isset( $_POST['submit_agent_find_bug'] ) ) {
		return;
	}

	if ( ! isset( $_POST['ai_wp_genius_agent_find_bug_nonce'] ) || ! wp_verify_nonce( $_POST['ai_wp_genius_agent_find_bug_nonce'], 'ai_wp_genius_agent_find_bug' ) ) {
		wp_die( __( 'Security check failed.', 'ai-wordpress-genius' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'ai-wordpress-genius' ) );
	}

	$bug_description = sanitize_textarea_field( $_POST['bug_description'] );
	$prompt = ai_wp_genius_generate_diagnosis_prompt( $bug_description );
	$ai_response_json = ai_wp_genius_get_ai_response( $prompt );

	if ( is_wp_error( $ai_response_json ) ) {
		add_action( 'admin_notices', function () use ( $ai_response_json ) {
			echo '<div class="notice notice-error is-dismissible"><p><strong>' . __( 'AI Diagnosis Error:', 'ai-wordpress-genius' ) . '</strong> ' . esc_html( $ai_response_json->get_error_message() ) . '</p></div>';
		} );
		return;
	}

	$decoded_response = ai_wp_genius_clean_and_decode_json( $ai_response_json );
	if ( ! $decoded_response || ! isset( $decoded_response['files'] ) || ! is_array( $decoded_response['files'] ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error is-dismissible"><p>' . __( 'The AI returned an invalid or unexpected JSON format for the file list. Please try again.', 'ai-wordpress-genius' ) . '</p></div>';
		} );
		return;
	}

	$files_to_inspect = $decoded_response['files'];

	if ( empty( $files_to_inspect ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-warning is-dismissible"><p>' . __( 'The AI diagnosed the issue but did not identify any specific files to inspect. The problem might be related to configuration or external factors.', 'ai-wordpress-genius' ) . '</p></div>';
		} );
		return;
	}

	// Step 2: Analyze the files and propose a fix for the first one that needs changes.
	$proposed_fix = ai_wp_genius_analyze_and_fix_files( $files_to_inspect, $bug_description );

	if ( is_wp_error( $proposed_fix ) ) {
		add_action( 'admin_notices', function () use ( $proposed_fix ) {
			echo '<div class="notice notice-error is-dismissible"><p><strong>' . __( 'AI Analysis Error:', 'ai-wordpress-genius' ) . '</strong> ' . esc_html( $proposed_fix->get_error_message() ) . '</p></div>';
		} );
		return;
	}

	if ( ! $proposed_fix ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-info is-dismissible"><p>' . __( 'The AI analyzed the suspected files but did not find any code to modify. The issue may be elsewhere, or it may not be a code-related problem.', 'ai-wordpress-genius' ) . '</p></div>';
		} );
		return;
	}

	// Step 3: Hand off the proposed fix to the approval workflow.
	$key = md5( uniqid( rand(), true ) );
	$modification_request = [
		'key'              => $key,
		'full_path'        => $proposed_fix['full_path'],
		'relative_path'    => str_replace( WP_CONTENT_DIR, '', $proposed_fix['full_path'] ),
		'original_content' => $proposed_fix['original_content'],
		'new_content'      => $proposed_fix['new_content'],
		'explanation'      => $proposed_fix['explanation'],
	];
	set_transient( 'ai_wp_genius_modification_request', $modification_request, HOUR_IN_SECONDS );

	wp_safe_redirect( admin_url( 'admin.php?page=ai-wordpress-genius' ) );
	exit;
}
add_action( 'admin_init', 'ai_wp_genius_handle_agent_bug_finder' );

/**
 * Generates the prompt for the AI to fix a file.
 *
 * @param string $bug_description The user's original bug description.
 * @param string $file_path The full path to the file being analyzed.
 * @param string $original_content The original content of the file.
 * @return string The generated prompt.
 */
function ai_wp_genius_generate_fix_prompt( $bug_description, $file_path, $original_content ) {
	return sprintf(
		"You are an expert WordPress developer and debugger. Your task is to analyze a PHP file that is suspected of causing a bug and rewrite it with a fix.

The original bug report from the user was: \"%s\"

Here is the entire original content of the PHP file (`%s`):
```php
%s
```

Please analyze the file in the context of the user's bug report and rewrite the entire file with the necessary corrections.

You MUST respond with ONLY a valid JSON object and nothing else. Do not include any explanatory text before or after the JSON. The JSON object must have the following structure:
{
  \"explanation\": \"A clear explanation of the changes you made to fix the bug, using Markdown for formatting.\",
  \"code\": \"The complete, modified content of the PHP file.\"
}
If you determine that no changes are needed in this specific file, you MUST return the original, unmodified file content in the \"code\" field and provide an explanation like \"No changes needed in this file.\"",
		$bug_description,
		$file_path,
		$original_content
	);
}

/**
 * Analyzes a list of files and returns a proposed fix for the first one that needs changes.
 *
 * @param array $files_to_inspect An array of full file paths.
 * @param string $bug_description The user's original bug description.
 * @return array|WP_Error|false An array with fix data, a WP_Error on failure, or false if no fix is needed.
 */
function ai_wp_genius_analyze_and_fix_files( $files_to_inspect, $bug_description ) {
	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();
	}

	foreach ( $files_to_inspect as $file_path ) {
		// Basic security check: ensure the file is within the wp-content directory.
		if ( strpos( realpath( $file_path ), realpath( WP_CONTENT_DIR ) ) !== 0 ) {
			continue; // Skip files outside of wp-content
		}

		if ( ! file_exists( $file_path ) ) {
			continue; // Skip files that don't exist
		}

		$original_content = $wp_filesystem->get_contents( $file_path );
		if ( $original_content === false ) {
			continue; // Skip files that can't be read
		}

		$prompt = ai_wp_genius_generate_fix_prompt( $bug_description, $file_path, $original_content );
		$ai_response_json = ai_wp_genius_get_ai_response( $prompt );

		if ( is_wp_error( $ai_response_json ) ) {
			return $ai_response_json; // Propagate the error up
		}

		$decoded_response = ai_wp_genius_clean_and_decode_json( $ai_response_json );

		if ( ! $decoded_response || ! isset( $decoded_response['code'] ) || ! isset( $decoded_response['explanation'] ) ) {
			// If the AI fails to return valid JSON, we can't proceed with this file.
			// We could return an error here, but it's better to just skip to the next file.
			continue;
		}

		$new_content = $decoded_response['code'];

		// Check if the AI actually made a change (trimming whitespace for comparison)
		if ( trim( $original_content ) !== trim( $new_content ) ) {
			return [
				'full_path'        => $file_path,
				'original_content' => $original_content,
				'new_content'      => $new_content,
				'explanation'      => $decoded_response['explanation'],
			];
		}
	}

	return false; // No changes were proposed for any of the files
}
