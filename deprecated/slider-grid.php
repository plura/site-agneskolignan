<?php

/**
 * . Essential Grid
 * . Revolution Slider
 * 
 */




/* ESSENTIAL GRID */

//https://theme.co/forum/t/essential-grid-with-custom-post-types-for-masonry-layout-solved/32245
//https://theme.co/archive/forums/topic/custom-archive-and-search-index-page-with-essential-grid/
function ak_egrid($items, $alias, $label = false) {

	$ids = [];

	foreach( $items as $item ) {

		if( is_a($item, 'WP_Term') ) {

			$ids[] = p_wpml_id( $item->term_id, true, 'term' );

		} else {

			$ids[] = p_wpml_id( $item->ID );

		}		

	}

	$atts = ['class' => 'ak-eg-holder'];
 
	if( $label ) {

		$atts['data-label'] = $label;

	}

	if( is_a($items[0], 'WP_Term') ) {

		add_filter('essgrid_get_posts_by_ids', fn( $posts ) => ak_egrid_terms2posts( $ids ), 4, 1);	

	}

	$html = do_shortcode('[ess_grid alias="' . $alias . '" posts="' . implode(',', $ids) . '"]');

	return "<div " . p_attributes( $atts ) . ">" . $html . "</div>";	

}


function ak_egrid_terms2posts( $ids ) {

	global $P_EGRID_DUMMY_POST;

	$posts = [];

	foreach( $ids as $id ) {

		$term = get_term( $id );

		if( $term ) {

			$posts[] = array_merge( $P_EGRID_DUMMY_POST, [
				'ID' => 1,
				'guid' => get_term_link( $term->term_id ),
				'post_name' => $term->slug,
				'post_title' => $term->name,
				'post_type' => 'post',

				'taxonomy' => $term->taxonomy,
				'term_id' => $term->term_id
			]);

		}

	}

	return $posts;

}




//essential grid hook for artists object grid
//https://www.essential-grid.com/faq/use-image-from-custom-field-as-grid-items-main-media/
function ak_egrid_custom_meta_image($media, $id, $postdata) {

	if( 

		( get_post_status( $id ) !== FALSE && preg_match('/ak_(client|object)/', get_post_type( $id ) ) ) ||

		( !empty( $postdata['term_id'] ) && preg_match('/ak_object_(category|collection|material)/', get_term( $postdata['term_id'] )->taxonomy ) )

	) {

		if( get_post_status( $id ) !== FALSE && preg_match('/ak_(client|object)/', get_post_type( $id ) ) ) {

			$image = ak_post_featured_image( $id );

		} else {

			$image = ak_term_featured_image( $postdata['term_id'] );

		}

        if( $image ) {

        	$media = array_merge( $media, [
        		'alternate-image' => $image[0],
			    'alternate-image-width' => $image[1],
			    'alternate-image-height' => $image[2],
			    'alternate-image-full' => $image[0],
			    'alternate-image-full-width' => $image[1],
			    'alternate-image-full-height' => $image[2]
        	]);

        }

    }
     
    return $media;
 
}
 
add_filter('essgrid_modify_media_sources', 'ak_egrid_custom_meta_image', 4, 3);




/* REVOLUTION SLIDER */

$AK_OBJECT_SLIDER_DEFAULTS = [
	'id' => '',	
	'rs' => ''		//slider revolution alias
];


function ak_slider( $args ) {

	$images = get_field('ak_object_gallery', $args['id']);

	if( $images ) {

		$ids = [];

		foreach( $images as $image ) {

			$ids[] = $image['ID'];

		}

		if( !empty( $args['rs'] ) && ( defined('AK_RS') && AK_RS === 1 ) ) {

			/*if( count( $ids ) === 1 ) {

				$ids[] = $ids[0];

			}*/

			return do_shortcode('[gallery rev_addon_gal_slider="' . $args['rs'] . '" ids="' . implode(',', $ids) . '"]');

		}

		return do_shortcode('[gallery ids="' . implode(',', $ids) . '"]');

	}

}

function ak_slider_shortcode( $args ) {

	global $AK_OBJECT_SLIDER_DEFAULTS;

	$atts = shortcode_atts( $AK_OBJECT_SLIDER_DEFAULTS, $args );

	if( !empty( $atts['id'] ) || !empty( $atts['ids'] ) || is_singular('ak_object') ) {

		if( empty( $atts['ids'] ) && empty( $atts['ids'] ) ) {

			$atts['id'] = get_the_ID();

		}

		return ak_slider( $atts );

	}

}

add_shortcode('ak-slider', 'ak_slider_shortcode');



/*
function ak_essgrid_ids( $arrPosts ) {

	global $AK_ESSGRID_POST_EMPTY;

	return $arrPosts;

}




add_filter('essgrid_get_posts_by_ids', 'ak_essgrid_ids', 4, 1);*/

//apply_filters('essgrid_get_posts_by_ids', $arrPosts);