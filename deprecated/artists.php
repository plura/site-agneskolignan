<?php

//https://wordpress.stackexchange.com/questions/120407/how-to-fix-pagination-for-custom-loops
//https://egtutorial.com/howto/how-to-add-numeric-or-numbered-pagination-in-wordpress/

/*function ak_artists_posts_where( $where ) {
	
	//$where = str_replace("meta_key = 'speakers_$", "meta_key LIKE 'speakers_%", $where);
	$where = str_replace("meta_key = 'rg_exhibition_artists_$", "meta_key LIKE 'rg_exhibition_artists_%", $where);

	return $where;

}

add_filter('posts_where', 'ak_artists_posts_where'); */








/*
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

add_shortcode('rg-artist-exhibitions', 'rg_artist_exhibitions_shortcode');*/


/*


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

add_shortcode('rg-artist-img', 'ak_artists_img_shortcode');*/





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