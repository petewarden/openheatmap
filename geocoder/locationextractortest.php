#!/usr/bin/php 
<?php 
 
require_once('locationextractor.php'); 
require_once('simple_html_dom.php'); 
require_once('geoutils.php'); 
require_once('cliargs.php');

$cliargs = array(
	'inputurl' => array(
		'short' => 'i',
		'type' => 'required',
		'description' => 'The URL to pull the text from',
	),
);	
 
ini_set('display_errors', true); 
set_time_limit(0); 
 
$result = array( 
    'errors' => array(), 
    'warnings' => array(), 
    'output_id' => '', 
    'use_base64' => false, 
); 

$options = cliargs_get_options($cliargs);
$input_url = $options['inputurl']; 
 
$input_string = curl_get($input_url); 
 
$input_html = str_get_html($input_string); 
 
$title_element = $input_html->find('title', 0); 
if (isset($title_element)) 
    $title = $title_element->plaintext; 
else 
    $title = ''; 
 
$title = clean_whitespace($title); 
     
print "Title: '$title'\n"; 
 
$body = $input_html->find('body', 0); 
if (!isset($body)) 
    fatal_error(0, "Couldn't find a <body> in '$input_url'"); 
     
$text = $body->text(); 
 
$text = clean_whitespace($text); 
     
print "Text: '$text'\n"; 
 
$places = extract_locations($text); 
 
print "Places: ".implode(', ', $places)."\n"; 
 
?>
