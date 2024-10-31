<?php
/**
 * @package Netflix_Buttons
 * @version 1.5
 */
/*
Plugin Name: Netflix Buttons
Plugin URI: http://wordpress.org/extend/plugins/netflix-buttons/
Description: This is a plugin to enable you to post netflix movies. The plugins features the add, play, and save buttons as well as optional images.
Author: James Swindle
Version: 1.5
Author URI: http://jaswin.net/
*/

include_once('simpleOAuth.php');
define('NETFLIX_BUTTONS_VERSION', '1.5');
define('NETFLIX_BUTTONS_URL', WP_PLUGIN_URL.'/netflix-buttons');
$count = 0;

function netflix_get_buttons($movieID, $img) {
	global $count;
	
	if(++$count % 4 == 0)
		sleep(1);
	$key = get_option('netflix_application_key');
	$secret = get_option('netflix_application_secret');
	$mid = intval($movieID);
	$available = array('instant' => false, 'add' => false, 'save' => false);
	
	//Check the availability of the movie
	$path = "http://api.netflix.com/catalog/titles/movies/$mid/format_availability"; 
	$oauth = new OAuthSimple();  
    $signed = $oauth->sign(array(
					'path' => $path,  
                    'parameters' => array('output' => 'json'),  
                    'signatures' => array('consumer_key' => $key, 'shared_secret' => $secret    
              )));
			  
	$curl = curl_init();  
    curl_setopt($curl,CURLOPT_URL,$signed['signed_url']);  
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);  
    curl_setopt($curl,CURLOPT_ENCODING,'gzip,deflate');
    $buffer = curl_exec($curl);
	$result = json_decode($buffer);
	if($result->status->status_code != 404){
		$availability = $result->delivery_formats->availability;
		if(is_array($availability)){
			foreach($availability as $format){
				if($format->available_from < time()){
					if(($format->category->term == 'DVD' || $format->category->term == 'Blu-ray') ||
						($format->term == 'DVD' || $format->term == 'Blu-ray'))
						$available['add'] = true;
					elseif($format->category->term == 'instant' || $format->term == 'instant')
						$available['instant'] = true;
				} else
					$available['save'] = true;
			}
		} else {
			if($availability->available_from < time()){
				if(($availability->category->term == 'DVD' || $availability->category->term == 'Blu-ray') ||
					($availability->term == 'DVD' || $availability->term == 'Blu-ray'))
					$available['add'] = true;
				elseif($availability->category->term == 'instant' || $availability->term == 'instant')
					$available['instant'] = true;
			} else
				$available['save'] = true;
		}
	}
	
	$buttons = false;
	$return = '';
	if($available['add'] && $available['instant'])
		$buttons = '["PLAY_BUTTON", "ADD_BUTTON"]';
	elseif($available['add'])
		$buttons = '["ADD_BUTTON"]';
	elseif($available['save'])
		$buttons = '["SAVE_BUTTON"]';
	
	if($buttons != false){
		$return = '<div class="netflix" id="'.$mid.'">';
		if($img == 'true')
			$return .= '<img src="http://cdn-7.nflximg.com/us/boxshots/large/'.$mid.'.jpg" alt="'.$mid.'" />';
		$return .= '<script src="http://jsapi.netflix.com/us/api/js/api.js">{"title_id" : "http://api.netflix.com/catalog/movie/'.$mid.'","button_type" : '.$buttons.',"show_logo": "false","x" : "40","y" : "20","dom_id" : "'.$mid.'","application_id" : "'.$key.'"}</script>';
		$return .= '</div>';
	}
	
	return $return;	
	
}

function print_netflix_buttons($content) {
	$pattern = '#\[netflix:.+:img:.+:end]#';
    preg_match_all ($pattern, $content, $matches);
	
	foreach($matches[0] as $match){
		$clear = '[netflix:';
        $m = str_replace($clear, '', $match);
		$clear = 'img:';
		$m = str_replace($clear, '', $m);
		$clear = ':end]';
		$m = str_replace($clear, '', $m);
		
		$pieces = explode(':',$m);
		
		$replace = netflix_get_buttons($pieces[0], $pieces[1]);
		
		$content = str_replace($match, $replace, $content);
		
	}
	return $content;
}

function show_netflix_options_page () {	
    if (isset($_POST['info_update']))
    {
        update_option('netflix_application_key', (string)$_POST["netflix_application_key"]);
        update_option('netflix_application_secret', (string)$_POST["netflix_application_secret"]);
        
        echo '<div id="message" class="updated fade">';
        echo '<p><strong>Options Updated!</strong></p></div>';
    }	
	
    $appKey = get_option('netflix_application_key');     
    $appSecret = get_option('netflix_application_secret');
                              
	?>
 	<h2>Netflix Buttons Settings v<?php echo NETFLIX_BUTTONS_VERSION; ?></h2>
 	
    
     <fieldset class="options">
    <legend>Usage:</legend>

    <p>To add the netflix buttons simply add the trigger text <strong>[netflix:MOVIE_ID:img:BOOL:end]</strong> to a post or page. Replace MOVIE_ID with the ID of the Netflix Movie, and replace BOOL with either <em>true</em> or <em>false</em>. For example: [netflix:70157187:img:true:end]</p>
    <p>(The Movie ID is located at the end of the movie URL. i.e. http://movies.netflix.com/WiMovie/The_League/<strong>70157187</strong>)</p>
    </fieldset>

    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
    <input type="hidden" name="info_update" id="info_update" value="true" />    
 	<?php
echo '
	<div class="postbox">
	<h3><label for="title">Application Key and Secret</label></h3>
	<div class="inside">';

echo '
<table class="form-table">
<tr valign="top">
<th scope="row">Application Key</th>
<td><input type="text" name="netflix_application_key" value="'.$appKey.'" size="40" /></td>
</tr>
<tr valign="top">
<th scope="row">Application Secret</th>
<td><input type="text" name="netflix_application_secret" value="'.$appSecret.'" size="40" /></td>
</tr>
<tr valign="top">
<th scope="row">How to get an Application Key and Secret</th>
<td><a href="http://developer.netflix.com/member/register" target="_blank">Register your website</a> and <a href="http://developer.netflix.com/apps/mykeys" target="_blank">find your key and secret here</a>.</td>
</tr>
</table>
</div></div>
    <div class="submit">
        <input type="submit" name="info_update" value="Update Options &raquo;" />
    </div>						
 </form>
 ';
    echo 'Like the Netflix Buttons plugin? <a href="http://wordpress.org/extend/plugins/netflix-buttons/" target="_blank">Give it a good rating</a>'; 
}

function netflix_options_page()
{
     echo '<div class="wrap"><h2>Netflix Buttons Options</h2>';
     echo '<div id="poststuff"><div id="post-body">';
     show_netflix_options_page();
     echo '</div></div>';
     echo '</div>';
}

// Display The Options Page
function netflix_buttons_options_page () 
{
     add_options_page('Netflix Buttons', 'Netflix Buttons', 'manage_options', __FILE__, 'netflix_options_page');  
}

function netflix_css()
{
    echo '<link type="text/css" rel="stylesheet" href="'.NETFLIX_BUTTONS_URL.'/netflix-buttons-style.css" />'."\n";
}

add_action('admin_menu','netflix_buttons_options_page');

add_filter('the_content', 'print_netflix_buttons',11);

add_action('wp_head', 'netflix_css');

/*
	TinyMCE Plugin
*/

function netflix_tinymce(){
	if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
		return;
		
	if ( get_user_option('rich_editing') == 'true') {
		add_filter("mce_external_plugins", "add_netflix_tinymce_plugin");
		add_filter('mce_buttons', 'register_netflix_button');
	}
}

function register_netflix_button($buttons){
	array_push($buttons, "|", "netflix");
	return $buttons;
}

function add_netflix_tinymce_plugin($plugin_array){
	$plugin_array['netflix'] = NETFLIX_BUTTONS_URL.'/tinymce/editor_plugin.js';
	return $plugin_array;
}

add_action('init', 'netflix_tinymce');

?>
