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

ini_set('memory_limit', '-1');

$cliargs = array(
	'osminput' => array(
		'short' => 'i',
		'type' => 'required',
		'description' => 'The file to read the OSM ways from',
	),
	'phpoutput' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the translation table to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
	'osmoutput' => array(
		'short' => 'u',
		'type' => 'optional',
		'description' => 'The file to write the fixed-up OSM geometry to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
);	

$options = cliargs_get_options($cliargs);

$osm_input = $options['osminput'];
$php_output = $options['phpoutput'];
$osm_output = $options['osmoutput'];

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($osm_input) or die("Couldn't read file '$osm_input'");
$input_osm_ways->deserialize_from_xml($input_contents);

$output_osm_ways = new OSMWays();

$input_nodes = $input_osm_ways->nodes;

$district_map = array();
$constituency_map = array();

foreach ($input_osm_ways->ways as &$way)
{
    $tags = &$way['tags'];

    $district_name = $tags['name_0'];
    $district_name = ucwords($district_name);
    $constituency_name = $tags['name_1'];
    
    if (!isset($district_map[$district_name]))
        $district_map[$district_name] = count($district_map);
    $district_code = $district_map[$district_name];

    if (!isset($constituency_map[$constituency_name]))
        $constituency_map[$constituency_name] = count($constituency_map);
    $constituency_code = $constituency_map[$constituency_name];

    $output_osm_ways->begin_way();
 
    $output_osm_ways->add_tag('name', $constituency_name);
    $output_osm_ways->add_tag('hk_district_code', $district_code);
    $output_osm_ways->add_tag('hk_constituency_code', $constituency_code);

    foreach ($way['nds'] as $nd_ref)
    {
        if (!isset($input_nodes[$nd_ref]))
            continue;
            
        $node = $input_nodes[$nd_ref];
        $output_osm_ways->add_vertex($node['lat'], $node['lon']);
    }
    
    $output_osm_ways->end_way();
}

$output_contents = $output_osm_ways->serialize_to_xml();
file_put_contents($osm_output, $output_contents) or die("Couldn't write file '$output_osm_file'");

$php_output_handle = fopen($php_output, "w") or die("Couldn't open $php_output\n");

fwrite($php_output_handle, '$hk_district_code_accepted_values = array('."\n");
foreach ($district_map as $name => $code)
    fwrite($php_output_handle, '    "'.$code.'",'."\n");
fwrite($php_output_handle, ');'."\n\n");

fwrite($php_output_handle, '$hk_constituency_code_accepted_values = array('."\n");
foreach ($constituency_map as $name => $code)
    fwrite($php_output_handle, '    "'.$code.'",'."\n");
fwrite($php_output_handle, ');'."\n\n");

fwrite($php_output_handle, '$hk_district_name_translation_table = array('."\n");
foreach ($district_map as $full_name => $code)
{
    $name_parts = explode(' ', $full_name, 2);
    fwrite($php_output_handle, '    \''.addslashes(strtolower($name_parts[0])).'\' => \''.$code.'\','."\n");    
    fwrite($php_output_handle, '    \''.addslashes(strtolower($name_parts[1])).'\' => \''.$code.'\','."\n");    
}
fwrite($php_output_handle, ');'."\n\n");

fwrite($php_output_handle, '$hk_constituency_name_translation_table = array('."\n");
foreach ($constituency_map as $full_name => $code)
{
    $name_parts = explode(' ', $full_name, 2);
    fwrite($php_output_handle, '    \''.addslashes(strtolower($name_parts[0])).'\' => \''.$code.'\','."\n");    
    fwrite($php_output_handle, '    \''.addslashes(strtolower($name_parts[1])).'\' => \''.$code.'\','."\n");    
}
fwrite($php_output_handle, ');'."\n\n");

foreach ($district_map as $full_name => $code)
{
    $name_parts = explode(' ', $full_name, 2);
    fwrite($php_output_handle, ucwords(strtolower($name_parts[1])).','.rand(0,100)."\n");
}

fwrite($php_output_handle, "\n\n");

foreach ($constituency_map as $full_name => $code)
{
    $name_parts = explode(' ', $full_name, 2);
    fwrite($php_output_handle, $name_parts[1].','.rand(0,100)."\n");
}


fclose($php_output_handle);

?>