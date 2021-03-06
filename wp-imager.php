<?php

/**
 *	WP Imager
 *
 *	Description			Script for WordPress that provides resizing, output customization and image caching. Supports Jetpack Photon. Can be used inside or outside the loop.
 *	First Release		29.01.2014
 *	Version				2.7.6
 *	License				GPL V3 - http://choosealicense.com/licenses/gpl-v3/
 *  External libs		TimThumb - http://code.google.com/p/timthumb/
 *
 *	Author:				Jany Martelli
 *	Author's Website:	https://www.shambix.com/
 *  Script url:			https://github.com/Jany-M/WP-Imager
 *
 *  @Requirements
 *  The plugin needs the cache_img folder to reside in the root of your website.
 *  Inside the cache_img folder you must create a cache folder, that is writable (try to chmod it to 777 in case script cant write to it)
 *  Inside the cache_img folder you must place the TimThumb script tt.php (and the TimThum config file if you need it, but it's not required)
 *  This script isnt "wp plugin-ready" at the moment, so you must place it in your template, and then include it in your functions.php (eg. include('wp_imager.php');)
 *
 *  @Params
 *	$width		int		Size of width (no px) - 100	(default)
 *	$height		int		Size of height (no px) - 100 (default)
 *	$crop		int		Type of cropping to perform - 1 (default)
 *						0 =	Resize to Fit exactly specified dimensions (no cropping)
 *	 					1 = Crop and resize to best fit the dimensions
 *						2 =	Resize proportionally to fit entire image into specified dimensions, and add borders if required
 *						3 =	Resize proportionally adjusting size of scaled image so there are no borders gaps
 *
 *	$class		string	class name/names to append to image - NULL (default)
 *	$link		bool	Wraps the image in HTML <a href="">img</a>, pointing to the image's post, with title attribute filled with post's title for better SEO. Wont' work with $exturl - false (default)
 *	$exturl		string	URL of some external image (eg. http://www.anothersite.com/image.jpg)
 *	$nohtml		bool	When false,images are wrapped already in their HTML tag <img src="" />, with alt attribute filled with post's title for better SEO. If true, only the image urlis returned - false (default)
 *	$post_id	int 	If empty, will retrieve current loop post->ID, or you can add your own post ID value
 *	$bg_color	int		In case of different cropping type (eg. with borders) or transparent png, you can add your own color (eg. 000000) - ffffff (default)
 *
 *  @Defaults
 *	Function always returns to avoid yet another parameter, so simply echo it in your code.
 *	Caching is done in a cache_img folder, in the root of your website, therefore this script requires your .htaccess to follow certain rules OR IT WONT WORK, that's why there is a .htaccess_sample file for you to use/adapt.
 *
**/

//function wp_imager($args = array()) {
function wp_imager($width=null, $height=null, $crop=1, $class=null, $link=true, $exturl=null, $nohtml=false, $post_id=null, $bg_color=null, $original_url=null) {

	/* --------------------------------------------------------------------------------
	*
	*   DEFAULTS
	*
	-------------------------------------------------------------------------------- */

	/*$default = array(
		'width'			=> '',
		'height'		=> '',
		'crop'			=> 1,
		'class'			=> '',
		'link'			=> false,
		'exturl'		=> '',
		'nohtml'		=> false,
		'post_id'		=> '',
		'bg_color' 		=> '',
		'greyscale'		=> '',
		'colorize' 		=> '',
		'original_size' => true,
		'original_url' 	=> false
	);
	$settings = array_merge($default,$args);
    extract($settings);

	$width 			= $settings['width'];
	$height 		= $settings['height'];
	$crop 			= $settings['crop'];
	$class 			= $settings['class'];
	$link 			= $settings['link'];
	$exturl 		= $settings['exturl'];
	$nohtml 		= $settings['nohtml'];
	$post_id 		= $settings['post_id'];
	$bg_color 		= $settings['bg_color'];
	$greyscale 		= $settings['greyscale'];
	$colorize 		= $settings['colorize'];
	$original_size 	= $settings['original_size'];
	$original_url 	= $settings['original_url'];*/

	// TEMP
	$original_size = $bg_color_tt = $printclass = $output = '';

	$cache = 'cache_img'; // the folder where the cached img are stored
	$htaccess = true; // will produce pretty urls - requires the htaccess
	$cdn = false;
	$photon = false;

	if($original_size === true) define('ORIGINAL_SIZE', true); // if one of the sizes isnt set, then use a function to determine it proportionally
	if($class !== '') $printclass = 'class="'.$class.'" ';

	// Default Sizes
	// Please adjust defaults in that in that file, not here
	$tt_conf = ABSPATH.$cache.'/tt-conf.php';
	if(is_file($tt_conf) || file_exists($tt_conf)) {
		require_once ($tt_conf);

		if(!isset($width) || $width == '') {
			$width = DEFAULT_WIDTH;
		} else {
			$width_tt = '&w='.$width;
		}
		if(!isset($height) || $height == '') {
			$height = DEFAULT_HEIGHT;
		} else {
			$height_tt = '&h='.$height;
		}
		if(!isset($crop) || $crop == '') {
			$crop = DEFAULT_ZC;
			$crop_tt = '&zc='.$crop;
		} else {
			$crop_tt = '&zc='.$crop;
		}
		if(!isset($bg_color) || $bg_color == '') {
			$bg_color = DEFAULT_CC;
		} else {
			$bg_color_tt = '&cc='.$bg_color.'&ct=0';
		}
	} else {
		// If tt-conf doesnt exist use these default values
		if($width == '') {
			$width = 300;
		} else {
			$width_tt = '&w='.$width;
		}
		if($height == '') {
			$height = 300;
		} else {
			$height_tt = '&h='.$height;
		}
		if(!isset($crop)) {
			$crop = 1;
			$crop_tt = '&zc='.$crop;
		} else {
			$crop_tt = '&zc='.$crop;
		}
		if(!isset($bg_color)) {
			$bg_color = 'ffffff';
		} else {
			$bg_color_tt = '&cc='.$bg_color.'&ct=0';
		}
	}

	// WP 4.5 image quality bump to 100
	/*function wpimager_full_quality( $quality ) {
    	return 100;
	}
	add_filter( 'wp_editor_set_quality', 'wpimager_full_quality' );
	add_filter( 'jpeg_quality', 'wpimager_full_quality' );*/

	/* --------------------------------------------------------------------------------
	*
	*   POST DATA SETUP
	*
	-------------------------------------------------------------------------------- */

	global $post;

	if ($exturl == '' && $post_id == '') { // global
		$the_id = $post->ID;
		$the_content = $post->post_content;
		$the_title = $post->post_title;
	} elseif ($post_id != '') { // post id in param
		$the_id = $post_id;
		$the_content = get_post_field('post_content', $post_id);
		$the_title = get_the_title($post_id);
	} elseif ($exturl != '') { // url in param
		$the_id = '';
		$the_content = '';
		$the_title = '';
	} else {
		$the_id = get_the_ID();
		$the_content = get_post_field('post_content', $the_id);
		$the_title = get_the_title($the_id);
	}

	// Get attachments
	$attachments = get_children( array('post_parent' => $the_id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'rand', 'numberposts' => 1) );

	// Get thumbnail
	$thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($the_id), 'full');

	/* --------------------------------------------------------------------------------
	*
	*   PLUGIN COMPATIBILITY
	*
	-------------------------------------------------------------------------------- */

	// Jetpack Photon
	// https://developer.wordpress.com/docs/photon/api/
	if(class_exists('Jetpack') && Jetpack::is_module_active('photon') ) { // method as of WP/Jetpack versions after 05/22/13
		$photon = true;
		remove_filter('image_downsize', array( Jetpack_Photon::instance(), 'filter_image_downsize'));
	} else {
		$photon = false;
	}
	if($photon && !function_exists('jetpack_photon_url' )) echo 'There is something wrong with your Jetpack / Photon module, or your server configuration - Make sure that your website is publicly reachable.';

	// OptiMole
	if(class_exists('Optml_Main')) {
		$cdn = true;
	}

	// WPML
	if(defined('ICL_LANGUAGE_CODE')) {
		global $sitepress;
		$deflang = $sitepress->get_default_language();
		if(ICL_LANGUAGE_CODE !== $deflang) {
			$lang = ICL_LANGUAGE_CODE;
			$genurl = str_replace('/'.$lang, '', get_bloginfo('url'));
			$exturl = str_replace(get_bloginfo('url').'/'.$lang, '', $exturl);
		}
	} else {
		$genurl = get_bloginfo('url');
		$exturl = str_replace(get_bloginfo('url').'/', '', $exturl);
	}


	$siteurl = $genurl.'/'.$cache;


	/* --------------------------------------------------------------------------------
	*
	*   1. EXTERNAL IMAGE URL
	*
	-------------------------------------------------------------------------------- */

	if ($exturl) {

		/*$img_name = substr($exturl, -7);
		$img_transient = 'img_transient'.$img_name;
		//delete_transient('archi_homeslide_6h');
        if(get_transient($img_transient) === false) {
			// Proportional sizes
			$prop_sizes = get_proportional_size($width, $height, $exturl);
			if(!empty($prop_sizes))
				$sizes = array();
				$sizes['w'] = '&w='.$prop_sizes[0];
				$sizes['h'] = '&h='.$prop_sizes[1];
			//Cache Results
	        set_transient($img_transient, $sizes, 4 * WEEK_IN_SECONDS );
	    }
	    $sizes = get_transient($img_transient);
		$width_tt = $sizes['w'];
		$height_tt = $sizes['h'];*/

		// Ouput
		if ($original_url) {
			$output = $exturl;
		} elseif($nohtml) {
			$output = $siteurl.'/tt.php?src='.$exturl.$width_tt.$height_tt.$crop_tt.$bg_color_tt.'&q=100';
		} else {
			$output = '<img src="'.$siteurl.'/tt.php?src='.$exturl.$width_tt.$height_tt.$crop_tt.$bg_color_tt.'&q=100" '.$printclass.'/>';
		}
		return $output;

	/* --------------------------------------------------------------------------------
	*
	*   2. WP FEATURED IMAGE
	*
	-------------------------------------------------------------------------------- */

	} elseif (function_exists('has_post_thumbnail') && has_post_thumbnail($the_id) && $thumbnail != false) {

		//echo 'feat img'; exit;

		// Fix for site url lang edit (WPML)
		if(defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE !== $deflang) {
			$thumb2part = str_replace(get_bloginfo('url').'/'.$lang.'/', '', $thumbnail[0]);
			$thumb2part = str_replace($genurl, '', $thumb2part);
		} else {
			$thumb2part = str_replace(get_bloginfo('url'), '', $thumbnail[0]);
		}

		// Proportional sizes
		/*$prop_sizes = get_proportional_size($width, $height, $thumbnail[0]);
		if(!empty($prop_sizes))
			$width = $prop_sizes[0];
			$height = $prop_sizes[1];
			$width_tt = '&w='.$prop_sizes[0];
			$height_tt = '&h='.$prop_sizes[1];*/

		// Fix for Photon
		if($photon) {
			$thumb2part = str_replace('https://','', $thumbnail[0]);
		}

		// Output
		if($original_url) {
			$output = $thumbnail[0];
		} elseif ($nohtml) {
			// PHOTON
			if($photon) {
				$output = 'https://i1.wp.com/'.$thumb2part.'?resize='.$width.','.$height.'&amp;quality=100&amp;strip=all';
			// CDN
			} elseif($cdn) {
				$output = $thumb2part;
			// HTACCESS
			} elseif ($htaccess) {
				$output = $siteurl.'/r/'.$width.'x'.$height.'-'.$crop.'/b/'.$bg_color.'/i/'.$thumb2part;
			// DEFAULT
			} else {
				$output = $siteurl.'/tt.php?src='.$thumb2part.$width_tt.$height_tt.$crop_tt.$bg_color_tt.'&q=100';
			}
		} else {
			if($link) $output .= '<a href="'.get_permalink($the_id).'" title="'.$the_title.'">';
			// PHOTON
			if($photon) {
				$output .= '<img src="https://i1.wp.com/'.$thumb2part.'?resize='.$width.','.$height.'&amp;quality=100&amp;strip=all" alt="'.$the_title.'" '.$printclass.' />';
			// CDN
			} elseif($cdn) {
				$output .= '<img src="'.$thumb2part.'" alt="'.$the_title.'" '.$printclass.' />';
			// HTACCESS
			} elseif ($htaccess) {
				$output .= '<img src="'.$siteurl.'/r/'.$width.'x'.$height.'-'.$crop.'/b/'.$bg_color.'/i/'.$thumb2part.'" alt="'.$the_title.'" '.$printclass.' />';
			// DEFAULT
			} else {
				$output .= '<img src="'.$siteurl.'/tt.php?src='.$thumb2part.$width_tt.$height_tt.$crop_tt.$bg_color_tt.'&q=100" alt="'.$the_title.'" '.$printclass.' />';
			}
			if($link !== '') $output .= '</a>';
		}
		return $output;

	/* --------------------------------------------------------------------------------
	*
	*   2. WP POST ATTACHMENTS
	*
	-------------------------------------------------------------------------------- */

	} elseif ($attachments == true) {

		//echo 'case attachments'; exit;

		foreach($attachments as $id => $attachment) {
			$img = wp_get_attachment_image_src($id, 'full');
			$img_url = parse_url($img[0], PHP_URL_PATH);

			//var_dump($img); exit;

			// Fix for site url lang edit (WPML)
			if(defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE !== $deflang) {
				$img2part = str_replace(get_bloginfo('url').'/'.$lang.'/', '', $img_url);
			} else {
				$img2part = str_replace(get_bloginfo('url'), '', $img_url);
			}

			// Proportional sizes
			/*if(is_null($height) || $hright < 1 ) {
				$prop_sizes = get_proportional_size($width, $height, $img[0]);
				//var_dump($prop_sizes); exit;
				if(!empty($prop_sizes))
					$width = $prop_sizes[0];
					$height = $prop_sizes[1];
					$width_tt = '&w='.$prop_sizes[0];
					$height_tt = '&h='.$prop_sizes[1];
			}*/

			/*echo $width.' - '.$height; //exit;
			echo ' --- '.$width_tt.' - '.$height_tt; exit;*/
			//echo $siteurl.'/tt.php?src='.$img2part.$width_tt.$height_tt.$crop_tt.$bg_color_tt; exit;

			// Fix for Photon
			if($photon) {
				$img2part = str_replace('https://','', $img_url);
			}

			// Output
			if($original_url) {
				$output = $img[0];
			} elseif ($nohtml) {
				// PHOTON
				if($photon) {
					$output = 'https://i1.wp.com/'.$img2part.'?resize='.$width.','.$height.'&amp;quality=100&amp;strip=all';
				// CDN
				} elseif($cdn) {
					$output = $img2part;
				// HTACCESS
				} elseif ($htaccess) {
					$output = $siteurl.'/r/'.$width.'x'.$height.'-'.$crop.'/b/'.$bg_color.'/i/'.$img2part;
				// DEFAULT
				} else {
					$output = $siteurl.'/tt.php?src='.$img2part.$width_tt.$height_tt.$crop_tt.$bg_color_tt.'&q=100';
				}
			} else {
				if($link) $output .= '<a href="'.get_permalink($the_id).'" title="'.$the_title.'">';
				// PHOTON
				if($photon) {
					$output .='<img src="https://i1.wp.com/'.$img2part.'?resize='.$width.','.$height.'&amp;quality=100&amp;strip=all" alt="'.$the_title.'" '.$printclass.' />';
				// CDN
				} elseif($cdn) {
					$output .='<img src="'.$img2part.'" alt="'.$the_title.'" '.$printclass.' />';
				// HTACCESS
				} elseif ($htaccess) {
					$output .='<img src="'.$siteurl.'/r/'.$width.'x'.$height.'-'.$crop.'/b/'.$bg_color.'/i/'.$img2part.'" alt="'.$the_title.'" '.$printclass.' />';
				// DEFAULT
				} else {
					$output .='<img src="'.$siteurl.'/tt.php?src='.$img2part.$width_tt.$height_tt.$crop_tt.$bg_color_tt.'&q=100" alt="'.$the_title.'" '.$printclass.' />';
				}
				if($link) $output .= '</a>';
			}
			return $output;
			break;
		}

	/* --------------------------------------------------------------------------------
	*
	*   2. POST CONTENT IMG PARSING
	*	Post contains some image, not attached to post (external or added through some file manager)
	*
	-------------------------------------------------------------------------------- */

	} else {

		$img_url = '';
  		ob_start();
  		ob_end_clean();
  		$search = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $the_content, $matches);
  		$img_url = $matches[1][0];
		// Keep original url for later
		$img_url_orig = $img_url;

  		// This is needed only in very rare cases (eg. imported content from other site and attachments were not correctly indexed within posts in db)
  		$pattern_url = '/^(.*)(\/wp-content\/.*)$/';
		$remove_url = '$2';
		$limit = -1;
		$img_url = preg_replace($pattern_url, $remove_url, $img_url, $limit, $count);

  		if(!empty($img_url)) {

		    // Fix for site url lang edit (WPML)
			if(defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE !== $deflang) {
				$img2part = str_replace(get_bloginfo('url').'/'.$lang.'/', '', $img_url);
			} else {
				$img2part = str_replace(get_bloginfo('url'), '', $img_url);
			}

			// Proportional sizes
			/*$prop_sizes = get_proportional_size($width, $height, $img2part);
			if(!empty($prop_sizes))
				$width = $prop_sizes[0];
				$height = $prop_sizes[1];
				$width_tt = '&w='.$prop_sizes[0];
				$height_tt = '&h='.$prop_sizes[1];*/

			if($original_url) {
				$output = $img_url_orig;
			} elseif ($nohtml) {

				/*if ($htaccess) {
					$output = $siteurl.'/r/'.$width.'x'.$height.'-'.$crop.'/b/'.$bg_color.'/i/'.$img2part;
				} else {*/
					$output = ''.$siteurl.'/tt.php?src='.$img2part.$width_tt.$height_tt.$crop_tt.$bg_color_tt.'&q=100';
				//}
			} else {

				if($link) $output .= '<a href="'.get_permalink($the_id).'" title="'.$the_title.'">';
				/*if ($htaccess) {
					//$output .='<img src="'.$siteurl.'/r/'.$width.'x'.$height.'-'.$crop.'/b/'.$bg_color.'/i/'.$img2part.'" alt="'.$the_title.'" '.$printclass.' />';
				} else {*/
					$output .='<img src="'.$siteurl.'/tt.php?src='.$img2part.$width_tt.$height_tt.$crop_tt.$bg_color_tt.'&q=100" alt="'.$the_title.'" '.$printclass.' />';
				//}
				if($link) $output .= '</a>';
			}
			return $output;

		}
	}

	// Since Photon keeps changing automatically all urls, we had to disable it, let's reactivate it now
	if ( $photon ) {
		add_filter( 'image_downsize', array( Jetpack_Photon::instance(), 'filter_image_downsize' ), 10, 3 );
	}
}

// Get proportional H of image
/*function get_proportional_size($width_target = null, $height_target = null, $img) {
	// Get IMG original size
	$img_data = getimagesize($img);

	//var_dump($img_data); exit;

	if(is_null($width_target) || $width_target == '')
		$width = $img_data[0];
	if(is_null($height_target) || $height_target == '')
		$height = $img_data[1];

	//echo 'W='.$width.' - H='.$height; exit;

	$ratio = $img_data[0] / $img_data[1];

	// If Ratio is 1 then we dont need to calculate proportions
	if($ratio != 1) {

		if($width_target > 0) {
			if ($width > $height) {
				$percentage = ($width_target / $width);
			} else {
				$percentage = ($width_target / $height);
			}
		} elseif($height_target > 0) {
			if ($width > $height) {
				$percentage = ($height_target / $width);
			} else {
				$percentage = ($height_target / $height);
			}
		}

		//echo $percentage; exit;

		//gets the new value and applies the percentage, then rounds the value
		$width = round($width * $percentage);
		$height = round($height * $percentage);
		//echo 'W='.$width.' - H='.$height; exit;
	}

	// put proportional w & h in array
	$new_sizes = array();
	$new_sizes[] = $width;
	$new_sizes[] = $height;

	//var_dump($new_sizes); exit;

	return $new_sizes;
}*/
?>
