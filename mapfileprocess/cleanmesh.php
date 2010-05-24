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
require_once('bucketgrid.php');

define('EPSILON', 0.00001);

function merge_nodes_on_edges($input_osm_ways, $verbose, $tolerance, $debug)
{
    if ($verbose)
        error_log("Starting merge_nodes_on_edges()");

    $bucket_debug = ($debug);
    
    $input_ways = &$input_osm_ways->ways;
    $input_nodes = &$input_osm_ways->nodes;

    $result = new OSMWays();
    
    $way_index = 0;
    foreach ($input_ways as $way_id => $way)
    {
        $way_index += 1;
//        if ($verbose&&(($way_index%100)===0))
            error_log("Merged $way_index/".count($input_ways));
            
        $nds = $way['nds'];
        $nds_count = count($nds);
        if ($nds_count<2)
        {
            $result->copy_way($way, $input_osm_ways);
            if ($debug)
                error_log("Skipping $way_id");
            continue;
        }
        
        $result->begin_way($way_id);
     
        foreach ($way['tags'] as $key => $value)
        {
            $result->add_tag($key, $value);
        }

        $nds_map = array_count_values($nds);

        $node_index = 0;
        foreach ($nds as $nd_ref)
        {
            $is_last = ($node_index==($nds_count-1));
            $node_index += 1;
            
            if (!isset($input_nodes[$nd_ref]))
            {
                if ($debug)
                    error_log("Missing node $nd_ref in $way_id");
                continue;
            }
                
            $node = $input_nodes[$nd_ref];
            
            $start_x = $node['lat'];
            $start_y = $node['lon'];
            
            if ($debug)
                error_log("Adding original $nd_ref ($start_x, $start_y)");
            
            $result->add_vertex($start_x, $start_y);
            
            if ($is_last)
                continue;
                
            $end_nd_ref = $nds[$node_index];
            $end_node = $input_nodes[$end_nd_ref];
            
            $end_x = $end_node['lat'];
            $end_y = $end_node['lon'];
            
            $coincident_points = $input_osm_ways->bucket_grid->find_points_near_line(
                $start_x, $start_y,
                $end_x, $end_y,
                $tolerance,
                $bucket_debug);

            $sortfunction = create_function('$a, $b', 'if ($a["output_s"]>$b["output_s"]) return 1; else return -1;'); 
            usort($coincident_points, $sortfunction);
            
            foreach ($coincident_points as $point)
            {
                $s = $point['output_s'];
                if (($s<0.0)||($s>1.0))
                    continue;
                    
                $point_nd_ref = $point['data']['id'];
                if (isset($nds_map[$point_nd_ref]))
                    continue;
                
                if ($debug)
                    error_log("Adding $point_nd_ref");
                
                $point_x = $point['x'];
                $point_y = $point['y'];
                
                $result->add_vertex($point_x, $point_y);
            }
            
        }
        
        $result->end_way();        
    }

    if ($verbose)
        error_log("Finished close_ways()");

    return $result;
}

ini_set('memory_limit', '-1');

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
    'verbose' => array(
        'short' => 'v',
        'type' => 'switch',
        'description' => 'Whether to show extra debugging information about the processing as it happens',
    ),
    'debug' => array(
        'short' => 'd',
        'type' => 'switch',
        'description' => 'Whether to show even more debugging information about the processing as it happens',
    ),
    'tolerance' => array(
        'short' => 't',
        'type' => 'optional',
        'description' => 'How much of a gap to allow between two nodes before they\'re considered to be separate',
        'default' => 0.00001,
    ),
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_file = $options['outputfile'];
$verbose = $options['verbose'];
$debug = $options['debug'];
$tolerance = $options['tolerance'];

if ($verbose)
    error_log("Starting load of '$input_file'");

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($input_file) or die("Couldn't read file '$input_file'");
$input_osm_ways->deserialize_from_xml($input_contents);


$output_osm_ways = merge_nodes_on_edges($input_osm_ways, $verbose, $tolerance, $debug);

if ($verbose)
    error_log("Starting save of '$output_file'");
    
$output_contents = $output_osm_ways->serialize_to_xml();
file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");

?>