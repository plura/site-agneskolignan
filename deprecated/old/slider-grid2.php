<?php


$AK_EGRID_POST_EMPTY = [
	'ID' => '',
	'post_author' => '',
	'post_date' => '',
	'post_date_gmt' => '',
	'post_content' => '',
	'post_title' => '',
	'post_excerpt' => '',
	'post_status' => '',
	'comment_status' => '',
	'ping_status' => '',
	'post_password' => '',
	'post_name' => '',
	'to_ping' => '',
	'pinged' => '',
	'post_modified' => '',
	'post_modified_gmt' => '',
	'post_content_filtered' => '',
	'post_parent' => '',
	'guid' => '',
	'menu_order' => '',
	'post_type' => '',
	'post_mime_type' => '',
	'comment_count' => '',
	'filter' => '',
	'ancestors' => [],
	'page_template' => '',
	'post_category' => [],
	'tags_input' => []
];




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


function ak_egrid_terms2posts( $ids) {

	global $AK_EGRID_POST_EMPTY;

	$posts = [];

	foreach( $ids as $id ) {

		$term = get_term( $id );

		if( $term ) {

			$posts[] = array_merge( $AK_EGRID_POST_EMPTY, [
				'ID' => 1,
				'guid' => get_term_link( $term->term_id ),
				'post_name' => $term->slug,
				'post_title' => $term->name,
				'post_type' => 'post',
				'term_id' => $term->term_id
			]);

		}

	}

	return $posts;

}




//essential grid hook for artists object grid
//https://www.essential-grid.com/faq/use-image-from-custom-field-as-grid-items-main-media/
function ak_egrid_custom_meta_image($media, $id, $postdata) {

	//print_r( $postdata );

	if( 

		( get_post_status( $id ) !== FALSE && get_post_type( $id ) === 'ak_object' ) ||

		( term_exists( $id ) && get_term( $id )->taxonomy === 'ak_object_category' )

	) {


		if( get_post_status( $id ) !== FALSE && get_post_type( $id ) === 'ak_object' ) {

			$image = ak_object_featured_image( $id );

		} else {

			$image = ak_term_featured_image( $id );

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






/*
function ak_essgrid_ids( $arrPosts ) {

	global $AK_ESSGRID_POST_EMPTY;

	return $arrPosts;

}




add_filter('essgrid_get_posts_by_ids', 'ak_essgrid_ids', 4, 1);*/

//apply_filters('essgrid_get_posts_by_ids', $arrPosts);