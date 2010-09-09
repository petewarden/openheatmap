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
require_once('osmways.php');
require_once('uktolatlon.php');

$cliargs = array(
	'inputgeometry' => array(
		'short' => 'g',
		'type' => 'required',
		'description' => 'The JSON file containing the geometry data from the .shp file',
	),
	'inputattributes' => array(
		'short' => 'a',
		'type' => 'required',
		'description' => 'The JSON file containing the attribute data from the .dbf file',
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the output OSM XML data to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
    'convertfromuk' => array(
        'short' => 'c',
        'type' => 'switch',
        'description' => 'Convert from UK Ordnance Survey easting/northing coordinates to lat/lon',
    ),
);	

ini_set('memory_limit', '-1');

$options = cliargs_get_options($cliargs);

$input_geometry = $options['inputgeometry'];
$input_attributes = $options['inputattributes'];
$output_file = $options['outputfile'];
$convert_from_uk = $options['convertfromuk'];

$osm_ways = new OSMWays();

$geometry_string = file_get_contents($input_geometry) or die("Couldn't open $input_geometry for reading");
$geometry = json_decode($geometry_string, true);

$attributes_string = file_get_contents($input_attributes) or die("Couldn't open $input_attributes for reading");
$attributes = json_decode($attributes_string, true);

$shapes = $geometry['shapes'];
foreach ($shapes as $shape)
{
    $index = $shape['index'];
    $parts = $shape['parts'];
    $current_attributes = $attributes[$index]['attributes'];
    
    error_log("Processing $index");
    
    foreach ($parts as $part)
    {
        $vertices = $part['vertices'];
        
        $osm_ways->begin_way();
        
        foreach ($vertices as $vertex)
        {
            $x = $vertex['x'];
            $y = $vertex['y'];
            
            if ($convert_from_uk)
            {
                $geo = NEtoLL($x, $y);
                $lat = $geo['latitude'];
                $lon = $geo['longitude'];
            }
            else
            {
                $lat = $y;
                $lon = $x;
            }
        
            $osm_ways->add_vertex($lat, $lon);
        }
        
        foreach ($current_attributes as $key => $value)
        {
            $osm_ways->add_tag($key, $value);
        }
        
        $osm_ways->end_way();
    }
}

$osm_xml = $osm_ways->serialize_to_xml();

$output_file_handle = fopen($output_file, 'w') or die("Couldn't open $output_file for writing");

fwrite($output_file_handle, $osm_xml);

fclose($output_file_handle);

?>