#!/usr/bin/php
<?php

/*
OpenHeatMap processing
Copyright (C) 2010 Pete Warden <pete@petewarden.com>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once('cliargs.php');
require_once('../website/utils.php');

define('FLICKR_URL_BASE', 'http://api.flickr.com/services/rest/?method=flickr.photos.search');
define('FLICKR_API_KEY_PUBLIC', '93870f821641d31268bff4a891929e2b');

$cliargs = array(
	'bbox' => array(
		'short' => 'b',
		'type' => 'required',
		'description' => 'A comma-delimited list of 4 values defining the bounding box of the area that will be searched',
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the output OSM XML data to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
	'daysago' => array(
		'short' => 'd',
		'type' => 'optional',
		'description' => 'How many days in the past to search for pictures',
        'default' => 3,
	),    
);	

function curl_get($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'OpenHeatMap (curl)');
	$result = curl_exec($ch) or die("Couldn't fetch election data from $url\n");
	curl_close($ch);

	return $result;
}

function flickr_api_call($method, $params)
{
    $api_url = FLICKR_URL_BASE;
    $api_url .= '&api_key='.FLICKR_API_KEY_PUBLIC;
    foreach ($params as $key => $value)
        $api_url .= '&'.$key.'='.urlencode($value);

    $result_string = curl_get($api_url);

    $result_xml = simplexml_load_string($result_string);
    
    return $result_xml;
}

ini_set('memory_limit', '-1');
suppress_date_warning();

$options = cliargs_get_options($cliargs);

$bbox_string = $options['bbox'];
$output_file = $options['outputfile'];
$days_ago = $options['daysago'];

$bbox = explode(',', $bbox_string);
$top_lat = max($bbox[0], $bbox[2]);
$left_lon = min($bbox[1], $bbox[3]);
$bottom_lat = min($bbox[0], $bbox[2]);
$right_lon = max($bbox[1], $bbox[3]);

$php_time = (time()-($days_ago*24*60*60));
$mysql_time = date ("Y-m-d H:i:s", $php_time);

$output_file_handle = fopen($output_file, 'w') or die("Couldn't open $output_file for writing");

foreach ($result_xml->photos->children() as $child)
{
    $name = strtolower($child->getName());
    
    if ($name=='photo')
    {
        $id = (string)($child['id']);
        $owner = (string)($child['owner']);
        $secret = (string)($child['secret']);
        $server = (string)($child['server']);
        $farm = (string)($child['farm']);
        $title = (string)($child['title']);
        
        $photo_url = "http://farm{$farm}.static.flickr.com/{$server}/{$id}_{$secret}_m.jpg";

        fwrite($output_file_handle, $photo_url."\n");
    }
}

fclose($output_file_handle);

?>