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
require_once('projconvert.php');

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
    'projection' => array(
        'short' => 'p',
        'type' => 'optional',
        'description' => 'Convert from projection\'s easting/northing coordinates to lat/lon',
        'default' => '',
    ),
    
);	

ini_set('memory_limit', '-1');

$options = cliargs_get_options($cliargs);

$input_geometry = $options['inputgeometry'];
$input_attributes = $options['inputattributes'];
$output_file = $options['outputfile'];
$convert_from_uk = $options['convertfromuk'];
$projection = $options['projection'];

if (!empty($projection))
{
    if ($projection=='mex')
    {
        $projection_parameters = array(
            'r_major' => 6378137.0,
            'r_minor' => 6356752.314,
            'false_easting' => 2500000.0,
            'false_northing' => 0.0,
            'center_lon' => -102.0,
            'lat1' => 17.5,
            'lat2' => 29.5,
            'center_lat' => 12.0,
        );
    }
    else
    {
        error_log("Unknown projection '$projection'");
        cliargs_print_usage_and_exit(); 
    }

    $proj_convert = new ProjConvert($projection_parameters);
}

$osm_ways = new OSMWays();

$geometry_string = file_get_contents($input_geometry) or die("Couldn't open $input_geometry for reading");
$geometry = json_decode($geometry_string, true);

$attributes_string = file_get_contents($input_attributes) or die("Couldn't open $input_attributes for reading");
$attributes = json_decode($attributes_string, true) or die("Couldn't decode $attributes_string");

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
            else if (isset($proj_convert))
            {
                list($lon, $lat) = $proj_convert->lcc2ll(array($x, $y));
            }
            else
            {
                $lat = $y;
                $lon = $x;
            }
        
            $osm_ways->add_vertex($lat, $lon);
        }
        
        if (!is_array($current_attributes))
            die("Bad attributes: ".error_log(print_r($current_attributes, true)));
        
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