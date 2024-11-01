<?php

/**
 * Plugin Name: wp autopost trakt.tv activity
 * Plugin URI: http://tehgeekinside.com/category/production/wp-autopost-trakt-tv-activity
 * Description: WP Autopost Trakt.tv Activity plugin does retrive your trakt.tv activity (scrobble and checkin) data and create custom post for each activity.
 * Version: 1.1
 * Author: hephaistosthemaker
 * Author URI: http://tehgeekinside.com
 * License: GPL2
 */

/*  Copyright 2014  hephaistos themaker  (email : hephaistos.themaker@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

register_activation_hook( __FILE__, 'wata_activation' );

function wata_activation() {
	wp_schedule_event( time(), 'twicedaily', 'wata_twicedaily_event_hook' );
}

add_action( 'wata_twicedaily_event_hook', 'wata_plugin_launch' );


register_deactivation_hook( __FILE__, 'wata_deactivation' );

function wata_deactivation() {
	wp_clear_scheduled_hook( 'wata_twicedaily_event_hook' );
}


// Add plugin menu and page
add_action('admin_menu', 'wata_admin_actions');

function wata_admin_actions() {
	add_options_page("wp autopost trakttv activity", "wp autopost trakttv activity", 1, "wp_autopost_trakttv_activity", "wata_admin");
}

function wata_admin () {
	include ('wata_options.php');
}

 
// Add jquery dependances
add_action( 'admin_enqueue_scripts', 'wata_scripts_init' );

function wata_scripts_init() {
	// js scripts init
		wp_enqueue_script('jquery');
		// load a JS file from my plugin
		wp_enqueue_script('my_script', plugins_url( 'my_jquery_functions.js' , __FILE__ ), array('jquery'));

	// css styles init
		// Respects SSL, Style.css is relative to the current file
		wp_register_style( 'wata-style', plugins_url('style.css', __FILE__) );
		wp_enqueue_style( 'wata-style' );
}


function wata_plugin_launch() {
	$log = "-----".PHP_EOL.date("j.n.Y-G:i").' --- wata_plugin_launch'.PHP_EOL;
	file_put_contents(WP_PLUGIN_DIR."/wp-autopost-trakttv-activity/log.txt", $log, FILE_APPEND);
	if (wata_ckeck_user_preferences()) {
		$movie = wata_get_trakt_movies_data();
		$log = "-".PHP_EOL;
		file_put_contents(WP_PLUGIN_DIR."/wp-autopost-trakttv-activity/log.txt", $log, FILE_APPEND);
		foreach ($movie as $value) {

			// check if this movie has already been published
			/*
			$postid = wata_movie_post_id ($value);
			if ($postid == "") {
				$postid = wata_create_movie_post ($value);//must return the post id created		
			}
			*/

			$postid = wata_create_movie_post ($value);
//$log = $log.$value[imdb_id].'->'.$postid.':'.PHP_EOL;
//file_put_contents(WP_PLUGIN_DIR."/wp-autopost-trakttv-activity/log.txt", $log, FILE_APPEND);
			//create status update ($value, $postid)
			//wata_create_movie_status ($value, $postid);

			//movie post comment ($value, $postid)

			//update post last seen section (div id=seen)
				// get movie post id
				// get content
				// get div id seen content
				// see if div id last seen has at least one iteration
				// put last seen: text
				// update div id last seen content
				// create ne div id seen content


			//wata_create_movie_update//featured update only recquired if multiple post for the same movie is forbidden
			wata_set_featured_movies ($postid, $value[imdb_id]);

		}// end of foreach loop
	}//end of if condition
}// end of wata_plugin_launch function


function wata_ckeck_user_preferences () {
	if (!get_option('wata_tuser')) {
		$log = 'ERROR: plugin not launched because trakt.tv username is not defined'.PHP_EOL;
		file_put_contents(WP_PLUGIN_DIR."/wp-autopost-trakttv-activity/log.txt", $log, FILE_APPEND);
		return false;
	} elseif (!get_option('wata_tuser')) {
		$log = 'ERROR: plugin not launched because trakt.tv apikey is not defined'.PHP_EOL;
		file_put_contents(WP_PLUGIN_DIR."/wp-autopost-trakttv-activity/log.txt", $log, FILE_APPEND);
		return false;
	} elseif (get_option('wata_tuser') == "") {
		$log = 'ERROR: plugin not launched because trakt.tv username is not defined'.PHP_EOL;
		file_put_contents(WP_PLUGIN_DIR."/wp-autopost-trakttv-activity/log.txt", $log, FILE_APPEND);
		return false;
	} elseif (get_option('wata_tuser') == "") {
		$log = 'ERROR: plugin not launched because trakt.tv apikey is not defined'.PHP_EOL;
		file_put_contents(WP_PLUGIN_DIR."/wp-autopost-trakttv-activity/log.txt", $log, FILE_APPEND);
		return false;
	} else {
		return true;
	}
}


function wata_get_trakt_movies_data () {// Get Trakt.tv data for movies

	// Get user preferences
	$apikey = get_option('wata_apikey');
	$username = get_option('wata_tuser');
	//
	$actions = "scrobble,checkin";

	//Transform get date in YYYY-MM-DD format to timestamp PST format for use with trakt.tv api
	$lastdate = get_option('wata_first_date');
	$mylastdate = DateTime::createFromFormat('Y-m-d', $lastdate);
	$newlastdate = $mylastdate->format('U');

	$lasttimestamp = get_option('wata_lasttimestamp');

	if ($newlastdate > $lasttimestamp) {
		update_option('wata_lasttimestamp', $newlastdate);
		$lasttimestamp = $newlastdate;
	}

	$personnalratings = get_option('wata_personnalratings');


	$request='http://api.trakt.tv/activity/user.json/'.$apikey.'/'.$username.'/movie/scrobble,checkin/'.$lasttimestamp;
	$contents = file_get_contents($request);
	$results = json_decode($contents, true);

	$activity = $results['activity'];

	//sort result array by timestamp
	function compareOrder($a, $b)
	{
	  return $a['timestamp'] - $b['timestamp'];
	}
	usort($activity, 'compareOrder');

		$log = count($activity).' activities found'.PHP_EOL;


	//construction of the result array with all eligible activity (lasttimestamp, ...)
	$i = 0;
	$data = array();

	foreach ($activity as $key => $value) {

		if ($value['timestamp'] > $lasttimestamp) {//Work only for recent entries
		if ($i < 10) {//Work only for the first 10 entries
			update_option('wata_lasttimestamp', $value['timestamp']);
			$lasttimestamp = $value['timestamp'];

			$data[$i]['timestamp'] = $value['timestamp'];		
			$data[$i]['date'] = $value['when']['day'];
			$data[$i]['time'] = $value['when']['time'];
			$data[$i]['movie'] = $value['movie']['title'];
			$data[$i]['year'] = $value['movie']['year'];
			$data[$i]['imdb_id'] = $value['movie']['imdb_id'];
			$data[$i]['url'] = $value['movie']['url'];
			$data[$i]['trailer'] = $value['movie']['trailer'];
			$data[$i]['overview'] = $value['movie']['overview'];
			$data[$i]['poster'] = $value['movie']['images']['poster'];
			$data[$i]['fanart'] = $value['movie']['images']['fanart'];

			//optionnaly look for personnal rating
			if ($personnalratings == "checked") {
				$ratingrequest='http://api.trakt.tv/activity/user/movies.json/'.$apikey.'/'.$username.'/'.$value["movie"]["imdb_id"].'/rating?min=1';
				$ratingcontents = file_get_contents($ratingrequest);
				$ratingresults = json_decode($ratingcontents, true);
				$ratingactivity = $ratingresults['activity'];

				foreach ($ratingactivity as $value2) {
					$data[$i]['rating'] = $value2['rating'];
					$data[$i]['rating_advanced'] = $value2['rating_advanced'];
					$data[$i]['use_rating_advanced'] = $value2['use_rating_advanced'];
				}
			}
		$i = $i+1;
		}// end of if condition to limit to the fisrt 10 entries
		}// end of if condition
	}// end of foreach loop

		$log = $log.count($data).' activities selected'.PHP_EOL;

		file_put_contents(WP_PLUGIN_DIR."/wp-autopost-trakttv-activity/log.txt", $log, FILE_APPEND);

	return $data;

} //end get_trakt_movies_data function


function wata_set_featured_image ($image_url, $post_id, $base_filename) {//get and set featured image for post
	$upload_dir = wp_upload_dir();
	$image_data = file_get_contents($image_url);
	$end_filename = basename($image_url);
	$filename = $base_filename.$end_filename;

	if(wp_mkdir_p($upload_dir['path'])) {
	    $file = $upload_dir['path'] . '/' . $filename;
	} else {
	    $file = $upload_dir['basedir'] . '/' . $filename;
	}

	file_put_contents($file, $image_data);
	$wp_filetype = wp_check_filetype($filename, null );
	$attachment = array(
	    'post_mime_type' => $wp_filetype['type'],
	    'post_title' => sanitize_file_name($filename),
	    'post_content' => '',
	    'post_status' => 'inherit'
	);
	$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
	require_once(ABSPATH . 'wp-admin/includes/image.php');
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	set_post_thumbnail( $post_id, $attach_id );
}//end of wata_set_featured_image function


function wata_create_movie_post ($input) {//Create movie post

	//Get user preferences
	$trailer = get_option('wata_trailer');
	$overview = get_option('wata_overview');
	$poster = get_option('wata_poster');
	$fanart = get_option('wata_fanart');
	$personnalratings = get_option('wata_personnalratings');

        $post_status = get_option('wata_post_status');
        $authorid = get_option('wata_authorid');
        $post_date = get_option('wata_post_date');

        $post_fanart_as_post_featured_image = get_option('wata_fanart_as_post_featured_image');

	$post_title_template = get_option('wata_post_title_template');
	$post_content_template = get_option('wata_post_content_template');
	$post_excerpt_template = get_option('wata_post_excerpt_template');

	$post_signature_option = get_option('wata_post_signature_option');
	$post_signature_template = get_option('wata_post_signature_template');

	//transform string into array
        $post_categories = get_option('wata_post_categories');
	$post_categories = preg_replace('/\s+/', '', $post_categories);
	$post_categories = explode(',', $post_categories);

	//transform string into array
        $post_tags = get_option('wata_post_tags');
	$post_tags = preg_replace('/\s+/', '', $post_tags);
	$post_tags = explode(',', $post_tags);
	foreach ($post_tags as $post_tag) {
		settype ($post_tag, "integer");
		$post_tags_int[] = $post_tag;
	}

	$post_type = 'post';

	// set youtube video code
	preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $input["trailer"], $matches);

	// create embed object html code
	$embed_video ='<object width="480" height="385"><param name="movie" value="http://www.youtube.com/v/'.$matches[0].'?fs=1&amp;hl=en_US"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/'.$matches[0].'?fs=1&amp;hl=en_US" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="480" height="385"></embed></object>';


	$post_template_input_array = array (
		'{{movie_title}}',
		'{{movie_year}}',
		'{{movie_imdb_id}}',
		'{{movie_trakt.tv_url}}',
		'{{movie_fanart_url}}',
		'{{movie_poster_url}}',
		'{{movie_overview}}',
		'{{movie_embed_trailer}}',
		'{{movie_watch_date}}',
		'{{movie_watch_time}}',
		'{{movie_personnal_rating}}',
		'{{movie_personnal_advanced_rating}}',
	);

	//tranform movie date from timestamp to compatible format
	$myDate = DateTime::createFromFormat('U', $input[timestamp]);
	$movie_date = $myDate->format('M j');

	$post_template_output_array = array (
		$input['movie'],
		$input['year'],
		$input['imdb_id'],
		$input['url'],
		$input['fanart'],
		$input['poster'],
		$input['overview'],
		$embed_video,
		$movie_date,
		$input['time'],
		$input['rating'],
		$input['rating_advanced'],
);

	$post_title = str_replace($post_template_input_array, $post_template_output_array, $post_title_template);

	$post_content = str_replace($post_template_input_array, $post_template_output_array, $post_content_template);

	$post_excerpt = str_replace($post_template_input_array, $post_template_output_array, $post_excerpt_template);


	if ($post_signature_option == "checked") {
		$post_content = $post_content.$post_signature_template;
	}

	$post_name = $post_name_customization.$input['movie'];
	$post_status = $post_status;
	$post_author = $authorid;
	
	$post = array(
		'post_content'   => $post_content,
		'post_name'      => $post_name,
		'post_title'     => $post_title,
		'post_status'    => $post_status,
		'post_type'      => $post_type,
		'post_author'    => $post_author,
		'post_excerpt'   => $post_excerpt,
	);

	if ($post_date == "original") {
		//tranform movie date and time from timestamp to compatible format
		$myDate = DateTime::createFromFormat('U', $input['timestamp']);
		$newDate = $myDate->format('Y-m-d H:i:s');
		$post_date = $newDate;
		$post['post_date'] = $post_date;
	}

/* wp_insert_post use current user to decide if sanitize_post function is called. To avoid that we set current user to post_author
*/
$wata_current_user = wp_get_current_user();
$wata_current_user_id = $wata_current_user->ID;
wp_set_current_user( $post_author );

		$log = 'INFO: current user before is '.$wata_current_user_id.PHP_EOL;
		file_put_contents(WP_PLUGIN_DIR."/wp-autopost-trakttv-activity/log.txt", $log, FILE_APPEND);

		$log = 'INFO: required post author is '.$post_author.PHP_EOL;
		file_put_contents(WP_PLUGIN_DIR."/wp-autopost-trakttv-activity/log.txt", $log, FILE_APPEND);
$wata_current_user = wp_get_current_user();
$wata_current_user_id = $wata_current_user->ID;
		$log = 'INFO: current user after is'.$wata_current_user_id.PHP_EOL;
		file_put_contents(WP_PLUGIN_DIR."/wp-autopost-trakttv-activity/log.txt", $log, FILE_APPEND);

/* end of first part*/

	$post_ID = wp_insert_post( $post );

	//get and set featured image from fanart url

	if ($post_fanart_as_post_featured_image == "yes") {

		$base_filename = $input["movie"].'_'.$input["year"].'_';
		$post_fanart = $input['fanart'];
		wata_set_featured_image ($post_fanart, $post_ID, $base_filename);

	}

	// set category to post
	wp_set_post_terms($post_ID,$post_categories,'category',false);

	// set tags to post
	wp_set_object_terms($post_ID, $post_tags_int, 'post_tag', false);

//set current user back to its original state
wp_set_current_user( $wata_current_user_id );

	return $post_ID;
}//end wata_create_movie_post


function wata_movie_post_id ($input) {//return post id for a movie object based on its title (only first result)

	// TODO: MUST BE SET AUTOMATICALLY FROM USER PREFENRECES
	$movie_post_title = $input["movie"].' ('.$input["year"].')';

	$post = get_page_by_title( $movie_post_title, OBJECT, 'post' );
	$postid = $post->ID;

	return $postid;
}


function wata_unTag_post($post_ids, $tags_ids) {
    if(! is_array($post_ids) ) {
        $post_ids = array($post_ids);
    }
    if(! is_array($tags_ids) ) {
        $tags_ids = array($tags_ids);
    }
    foreach($post_ids as $post_id) {
        $terms = wp_get_object_terms($post_id, 'post_tag');
        $newterms = array();
        foreach($terms as $term) {
		$termid = $term->term_id;
		$tagid = (int)$termid;
		if ( !in_array($tagid,$tags_ids) ) {
			$newterms[] = $tagid;
		}
        }
        wp_set_object_terms($post_id, $newterms, 'post_tag', false);
    }
}// end function untag_post


function wata_addTag_post($post_ids, $tags_ids) {
	if(! is_array($post_ids) ) {
	$post_ids = array($post_ids);
	}
	if(! is_array($tags_ids) ) {
	$tags_ids = array($tags_ids);
	}
	foreach ($post_ids as $post_id) {
		$terms = wp_get_object_terms($post_id, 'post_tag');
		$newterms = array();
		foreach($terms as $term) {
			$termid = $term->term_id;
			$tagid = (int)$termid;
			$newterms[] = $tagid;
		}
		foreach ($tags_ids as $tags_id) {
			$newterms[] = (int)$tags_id;
		}
		wp_set_object_terms($post_id, $newterms, 'post_tag', false);
	}
}// end function addTag_post


function wata_set_featured_movies ($input, $imdb_id) {

	//Get user preferences
	$featured_movies_tagid = get_option('wata_movie_featuring_tag');
	$features_movies_maxnumber = get_option('wata_movie_featured_number');

	//get movie post id
	$postid = $input;
	$pair = array($imdb_id, $postid);
	//update array with new movies
	if (!get_option('wata_featured_movies')) {
		$featured_movies[] = $pair;
		update_option('wata_featured_movies', $featured_movies);
	} else {
		$featured_movies = get_option('wata_featured_movies');
		//add new pair of imdb id and post id to the begining of the array
		array_unshift($featured_movies, $pair);
		// reverse the feature movies array to older one first
		$featured_movies_reversed = array_reverse($featured_movies);
		//remove duplicates based on imdb_id begining by the most older movie added
		$featured_movies_noduplicates = array();
		$imax = count($featured_movies);
		foreach($featured_movies_reversed as $key => $value) {
			$imin = $key+1;
			$trap = 0;
    			for ($i=$imin; $i < $imax; $i++) {
				if ($value[0] == $featured_movies_reversed[$i][0]) {
					$trap = 1;
				}
			}
			if ($trap == 0) {
				$pair2 = array($value[0], $value[1]);
				array_unshift($featured_movies_noduplicates, $pair2);
			} 
  		}

		foreach($featured_movies_noduplicates as $key => $value) {
			//output is ordered from newest post_id to older
			if ($key < $features_movies_maxnumber) {
				$pair3 = array ($value[0], $value[1]);
				$featured_movies_limited[$key] = $pair3;
			}
  		}

		//update option
		$featured_movies = $featured_movies_limited;
		update_option('wata_featured_movies', $featured_movies_limited);
	}

	//get all the post id that have the feature tags
	$args = array(
		'tag_id' => $featured_movies_tagid,
		'posts_per_page' => -1);
	$posts_array = get_posts( $args );
	$postsid_array = array();

	foreach ( $posts_array as $post ) {
		$postsid_array[] = $post->ID;
	}

	//remove feature tag from all post 
	wata_unTag_post($postsid_array, $featured_movies_tagid);

	//set feature tag for post in the array
	foreach ($featured_movies as $value) {
		$featured_movies_postid[] = $value[1];

	}

	wata_addTag_post($featured_movies_postid, $featured_movies_tagid);

}// endo of function wata_set_featured_movies

/*
function wata_create_movie_status ($movie, $postid) {

	//get options
        $status_status = get_option('wata_status_status');
        $status_authorid = get_option('wata_status_authorid');
        $status_categories = get_option('wata_status_categories');

	//transform string into array
	$status_categories = preg_replace('/\s+/', '', $status_categories);
	$status_categories = explode(',', $status_categories);

	$status_type = 'post';
	$status_format = 'status';

	$timestamp = $movie[timestamp];
	$title = $movie[movie];
	$year = $movie[year];
	$url = $movie[url];
	$rating = $movie[rating_advanced];

	//set arg for wp_insert_post function
	$permalink = get_permalink( $postid );

	$status_content = '['.$rating.'/10] <a href="'.$permalink.'">'.$title.' ('.$year.')</a> <i>via</i> <a href="'.$url.'">trakt.tv</a>';

	$status_name = $title;
	$status_title = '['.$rating.'/10] '.$title.' ('.$year.') via trakt.tv';
	$status_status = $status_status;
	$status_author = $status_author;

	// prepare arg for wp_insert_post function
	$post = array(
		'post_content'   => $status_content,
		'post_title'     => $status_title,
		'post_status'    => $status_status,
		'post_type'      => $status_type,
		'post_author'    => $status_author,
	);

	//tranform movie date and time from timestamp to compatible format
	$myDate = DateTime::createFromFormat('U', $timestamp);
	$newDate = $myDate->format('Y-m-d H:i:s');
	$post_date = $newDate;
	$post[post_date] = $post_date;

	//TODO 'tags_input'     => 

	$post_ID = wp_insert_post( $post );
	wp_set_post_terms($post_ID,$status_categories,'category',false);
	set_post_format( $post_ID , $status_format);

	return $post_ID;
}// end of wata_create_movie_status fonction
*/





