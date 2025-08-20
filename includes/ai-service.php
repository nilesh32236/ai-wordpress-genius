<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Makes a request to the configured AI service.
 *
 * @param string $prompt The prompt to send to the AI.
 * @return string|WP_Error The AI's response text on success, or a WP_Error object on failure.
 */
function ai_wp_genius_get_ai_response( $prompt ) {
    $api_key = get_option( 'ai_wp_genius_api_key' );

    if ( empty( $api_key ) ) {
        return new WP_Error( 'api_key_missing', __( 'AI API key is not configured. Please add it in the settings page.', 'ai-wordpress-genius' ) );
    }

    $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key;

    $request_body = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt,
                    ],
                ],
            ],
        ],
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

    return $response_data['candidates'][0]['content']['parts'][0]['text'];
}
