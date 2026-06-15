<?php

define('AK_EG', 1);

define('AK_RS', 1);

define('AK_CLIENT_IGNORE', 39);



function ak_config_bg_images() {

	$bgs = get_field('ak_config_bg_images', 'option');

	if( $bgs ) {

		return $bgs;

	}

	return false;

}


function ak_config_bg_image( $random = true ) {

	$bgs = ak_config_bg_images();

	if( $bgs ) {

		return $bgs[ array_rand( $bgs, 1 ) ];

	}

	return false;

}
