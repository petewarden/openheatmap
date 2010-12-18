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
);	

$options = cliargs_get_options($cliargs);

$osm_input = $options['osminput'];
$php_output = $options['phpoutput'];

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($osm_input) or die("Couldn't read file '$osm_input'");
$input_osm_ways->deserialize_from_xml($input_contents);

$code_map = array();

$code_key = null;

foreach ($input_osm_ways->ways as &$way)
{
    $tags = &$way['tags'];
    
    if (empty($tags['name']))
        continue;

    $name = $tags['name'];    
    $name = strtolower($name);

    if (!isset($code_key))
    {
        foreach ($tags as $key => $value)
        {
            if (preg_match('/_code$/', $key))
                $code_key = $key;
        }
        if (!isset($code_key))
            die("Code key not found in ".print_r($tags, true)."\n");
            
        print "Found $code_key\n";
    }

    if (!isset($tags[$code_key]))
    {
        error_log("Couldn't find code key in ".print_r($tags, true));
        continue;
    }
    
    $code = $tags[$code_key];
    
    if (!isset($code_map[$code]))
    {
        $code_map[$code] = $name;
    }
    else
    {
        if ($code_map[$code]!==$name)
            error_log("Different name found for same code '$code': '$name' vs".$code_map[$code]);
    }
}

$php_output_handle = fopen($php_output, "w") or die("Couldn't open $php_output\n");

fwrite($php_output_handle, '$'.$code_key.'_accepted_values = array('."\n");
foreach ($code_map as $code => $name)
    fwrite($php_output_handle, '    "'.$code.'",'."\n");
fwrite($php_output_handle, ');'."\n\n");

$code_key_prefix = str_replace('_code', '', $code_key);

fwrite($php_output_handle, '$'.$code_key_prefix.'_name_translation_table = array('."\n");
foreach ($code_map as $code => $name)
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
        'north central',
        'east central',
        'south central',
        'west central',
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
        else if (preg_match('/ '.$direction.'$/', $name))
        {
            $place_name = trim(str_replace($direction, '', $name));
            $reversed_name = $direction.' '.$place_name;
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