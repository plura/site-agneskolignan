<?php

/**
 *	. Globals
 *	. Header Title
 *	. Posts
 *		. Grid
 *		. Grid Item (Post)
 *	. Post
 *		. Gallery
 *	. Taxonomy
 *		. Grid
 *		. Grid Item
 *		. Grid Item URL
 *		. Vars
 *	. Term
 *		. Featured Image
 */


//Header Title 
function ak_title_breadcrumbs( bool $title = true, bool $breadcrumbs = true ): ?string {

	$atts = ["class" => 'ak-title-breadcrumbs'];

	$html = [];

	if( $title ) {

		$atts['data-title'] = 1;

		$html[] = plura_wp_title(object: get_the_ID());

	}

	if( $breadcrumbs ) {

		$b = plura_wp_breadcrumbs();

		if( $b ) {

			$atts['data-breadcrumbs'] = 1;

			$html[] = $b;

		}

	}

	if( !empty( $html ) ) {

		return "<div " . plura_attributes( $atts ) . ">" . implode('', $html) . "</div>";

	}

	return null;

}

function ak_title_breadcrumbs_shortcode( $args ) {

	$atts = shortcode_atts([
		'title' => true,
		'breadcrumbs' => true
	], $args);

	return ak_title_breadcrumbs( (bool) $atts['title'], (bool) $atts['breadcrumbs'] );

}

add_shortcode('ak-title-breadcrumbs', 'ak_title_breadcrumbs_shortcode');




/* POSTS */

//Objects: Grid
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

	$query_vars = ak_objects_query_vars(
		// Query vars
		type:   $type,
		limit:  $limit,
		ids:    $ids,
		exclude: $exclude,
		active: $active,
		rand:   $rand,

		// Query vars: taxonomies
		collection: $collection,
		category:   $category,
		material:   $material,
		tag:        $tag,
		client:     $client
	);

	$query = new WP_Query($query_vars);

	if( $query->have_posts() ) {

		return ak_posts_grid(
			posts: $query->posts,
			class: $class,
			label: $label,
			data: $data
		);

	}

	return null;
}


//get objects grid
function ak_posts_grid( 
    array $posts, 
    array|string|null $class = null, 
    ?string $label = null, 
    array|string|null $data = null 
): string {

    $a = [];

    foreach ( $posts as $post ) {
        $a[] = ak_posts_grid_item( $post );
    }

    $atts = [
        'class' => ['plura-wp-posts'],
        'data-type' => $post->post_type,
        'data-layout' => 'grid',
        'data-n' => count( $posts )
    ];

    // Only merge if class is not empty
    if ( $class ) {
        $class = (array) $class;
        $atts['class'] = array_unique([ ...$atts['class'], ...$class ]);
    }

    // Only add data-* if not empty
    if ( $data ) {
        $data = (array) $data;
        foreach ( $data as $k => $v ) {
            $atts['data-' . $k] = $v;
        }
    }

    // Only add label if not empty
    if ( $label ) {
        $atts['data-label'] = $label;
    }

    return "<div " . plura_attributes( $atts ) . ">" . implode('', $a) . "</div>";
}



function ak_posts_grid_item( WP_Post $post, bool $full = true ): string {

	$atts = ['class' => ['plura-wp-post']];

	$atts_link = ['href' => get_permalink( $post->ID ), 'title' => $post->post_title];

	$img = ak_post_featured_image( $post->ID ); 

	if( $img ) {

		$atts = array_merge( $atts, [

			'data-bg-dir' => $img[1] >= $img[2] ? 'l' : 'p',

			'style' => "background-image: url('" . $img[0] . "');",

		]);

	}

	$title = "";

	if( $full ) {

		$tag = "div";

		$atts['class'][] = 'full';

		$atts_link['class'] = ['plura-wp-post-title-link'];

		$atts_title = ['class' => 'plura-wp-post-title'];

		$title = "<div " . plura_attributes( $atts_title ) . "><a " . plura_attributes( $atts_link ) . ">" . $post->post_title . "</a></div>";

	} else {

		$tag = "a";

		$atts = array_merge( $atts, $atts_link);

	}

	return "<$tag " . plura_attributes( $atts ) . ">" . $title . "</$tag>";

}



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



//Post: Gallery
function ak_gallery( int $id, string $posttype = 'post', string $type = 'carousel' ): ?string {

	$gallery = get_field('ak_' . $posttype . '_gallery', $id );

	if( $gallery ) {

		$html = [];

		foreach( $gallery as $image ) {

			$attr_img_holder = [
				'class' => ['f-carousel__slide'],
				'data-thumb-src' => $image['sizes']['medium']
			];

			$attr_img = [
				'data-fancybox' => 'ak-gallery',
				'data-lazy-src' => $image['url'],				
				'height' => $image['height'],
				'width' => $image['width']
			];

			$html[] = "<div " . plura_attributes( $attr_img_holder ) . "><img " . plura_attributes( $attr_img ) . "/></div>";

		}

		$attr = [
			'class' => ['f-carousel', 'ak-gallery'],
			'id' => 'ak-' . $posttype . '-gallery',
			'data-type' => $posttype,
			'data-gallery-type' => $type
		];

		return "<div " . plura_attributes( $attr ) . ">" . implode('', $html) . "</div>";

	}

}

add_shortcode('ak-gallery', function( $args) {

	$atts = shortcode_atts([
		'id' => '',
		'posttype' => 'post',
		'type' => 'carousel'
	], $args );

	if( !empty( $atts['id'] ) || is_singular('ak_' . $args['posttype']) ) {

		if( is_singular('ak_' . $args['posttype']) ) {

			$atts['id'] = plura_wpml_id( get_the_ID() );

		} else {

			$atts['id'] = (int) $atts['id'];

		}

		return ak_gallery( ...$atts );

	}

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

		$atts = array_merge( $atts, [

			'data-bg-dir' => $img[1] >= $img[2] ? 'l' : 'p',

			'style' => "background-image: url('" . $img[0] . "');",

		]);

		$atts['style'] = "background-image: url('" . $img[0] . "');";

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

	//return ak_taxonomy( ...array_intersect_key( $atts, array_flip( $tax_keys ) ) );
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
