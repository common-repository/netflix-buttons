<?php
/**
 * @package Netflix_Buttons
 * @version 1.5
 */
$admin = dirname(__FILE__);
$admin = substr($admin, 0, strpos($admin, "wp-content"));
require_once($admin.'wp-admin/admin.php');
require_once('../simpleOAuth.php');

function search_netflix_titles($query){
	$query = urlencode($query);
	
	$key = get_option('netflix_application_key');
	$secret = get_option('netflix_application_secret');

	$available = array('instant' => false, 'add' => false, 'save' => false);
	
	//Check the availability of the movie
	$path = "http://api.netflix.com/catalog/titles"; 
	$oauth = new OAuthSimple();  
    $signed = $oauth->sign(array(
					'path' => $path,  
                    'parameters' => array('output' => 'json', 'term' => $_GET['title'], 'max_results' => 10),  
                    'signatures' => array('consumer_key' => $key, 'shared_secret' => $secret    
              )));
			  
	$curl = curl_init();  
    curl_setopt($curl,CURLOPT_URL,$signed['signed_url']);  
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);  
    curl_setopt($curl,CURLOPT_ENCODING,'gzip,deflate');
    $buffer = curl_exec($curl);
	$result = json_decode($buffer);
	
	$titles = array();
	foreach($result->catalog_titles->catalog_title as $title){
		$id = preg_replace('/http:\/\/api.netflix.com\/catalog\/titles\/(.+)\/([0-9]+)/i', '$2', $title->id);
		$titles[] = array('title' => $title->title->regular, 'id' => $id);
	}
	return json_encode($titles);
}

echo search_netflix_titles($_GET['title']);

?>
