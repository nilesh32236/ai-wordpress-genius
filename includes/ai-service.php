<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Makes a request to the configured AI service, supporting conversational history.
 *
 * @param string $prompt The new user prompt to send to the AI.
 * @param string|null $session_id The optional ID of the conversation session.
 * @return string|WP_Error The AI's response text on success, or a WP_Error object on failure.
 */
function ai_wp_genius_get_ai_response( $prompt, $session_id = null ) {
	$api_key = get_option( 'ai_wp_genius_api_key' );

	if ( empty( $api_key ) ) {
		return new WP_Error( 'api_key_missing', __( 'AI API key is not configured. Please add it in the settings page.', 'ai-wordpress-genius' ) );
	}

	$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key;
	$history = [];

	if ( $session_id ) {
		// Get existing history
		$history = ai_wp_genius_get_session_history( $session_id );
		// Add the new user prompt to the database
		ai_wp_genius_add_to_session_history( $session_id, 'user', $prompt );
	}

	// Construct the 'contents' array for the API call
	$contents = $history;
	$contents[] = [
		'role' => 'user',
		'parts' => [
			[
				'text' => $prompt,
			],
		],
	];

	$request_body = [
		'contents' => $contents,
	];

	$response = wp_remote_post(
        $api_url,
        [
            'method'  => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body'    => json_encode( $request_body ),
            'timeout' => 60, // Increase timeout for potentially long AI responses
        ]
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );
    $response_data = json_decode( $response_body, true );

    if ( $response_code !== 200 ) {
        $error_message = isset( $response_data['error']['message'] ) ? $response_data['error']['message'] : __( 'Unknown API error.', 'ai-wordpress-genius' );
        return new WP_Error( 'api_error', 'API Error: ' . $error_message );
    }

    if ( empty( $response_data['candidates'][0]['content']['parts'][0]['text'] ) ) {
        // This can happen if the AI returns a "safety" response or other empty content
        return new WP_Error( 'no_content', __( 'The AI returned an empty or invalid response. This might be due to the safety settings of your prompt or a temporary API issue.', 'ai-wordpress-genius' ) );
    }

    $ai_response_text = $response_data['candidates'][0]['content']['parts'][0]['text'];

    if ( $session_id ) {
        // Save the AI's response to the history
        ai_wp_genius_add_to_session_history( $session_id, 'model', $ai_response_text );
    }

    return $ai_response_text;
}
