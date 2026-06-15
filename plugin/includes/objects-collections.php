<?php

//Some reading
//https://support.advancedcustomfields.com/forums/topic/meta-query-with-array-as-values/

/**
 *	. Globals
 * 	. Collections
 * 		- Grid
 * 		- Grid Item URL Hook
 * 		- Grid Item Featured Image Hook
 * 	. Collection
 * 		- Multi Client
 * 	. Rewrite
 *   
 */

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


/* Collections: Grid Item URL Hook */
add_filter('ak_taxonomy_term_url', function(string $url, WP_Term $term): string {

	global $post;

	//if number of clients of one collection is more than one, an extra parameter should be added
	//to the url in order to filter the collections' objects pertaining only to the client
	if( is_singular('ak_client') && ak_collection_multi_client( $term ) ) {

		$url .= $post->post_name . "/";

	}

	return $url;

}, 10, 2);


/* Collections: Grid Item Featured Image Hook */
add_filter('ak_term_featured_image', function( WP_Term $term, $term_featured_image, array $term_posts_vars ) {

	global $wp_query;

	if( is_singular('ak_client') && $term->taxonomy === 'ak_object_collection' && ak_collection_multi_client( $term ) /*&& $term->slug === 'autumn-winter-2016'*/ ) {


		//get all objects from that collection pertaining to a specific client
		$posts_vars = array_merge( $term_posts_vars, ['posts_per_page' => -1, 'meta_query' => [
			
			[
				'key' => 'ak_object_client',
				'value' => get_the_ID()
			]

		] ] );

		$posts = get_posts( $posts_vars );

		$images = [];

		//check if any post's featured image ID equals 'term featured image'
		foreach( $posts as $post ) {

			$imgID = ak_post_featured_image_id( $post->ID );

			
			if( $imgID === $term_featured_image['ID'] ) {

				return $term_featured_image;

			} else {

				$images[] = $imgID;

			}

		}

		if( !empty( $images ) ) {

			return $images[0];

		}

	}

	return $term_featured_image;

}, 10, 3);



/* Collection: Multi Client check */
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


/* add_action( 'init', function () {
	flush_rewrite_rules();
}, 99 ); */


/* Rewrite */
add_action( 'init',  function() {
   /**
    * ([^/]*) - collection
    * ([^/]*) - client
    */
/*    add_rewrite_rule(
		'^collections/([^/]+)/([^/]+)/?$',
		'index.php?ak_object_collection=$matches[1]&ak_object_collection_client=$matches[2]',
		'top'
    ); */

} );


/* add_filter( 'query_vars', function( array $query_vars ): array {

   $query_vars[] = 'ak_object_collection';
   $query_vars[] = 'ak_object_collection_client';

   return $query_vars;

} ); */

