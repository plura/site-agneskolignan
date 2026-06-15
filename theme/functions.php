<?php


add_action( 'wp_head', function() { ?>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Permanent+Marker&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
<?php }, 1 );

add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles');
function my_theme_enqueue_styles() {
   wp_enqueue_style('child-style', get_theme_file_uri('/style.css'), [], time());
}

add_action( 'wp_enqueue_scripts', 'ak_theme_styles' );
function ak_theme_styles() {
	$scripts = [
		__DIR__ . '/assets/css/base.css',
		__DIR__ . '/assets/css/layout.css',
		__DIR__ . '/assets/css/grid.css',
		__DIR__ . '/assets/css/theme.css',
		__DIR__ . '/assets/js/scripts.js' => ['handle' => 'ak-core', 'module' => true],
	];

	if ( is_singular( ['ak_exhibition', 'ak_object'] ) ) {
		$scripts = [
			...$scripts,
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css'           => ['handle' => 'fancybox'],
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js'        => ['handle' => 'fancybox'],
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/panzoom/panzoom.css'             => ['handle' => 'panzoom'],
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/panzoom/panzoom.umd.js'         => ['handle' => 'panzoom'],
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.css'           => ['handle' => 'carousel'],
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.umd.js'       => ['handle' => 'carousel'],
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.thumbs.css'   => ['handle' => 'carousel-thumbs'],
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.thumbs.umd.js' => ['handle' => 'carousel-thumbs'],
		];
	}

	plura_wp_enqueue( scripts: $scripts, prefix: 'ak-', cache: false );
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
