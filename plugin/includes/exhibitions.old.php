<?php

//common query vars for the exhibition CPT
function rg_exhibitions_query_vars( $args ) {

	$vars = [
		'post_type' => 'rg_exhibition',
		'posts_per_page' => -1,
		'meta_key' => 'rg_exhibition_date_start',
		'orderby'  => 'meta_value',
		'order'  	=> 'DESC'
	];

	$meta_query = [];

	$tax_query = [];

	if( !empty( $args['terms_exclude'] ) ) {

		//$terms_exclude = rg_wpml_id( explode(',', $args['terms']), false, 'rg_exhibitions_category' ); 

		$tax_query[] = [
			'taxonomy' => 'rg_exhibitions_category',
			'field' => 'term_id',
			'terms' => explode(',', $args['terms_exclude']),//$terms_exclude,
			'operator' => 'NOT IN'
		];

	} else if( !empty( $args['terms'] ) ) {

		//$terms = rg_wpml_id( explode(',', $args['terms']), false, 'rg_exhibitions_category' ); 

		$tax_query[] = [
			'taxonomy' => 'rg_exhibitions_category',
			'field' => 'term_id',
			'terms' =>  explode(',', $args['terms']),//$terms,
			'operator' => 'IN'
		];

	}

	if( !empty( $args['year'] ) ) {

		$year = $args['year'];

		array_push( $meta_query,

			[
				'key'           => 'rg_exhibition_date_start',
				'compare'       => '>=',
				'value'         => DateTime::createFromFormat('d/m/Y', "01/01/$year")->format('Y-m-d H:i:s'),
				'type'          => 'DATETIME',
			],
			
			[
				'key'           => 'rg_exhibition_date_start',
				'compare'       => '<=',
				'value'         => DateTime::createFromFormat('d/m/Y', "31/12/$year")->format('Y-m-d H:i:s'),
				'type'          => 'DATETIME',
			]

		);

	}

	foreach( ['tax_query' => $tax_query, 'meta_query' => $meta_query] as $k => $v ) {

		if( !empty( $v ) ) {

			$vars[ $k ] = $v;

		}

	}

	return $vars;

}



function rg_exhibitions( $args ) {  

	global $sitepress;

	$current_lng = rg_wpml_set_lang( true );

	$query = new WP_Query( rg_exhibitions_query_vars( $args ) );
	
	if( $current_lng ) {

		rg_wpml_set_lang( $current_lng );

	}

	if( $query->have_posts() ) {

		if( !empty( $args['eg'] ) ) {

			return rg_essential_grid( $query->posts, $args['eg'] );

		}

		return rg_exhibitions_grid( $query->posts );

	}

}

function rg_exhibitions_shortcode( $args ) {

	$atts = shortcode_atts( [
		'eg' => '',
		'terms' => '',
		'terms_exclude' => ''
	], $args );

	return rg_exhibitions( $atts );

}

add_shortcode('rg-exhibitions', 'rg_exhibitions_shortcode');



//https://developer.wordpress.org/reference/functions/add_query_arg/
//https://wordpress.stackexchange.com/questions/51444/add-extra-parameters-after-permalink
function rg_exhibitions_archive( $args ) {

	global $wp_query;

	$link = get_permalink( get_the_ID() );

	$t = [];

	//print_r( $wp_query );
	
	foreach(['rg_exhibition_year', 'lang', 'test'] as $k) {

		if( $wp_query->get($k) ) $t[$k] = $wp_query->get($k);

	}

	//print_r( $t );

	//get all exhibitions from a specific year
	//https://stackoverflow.com/questions/63870725/filter-custom-post-type-based-on-acf-date-field-value
	if( $wp_query->get('rg_exhibition_year') ) {

		$args['year'] = $wp_query->get('rg_exhibition_year');
		
		return rg_exhibitions( $args );

	}

	return rg_exhibitions_years_grid( $args, $link );

}

function rg_exhibitions_archive_shortcode( $args ) {

	$atts = shortcode_atts( [
		'eg' => '',
		'terms' => '',
		'terms_exclude' => ''
	], $args );

	return rg_exhibitions_archive( $atts );

}

add_shortcode('rg-exhibitions-archive', 'rg_exhibitions_archive_shortcode');




//get all different years with exhibitions 
//https://wordpress.stackexchange.com/a/112120
function rg_exhibitions_years_grid( $args, $link = '' ) {

	$query_vars = '';

	if( empty( $link ) ) {

		$link = get_permalink( get_the_ID() );

	}

	$has_query_vars = preg_match('/(\?.+)?$/', $link, $matches);

	if( $has_query_vars ) {

		$query_vars = $matches[1];

		$link = preg_replace('/(\?.+)?$/', '', $link);

	} 
 
 	$current_lng = rg_wpml_set_lang( true );

	$query = new WP_Query( rg_exhibitions_query_vars( $args ) );

	if( $current_lng ) {

		rg_wpml_set_lang( $current_lng );

	}

	if( $query->have_posts() ) {

		$years = [];

		foreach( $query->posts as $post ) {

			$date = get_field('rg_exhibition_opening_date', $post->ID);

			if( $date ) {

				$datetime = DateTime::createFromFormat('d/m/Y', $date);

				$year = $datetime->format('Y');

				if( !array_key_exists($year, $years) ) {

					$years[ $year ] = ['posts' => []];

				}

				if( !isset( $years[ $year ]['img'] ) && $img = p_thumbnail( $post->ID ) ) {

					$years[ $year ]['img'] = $img[0];

				} 

				$years[ $year ]['posts'] = $post;

			}

		}

		$html = [];

		foreach( $years as $k => $year ) {

			$classes = ['rg-group-item', 'rg-exhibitions-year'];

			$href = $link . $k . '/' . $query_vars;

			$atts = [
				'class' => implode(' ', $classes), 
				'data-exhibition-year' => $k,
				'href' => $href,
				'title' => $k
			];

			if( isset( $year['img'] ) ) {

				$atts['style'] = "background-image: url('" . $year['img'] . "');";

			}

			$html[] = "<a " . p_attributes( $atts ) . "></a>";


		}

		$atts = [
			'class' => 'rg-group',
			'data-group' => 'year',
			'data-type' => 'exhibition',
			'data-layout' => 'grid'
		];

		return "<div " . p_attributes($atts) . ">" . implode('', $html) . "</div>";

	}

}


//renders grid
function rg_exhibitions_grid( $posts ) {

	$html = [];

	foreach( $posts as $post ) {

		$classes = ['rg-group-item', 'rg-exhibition'];

		$atts = [
			'class' => implode(' ', $classes), 
			'href' => get_permalink( $post->ID ),
			'title' => $post->post_title
		];

		$img = p_thumbnail( $post->ID );

		if( $img ) {

			$atts['style'] = "background-image: url('" . $img[0] . "');";

		}

		$html[] = "<a " . p_attributes( $atts ) . "><b>" . get_field('rg_exhibition_date_start', $post->ID) . "</b></a>";

	}

	$atts = [
		'class' => 'rg-group',
		'data-type' => 'exhibition',
		'data-layout' => 'grid'
	];

	return "<div " . p_attributes( $atts ) . ">" . implode('', $html) . "</div>";

}



//get all date / hours info related to an exhibition
function rg_exhibition_datetime( $args ) {

	$id = !empty( $args['id'] ) ? $args['id'] : rg_wpml_id();

	$data = [];

	$html = [];

	$c = ['rg-datetime-item'];

	foreach( ['date_start', 'date_end', 'opening_date', 'opening_time_start', 'opening_time_end'] as $field ) {

		if( $value = get_field( 'rg_exhibition_' . $field, $id ) ) {

			$data[$field] = $value;

		}		

	}


	if( !preg_match('/(false|0)/', $args['date'] ) && isset( $data['date_start'], $data['date_end'] ) ) {

		$atts = ['class' => implode(' ', array_merge(['start-end'], $c) ) ];

		$start = wp_date( __('j F Y', 'rg'), DateTime::createFromFormat('d/m/Y', $data['date_start'])->getTimestamp() );

		$end = wp_date( __('j F Y', 'rg'), DateTime::createFromFormat('d/m/Y', $data['date_end'])->getTimestamp() );

		$html[] = "<div " . p_attributes( $atts ) . ">

			<div class=\"start\">" . $start . "</div>

			<div class=\"end\">" . $end . "</div>

		</div>";

	}


	if( !preg_match('/(false|0)/', $args['opening'] ) && isset( $data['opening_date'] ) ) {

		$classes = array_merge(['opening'], $c);

		$date = wp_date( __('l, F j, Y', 'rg'), DateTime::createFromFormat('d/m/Y', $data['opening_date'])->getTimestamp() );

		$html_opening_time = "";

		if( isset( $data['opening_time_start'], $data['opening_time_end'] ) ) {

			$classes[] = 'has-time';

			$html_opening_time = "	<div class=\"start\">" . $data['opening_time_start'] . "</div>

									<div class=\"end\">" . $data['opening_time_end'] . "</div>";

		}

		$atts = ['class' => implode(' ', $classes), 'data-label' => __('Opening', 'rg') ];

		$html[] = 	"<div " . p_attributes( $atts ) . ">

						<div class=\"date\">" . $date . "</div>" . $html_opening_time . "

					</div>";

	}


	if( !empty( $html ) ) {

		$atts = ['class' => 'rg-datetime'];

		return "<div " . p_attributes( $atts ) . ">" . implode('', $html) . "</div>";

	}

}

function rg_exhibition_datetime_shortcode( $args ) {

	$atts = shortcode_atts( [
		'date' => '',
		'id' => '',
		'opening' => ''
	], $args );

	return rg_exhibition_datetime( $atts );

}

add_shortcode('rg-exhibition-datetime', 'rg_exhibition_datetime_shortcode');