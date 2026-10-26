<?php
/**
 * Plugin Name: Are Media Category Articles
 * Description: A magazine-style Gutenberg block showing the latest category articles.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: jlrampola
 * License: GPL-2.0-or-later
 * Text Domain: aremedia-gutenberg
 * Domain Path: /languages
 *
 * @package AreMediaGutenberg
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Register the block and optional public sponsor text. */
function aremedia_gutenberg_register() {
	register_block_type( __DIR__ . '/build' );
	wp_set_script_translations( 'aremedia-category-articles-editor-script', 'aremedia-gutenberg', __DIR__ . '/languages' );
	register_post_meta(
		'post',
		'aremedia_sponsor',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => 'aremedia_gutenberg_can_edit_sponsor',
		)
	);
}
add_action( 'init', 'aremedia_gutenberg_register' );

/** Load the sponsor panel only in the standard post block editor. */
function aremedia_gutenberg_enqueue_sponsor_editor() {
	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->post_type ) {
		return;
	}
	$asset = require __DIR__ . '/build/sponsor.asset.php';
	wp_enqueue_script(
		'aremedia-sponsor-editor',
		plugins_url( 'build/sponsor.js', __FILE__ ),
		$asset['dependencies'],
		$asset['version'],
		true
	);
	wp_set_script_translations( 'aremedia-sponsor-editor', 'aremedia-gutenberg', __DIR__ . '/languages' );
}
add_action( 'enqueue_block_editor_assets', 'aremedia_gutenberg_enqueue_sponsor_editor' );

/**
 * Limit sponsor changes to users who can edit the owning post.
 *
 * @param bool   $allowed Existing authorization.
 * @param string $key     Meta key.
 * @param int    $post_id Owning post.
 * @return bool
 */
function aremedia_gutenberg_can_edit_sponsor( $allowed, $key, $post_id ) {
	return current_user_can( 'edit_post', $post_id );
}

/**
 * Shared query contract for frontend and the existing posts REST endpoint.
 *
 * @param int $category_id Selected category.
 * @return array
 */
function aremedia_gutenberg_query_args( $category_id ) {
	return array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'has_password'        => false,
		'posts_per_page'      => 5,
		'ignore_sticky_posts' => true,
		'orderby'            => array( 'date' => 'DESC', 'ID' => 'DESC' ),
		'tax_query'          => array(
			array(
				'taxonomy'         => 'category',
				'field'            => 'term_id',
				'terms'            => array( $category_id ),
				'include_children' => false,
			),
		),
	);
}

/**
 * Register a scoped parameter; normal posts REST requests are unchanged.
 *
 * @param array $params Collection parameters.
 * @return array
 */
function aremedia_gutenberg_rest_params( $params ) {
	$params['aremedia_category'] = array(
		'description' => __( 'Exact category for the Category Articles preview.', 'aremedia-gutenberg' ),
		'type'        => 'integer',
		'minimum'     => 1,
	);
	return $params;
}
add_filter( 'rest_post_collection_params', 'aremedia_gutenberg_rest_params' );

/**
 * Keep preview filtering and tie ordering identical to the frontend.
 *
 * @param array           $args    Existing query arguments.
 * @param WP_REST_Request $request REST request.
 * @return array
 */
function aremedia_gutenberg_rest_query( $args, $request ) {
	if ( ! empty( $request['aremedia_category'] ) ) {
		$args = array_replace( $args, aremedia_gutenberg_query_args( (int) $request['aremedia_category'] ) );
	}
	return $args;
}
add_filter( 'rest_post_query', 'aremedia_gutenberg_rest_query', 10, 2 );
