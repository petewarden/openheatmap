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

ini_set('memory_limit', '-1');

function get_lat_lon_from_location_string($location, $place_cache, $minimum_precision = 360.0)
{
    if (preg_match('/([0-9.\-]+\.[0-9.\-]+)[, ]([0-9.\-]+\.[0-9.\-]+)$/i', $location, $matches))
    {
        $lat = $matches[1];
        $lon = $matches[2];
        
        if (!is_numeric($lat)||!is_numeric($lon))
        {
//                error_log("$location not decoded");
            return null;                
        }
    }
    else
    {
        $normalized_location = strtolower(json_decode('"'.$location.'"'));
        $normalized_location = preg_replace('/[,\-\/*+!$%.]/', ' ', $normalized_location);
        $normalized_location = preg_replace('/ +/', ' ', $normalized_location);
        $normalized_location = trim($normalized_location);

        if (!isset($place_cache[$normalized_location]))
        {
//            error_log("$normalized_location not found");
            return null;
        }
        
        $place_info = $place_cache[$normalized_location];

        $bounding_box = $place_info['boundingBox'];

        $south_west = $bounding_box['southWest'];
        $north_east = $bounding_box['northEast'];
        
        $lat_size = ($north_east['lat']-$south_west['lat']);
        $lon_size = ($north_east['lng']-$south_west['lng']);
        
        if (($lat_size>$minimum_precision)||($lon_size>$minimum_precision))
        {
//            error_log("$normalized_location too large");
            return null;
        }
        
        $centroid = $place_info['centroid'];
        
        $lat = $centroid['lat'];
        $lon = $centroid['lng'];
    }

    $result = array('lat' => $lat, 'lon' => $lon);
    
    return $result;
}

function process_twitter_file($file_name, $output_file_name, $place_cache)
{
    $file_handle = fopen($file_name, "r") or die("Couldn't open $file_name\n");
    $output_file_handle = fopen($output_file_name, "w") or die("Couldn't open $output_file_name\n");

    $previous_key = '';

    while(!feof($file_handle))
    {
        $current_line = trim(fgets($file_handle));
        
        if (empty($current_line))
            continue;
        
        $current_parts = explode(':', $current_line, 2);

        if (count($current_parts)!==2)
            continue;
        
        $current_key = trim($current_parts[0]);
        $current_data_string = trim($current_parts[1]);
        $current_data = json_decode($current_data_string, true);

        if ($current_key===$previous_key)
            continue;
            
        $previous_key = $current_key;
        
        $location = $current_data['user']['location'];
        
        if (empty($location))
            continue;
            
        $position = get_lat_lon_from_location_string($location, $place_cache, 2.0);
        
        if (empty($position))
            continue;
            
        fwrite($output_file_handle, $current_key."\t".json_encode($position)."\n");
    }

    fclose($file_handle);
    fclose($output_file_handle);
}


$cliargs = array(
	'inputfile' => array(
		'short' => 'i',
		'type' => 'optional',
		'description' => 'The input file containing the raw twitter data',
        'default' => 'php://stdin',
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the location data to',
        'default' => 'php://stdout',
	),
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_file = $options['outputfile'];

$place_cache_string = file_get_contents('placecache.json');
$place_cache = json_decode($place_cache_string, true) or error_log("Couldn't decode placecache");

process_twitter_file($input_file, $output_file, $place_cache);

?>