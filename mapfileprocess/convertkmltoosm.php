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

function convert_kml_to_osm($input_data, &$osm_ways)
{
    $state = array(
        'ancestors' => array(),
    );
    
    convert_kml_children_to_osm($input_data, $osm_ways, $state);
}

function convert_kml_children_to_osm($input_data, &$osm_ways, &$state)
{
    foreach ($input_data->children() as $child)
    {
        $name = strtolower($child->getName());
        $value = trim((string)($child));

        if ($name=='coordinates')
        {
            $name_stack = array();
            $is_tag_ancestor = array();
            
            foreach ($state['ancestors'] as $ancestor)
            {
                $ancestor_name = strtolower($ancestor->getName());
                $ancestor_value = (string)($ancestor);

                $is_tag_ancestor[$ancestor_name] = true;

                foreach ($ancestor->children() as $ancestor_child)
                {
                    $ancestor_child_name = strtolower($ancestor_child->getName());
                    $ancestor_child_value = (string)($ancestor_child);
                
                    if ($ancestor_child_name=='name')
                    {
                        $name_stack[] = $ancestor_child_value;
                    }
                }
            }
            
            if (empty($name_stack)||
                !isset($is_tag_ancestor['polygon'])||
                !isset($is_tag_ancestor['linearring']))
            {
                error_log("Missing ancestors: ".print_r($name_stack, true)." : ".print_r($is_tag_ancestor, true));
                continue;
            }
                
            $coordinates_list = explode(' ', $value);
            
            $osm_ways->begin_way();
            
            foreach ($coordinates_list as $coordinates_string)
            {
                $coordinates = explode(',', trim($coordinates_string));
                if (count($coordinates)<2)
                    continue;
                    
                $longitude = $coordinates[0];
                $latitude = $coordinates[1];
                
                $osm_ways->add_vertex($latitude, $longitude);
            }
            
            $name_index = 0;
            foreach ($name_stack as $name_value)
            {
                $osm_ways->add_tag('name_'.$name_index, $name_value);
                $name_index += 1;
            }
            
            $osm_ways->end_way();
        }
        else
        {
            $state['ancestors'][] = $child;
            convert_kml_children_to_osm($child, $osm_ways, $state);
            array_pop($state['ancestors']);
        }
    }

}

$cliargs = array(
	'input' => array(
		'short' => 'i',
		'type' => 'optional',
		'description' => 'The input KML file to read from - if unset will read from stdin',
        'default' => 'php://stdout',
	),
	'output' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the output OSM XML data to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
);	

ini_set('memory_limit', '-1');

$options = cliargs_get_options($cliargs);

$input_name = $options['input'];
$output_name = $options['output'];

$input_contents = file_get_contents($input_name) or die("Couldn't open '$input_name' for reading");

$input_data = simplexml_load_string($input_contents);

$osm_ways = new OSMWays();

convert_kml_to_osm($input_data, $osm_ways);

$osm_xml = $osm_ways->serialize_to_xml();

$output_file_handle = fopen($output_name, 'w') or die("Couldn't open $output_file for writing");
fwrite($output_file_handle, $osm_xml);
fclose($output_file_handle);

?>