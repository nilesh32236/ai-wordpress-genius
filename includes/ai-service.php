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

    // Handle specific HTTP errors that suggest a retry.
    if ( in_array( $response_code, [ 429, 503 ], true ) ) {
        return new WP_Error(
            'api_retryable_error',
            __( 'The API service is temporarily unavailable or you have exceeded the rate limit. Please try again in a few minutes.', 'ai-wordpress-genius' ),
            [ 'status' => $response_code ]
        );
    }

    $response_data = json_decode( $response_body, true );

    // Handle JSON decoding errors.
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error(
            'invalid_json_response',
            __( 'The API returned an invalid response that could not be decoded.', 'ai-wordpress-genius' ),
            [
                'status'        => $response_code,
                'json_error'    => json_last_error_msg(),
                'response_body' => substr( $response_body, 0, 500 ), // Include a snippet of the body.
            ]
        );
    }

    // Handle non-200 responses that are valid JSON.
    if ( $response_code !== 200 ) {
        $error_message = isset( $response_data['error']['message'] ) ? $response_data['error']['message'] : __( 'An unknown API error occurred.', 'ai-wordpress-genius' );
        return new WP_Error(
            'api_error',
            sprintf( __( 'API Error (%d): %s', 'ai-wordpress-genius' ), $response_code, $error_message ),
            isset( $response_data['error'] ) ? $response_data['error'] : []
        );
    }

    // Handle safety blocks from the API.
    if ( ! empty( $response_data['promptFeedback']['blockReason'] ) ) {
        $reason = $response_data['promptFeedback']['blockReason'];
        /* translators: %s: The reason the prompt was blocked by the API. */
        $message = sprintf( __( 'The request was blocked by the API for the following reason: %s. Please adjust your prompt and try again.', 'ai-wordpress-genius' ), '<strong>' . esc_html( $reason ) . '</strong>' );
        return new WP_Error( 'api_safety_block', $message, $response_data['promptFeedback'] );
    }

    if ( empty( $response_data['candidates'][0]['content']['parts'][0]['text'] ) ) {
        // This can happen if the AI returns empty content for other reasons.
        return new WP_Error( 'no_content', __( 'The AI returned an empty or invalid response. This might be due to the safety settings of your prompt or a temporary API issue.', 'ai-wordpress-genius' ) );
    }

    $ai_response_text = $response_data['candidates'][0]['content']['parts'][0]['text'];

    if ( $session_id ) {
        // Save the AI's response to the history
        ai_wp_genius_add_to_session_history( $session_id, 'model', $ai_response_text );
    }

    return $ai_response_text;
}
