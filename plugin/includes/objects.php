<?php


// Default number of related objects. A const, not a file-scope variable: plura_includes()
// runs include_once inside a function, so a variable declared here is local to that call —
// the `global` that used to read it got null, silently discarding both attributes.
const AK_OBJECTS_RELATED_LIMIT = 6;


//Objects: Grid

/**
 * Shortcode to display a grid of 'ak_object' custom post types.
 *
 * It normalizes attributes inline when passing them to the ak_posts function.
 */
/**
 * Shortcode to display a grid of 'ak_object' custom post types.
 *
 * @param array|string $args Shortcode attributes.
 * @return string|null
 */
function ak_objects_shortcode( $args ) {

	$defaults = [
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
	];

	$atts = ak_vals( shortcode_atts( $defaults, $args ), $defaults );

	// Auto mode: on a taxonomy archive, filter by the term being viewed. The test read
	// !ak_val(...) === false, where ! binds tighter than ===; it agreed with this for every
	// input, but by accident rather than intent.
	if( $atts['auto'] === true ) {

		foreach( ['category', 'collection', 'material'] as $k ) {

			if( is_tax('ak_object_' . $k) ) {

				$atts[ $k ] = get_queried_object()->term_id;

				break;

			}

		}

	}

	// 'auto' is this shortcode's own; ak_posts() has no such parameter.
	unset( $atts['auto'] );

	return ak_posts( ...$atts );

}

add_shortcode('ak-objects', 'ak_objects_shortcode');




//Objects: Related
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
			limit: ak_val( $args['limit'] ?? null, 'int' ) ?? AK_OBJECTS_RELATED_LIMIT,
			rand: true,

			// Output / HTML
			label: __('Related Items', 'ak'),
			data: ['related' => 1]
		);
	}

	return false;
}


function ak_objects_related_shortcode( $args ) {

	$defaults = [
		'id'    => null,
		'limit' => AK_OBJECTS_RELATED_LIMIT
	];

	// 'id' was missing from these defaults, so shortcode_atts() dropped it and the
	// attribute never reached ak_objects_related().
	$atts = ak_vals( shortcode_atts( $defaults, $args ), $defaults );

	if( empty( $atts['id'] ) ) {

		if( ! is_singular('ak_object') ) {

			return null;

		}

		$atts['id'] = get_the_ID();

	}

	return ak_objects_related( $atts );

}

add_shortcode('ak-objects-related', 'ak_objects_related_shortcode');





/**
 * Fall back to an object's first gallery image when it has no thumbnail.
 *
 * @param int $objectID Object post ID.
 * @return int|false
 */
function ak_object_featured_image_id( $objectID ): int|false {

	return ak_gallery_image_id( get_field('ak_object_gallery', $objectID) ) ?? false;

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

		if( $exclude && in_array( $field, (array) $exclude, true ) ) {

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

/**
 * Meta rows for ak_object_info(), in display order.
 *
 * Categories, collections and materials are absent by design: plura_wp_post_terms()
 * renders those above the meta table, so listing them here only to skip them in the loop
 * was noise.
 *
 * @return array<string, string> Field key, without its ak_<type>_ prefix, => label.
 */
function ak_object_info_fields(): array {

	return [
		'client'     => __('Client', 'ak'),
		'dimensions' => __('Dimensions', 'ak'),
		'year'       => __('Year', 'ak')
	];

}


function ak_object_info_shortcode( $args ) {

	$atts = shortcode_atts( [
		'exclude' => null,
		'id'      => null
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
