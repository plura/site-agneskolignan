<?php
/*
 * Plugin Name: AgnesKolignan
 * Description: Common, site specific code changes for agneskolignan website
 * Domain Path: /languages
 * Text Domain: agneskolignan
 */
add_action('plugins_loaded', function () {
	if (!function_exists('plura_includes')) {
		add_action('admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>My Plugin:</strong> The <code>Plura</code> plugin must be active for this plugin to work properly.</p></div>';
		});
		return;
	}

	plura_includes([
		'includes/clients',
		'includes/common',
		'includes/config',
		'includes/objects',
		'includes/objects-collections'
	], __DIR__);
});





add_action( 'init', 'ak_init' );
  
/**
 * Load plugin textdomain.
 */
function ak_init() {

	load_plugin_textdomain( 'agneskolignan', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

}


add_action( 'admin_enqueue_scripts', function() {

	//plura_wp_enqueue( scripts: [__DIR__ . '/assets/css/admin.css'], prefix: 'ak-', cache: true );

	wp_enqueue_style( 'ak-admin', plugins_url( "/assets/css/admin.css", __FILE__ ) );

} );




//https://wordpress.stackexchange.com/a/237795

function wpdocs_channel_nav_class( $classes, $item, $args ) {

	if( isset( $item->object_id ) ) {

		$classes[] = sprintf( 'menu-item-object-id-%d', $item->object_id );

	}

	return $classes;
}

add_filter( 'nav_menu_css_class' , 'wpdocs_channel_nav_class' , 10, 4 );


function ak_enqueue_integrity($html, $handle, $src = "", $media = "") {

	if( $handle === 'leaflet' ) {

		if( preg_match('/\.js/', $html) ) {

			return preg_replace('/(src)/', 'integrity="sha256-o9N1jGDZrf5tS+Ft4gbIK7mYMipq9lqpVJ91xHSyKhg=" crossorigin="" $1', $html);

		} elseif( preg_match('/\.css/', $html) ) {

			return preg_replace('/(href)/', 'integrity="sha256-sA+zWATbFveLLNqWO2gtiw3HL/lh1giY/Inf1BJ0z14=" crossorigin="" $1', $html);

		}

	}
   
	return $html;

}

add_filter('style_loader_tag', 'ak_enqueue_integrity', 10, 2 );

add_filter('script_loader_tag', 'ak_enqueue_integrity', 10, 4);


add_action( 'wp_head', function() {

	$img = ak_config_bg_image();

	if( !$img ) return;

	$var = '--ak-config-bg:url("' . $img['url'] . '")';

	?><style type="text/css">:root {<?php echo $var; ?>}</style><?php

} );



//custom login page
add_action( 'login_enqueue_scripts', function() {

		?> 
		<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,600;1,400&display=swap" rel="stylesheet">
		<link rel='stylesheet' id='ak-login-css' href='<?php echo plugins_url('includes/css/login.css', __FILE__ ) . '?' . time(); ?>' type='text/css' media='all' />

		<?php 
} );



//add body class
add_filter('body_class', function( $classes ) {

	$c = [];

	if( ak_config_bg_image() ) {

		$c[] = 'ak-has-bg';

	}

	return array_merge($classes, $c);

} ); 
