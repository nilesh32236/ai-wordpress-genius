<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Generates a new unique session ID.
 *
 * @return string The unique session ID.
 */
function ai_wp_genius_create_new_session_id() {
	return wp_generate_uuid4();
}

/**
 * Adds a message to the session history in the database.
 *
 * @param string $session_id The ID of the session.
 * @param string $role The role of the message author ('user' or 'model').
 * @param string $content The content of the message.
 * @return void
 */
function ai_wp_genius_add_to_session_history( $session_id, $role, $content ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ai_genius_session_history';

	$wpdb->insert(
		$table_name,
		[
			'session_id' => $session_id,
			'role'       => $role,
			'content'    => $content,
			'created_at' => current_time( 'mysql' ),
		],
		[
			'%s',
			'%s',
			'%s',
			'%s',
		]
	);
}

/**
 * Retrieves and formats the conversation history for a given session.
 *
 * @param string $session_id The ID of the session.
 * @return array The formatted history ready for the Gemini API.
 */
function ai_wp_genius_get_session_history( $session_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ai_genius_session_history';

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT role, content FROM $table_name WHERE session_id = %s ORDER BY created_at ASC",
			$session_id
		)
	);

	$history = [];
	foreach ( $results as $row ) {
		$history[] = [
			'role'  => $row->role,
			'parts' => [
				[
					'text' => $row->content,
				],
			],
		];
	}

	return $history;
}
