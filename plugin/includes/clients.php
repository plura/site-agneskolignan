<?php

/**
 * 	. Globals
 * 	. Clients
 *  	- Grid
 *  . Client
 *  	- Collections
 *   	- Featured Image ID
 *   	- Ignore
 */


//Clients: Grid
function ak_clients_shortcode( $args ) {

/* 	global $AK_CLIENTS_DEFAULT; */

	$atts = shortcode_atts([
		// Query vars
		'limit'      => -1,
		'ids'        => null,
		'exclude'    => null,
		'rand'       => false,
		'active'     => false,

		// Query vars: taxonomies
		'collection' => null,
		'category'   => null,
		'material'   => null,
		'tag'        => null,
		'client'     => null,

		// Output / HTML
		'class'      => null,
		'label'      => null,
		'data'       => []

	], $args );

	$atts['type'] = 'ak_client';

	return ak_posts( ...$atts );

}

add_shortcode('ak-clients', 'ak_clients_shortcode');


//Client: Collections
add_shortcode('ak-client-collections', function( $args ) {
	$atts = shortcode_atts([
		// Query vars: taxonomies [required]
		'tax'       => 'ak_object_collection',

		// Query vars
		'order'     => 'term_order',
		'exclude'   => null,
		'include'   => null,
		'limit'     => -1,
		'parent'    => null,

		// Query vars [collections]
		'client'    => null,
		'client_not'=> null,

		// Output / HTML
		'label'     => ''
	], $args);

	if( !empty( $atts['client'] ) || ( empty( $atts['client'] ) && is_singular('ak_client') ) ) {

		if( empty( $atts['client'] ) ) {

			$atts['client'] = get_the_ID();

		}

		return ak_collections( ...$atts );

	}

});



//Client: Featured Image ID
function ak_client_featured_image_id( int $clientID ) {

	$query = new WP_Query([
		'post_type' => 'ak_object',
		'posts_per_page' => 1,
		'meta_query' => [

			//'relation' => 'AND', //no need for adding 'relation' since 'AND' is default
			
			[
				'key' => 'ak_object_client',
				'value' => $clientID
			],

			[
				'key' => 'ak_object_status',
				'value' => '1',
				'compare' => '==' // not really needed, this is the default
			]

		]
	]);

	if( $query && !empty( $query->posts ) ) {

		return ak_object_featured_image_id( $query->posts[0]->ID );

	}

	return false;

}



//Client: Ignore
function ak_client_ignore( int $clientID ): bool {

	if( defined('AK_CLIENT_IGNORE') && ( 

			( is_array( AK_CLIENT_IGNORE ) && in_array( $clientID, AK_CLIENT_IGNORE ) ) ||

			( AK_CLIENT_IGNORE === $clientID )

	) ) {

		return true;

	}

	return false;

}
