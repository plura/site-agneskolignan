<?php

/*

//change permalink structure - /objects/postID
//https://wordpress.stackexchange.com/a/158224
add_filter('post_type_link', 'rg_object_change_link', 1, 3);

function ak_object_change_link( $link, $post = 0 ){
    
    if ( $post->post_type == 'rg_object' ) {

        return home_url( 'objects/' . $post->ID );
    
    }

	return $link; 
 
}

add_action( 'init', 'rg_object_change_rewrites_init' );

function ak_object_change_rewrites_init(){

	add_rewrite_rule(
		'objects/([0-9]+)?$',
		'index.php?post_type=rg_object&p=$matches[1]',
		'top');

}

//change object title
//https://www.cyberciti.biz/programming/how-to-customize-title-in-wordpress-themes-using-pre_get_document_title/
add_filter('pre_get_document_title', 'rg_object_page_title', 999, 1);

function ak_object_page_title( $title ) {

     if ( is_singular('rg_object') ) {

		return __('Object', 'rg') . ' ' . get_the_ID() . ' - ' . get_bloginfo('name');
     
     }

     return $title;

} */


/*
function ak_gallery_shortcode( $args ) {

	$atts = shortcode_atts( [
		'artist' => '',
		//'eg' => 'grid-artist-objects',
		'ids' => '',
		'label' => '',
		'limit' => '-1',
		'rand' => '',
		'shop' => ''
	], $args );

	return ak_objects( $atts );

} 

add_shortcode('ak-gallery', 'ak_gallery_shortcode');*/