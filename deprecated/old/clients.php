<?php


//https://wordpress.stackexchange.com/questions/120407/how-to-fix-pagination-for-custom-loops
//https://egtutorial.com/howto/how-to-add-numeric-or-numbered-pagination-in-wordpress/

/*function ak_artists_posts_where( $where ) {
	
	//$where = str_replace("meta_key = 'speakers_$", "meta_key LIKE 'speakers_%", $where);
	$where = str_replace("meta_key = 'rg_exhibition_artists_$", "meta_key LIKE 'rg_exhibition_artists_%", $where);

	return $where;

}

add_filter('posts_where', 'ak_artists_posts_where'); */



function ak_clients( $args ) {

	global $sitepress;

	$query_vars = [
		'post_type' => 'ak_client',
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC'
	];

	$meta = [];

	if( !empty( $args['external'] ) && preg_match('/(false|0)/', $args['external']) ) {

		$meta[] = [
			'key'   => 'rg_artist_rg',
			'value' => '1'
		];

	}

	if( !empty( $meta ) ) {

		$query_vars['meta_query'] = $meta;

	}

	
	if( class_exists('sitepress') && $sitepress->get_current_language() !== $sitepress->get_default_language() ) {

		$current_lng = $sitepress->get_current_language();

		$sitepress->switch_lang( $sitepress->get_default_language() );

	}

	$query = new WP_Query( $query_vars );

	if( class_exists('sitepress') && isset( $current_lng ) ) {

		$sitepress->switch_lang( $current_lng );

	}


	if( $query->have_posts() ) {

		if( !empty( $args['eg'] ) ) {

			return rg_essential_grid( $query->posts, $args['eg'] );

		}

		return ak_clients_grid( $query->posts );

	}


}

function ak_clients_grid( $posts ) {

	$html = [];

	foreach( $query->posts as $post ) {

		$html[] = ak_clients_grid_item( $post );

	}

	$atts = [
		'class' => 'rg-group',
		'data-type' => 'artist',
		'data-layout' => 'grid'
	];

	return "<div " . p_attributes( $atts ) . ">" . implode('', $html) . "</div>";

}

function ak_artists_shortcode( $args ) {

	$atts = shortcode_atts( [
		'external' => '',
		'eg' => '',
		'limit' => '-1',
		'rand' => ''
	], $args );

	return ak_artists( $atts );

}

add_shortcode('rg-artists', 'ak_artists_shortcode');




function ak_clients_grid_item( $post ) {

	$classes = ['rg-group-item', 'rg-artist'];

	$img = p_thumbnail( $post->ID );

	if( $img ) {

		$classes[] = 'has-img';

	}

	$atts = [
		'class' => implode(' ', $classes),
		'href' => get_permalink( $post->ID ),
		'title' => $post->post_title
	];

	if( $img ) {

		$atts['style'] = 'background-image: url("' . $img[0] . '");';

	}

	return "<a " . p_attributes( $atts ) . ">" . $post->post_title . "</a>";

}




//https://galeriareverso.com/v2/rg_exhibition/fabian-kalman/
//get all artists related to an exhibition
function rg_exhibition_artists( $exhibitionID = false ) {

	if( !$exhitibionID ) {

		$exhitibionID = rg_wpml_id();

	}


	if( have_rows('rg_exhibition_artists', $exhitibionID) ) {

		$html = [];

		while ( have_rows('rg_exhibition_artists', $exhitibionID) ): the_row();

			$artist = get_sub_field('rg_exhibition_artist');

			$html[] = ak_clients_grid_item( $artist );

		endwhile;


		$atts = [
			'class' => 'rg-group',
			'data-type' => 'artist',
			'data-layout' => 'list',
			'data-label' => __('Artists', 'rg')
		];

		return "<div " . p_attributes( $atts ) . ">" . implode('', $html) . "</div>";

	}


}

function rg_exhibition_artists_shortcode() {

	return rg_exhibition_artists();

}

add_shortcode('rg-exhibition-artists', 'rg_exhibition_artists_shortcode');




//get all exhibitions related to an artist
function rg_artist_exhibitions( $artistID = false ) {

	if( !$artistID ) {

		$artistID = get_the_ID();

	}

	$query = new WP_Query([
		'post_type' => 'rg_exhibition',
		'posts_per_page' => -1,
		'meta_query' => array(
			array(
				'key' => 'rg_exhibition_artists_$_rg_exhibition_artist',
				'value' => rg_wpml_id( $artistID ),
				'compare' => 'LIKE'
			)
		)
	]);

	if( $query->have_posts() ) {

		$html = [];

		foreach( $query->posts as $post ) {

			$classes = ['rg-group-item', 'rg-artist-exhibition'];

			$atts = [
				'class' => implode(' ', $classes),
				'href' => get_permalink( $post->ID ),
				'title' => $post->post_title
			];

			$html[] = "<a " . p_attributes( $atts ) . ">" . $post->post_title . "</a>";

		}

		$atts = [
			'class' => 'rg-group',
			'data-type' => 'exhibition',
			'data-label' => __('Exhibitions', 'rg'),
			'data-layout' => 'list'
		];

		return "<div " . p_attributes( $atts ) . ">" . implode('', $html) . "</div>";

	}

}

function rg_artist_exhibitions_shortcode() {

	return rg_artist_exhibitions();

}

add_shortcode('rg-artist-exhibitions', 'rg_artist_exhibitions_shortcode');





function ak_artists_img( $artistID ) {

	$img = p_thumbnail( rg_wpml_id( $artistID ) );

	if( $img ) {

		$atts = ['class' => 'rg-artist-img', 'src' => $img[0], 'width' => $img[1], 'height' => $img[2]];

		return "<img " . p_attributes( $atts ) . "/>";

	}

	//return p_thumbnail( $artistID );

}

function ak_artists_img_shortcode() {

	$atts = shortcode_atts( ['id' => ''], $args ); 

	return ak_artists_img( empty( $atts['id'] ) ?  $atts['id'] : get_the_ID() );

}

add_shortcode('rg-artist-img', 'ak_artists_img_shortcode');





function ak_artists_bio( $artistID ) {

	$bio = get_field('artist_bio', rg_wpml_id( $artistID ) );

	if( $bio ) { 

		$atts = ['class' => 'rg-artist-bio', 'href' => $bio['url'], 'target' => '_blank', 'title' => __('Biography', 'rg') ];

		return "<a " . p_attributes( $atts ) . ">" .  __('Biography', 'rg') . "</a>";

	}

}

function ak_artists_bio_shortcode() {

	$atts = shortcode_atts( ['id' => ''], $args ); 

	return ak_artists_bio( empty( $atts['id'] ) ?  $atts['id'] : get_the_ID() );

}

add_shortcode('rg-artist-bio', 'ak_artists_bio_shortcode'); 