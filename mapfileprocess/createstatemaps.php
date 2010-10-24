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

function split_by_country($input_osm_ways)
{
    $result = array();

    $input_ways = $input_osm_ways->ways;

    $count = 0;
    foreach ($input_ways as $input_way)
    {
        $count += 1;
        if (($count%100)==0)
            error_log("Processed $count ways");

        $tags = $input_way['tags'];

        if (!isset($tags['ISO']))
            die("Country code not found in $count: ".print_r($input_way, true));
        $country_code = $tags['ISO'];
        
        if (!isset($result[$country_code])) 
            $result[$country_code] = new OSMWays();

        $result[$country_code]->copy_way($input_way, $input_osm_ways);
    }

    return $result;
}

function save_country_maps($output_maps, $output_folder)
{
    foreach ($output_maps as $country_code => $output_osm_ways)
    {
        $output_file = $output_folder.'/'.strtolower($country_code).'_state.osm';
        $output_contents = $output_osm_ways->serialize_to_xml();
        file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");
    }
}

$cliargs = array(
	'inputfile' => array(
		'short' => 'i',
		'type' => 'optional',
		'description' => 'The file to read the input OSM XML data from - if unset, will read from stdin',
        'default' => 'php://stdout',
	),
	'outputfolder' => array(
		'short' => 'o',
		'type' => 'required',
		'description' => 'The directory to write the state maps for each country to',
	),
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_folder = $options['outputfolder'];

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($input_file) or die("Couldn't read file '$input_file'");
$input_osm_ways->deserialize_from_xml($input_contents);

$output_maps = split_by_country($input_osm_ways);
    
save_country_maps($output_maps, $output_folder);

?>