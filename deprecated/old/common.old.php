<?php


$REWRITE_RULES = [

	[

		'page' => [
			'en' => 'past-exhibitions',
			'pt-pt' => 'exposicoes'
		],
		'regex' => "/%s\/([0-9]+)\/?/",
		'var' => 'rg_exhibition_year'

	]

];



//https://halfelf.org/2017/when-a-page-is-an-endpoint/
//https://second-cup-of-coffee.com/creating-a-rewrite-rule-in-wordpress/
add_action( 'init', 'rg_rewrite_rules');

function rg_rewrite_rules() {

	global $REWRITE_RULES;

	foreach( $REWRITE_RULES as $rule ) {

        $pages = is_array( $rule['page'] ) ? $rule['page'] : [ $rule['page'] ];

        foreach( $pages as $page ) {

            add_rewrite_rule(
                //"^" . $page . "/([0-9]+)(/(.+)?)?$",
                "^" . $page . "\/([0-9]+)/?",
                'index.php?pagename=' . $page . '&' . $rule['var'] . '=$matches[1]',
                'top'
            );

        }
 
	}

}


function rg_query_vars( $query_vars ) {

    global $REWRITE_RULES;

    foreach( $REWRITE_RULES as $rule ) {

        $query_vars[] = $rule['var'];

    }

    return $query_vars;

}

add_filter( 'query_vars', 'rg_query_vars' );



// Filter to handle rewrite rules in WPML Switcher
// https://barebones.dev/articles/wpml-hide-or-rewrite-language-urls-in-language-switcher-and-hreflang-tags/
add_filter('wpml_head_langs', 'rg_wpml_lang_switcher_rewrite_fix');
add_filter('icl_ls_languages', 'rg_wpml_lang_switcher_rewrite_fix'); 

     
function rg_wpml_lang_switcher_rewrite_fix( $languages ) {

	global $REWRITE_RULES, $wp;

	if( !empty( $REWRITE_RULES ) ) {

		foreach( $REWRITE_RULES as $rule ) {

			$pages = array_map( fn($value) => $value, $rule['page'], array_keys( $rule['page'] ) );

			$regex = sprintf( $rule['regex'], "(" . implode("|", $pages) . ")" );

			foreach( $rule['page'] as $lang => $page ) {

				$matches = [];

				if( isset( $languages[ $lang ] ) && preg_match( $regex, home_url( $wp->request ), $matches ) ) {

					$languages[ $lang ]['url'] = preg_replace('/\/(\?[^\/]+)?$/', "/$matches[2]/$1", $languages[ $lang ]['url'] );
					
				}		

			}

		}

	}

	return $languages;

}





/* WPML */
/*function rg_wpml() {

	return class_exists('sitepress');

}*/


function rg_wpml_set_lang( $lang, $return_current = true ) {

	global $sitepress;

	if( rg_wpml() && (

		( $lang === true && $sitepress->get_current_language() !== $sitepress->get_default_language() ) || 

		( is_string( $lang ) && $sitepress->get_current_language() !== $lang )

	 ) ) {

	 	if( $lang === true ) {

	 		$lang = $sitepress->get_default_language();

	 	}

	 	$current_lng = $sitepress->get_current_language();

	 	$sitepress->switch_lang( $lang );

	 	if( $return_current ) {

	 		return $current_lng;

	 	}

	}

}

/*
//gets the wpml id
function rg_wpml_id( $id = false, $default = true, $type = 'post' ) {

    global $sitepress;

    if( !$id ) {

    	$id = get_the_ID();

    }

    if( rg_wpml() && ( !$default || $sitepress->get_current_language() !== $sitepress->get_default_language() ) ) {

    	$objectIDs = is_array( $id ) ? $id : [ $id ];

    	$ids = [];

	    $lang = $default ? $sitepress->get_default_language() : $sitepress->get_current_language();

    	foreach( $objectIDs as $objectID ) {

	    	if( $type === 'post' ) {

	    		$type = get_post_type( $objectID );

	    	}

	        $ids[] = apply_filters( 'wpml_object_id', $objectID, $type, true, $lang );

    	}

    	if( !is_array( $id ) ) {

    		return $ids[0];

    	}

    	return $ids;

    }

    return $id;

} */



/* ESSENTIAL GRID */

//https://theme.co/forum/t/essential-grid-with-custom-post-types-for-masonry-layout-solved/32245
//https://theme.co/archive/forums/topic/custom-archive-and-search-index-page-with-essential-grid/
function rg_essential_grid($posts, $alias, $label = false) {

	$ids = [];

	foreach( $posts as $post ) {

		$ids[] = rg_wpml_id( $post->ID );

	}

	$atts = ['class' => 'rg-eg-holder'];
 
	if( $label ) {

		$atts['data-label'] = $label;

	}

	$html = do_shortcode('[ess_grid alias="' . $alias . '" posts="' . implode(',', $ids) . '"]');

	return "<div " . p_attributes( $atts ) . ">" . $html . "</div>";	

}




//essential grid hook for artists object grid
//https://www.essential-grid.com/faq/use-image-from-custom-field-as-grid-items-main-media/
function rg_custom_meta_image($media, $post_id) {

    if( get_post_type( $post_id ) === 'rg_object' ) {

        $image = rg_object_featured_image( $post_id );

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
 
add_filter('essgrid_modify_media_sources', 'rg_custom_meta_image', 4, 2);



/* OBJECT/PUBLICATION INFO */

//arrow functions instead of variables (translation will not 
//work otherwise) b/c language domain is not initiated yet

$RG_INFO_PUBLICATION = fn() => [
	'title' => __('Title', 'rg'),
	'author' => __('Author', 'rg'),
	'publisher' => __('Publisher', 'rg'),
	'designer' => __('Designer', 'rg'), 
	'year' => __('Year', 'rg'),
	'pages' => __('Pages', 'rg'),
	'languages' => __('Languages', 'rg'),
	'type' => __('Type', 'rg')
];



/* FEATURED IMAGE */
function rg_featured_image_shortcode( $args ) {

	if( !empty( $args['id'] ) || is_singular() ) {

		$id = !empty( $args['id'] ) ? $args['id'] : get_the_ID();

		$img = p_thumbnail( $id );

		if( $img ) {

			$atts = ['src' => $img[0], 'width' => $img[1], 'height' => $img[2], 'class' => 'rg-featured-image'];

			return "<img " . p_attributes( $atts ) . "/>";

		}

	}

}

add_shortcode('rg-featured-image', 'rg_featured_image_shortcode');

