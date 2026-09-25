<?php

//Some reading
//https://support.advancedcustomfields.com/forums/topic/meta-query-with-array-as-values/

function ak_acf($key, $value) {

	$meta = [];

	$ids = explode(',', $value);

	foreach($ids as $id) {

		$meta[] = [
			'key'       => $key,
			'value'     => '"' . $id . '"',
			'compare'   => 'LIKE'
		];

	}

	if( count( $ids ) > 1 ) {

		$meta['relation'] = 'OR';

	} else {

		$meta = $meta[0];

	}

	return $meta;

}

function ak_collections( 
	// Query vars: Required
    string $tax = 'category',

	// Query vars
    string $order = '',
    array|int|null $exclude = null,
    array|int|null $include = null,
    int $limit = -1,
	?int $parent = null,

    // Query vars: taxonomies 
	?int $client = null,
	?int $client_not = null,
    array|null $meta = null,

    // Output
    string|null $label = null
) {

	// Build meta_query based on client and client_not
	$meta = [];

	if ( $client ) {
		$meta[] = ak_acf('ak_collection_client', $client);
	}

	if ( $client_not ) {
		$meta[] = ak_acf('ak_collection_client', $client_not);
	}

	if ( count($meta) > 1 ) {
		$meta['relation'] = 'AND';
	}

	return ak_taxonomy(
		// Query vars: taxonomies [required]
		tax:     $tax,

		// Query vars
		order:   $order,
		exclude: $exclude,
		include: $include,
		limit:   $limit,
		parent:  $parent,

/* 		orderby: $orderby, */

		// Query vars: meta
		meta:    $meta ?: null,

		// Output / HTML
		label:   $label
	);

}

function ak_collections_shortcode( $args ) {
	$atts = shortcode_atts([
		// Query vars: required
		'tax'       => 'ak_object_collection',

		// Query vars
		'order'     => 'term_order',
		'exclude'   => null,
		'include'   => null,
		'limit'     => -1,
		'parent'    => null,

		// Query vars: collections
		'client'    => null,
		'client_not'=> null,

		// Output / HTML
		'label'     => ''/* ,

		// Others
		'auto'      => true */
	], $args);

	return ak_collections( ...$atts );

}

add_shortcode('ak-collections', 'ak_collections_shortcode');


//Collections: Grid Item URL Hook
add_filter('plura_wp_link_atts', function( array $link_atts, $target, ?string $context = null ): array {

	global $post;

	//if number of clients of one collection is more than one, an extra parameter should be added
	//to the url in order to filter the collections' objects pertaining only to the client
	if( $target instanceof WP_Term && is_singular('ak_client') && ak_collection_multi_client( $target ) ) {

		$link_atts['href'] .= $post->post_name . "/";

	}

	return $link_atts;

}, 10, 3);


/**
 * Collections: Grid Item Featured Image Hook
 *
 * On a client's page a shared collection should show that client's work, not whichever
 * post plura_wp_term_featured_image() picked as the fallback.
 *
 * The original walked every one of the client's objects to see whether any used the term's
 * own image, keeping it when one did and taking the first otherwise. Either branch ends up
 * with an image from the client's objects, so it just takes the first now.
 *
 * @param string|null  $result  Rendered <img>, or null when neither term nor fallback had one.
 * @param WP_Term      $term    Term being rendered.
 * @param string       $size    Image size requested.
 * @param array        $atts    Attributes already merged by the caller.
 * @param string|null  $context Context passed down from ak_taxonomy().
 * @param WP_Post|null $post    The post the fallback image came from, if any.
 * @return string|null
 */
add_filter('plura_wp_term_featured_image', function( ?string $result, WP_Term $term, string $size, array $atts, ?string $context = null, $post = null ): ?string {

	if( ! is_singular('ak_client') || $term->taxonomy !== 'ak_object_collection' || ! ak_collection_multi_client( $term ) ) {

		return $result;

	}

	//get the first object from that collection pertaining to the client being viewed
	$objects = get_posts([
		'post_type'      => 'ak_object',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'tax_query'      => [[
			'taxonomy' => $term->taxonomy,
			'field'    => 'term_id',
			'terms'    => $term->term_id
		]],
		'meta_query'     => [[
			'key'   => 'ak_object_client',
			'value' => get_the_ID()
		]]
	]);

	if( ! $objects ) {

		return $result;

	}

	$id = ak_post_featured_image_id( $objects[0] );

	return $id ? plura_wp_image( (int) $id, $size, $atts ) : $result;

}, 10, 6);



//Collection: Multi Client Check
function ak_collection_multi_client( $term ) {

	$clients = get_field('ak_collection_client', $term);

	if( count( $clients ) > 1 ) {

		return true;

	}

	return false;

}






// backend/functions/rewrites.php

/**
 * Add custom rewrite rules to support:
 * - /collections/{collection}/
 * - /collections/{collection}/{client}/ (only used when the collection is associated with multiple clients)
 *
 * These allow, optionally, filtering the objects in a multi-client collection to show only those related to a specific client.
 */
add_action( 'init', function () {
	/**
	 * Two segments: /collections/collection/client
	 */
	add_rewrite_rule(
		'^collections/([^/]+)/([^/]+)/?$',
		'index.php?ak_object_collection=$matches[1]&ak_object_collection_client=$matches[2]',
		'top'
	);

	/**
	 * One segment: /collections/collection
	 */
	add_rewrite_rule(
		'^collections/([^/]+)/?$',
		'index.php?ak_object_collection=$matches[1]',
		'top'
	);
} );

add_filter( 'query_vars', function( array $query_vars ): array {
	$query_vars[] = 'ak_object_collection';
	$query_vars[] = 'ak_object_collection_client';
	return $query_vars;
} );


/**
 * Flush the rewrite rules once, whenever the rules above change.
 *
 * The rules are registered on every request but only take effect once written to the
 * rewrite_rules option, and nothing here was writing them — so the two-segment collection
 * URL 404'd and WordPress guessed its way to the client page instead. An activation hook
 * would not help either, since this site deploys over SFTP and never reactivates.
 *
 * Bump AK_REWRITE_VERSION whenever a rule above is added or changed; the flush is skipped
 * on every request after that, so the cost is paid once per deploy that needs it.
 */
const AK_REWRITE_VERSION = '1';

add_action( 'init', function () {

	if( get_option('ak_rewrite_version') === AK_REWRITE_VERSION ) {

		return;

	}

	flush_rewrite_rules();

	update_option('ak_rewrite_version', AK_REWRITE_VERSION);

}, 99 );


