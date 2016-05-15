<?php
/*
Plugin Name: http2img
Plugin URI: http://trog.qgl.org/http2img
Description: HTTP/2 image serving
Author: trogau
Version: 0.1.0
Author URI: http://trog.qgl.org
License: GPLv2 or later
*/

// Inspired by https://blog.cloudflare.com/using-http-2-server-push-with-php/

// Need to turn on output buffering so we can add headers
ob_start();

add_action('shutdown', function() {
	$final = '';

	$levels = ob_get_level();
    
	for ($i = 0; $i < $levels; $i++)
	{
		$final .= ob_get_clean();
	}

	// Apply any filters to the final output
	echo apply_filters('final_output', $final);
}, 0);

add_filter('final_output', function($output) {
	// Pull out all the absolute image URLs we can find in the buffered WordPress output
	preg_match_all('/\bhttps?:\/\/\S+(?:png|gif|jpg)\b/', $output, $matches);

	// Filter out duplicates
	$matches[0] = array_unique($matches[0]);

	foreach ($matches[0] as $img)
	{
		// Strip out the site URL from the links and pass the result to pushImage(), which will
		// add headers for each image. 
		if (strstr($img, get_site_url()))
		{
			$img = str_replace(get_site_url(), "", $img);
			pushImage($img);
		}
	}
	return $output;
});


function pushImage($uri) 
{
	header("Link: <{$uri}>; rel=preload; as=image", false);
}
