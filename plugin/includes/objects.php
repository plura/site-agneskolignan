<?php

/**
 *	. Globals
 *	. Objects 
 *   	. Query Vars
 *   	. Grid
 * 		. Related Objects
 *  . Object
 *   	. Featured Image ID  	
 *     	. Info
 *     	. Terms
 *     	. Term URL Hook
 *  . Collection 
 *  	. Featured Object Image ID
 *  . Permalink Structure
 */


//Globals
$AK_OBJECTS_RELATED_DEFAULTS = [
	'limit' => 6
];


//Objects: Grid

/**
 * Shortcode to display a grid of 'ak_object' custom post types.
 *
 * It normalizes attributes inline when passing them to the ak_posts function.
 */
function ak_objects_shortcode( $args ) {

	$atts = shortcode_atts([
		// Query vars
		'type'       => 'ak_object',
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
		'data'       => [],

		// Special
		'auto'       => false
	], $args );

	// Auto mode for tax context.
	// Note: This relies on the truthiness of the 'auto' attribute.
	// For robust boolean handling, 'auto' is normalized in the return statement for other functions,
	// but here we check it before that. A simple `auto="true"` will work.
	if ( $atts['auto'] && !ak_val($atts['auto'], 'bool') === false ) {
		foreach ( ['category', 'collection', 'material'] as $k ) {
			if ( is_tax('ak_object_' . $k) ) {
				$atts[$k] = get_queried_object()->term_id;
				break;
			}
		}
	}

	// Call ak_posts, normalizing values directly in the arguments.
	return ak_posts(
		// Query vars
		type:       $atts['type'],
		limit:      ak_val($atts['limit'], 'int') ?? -1,
		ids:        ak_val($atts['ids'], ['int', 'array']),
		exclude:    ak_val($atts['exclude'], ['int', 'array']),
		rand:       ak_val($atts['rand'], 'bool'),
		active:     ak_val($atts['active'], 'bool'),

		// Query vars: taxonomies
		collection: ak_val($atts['collection'], ['int', 'array']),
		category:   ak_val($atts['category'], ['int', 'array']),
		material:   ak_val($atts['material'], ['int', 'array']),
		tag:        ak_val($atts['tag'], ['int', 'array']),
		client:     ak_val($atts['client'], ['int', 'array']),

		// Output / HTML
		class:      $atts['class'],
		label:      $atts['label'],
		data:       $atts['data']
	);
}

add_shortcode('ak-objects', 'ak_objects_shortcode');




//related objects of the same object
function ak_objects_related( $args ) {
	$tax = [];

	// Check if object has a collection; if not, fallback to material.
	foreach ( ['collection', 'material'] as $k ) {
		$terms = get_the_terms( $args['id'], 'ak_object_' . $k );

		if ( $terms ) {
			$ids = [];

			foreach ( $terms as $term ) {
				$ids[] = $term->term_id;
			}

			$tax[ $k ] = $ids;
			break;
		}
	}

	if ( !empty( $tax ) ) {
		return ak_posts(
			...$tax,

			// Query vars
			exclude: ak_val( $args['id'], 'int' ),
			limit: ak_val( $args['limit'] ?? null, 'int' ) ?? 6,
			rand: true,

			// Output / HTML
			label: __('Related Items', 'ak'),
			data: ['related' => 1]
		);
	}

	return false;
}


function ak_objects_related_shortcode( $args ) {

	global $AK_OBJECTS_RELATED_DEFAULTS;

	$atts = shortcode_atts( $AK_OBJECTS_RELATED_DEFAULTS, $args );

	if( !empty( $atts['id'] ) || ( empty( $atts['id'] ) && is_singular('ak_object') ) ) {

		if( empty( $atts['id'] ) ) {

			$atts['id'] = get_the_ID();

		}

		return ak_objects_related( $atts );

	}

}

add_shortcode('ak-objects-related', 'ak_objects_related_shortcode');




// OBJECT

//Object: Featured Image ID
function ak_object_featured_image_id( $objectID ) {

	$gallery = get_field('ak_object_gallery', $objectID);

	if( $gallery ) {

		return $gallery[0]['ID'];

	}

	return false;

}


//Object: Info
function ak_object_info(
	int $id,
	array|string|null $exclude = null,
	string $type = ''
) {

	$html = [];

	$html[] = plura_wp_post_terms(
		post: $id,
		allowed_taxonomies: ['ak_object_category', 'ak_object_collection', 'ak_object_material'],
		taxonomy: false
	);

	$meta = [];

	foreach( ak_object_info_fields() as $field => $label ) {

		//skip field if key is found in excluded array
		if( $exclude && in_array($field, (array) $exclude) ) {

			continue;

		} else if( preg_match('/(categories|collections|materials|tags)/', $field) ) {

			continue;

		}

		$meta[ $field ] = [

			'key' => 'ak_' . $type . '_' . $field,

			'label' => $label,

			'raw_html' => true,

			/**
			 * plura_wp_post_meta() calls this with the value alone, which is all either job
			 * needs. Returning '' drops the row, since skip_empty defaults on.
			 *
			 * @param mixed $value Field value; a WP_Post for relationship fields.
			 * @return string
			 */
			'sanitize_callback' => function( mixed $value ) use ( $field ): string {

				// Mirrors the original empty() test: skip_empty only recognises null and '',
				// and a non-scalar left in place would trip the plugin's warning path.
				if( empty( $value ) ) {

					return '';

				}

				if( $value instanceof WP_Post ) {

					return preg_match('/(client)/', $field) && ak_client_ignore( $value->ID )
						? ''
						: plura_wp_link( html: esc_html( $value->post_title ), target: $value );

				}

				return esc_html( $value );

			}

		];

	}

	if( !empty( $meta ) ) {

		$html[] = plura_wp_post_meta(
			post: $id,
			meta: $meta,
			label_as_data_attr: true
		);

	}

	$atts = ['class' => 'ak-object-info'];

	return "<div " . plura_attributes( $atts ) . ">" . implode('', $html) . "</div>";

}

function ak_object_info_fields() {

	return [
		'categories' => __('Categories', 'ak'),
		'client' => __('Client', 'ak'),
		'collections' => __('Collections', 'ak'),	
		'dimensions' => __('Dimensions', 'ak'),	
		'materials' => __('Materials', 'ak'), 
		'year' => __('Year', 'ak')
	];

}


function ak_object_info_shortcode( $args ) {

	$atts = shortcode_atts( [
		//'class' => '',
		'exclude' => null,
		'id' => null,
		//'layout' => ''
	], $args );

	if( is_singular( ['ak_object'] ) && ( empty( $args['id'] ) || preg_match('/true/', $args['id']) ) ) {

		$atts['id'] = get_the_ID();

	}

	if( !empty( $atts['exclude'] ) ) {

		$atts['exclude'] = explode(',', $atts['exclude']);

	}

	if( !empty( $atts['id'] ) ) {

		$atts['type'] = preg_replace('/ak_/', '', get_post_type( $atts['id'] ) );

		return ak_object_info( ...$atts );

	}

}

add_shortcode('ak-object-info', 'ak_object_info_shortcode');




/* Collections: Grid Item URL Hook */
add_filter('ak_taxonomy_term_url', function(string $url, WP_Term $term): string {

	//if number of clients of one collection is more than one, an extra parameter should be added
	//to the url in order to filter the collections' objects pertaining only to the client
	if( is_singular('ak_object') && $term->taxonomy === 'ak_object_collection' && ak_collection_multi_client( $term ) ) {

		$client = get_field('ak_object_client');

		if( $client ) {

			$url .= $client->post_name . "/";

		}

	}

	return $url;

}, 10, 2);

