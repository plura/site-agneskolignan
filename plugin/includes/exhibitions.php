<?php

/**
 * Exhibitions grid.
 *
 * Migration step: the grid renders plura_wp_posts()' own markup rather than reproducing
 * the legacy ak_posts_grid_item() output, so styling is the only thing left to port.
 * Target the container with [data-context="exhibitions"], which plura_wp_posts() emits.
 *
 * [plura-wp-posts type="ak_exhibition" context="exhibitions"]
 */

const AK_EXHIBITIONS_CONTEXT = 'exhibitions';


/**
 * Keep only the parts the grid shows, dropping datetime, meta, timeline, content and
 * read-more. Iterating $entry preserves plura_wp_post()'s ordering.
 *
 * @param array       $entry   Ordered content parts keyed by section.
 * @param WP_Post     $post    Post being rendered.
 * @param string|null $context Context passed through the shortcode.
 * @return array
 */
add_filter('plura_wp_post', function( array $entry, WP_Post $post, ?string $context = null ): array {

	if( $context !== AK_EXHIBITIONS_CONTEXT ) {

		return $entry;

	}

	$parts = [];

	foreach( $entry as $key => $value ) {

		if( in_array( $key, ['featured-image', 'title', 'excerpt'], true ) ) {

			$parts[ $key ] = $value;

		}

	}

	return $parts;

}, 10, 3);


/**
 * Fall back to the first ACF gallery image when an exhibition has no thumbnail.
 *
 * plura_wp_post_featured_image() passes null through when get_post_thumbnail_id() is
 * empty, which is the hook its own docblock points at for exactly this.
 *
 * @param string|null $result  Rendered <img>, or null when there is no thumbnail.
 * @param WP_Post     $post    Post being rendered.
 * @param string      $size    Image size requested.
 * @param array       $atts    Attributes already merged by the caller.
 * @param string|null $context Context passed through the shortcode.
 * @return string|null
 */
add_filter('plura_wp_post_featured_image', function( ?string $result, WP_Post $post, string $size, array $atts, ?string $context = null ): ?string {

	if( $result || $context !== AK_EXHIBITIONS_CONTEXT ) {

		return $result;

	}

	$id = ak_post_featured_image_id( $post->ID, 'ak_exhibition_gallery' );

	return $id ? plura_wp_image( (int) $id, $size, $atts ) : null;

}, 10, 5);
