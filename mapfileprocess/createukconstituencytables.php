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
	'csvinput' => array(
		'short' => 'c',
		'type' => 'required',
		'description' => 'The file to read the constituency code data from',
	),
	'phpoutput' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the translation table to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
	'osmoutput' => array(
		'short' => 's',
		'type' => 'required',
		'description' => 'The file to write the updated OSM data to',
	),
);	

$options = cliargs_get_options($cliargs);

$osm_input = $options['osminput'];
$csv_input = $options['csvinput'];
$php_output = $options['phpoutput'];
$osm_output = $options['osmoutput'];

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($osm_input) or die("Couldn't read file '$osm_input'");
$input_osm_ways->deserialize_from_xml($input_contents);

$csv_input_handle = fopen($csv_input, 'rb') or die("Couldn't read file '$csv_input'");
$code_map = array();
$line_index = 0;
while(!feof($csv_input_handle))
{
    $current_parts = fgetcsv($csv_input_handle);
    
    if (empty($current_parts))
        continue;
    
    $line_index += 1;
    if ($line_index<2)
        continue;
        
    $code = $current_parts[0];
    $name = $current_parts[1];
    $name = trim(strtolower($name));

    $code_map[$name] = $code;
}
fclose($csv_input_handle);

$name_map = array();

foreach ($input_osm_ways->ways as &$way)
{
    $tags = &$way['tags'];
    
    if (empty($tags['name']))
        continue;

    $name = $tags['name'];    
    $name = strtolower($name);

    if (!isset($code_map[$name]))
        error_log("Couldn't find $name");

    $code = $code_map[$name];
    $tags['uk_constituency_code'] = $code;
}

$output_contents = $input_osm_ways->serialize_to_xml();
file_put_contents($osm_output, $output_contents) or die("Couldn't write file '$osm_output'");

$php_output_handle = fopen($php_output, "w") or die("Couldn't open $php_output\n");

fwrite($php_output_handle, '$uk_constituency_code_accepted_values = array('."\n");
foreach ($code_map as $name => $code)
    fwrite($php_output_handle, '    "'.$code.'" => true,'."\n");
fwrite($php_output_handle, ');'."\n\n");

fwrite($php_output_handle, '$uk_constituency_name_translation_table = array('."\n");
foreach ($code_map as $name => $code)
{
    $name_variations = array($name => true);

    $name = trim(str_replace(' city', '', $name));
    $name_variations[$name] = true;

    $name = trim(str_replace('st. ', 'saint ', $name));
    $name_variations[$name] = true;

    $name = trim(str_replace('saint ', 'st ', $name));
    $name_variations[$name] = true;

    $name = trim(str_replace('\'', '', $name));
    $name_variations[$name] = true;

    $name = trim(str_replace('-', ' ', $name));
    $name_variations[$name] = true;

    $name = trim(str_replace(',', '', $name));
    $name_variations[$name] = true;

    $directions = array(
        'north east',
        'south east',
        'south west',
        'north west',
        'north',
        'east',
        'south',
        'west',
    );

    foreach ($directions as $direction)
    {
        if (preg_match('/^'.$direction.' /', $name))
        {
            $place_name = trim(str_replace($direction, '', $name));
            $reversed_name = $place_name.' '.$direction;
            $name_variations[$reversed_name] = true;
            break;
        }
    }

    $name = trim(str_replace(' and ', ' & ', $name));
    $name_variations[$name] = true;

    foreach ($name_variations as $name_variation => $value)
        fwrite($php_output_handle, '    \''.addslashes($name_variation).'\' => \''.$code.'\','."\n");    
}
fwrite($php_output_handle, ');'."\n\n");

fclose($php_output_handle);

?>