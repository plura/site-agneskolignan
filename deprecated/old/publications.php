<?php



function rg_publications( $args ) {

	$query_vars = [
		'post_type' => 'rg_publication',
		'posts_per_page' => $args['limit'],
		'orderby' => 'meta_value_num',
		'meta_key' => 'rg_publication_year'
	];

	$query = new WP_Query( $query_vars );

	if( count( $query->posts ) ) {

		if( !empty( $args['eg'] ) ) {

			return rg_essential_grid( $query->posts, $args['eg'] );

		}

		return rg_publications_grid( $query->posts );

	}

}


function rg_publications_grid( $posts ) {

	$a = [];

	foreach( $posts as $post ) {

		$atts = ['class' => 'rg-group-item'];

		$img = p_thumbnail( $post->ID );

		if( $img ) {

			$atts['style'] = "background-image: url('" . $img[0] . "');";

		}

		$html = "<div " . p_attributes( $atts ) . ">";

		//$html .= "<div class=\"rg-group-item-title\">" . $post->post_title . "</div>";

		$a[] = $html . "</div>";

	}

	$atts = [
		'class' => 'rg-group',
		'data-type' => 'publications',
		'data-layout' => 'grid'
	];

	return "<div " . p_attributes( $atts ) . ">" . implode('', $a) . "</div>";

}


function rg_publications_shortcode( $args ) {

	$atts = shortcode_atts( [
		'eg' => '',
		'limit' => '-1',
		'rand' => '',
		'limit' => -1
	], $args );

	return rg_publications( $atts );

}


add_shortcode('rg-publications', 'rg_publications_shortcode');
