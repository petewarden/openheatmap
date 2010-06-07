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

function get_clipping_planes_for_box($min_lat, $min_lon, $max_lat, $max_lon)
{
    $result = array();
    
    $result[] = array(
        'v_lat' => 1.0,
        'v_lon' => 0.0,
        'c' => $min_lat
    );
    
    $result[] = array(
        'v_lat' => 0.0,
        'v_lon' => 1.0,
        'c' => $min_lon
    );

    $result[] = array(
        'v_lat' => -1.0,
        'v_lon' => 0.0,
        'c' => -$max_lat
    );
    
    $result[] = array(
        'v_lat' => 0.0,
        'v_lon' => -1.0,
        'c' => -$max_lon
    );

    return $result;
}

function distance_from_plane($vertex, $plane)
{
    $dot_product = 
        ($vertex['lat']*$plane['v_lat'])+
        ($vertex['lon']*$plane['v_lon']);
    
    $distance = ($dot_product-$plane['c']);
    
    return $distance;
}

function clip_vertices_against_plane($vertices, $plane, $verbose = false)
{
    if ($verbose)
        error_log("Testing plane ".print_r($plane, true));

    if (empty($vertices))
    {
        if ($verbose)
            error_log("Empty vertex list, returning early");
        return array();
    }
        
    $v_lat = $plane['v_lat'];
    $v_lon = $plane['v_lon'];
    $c = $plane['c'];
    
    $result = array();
    
    $vertex_count = count($vertices);
    
    for ($vertex_index=0; $vertex_index<$vertex_count; $vertex_index+=1)
    {
        $next_index = (($vertex_index+1)%$vertex_count);
        
        $current_vertex = $vertices[$vertex_index];
        $next_vertex = $vertices[$next_index];
        
        $current_distance = distance_from_plane($current_vertex, $plane);
        $next_distance = distance_from_plane($next_vertex, $plane);
    
        $is_current_above = ($current_distance>=0);
        $is_next_above = ($next_distance>=0);
    
        if ($is_current_above&&$is_next_above)
        {
            // All outside of the clipping plane, so just add the vertex
            if ($verbose)
                error_log("${current_vertex['lat']},${current_vertex['lon']} to ${next_vertex['lat']},${next_vertex['lon']}: both inside");

            $result[] = $current_vertex;            
        }
        else if ((!$is_current_above) && (!$is_next_above))
        {
            // Both inside the clipping plane, so throw the vertex away
            if ($verbose)
                error_log("${current_vertex['lat']},${current_vertex['lon']} to ${next_vertex['lat']},${next_vertex['lon']}: both outside");
        }
        else if (($is_current_above) && (!$is_next_above))
        {
            // The line is leaving the allowed area, so add the original
            // vertex and an extra one where it intersects
            if ($verbose)
                error_log("${current_vertex['lat']},${current_vertex['lon']} to ${next_vertex['lat']},${next_vertex['lon']}: starts inside, goes outside");
            
            $result[] = $current_vertex;

            $total_distance = ($current_distance+(-$next_distance));
            $similar_ratio = ($current_distance/$total_distance);
            
            $current_lat = $current_vertex['lat'];
            $current_lon = $current_vertex['lon'];
            
            $next_lat = $next_vertex['lat'];
            $next_lon = $next_vertex['lon'];
            
            $lat_delta = ($next_lat-$current_lat);
            $lon_delta = ($next_lon-$current_lon);
            
            $new_vertex = array(
                'lat' => ($current_lat+($similar_ratio*$lat_delta)),
                'lon' => ($current_lon+($similar_ratio*$lon_delta)),
            );
            $result[] = $new_vertex;
        }
        else if ((!$is_current_above) && ($is_next_above))
        {
            // The line is entering the allowed area, so add an extra vertex
            // where it enters
            if ($verbose)
                error_log("${current_vertex['lat']},${current_vertex['lon']} to ${next_vertex['lat']},${next_vertex['lon']}: starts outside, goes inside");

            $total_distance = ((-$current_distance)+$next_distance);
            $similar_ratio = ((-$current_distance)/$total_distance);
            
            $current_lat = $current_vertex['lat'];
            $current_lon = $current_vertex['lon'];
            
            $next_lat = $next_vertex['lat'];
            $next_lon = $next_vertex['lon'];
            
            $lat_delta = ($next_lat-$current_lat);
            $lon_delta = ($next_lon-$current_lon);
            
            $new_vertex = array(
                'lat' => ($current_lat+($similar_ratio*$lat_delta)),
                'lon' => ($current_lon+($similar_ratio*$lon_delta)),
            );
            $result[] = $new_vertex;
        }
    }
    
    return $result;
}

function crop_ways_to_box(&$input_osm_ways, $min_lat, $min_lon, $max_lat, $max_lon, $verbose)
{
    if ($verbose)
        error_log("Starting way cropping");

    $clipping_planes = get_clipping_planes_for_box($min_lat, $min_lon, $max_lat, $max_lon);

    $input_nodes = $input_osm_ways->nodes;
    $input_ways = $input_osm_ways->ways;

    $result = new OSMWays();

    $count = 0;
    foreach ($input_ways as $input_way)
    {
        $count +=1;
        
        if ($verbose&&(($count%1000)===0))
            error_log("Processing $count/".count($input_ways));

        $nds = $input_way['nds'];
        if (empty($nds))
            continue;

        $output_vertices = array();
        foreach ($nds as $nd_ref)
        {
            $current_vertex = $input_nodes[$nd_ref];
            $output_vertices[] = $current_vertex;
        }
        
        foreach ($clipping_planes as $plane)
        {
            $output_vertices = clip_vertices_against_plane($output_vertices, $plane, $verbose);
        }
        
        if (empty($output_vertices))
            continue;

        $result->begin_way();
     
        foreach ($input_way['tags'] as $key => $value)
            $result->add_tag($key, $value);

        foreach ($output_vertices as $vertex)
            $result->add_vertex($vertex['lat'], $vertex['lon']);
        
        $result->end_way();
    }

    if ($verbose)
        error_log("Finished way cropping");

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
	'top_left' => array(
		'short' => 't',
		'type' => 'required',
		'description' => 'The top-left corner of the box to crop the ways to, as a latitude,longitude pair, eg 40,20',
	),
	'bottom_right' => array(
		'short' => 'b',
		'type' => 'required',
		'description' => 'The bottom-right corner of the box to crop the ways to, as a latitude,longitude pair, eg 40,20',
	),
	'verbose' => array(
		'short' => 'v',
		'type' => 'switch',
		'description' => 'Enables debugging output about the job\'s progress',
	),
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_file = $options['outputfile'];
$top_left = $options['top_left'];
$bottom_right = $options['bottom_right'];
$verbose = $options['verbose'];

$top_left_parts = explode(',', $top_left);
if (count($top_left_parts)!==2)
{
    print("Bad top_left parameter '$top_left'");
    cliargs_print_usage_and_exit($cliargs);
}

$bottom_right_parts = explode(',', $bottom_right);
if (count($bottom_right_parts)!==2)
{
    print("Bad bottom_right parameter '$bottom_right'");
    cliargs_print_usage_and_exit($cliargs);
}

$min_lat = min($top_left_parts[0], $bottom_right_parts[0]);
$max_lat = max($top_left_parts[0], $bottom_right_parts[0]);

$min_lon = min($top_left_parts[1], $bottom_right_parts[1]);
$max_lon = max($top_left_parts[1], $bottom_right_parts[1]);

if ($verbose)
    error_log("Starting load of '$input_file'");

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($input_file) or die("Couldn't read file '$input_file'");
$input_osm_ways->deserialize_from_xml($input_contents);

$output_osm_ways = crop_ways_to_box($input_osm_ways, $min_lat, $min_lon, $max_lat, $max_lon, $verbose);

if ($verbose)
    error_log("Starting save of '$output_file'");
    
$output_contents = $output_osm_ways->serialize_to_xml();
file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");

?>