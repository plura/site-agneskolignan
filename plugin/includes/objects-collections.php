<?php

//Some reading
//https://support.advancedcustomfields.com/forums/topic/meta-query-with-array-as-values/

/**
 * meta_query clause matching an ACF relationship field against one or more post IDs.
 *
 * ACF serializes these, so the only way to match one is LIKE '"<id>"' — the quotes are
 * what stop 4 matching 41. Several ids OR together; negating uses NOT LIKE joined by AND,
 * since a term has to miss every one of them to qualify.
 *
 * A term with nothing stored for $key has no meta row at all, so it satisfies neither
 * form: NOT LIKE excludes it rather than keeping it.
 *
 * @param string           $key   Meta key.
 * @param array|int|string $value One id, a comma-separated list, or an array of ids.
 * @param bool             $not   Negate the match.
 * @return array A single clause, or a nested group carrying its own relation.
 */
function ak_acf( string $key, array|int|string $value, bool $not = false ): array {

	$ids = is_array( $value ) ? $value : explode( ',', (string) $value );

	$meta = [];

	foreach( $ids as $id ) {

		$meta[] = [
			'key'     => $key,
			'value'   => '"' . trim( (string) $id ) . '"',
			'compare' => $not ? 'NOT LIKE' : 'LIKE'
		];

	}

	if( count( $ids ) === 1 ) {

		return $meta[0];

	}

	$meta['relation'] = $not ? 'AND' : 'OR';

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
	array|int|null $client = null,
	array|int|null $client_not = null,
	array|null $meta = null,

	// Output
	string|null $label = null
) {

	$meta = [];

	if( $client ) {

		$meta[] = ak_acf('ak_collection_client', $client);

	}

	// client_not built the identical LIKE clause as client, so it included rather than
	// excluded — the attribute never did what its name says.
	if( $client_not ) {

		$meta[] = ak_acf('ak_collection_client', $client_not, not: true);

	}

	if( count( $meta ) > 1 ) {

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

		// Query vars: meta
		meta:    $meta ?: null,

		// Output / HTML
		label:   $label
	);

}

function ak_collections_shortcode( $args ) {

	$defaults = [
		// Query vars: required
		'tax'        => 'ak_object_collection',

		// Query vars
		'order'      => 'term_order',
		'exclude'    => null,
		'include'    => null,
		'limit'      => -1,
		'parent'     => null,

		// Query vars: collections
		'client'     => null,
		'client_not' => null,

		// Output / HTML
		'label'      => ''
	];

	return ak_collections( ...ak_vals( shortcode_atts( $defaults, $args ), $defaults ) );

}

add_shortcode('ak-collections', 'ak_collections_shortcode');


/**
 * Collections: append the client slug to a shared collection's URL.
 *
 * A collection used by more than one client links to /collections/{collection}/{client}/
 * so the archive shows only that client's work; the rewrite rules below turn the second
 * segment into a query var.
 *
 * Which client depends on where the link sits: on a client's page it is that client, on a
 * single object it is the object's. This was two near-identical filters, one per page
 * type, and the ak_client half had no taxonomy guard — so any term link on a client page
 * reached ak_collection_multi_client().
 *
 * @param array       $link_atts Attributes plura_wp_link() will render.
 * @param mixed       $target    Link target; only a WP_Term is of interest here.
 * @param string|null $context   Context passed down from the caller.
 * @return array
 */
add_filter('plura_wp_link_atts', function( array $link_atts, $target, ?string $context = null ): array {

	if( ! $target instanceof WP_Term
		|| $target->taxonomy !== 'ak_object_collection'
		|| ! ak_collection_multi_client( $target ) ) {

		return $link_atts;

	}

	if( is_singular('ak_client') ) {

		$slug = get_post_field('post_name', get_the_ID());

	} elseif( is_singular('ak_object') ) {

		$client = get_field('ak_object_client');

		$slug = $client instanceof WP_Post ? $client->post_name : null;

	} else {

		return $link_atts;

	}

	if( $slug ) {

		$link_atts['href'] .= $slug . '/';

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



/**
 * Whether a collection is shared by more than one client.
 *
 * get_field() returns false, null or '' for an empty relationship field, and count()
 * fatals on all three rather than returning 0. Every other get_field() call site in this
 * plugin guards the same way.
 *
 * @param WP_Term $term Collection term.
 * @return bool
 */
function ak_collection_multi_client( $term ): bool {

	$clients = get_field('ak_collection_client', $term);

	return is_array( $clients ) && count( $clients ) > 1;

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


