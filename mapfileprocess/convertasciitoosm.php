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

function parse_ascii_description_file($file_name, $type)
{
    if ($type=='zip')
    {
        $field_names = array(
            'state_code',
            'county_code',
            'name',
            'admin_level',
            'admin_level_name',
            '',
        );
    }
    else if ($type=='county')
    {
        $field_names = array(
            'zip_code',
            'other_code',
            'tla',
            'desc',
            '',
        );            
    }
    else if ($type=='congress')
    {
        $field_names = array(
            'state_code',
            'district_code',
            'district_code_no_padding',
            '_unknown_',
            'desc',
            '',
        );
    }
    else
    {
        die("Unknown boundary type '$type'\n");
    }

    $field_length = count($field_names);

    $result = array();

    $file_handle = fopen($file_name, "r") or die("Couldn't open $file_name\n");

    $line_index = 0;
    while(!feof($file_handle))
    {
        $current_line = fgets($file_handle);
        $current_line = trim($current_line, " \t\"\n");

        if ($line_index===0)
        {
            $current_id = $current_line;
            $result[$current_id] = array();
        }
        else
        {            
            $key = $field_names[$line_index-1];
            if (!empty($key))
                $result[$current_id][$key] = $current_line;
        }
        
        $line_index += 1;
        if ($line_index>$field_length)
            $line_index = 0;
    }
    
    fclose($file_handle);
    
    return $result;
}

function parse_ascii_vertex_file($file_name, $description_data, &$result)
{
    $file_handle = fopen($file_name, "r") or die("Couldn't open $file_name\n");

    $on_first_line = true;
    $last_source_id = null;
    while(!feof($file_handle))
    {
        $current_line = fgets($file_handle);
        $current_line = trim($current_line, " \t\n\"");
        
        $current_line = preg_replace('/ +/', ' ', $current_line);
        $current_data = split(' ', $current_line);
        
        if ($on_first_line)
        {
            if ($current_line==='END')
                break;

            $source_id = $current_data[0];
            
            if ($source_id==='-99999')
                $source_id = $last_source_id;
            
            $last_source_id = $source_id;
            
            $output_id = $result->begin_way();
        
            if (!isset($description_data[$source_id]))
            {  
                error_log("'$source_id' was not found in $file_name");
                error_log("Description data: ".print_r($description_data, true));
                die();
            }
            $current_description = $description_data[$source_id];
            foreach ($current_description as $key => $value)
            {
                $result->add_tag($key, $value);
            }
        
            $on_first_line = false;
        }
        else
        {
            if ($current_line==='END')
            {
                $result->end_way();
                $on_first_line = true;
                continue;
            }
            
            $lat = (float)($current_data[1]);
            $lon = (float)($current_data[0]);
            
            $result->add_vertex($lat, $lon);
        }
    }
    
    return $result;
}

$cliargs = array(
	'inputdirectory' => array(
		'short' => 'i',
		'type' => 'required',
		'description' => 'The folder containing the ASCII files',
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the output OSM XML data to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
    'type' => array(
        'short' => 't',
        'type' => 'optional',
        'description' => 'Whether the input file contains ZIP code (zip), county (county) or congressional district (congress) boundaries',
        'default' => 'county',
    ),
);	

ini_set('memory_limit', '-1');

$options = cliargs_get_options($cliargs);

$input_directory = $options['inputdirectory'];
$output_file = $options['outputfile'];
$type = $options['type'];

$input_path = $input_directory.'/*a.dat';

error_log("Looking for '$input_path'");

$osm_ways = new OSMWays();

foreach (glob($input_path) as $description_file)
{
    $vertex_file = str_replace('a.dat', '.dat', $description_file);

    $description_data = parse_ascii_description_file($description_file, $type);

    parse_ascii_vertex_file($vertex_file, $description_data, $osm_ways);
}

$osm_xml = $osm_ways->serialize_to_xml();

$output_file_handle = fopen($output_file, 'w') or die("Couldn't open $output_file for writing");

fwrite($output_file_handle, $osm_xml);

fclose($output_file_handle);

?>