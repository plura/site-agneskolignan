<?php


// Scopes the plura_wp_posts_query filter below to queries coming from ak_posts().
const AK_POSTS_CONTEXT = 'ak-posts';




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
		// passing a class keep it. link:1 wraps each tile in a single anchor, which is what
		// ak_posts_grid_item() achieved with a click handler in scripts.js.
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
 * Reduce each tile to image and title, as ak_posts_grid_item() rendered them.
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
 * Carry the per-type featured image fallbacks into the migrated rendering.
 *
 * ak_post_featured_image_id() dispatches to ak_object_featured_image_id() and
 * ak_client_featured_image_id() by name, which is site logic with no Plura equivalent.
 * It retires with the rest of the legacy helpers once objects and clients are done.
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


function ak_post_featured_image( int $postID, string $size = 'medium' ): array|bool {

	$id = ak_post_featured_image_id( $postID );

	if( $id ) {

		foreach( ['large', 'full', 'medium', 'thumbnail'] as $imgsize ) {

			$img = wp_get_attachment_image_src($id, $imgsize);

			if( $img ) {

				return $img;

			}

		}

	}

	return false;

}


//Post: Featured Image ID
function ak_post_featured_image_id( int $postID ): string|bool {

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
 * reads, and Carousel is pointed at .plura-wp-gallery-item through its classes option in
 * scripts.js rather than needing f-carousel__slide in the markup.
 *
 * The legacy container id and data-gallery-type are dropped — nothing in the CSS or JS
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
		context:    $atts['posttype']
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
    $terms = get_terms(
        ak_taxonomy_vars(
			// Required
            tax: $tax,

			// Query parameters
			order: $order,
            exclude: $exclude,
            include: $include,
            limit: $limit,
			parent: $parent,
			meta: $meta
        )
    );

    if ($terms) {
        return ak_taxonomy_grid($terms, $label);
    }
}


//Taxonomy: Grid
function ak_taxonomy_grid( $terms, bool $label = false ): string {

	$items = [];

	foreach( $terms as $term ) {

		$items[] = ak_taxonomy_grid_item( $term );

	}

	$atts = [
		'class' => 'plura-wp-terms',
		'data-type' => 'taxonomy',
		'data-layout' => 'grid',
		'data-taxonomy' => preg_replace('/ak_([a-z]+)_([a-z])/', '$1-$2', $terms[0]->taxonomy),
		'data-n' => count( $terms )
	];

	if( $label ) {

		$atts['data-label'] = $label;

	}

	return "<div " . plura_attributes( $atts ) . ">" . implode('', $items) . "</div>";

}


//Taxonomy: Grid Item
function ak_taxonomy_grid_item( $term, bool $full = true ): string {

	$atts = ['class' => ['plura-wp-term']];

	$atts_link = ['href' => ak_taxonomy_term_url( $term ), 'title' => $term->name];

	$img = ak_term_featured_image( $term );

	if( $img ) {

		$atts = array_merge_recursive( $atts, [

			'class' => ['has-img'],

			'data-bg-dir' => $img[1] >= $img[2] ? 'l' : 'p',

			'style' => '--ak-bg-img: url(\'' . $img[0] . '\');',
			//'style' => "background-image: url('" . $img[0] . "');",

		]);

		/* $atts['style'] = "background-image: url('" . $img[0] . "');"; */

	}

	$title = "";

	if( $full ) {

		$tag = "div";

		$atts['class'][] = 'full';

		$atts_link['class'] = ['plura-wp-term-title-link'];

		$atts_title = ['class' => 'plura-wp-term-title'];

		$title = "<div " . plura_attributes( $atts_title ) . "><a " . plura_attributes( $atts_link ) . ">" . $term->name . "</a></div>";

	} else {

		$tag = "a";

		$atts = array_merge( $atts, $atts_link);

	}

	return "<$tag " . plura_attributes( $atts ) . ">" . $title . "</$tag>";

}


//Taxonomy: Grid Item URL
function ak_taxonomy_term_url( WP_Term $term ): string {

	$url = get_term_link( $term );

	if( has_filter('ak_taxonomy_term_url') ) {

		$url = apply_filters('ak_taxonomy_term_url', $url, $term);

	}

	return $url;

}


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

	$atts = shortcode_atts( $defaults, $args );

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







//Taxonomy: Vars
function ak_taxonomy_vars(
    // Query parameters [required]
    string $tax,
    
    // Query parameters
    string $order = '',
    array|int|null $exclude = null,
    array|int|null $include = null,
    int $limit = -1,
    ?int $parent = null,
    array|null $meta = null
): array {
    $params = ['taxonomy' => $tax];

    if ($order) {
        $params['orderby'] = $order;
        $params['ignore_term_order'] = 1;
    }

    if ($exclude) {
        $params['exclude'] = $exclude;
    }

    if ($include) {
        $params['include'] = $include;
    }

    if ($limit > 0) {  // Only apply if positive number
        $params['number'] = $limit;
    }

    if ($parent) {
        $params['child_of'] = $parent;
    }

    if ($meta) {
        $params['meta_query'] = $meta;
    }

    return $params;
}


//Term: Featured Image
function ak_term_featured_image( $term, string $type = 'ak_object', string $size = 'large' ) {

	if( is_int( $term ) ) {

		$term = get_term( $term );

	}	

	$posts_vars = [
		'post_type' => $type,
		'posts_per_page' => 1,
		'tax_query' => [
			[
				'field' => 'term_id',
				'taxonomy' => $term->taxonomy,
				'terms' => $term->term_id
			]
		]
	];

	$img = get_field('featured_image', $term);

	if( has_filter('ak_term_featured_image') ) {

		$img = apply_filters('ak_term_featured_image', $term, $img, $posts_vars);

	}

	if( $img ) {

		$attachment_id = is_array($img) && isset($img['ID']) ? (int) $img['ID'] : (int) $img;

		return wp_get_attachment_image_src($attachment_id, $size);

	} else {

		$posts = get_posts( $posts_vars );

		if( $posts ) {

			return ak_post_featured_image( $posts[0]->ID, $size );

		}			

	}

	return false;

}


/**
 * Normalizes a given value to one or more specified types.
 *
 * @param mixed $value The input value from the shortcode attribute.
 * @param string|array $types The desired type(s), e.g., 'int', 'array', 'bool'.
 * @return mixed The normalized value, or null if no valid type is matched.
 */
function ak_val(mixed $value, string|array $types): mixed {
	$types = (array) $types;

	// Handle null
	if (is_null($value)) return null;

	// Handle int. This will also handle numeric strings like "123".
	if (in_array('int', $types, true)) {
		if ((is_numeric($value) && !is_string($value)) || (is_string($value) && ctype_digit($value))) {
            // Check if it's a simple numeric value, not a comma-separated list.
            if (strpos($value, ',') === false) {
			    return (int) $value;
            }
		}
	}

	// Handle array of ints from comma-separated string or an existing array.
	if (in_array('array', $types, true)) {
		if (is_string($value) && preg_match('/^\d+(,\d+)*$/', $value)) {
			return array_map('intval', explode(',', $value));
		}

		if (is_array($value)) {
			// Filter for numeric values and ensure they are integers.
			$valid_items = array_filter($value, 'is_numeric');
			if (count($valid_items) > 0) {
                return array_map('intval', $valid_items);
            }
		}
	}

	// Handle bool
	if (in_array('bool', $types, true)) {
		if (is_bool($value)) return $value;
		if (in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true)) return true;
		if (in_array(strtolower((string) $value), ['0', 'false', 'off', 'no'], true)) return false;
	}

	// Fallback for this specific use case: if it was meant to be an int/array but didn't match, return null.
	return null;
}
