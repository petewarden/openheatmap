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

function does_match($key, $value, $rule_list)
{
    foreach ($rule_list as $match_key => $match_value)
    {
        if (preg_match('/'.$match_key.'/i', $key)&&
            preg_match('/'.$match_value.'/i', $value))
            return true;
    }
    
    return false;
}

function remap_single_tag($key, $value, $rules)
{
    $result = array($key => $value);
    if (isset($rules['to_delete'])&&
        does_match($key, $value, $rules['to_delete']))
    {
        unset($result[$key]);
    }

    if (isset($rules['to_rename']))
    {
        foreach ($rules['to_rename'] as $match_key => $new_key)
        {
            if (preg_match('/'.$match_key.'/i', $key))
            {
                unset($result[$key]);
                $result[$new_key] = $value;
            }
        }
    }
    
    if (isset($rules['to_add']))
    {
        foreach ($rules['to_add'] as $match_key => $remap_table)
        {
            if (preg_match('/'.$match_key.'/i', $key))
            {
                $new_key = $remap_table['new_key'];
                $mapping = $remap_table['mapping'];
                foreach ($mapping as $match_value => $new_value)
                {
                    if (preg_match('/'.$match_value.'/i', $value))
                    {   
                        $result[$new_key] = $new_value;
                    }
                }
            }
        
        }
    }
    
    if (isset($rules['to_ucwords']))
    {
        foreach ($rules['to_ucwords'] as $match_key)
        {
            if (preg_match('/'.$match_key.'/i', $key))
            {
                $new_value = ucwords(strtolower($value));
                $new_value = str_replace(' And ', ' and ', $new_value);
                $result[$key] = $new_value;
            }
        }
    }
    
    return $result;
}

function remap_way_tags($tags,$rules)
{
    $new_tags = array();
    foreach ($tags as $key=>$value)
    {
        $output_tags = remap_single_tag($key, $value, $rules);
        
        foreach ($output_tags as $output_key => $output_value)
            $new_tags[$output_key] = $output_value;
    }
    
    return $new_tags;
}

function remap_all_tags(&$input_osm_ways, $rules)
{
    $input_nodes = $input_osm_ways->nodes;
    $input_ways = $input_osm_ways->ways;

    $result = new OSMWays();

    $count = 0;
    foreach ($input_ways as $input_way)
    {
        $tags = $input_way['tags'];
        
        $new_tags = remap_way_tags($tags, $rules);
        if (!isset($new_tags))
            continue;

        $result->begin_way();
     
        foreach ($input_way['nds'] as $nd_ref)
        {
            if (!isset($input_nodes[$nd_ref]))
                continue;
                
            $node = $input_nodes[$nd_ref];
            $result->add_vertex($node['lat'], $node['lon']);
        }

        foreach ($new_tags as $key => $value)
        {
            $result->add_tag($key, $value);
        }
        
        $result->end_way();    
    }

    return $result;
}

$cliargs = array(
	'inputfile' => array(
		'short' => 'i',
		'type' => 'optional',
		'description' => 'The file to read the input OSM XML data from - if unset, will read from stdin',
        'default' => 'php://stdout',
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the output OSM XML data to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
	'rules' => array(
		'short' => 'r',
		'type' => 'required',
		'description' => 'The JSON description of the transformations to apply to the tags',
	),
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_file = $options['outputfile'];
$rules_file = $options['rules'];

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($input_file) or die("Couldn't read file '$input_file'");
$input_osm_ways->deserialize_from_xml($input_contents);

$rules_content = file_get_contents($rules_file) or die("Couldn't read file '$rules_file'");
$rules = json_decode($rules_content, true) or die("Couldn't parse rules file containing '$rules_content'");

$output_osm_ways = remap_all_tags($input_osm_ways, $rules);
    
$output_contents = $output_osm_ways->serialize_to_xml();
file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");

?>