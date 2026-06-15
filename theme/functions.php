<?php


add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles');
function my_theme_enqueue_styles() {
   wp_enqueue_style('child-style', get_theme_file_uri('/style.css'), [], time());
}



define('AK_PAGE_CLIENTS', 932);
define('AK_PAGE_COLLABORATIONS', 1706);
define('AK_PAGE_COLLECTIONS', 769);
define('AK_PAGE_MATERIALS', 849);
define('AK_PAGE_TAGS', 1863);

//Header Title: breadcrumbs hook
add_filter('plura_wp_breadcrumbs', function( ?array $crumbs, $object, ?string $context) {

    global $wp_query;

    //client
    if( is_singular('ak_client') ) {

        $crumbs[] = [ plura_wp_breadcrumb( AK_PAGE_COLLABORATIONS ) ];

    } else if( is_singular('ak_exhibition') ) {

        $crumbs[] = [ 
             ['name' => __('Exhibitions', 'twentytwentyfourchild') ]
        ];

    //collection tax
    } else if( is_tax('ak_object_collection') ) {

        if( $wp_query->get('ak_object_collection_client') ) {

            $client = get_page_by_path( $wp_query->get('ak_object_collection_client'), OBJECT, 'ak_client' );

            $crumbs[] = [ plura_wp_breadcrumb( AK_PAGE_COLLABORATIONS ), plura_wp_breadcrumb( $client ) ];

        } else {

            $crumbs[] = [ plura_wp_breadcrumb(AK_PAGE_COLLECTIONS) ];

        }

    //material tax
    } else if( is_tax('ak_object_material') ) {

        $crumbs[] = [ plura_wp_breadcrumb(AK_PAGE_MATERIALS) ];

    //tag tax
    } else if( is_tax('ak_object_tag') ) {

        $crumbs[] = [ plura_wp_breadcrumb(AK_PAGE_TAGS) ];

    }

    return $crumbs;

}, 10, 3);
