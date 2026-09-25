<?php


// Scopes the plura_wp_posts_query filter below to queries coming from ak_posts().
const AK_POSTS_CONTEXT = 'ak-posts';

// The same, for ak_taxonomy() and the plura_wp_terms_query filter.
const AK_TERMS_CONTEXT = 'ak-terms';


//Posts: Grid
function ak_posts(
	// Query vars
	string $type = 'ak_object',
	int $limit = -1,
	array|int|null $ids = null,
	array|int|null $exclude = null,
	bool $active = true,
	bool $rand = false,

	// Query vars: taxonomies
	array|int|null $collection = null,
	array|int|null $category = null,
	array|int|null $material = null,
	array|int|null $tag = null,
	int|string|null $client = null,

	// HTML output
	string|null $class = null,
	string|null $label = null,
	array|null $data = null
) {

	// ak_posts_grid() prefixed these itself; plura_wp_posts() merges $data into the
	// container attributes exactly as given.
	$atts_data = [];

	foreach( (array) $data as $key => $value ) {

		$atts_data[ 'data-' . $key ] = $value;

	}

	$html = plura_wp_posts(
		// Query vars
		type:    $type,
		limit:   $limit,
		ids:     $ids ?? [],
		exclude: $exclude ?? [],

		// ak_objects_query_vars() gave explicit IDs precedence over rand and preserved the
		// order they were passed in; plura_wp_posts() tests rand first, so suppress it.
		rand:    $ids ? 0 : ( $rand ? 1 : 0 ),
		orderby: $ids ? 'post__in' : 'date',

		// Native, so no filter needed: null leaves the clause out entirely, matching the
		// original's `if ($active)`.
		active:     $active ? 1 : null,
		active_key: 'ak_object_status',

		// The taxonomy and client clauses have no native equivalent — see the
		// plura_wp_posts_query filter below.
		context: AK_POSTS_CONTEXT,
		params:  compact('category', 'collection', 'material', 'tag', 'client'),

		// Output. 'grid' is appended rather than replacing $class, so shortcodes already
		// passing a class keep it. link:1 wraps each tile in a single anchor, so the tile
		// needs no click handler.
		class: trim( 'grid ' . (string) $class ),
		label: $label,
		data:  $atts_data,
		link:  1
	);

	// plura_wp_posts() returns '' when nothing matches; ak_posts() has always returned null.
	return $html ?: null;
}


/**
 * Taxonomy and client clauses for ak_posts(), which plura_wp_posts() cannot express:
 * it takes a single taxonomy/terms pair, while these filter across four at once.
 *
 * Ported from ak_objects_query_vars(). The client clause originally said 'field', which
 * belongs to tax_query — meta_query saw a clause with a value and no key and matched on
 * meta_value alone, ignoring which field it came from.
 *
 * Moves to objects.php when the rendering follows in the next step.
 *
 * @param array $query_params WP_Query arguments.
 * @param array $args         Arguments plura_wp_posts_query() was called with.
 * @return array
 */
add_filter('plura_wp_posts_query', function( array $query_params, array $args ): array {

	if( ( $args['context'] ?? '' ) !== AK_POSTS_CONTEXT ) {

		return $query_params;

	}

	global $wp_query;

	$params = $args['params'] ?? [];

	$tax = [];

	foreach( ['category', 'collection', 'material', 'tag'] as $taxKey ) {

		if( !empty( $params[ $taxKey ] ) ) {

			$tax[] = [
				'taxonomy' => 'ak_object_' . $taxKey,
				'field'    => 'term_id',
				'terms'    => function_exists('plura_wpml_id') ? plura_wpml_id( (array) $params[ $taxKey ] ) : (array) $params[ $taxKey ],
			];

		}

	}

	if( !empty( $tax ) ) {

		$query_params['tax_query'] = $tax;

	}

	// Filter by client ID or URL-based rewrite rule
	$client = $params['client'] ?? null;

	if( !empty( $client ) || $wp_query->get('ak_object_collection_client') ) {

		if( empty( $client ) ) {

			$client = get_page_by_path( $wp_query->get('ak_object_collection_client'), OBJECT, 'ak_client' );

			$client = $client?->ID;

		}

		if( !empty( $client ) ) {

			$query_params['meta_query'][] = [
				'key'   => 'ak_object_client',
				'value' => $client,
			];

		}

	}

	return $query_params;

}, 10, 2);


/**
 * Reduce each tile to image and title, dropping every other part plura_wp_post() emits.
 *
 * @param array       $entry   Ordered content parts keyed by section.
 * @param WP_Post     $post    Post being rendered.
 * @param string|null $context Context passed down from ak_posts().
 * @return array
 */
add_filter('plura_wp_post', function( array $entry, WP_Post $post, ?string $context = null ): array {

	if( $context !== AK_POSTS_CONTEXT ) {

		return $entry;

	}

	$parts = [];

	foreach( $entry as $key => $value ) {

		if( in_array( $key, ['featured-image', 'title'], true ) ) {

			$parts[ $key ] = $value;

		}

	}

	return $parts;

}, 10, 3);


/**
 * Fall back to the per-type featured image when a post has no thumbnail.
 *
 * ak_post_featured_image_id() dispatches to ak_object_featured_image_id() and
 * ak_client_featured_image_id() by name — site logic with no Plura equivalent, so it
 * stays.
 *
 * @param string|null $result  Rendered <img>, or null when there is no thumbnail.
 * @param WP_Post     $post    Post being rendered.
 * @param string      $size    Image size requested.
 * @param array       $atts    Attributes already merged by the caller.
 * @param string|null $context Context passed down from ak_posts().
 * @return string|null
 */
add_filter('plura_wp_post_featured_image', function( ?string $result, WP_Post $post, string $size, array $atts, ?string $context = null ): ?string {

	if( $result || $context !== AK_POSTS_CONTEXT ) {

		return $result;

	}

	$id = ak_post_featured_image_id( $post->ID );

	return $id ? plura_wp_image( (int) $id, $size, $atts ) : null;

}, 10, 5);

/**
 * The attachment ID of a gallery field's first image.
 *
 * ACF hands back gallery items as image arrays, IDs or URLs depending on how the field is
 * configured, and only the first two yield an ID. reset() rather than [0], because the
 * array is not guaranteed to be a list.
 *
 * @param mixed $gallery Raw get_field() return for a gallery field.
 * @return int|null
 */
function ak_gallery_image_id( mixed $gallery ): ?int {

	if( ! is_array( $gallery ) || ! $gallery ) {

		return null;

	}

	$first = reset( $gallery );

	if( is_array( $first ) ) {

		return isset( $first['ID'] ) ? (int) $first['ID'] : null;

	}

	return is_numeric( $first ) ? (int) $first : null;

}


//Post: Featured Image ID
function ak_post_featured_image_id( int $postID ): int|false {

	if( has_post_thumbnail( $postID ) ) {

		return get_post_thumbnail_id( $postID );

	} else {

		$type = preg_replace('/(ak_)?([a-z]+)/', '$2', get_post( $postID )->post_type );

		if( function_exists('ak_' . $type . '_featured_image_id') ) {

			return ('ak_' . $type . '_featured_image_id')( $postID );

		}

	}

	return false;

}


/**
 * Post: Gallery
 *
 * plura_wp_gallery() already emits data-thumb-src on each item, which is what Thumbs
 * reads.
 *
 * The old container id and data-gallery-type are dropped — nothing in the CSS or JS
 * referenced them — and with them the 'type' attribute, which only fed the latter.
 */
add_shortcode('ak-gallery', function( $args ) {

	$atts = shortcode_atts([
		'id' => '',
		'posttype' => 'post'
	], $args );

	// Read from $atts, not $args: $args holds only what the shortcode was given, so a
	// bare [ak-gallery] hit an undefined key here and tested is_singular('ak_').
	$singular = is_singular( 'ak_' . $atts['posttype'] );

	if( empty( $atts['id'] ) && ! $singular ) {

		return null;

	}

	// A singular view takes precedence over any id passed in, as it always has.
	$id = $singular
		? ( function_exists('plura_wpml_id') ? plura_wpml_id( get_the_ID() ) : get_the_ID() )
		: (int) $atts['id'];

	// Images come back at 'large', plura_wp_image()'s default, which plura_wp_gallery()
	// gives no way to override. Fancybox resolves href || currentSrc || src, so the
	// lightbox opens that rather than the full-size file the old markup carried.
	return plura_wp_gallery(
		source:     $id,
		source_key: 'ak_' . $atts['posttype'] . '_gallery',
		class:      'f-carousel ak-gallery',
		context:    $atts['posttype'],

		// Carousel's stylesheet sizes slides through .f-carousel__slide, so the class has
		// to be on the markup; scripts.js used to add it before init.
		item_class: 'f-carousel__slide'
	) ?: null;

});


//Taxonomy
function ak_taxonomy(
	// Query vars: Required
    string $tax,

	// Query vars
    string $order = '',
    array|int|null $exclude = null,
    array|int|null $include = null,
    int $limit = -1,
	?int $parent = null,	

    // Query vars: taxonomies
    array|null $meta = null,

    // Output
    string|null $label = null
) {
	$terms = plura_wp_terms(
		taxonomy: $tax,

		exclude: $exclude ?? [],
		ids:     $include ?? [],
		limit:   $limit,
		orderby: $order ?: 'name',

		// ignore_term_order, child_of and meta_query have no native equivalent — see the
		// plura_wp_terms_query filter below.
		context: AK_TERMS_CONTEXT,
		params:  ['ignore_term_order' => (bool) $order, 'child_of' => $parent, 'meta' => $meta],

		// link: 1 wraps each tile in a single anchor, matching ak_posts(), so the tile
		// needs no click handler.
		class: 'grid',
		label: $label,
		link:  1
	);

	// plura_wp_terms() returns '' when nothing matches; ak_taxonomy() returned null.
	return $terms ?: null;
}


/**
 * Term query clauses that plura_wp_terms_query() cannot express, ported from
 * ak_taxonomy_vars() unchanged.
 *
 * child_of rather than parent is deliberate: the original took every descendant, while
 * plura_wp_terms()' parent argument means direct children only.
 *
 * @param array $query_params WP_Term_Query arguments.
 * @param array $args         Arguments plura_wp_terms_query() was called with.
 * @return array
 */
add_filter('plura_wp_terms_query', function( array $query_params, array $args ): array {

	if( ( $args['context'] ?? '' ) !== AK_TERMS_CONTEXT ) {

		return $query_params;

	}

	$params = $args['params'] ?? [];

	// The original paired every explicit orderby with ignore_term_order, so a chosen order
	// wins over the sequence the term-order plugin stores.
	if( !empty( $params['ignore_term_order'] ) ) {

		$query_params['ignore_term_order'] = 1;

	}

	if( !empty( $params['child_of'] ) ) {

		$query_params['child_of'] = $params['child_of'];

	}

	if( !empty( $params['meta'] ) ) {

		$query_params['meta_query'] = $params['meta'];

	}

	return $query_params;

}, 10, 2);


function ak_taxonomy_shortcode( $args ) {
	// Unified defaults
	$defaults = [
		// Common
		'auto'       => 1,
		'exclude'    => null,
		'ids'        => null,
		'label'      => '',
		'limit'      => -1,
		'rand'       => 0,

		// Taxonomy
		'order'      => 'term_order',
		'parent'     => null,
		'tax'        => '',

		// Object
		'active'     => 1,
		'category'   => null,
		'client'     => null,
		'collection' => null,
		'material'   => null,
		'tag'        => null,
		'type'       => 'ak_object',
	];

	$atts = ak_vals( shortcode_atts( $defaults, $args ), $defaults );

	// Keys expected by each function
	$object_keys = [
		'active', 'category', 'client', 'collection',
		'exclude', 'ids', 'label', 'material',
		'limit', 'tag', 'type', 'rand'
	];

	$tax_keys = [
		'exclude', 'include', 'label',
		'limit', 'order', 'parent', 'tax'
	];

	// Object listing condition
	if (
		$atts['auto']
		&& is_tax( $atts['tax'] )
		&& !count( get_term_children( get_queried_object()->term_id, get_queried_object()->taxonomy ) )
	) {
		$taxonomy_key = preg_replace(
			'/ak_object_([a-z]+)/',
			'$1',
			get_queried_object()->taxonomy
		);
		$atts[$taxonomy_key] = get_queried_object()->term_id;

		return ak_posts( ...array_intersect_key( $atts, array_flip( $object_keys ) ) );
	}

	// Taxonomy listing condition
	if ( $atts['auto'] && is_tax( $atts['tax'] ) ) {
		$atts['parent'] = get_queried_object()->term_id;
	}

	return ak_taxonomy( ...array_intersect_key( $atts, array_flip( $tax_keys ) ) );
}

add_action('init', 'ak_register_shortcodes');

function ak_register_shortcodes() {
	add_shortcode('ak-taxonomy', 'ak_taxonomy_shortcode');
}


/**
 * Normalize a shortcode attribute to one of the given types.
 *
 * @param mixed        $value Raw attribute value; shortcodes always deliver strings.
 * @param string|array $types Desired type(s): 'int', 'array', 'bool'.
 * @return mixed The normalized value, or null when none of the types match.
 */
function ak_val( mixed $value, string|array $types ): mixed {

	$types = (array) $types;

	if( is_null( $value ) ) {

		return null;

	}

	// ctype_digit() rejected a leading '-', so limit="-1" normalized to null and then hit
	// int $limit as a TypeError. The pattern also excludes comma lists, which fall through
	// to the 'array' branch below.
	if( in_array('int', $types, true) ) {

		if( is_int( $value ) || ( is_string( $value ) && preg_match('/^-?\d+$/', $value) ) ) {

			return (int) $value;

		}

	}

	if( in_array('array', $types, true) ) {

		if( is_string( $value ) && preg_match('/^\d+(,\d+)*$/', $value) ) {

			return array_map('intval', explode(',', $value));

		}

		if( is_array( $value ) ) {

			$ids = array_filter( $value, 'is_numeric' );

			if( $ids ) {

				return array_map('intval', $ids);

			}

		}

	}

	if( in_array('bool', $types, true) ) {

		if( is_bool( $value ) ) {

			return $value;

		}

		if( in_array( strtolower( (string) $value ), ['1', 'true', 'on', 'yes'], true ) ) {

			return true;

		}

		if( in_array( strtolower( (string) $value ), ['0', 'false', 'off', 'no'], true ) ) {

			return false;

		}

	}

	return null;

}


/**
 * The type each shared shortcode attribute normalizes to.
 *
 * A const rather than a file-scope variable: plura_includes() runs include_once inside a
 * function, so a variable declared here would be local to it and never reach $GLOBALS.
 */
const AK_ATT_TYPES = [
	'active'     => 'bool',
	'auto'       => 'bool',
	'category'   => ['int', 'array'],
	'client'     => ['int', 'array'],
	'client_not' => ['int', 'array'],
	'collection' => ['int', 'array'],
	'exclude'    => ['int', 'array'],
	'id'         => 'int',
	'ids'        => ['int', 'array'],
	'include'    => ['int', 'array'],
	'limit'      => 'int',
	'material'   => ['int', 'array'],
	'parent'     => 'int',
	'rand'       => 'bool',
	'tag'        => ['int', 'array']
];


/**
 * Normalize a whole shortcode_atts() result in one pass.
 *
 * Shortcode values arrive as strings while ak_posts(), ak_taxonomy() and ak_collections()
 * declare union types, so ids="1,2" against array|int|null is a TypeError rather than a
 * coercion. Every shortcode routes its attributes through here before spreading them.
 *
 * Keys absent from AK_ATT_TYPES — tax, order, class, label, type, data — pass through.
 *
 * @param array $atts     Result of shortcode_atts().
 * @param array $defaults The defaults that same call was given.
 * @return array
 */
function ak_vals( array $atts, array $defaults = [] ): array {

	foreach( $atts as $key => $value ) {

		if( ! array_key_exists( $key, AK_ATT_TYPES ) ) {

			continue;

		}

		// A value that will not normalize (limit="abc") falls back to the shortcode's own
		// default rather than to null, which the non-nullable parameters would reject.
		$atts[ $key ] = ak_val( $value, AK_ATT_TYPES[ $key ] ) ?? ( $defaults[ $key ] ?? null );

	}

	return $atts;

}
