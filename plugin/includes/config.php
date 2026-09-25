<?php

// The ak_client post standing for Agnes herself, so her own work is not labelled as a
// commission. Read by ak_client_ignore(); the Collaborations and My World pages pass the
// same id as a shortcode attribute.
define('AK_CLIENT_IGNORE', 39);



function ak_config_bg_images() {

	$bgs = get_field('ak_config_bg_images', 'option');

	if( $bgs ) {

		return $bgs;

	}

	return false;

}


// Always random: the $random parameter this used to take was never read, and no caller
// ever passed one.
function ak_config_bg_image() {

	$bgs = ak_config_bg_images();

	if( $bgs ) {

		return $bgs[ array_rand( $bgs, 1 ) ];

	}

	return false;

}
