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

function get_node_distance($a, $b)
{
    $a_lat = $a['lat'];
    $a_lon = $a['lon'];
    
    $b_lat = $b['lat'];
    $b_lon = $b['lon'];
    
    $delta_lat = ($a_lat-$b_lat);
    $delta_lon = ($a_lon-$b_lon);
    
    $distance_squared = ($delta_lat*$delta_lat)+($delta_lon*$delta_lon);
    
    $distance = sqrt($distance_squared);
    
    return $distance;
}

function close_ways($input_osm_ways, $verbose, $tolerance, $debug)
{
    if ($verbose)
        error_log("Starting close_ways()");

    $bucket_debug = ($debug&&false);
        
    $bucket_grid = new BucketGrid($tolerance*2);
    
    $input_ways = &$input_osm_ways->ways;
    $input_nodes = &$input_osm_ways->nodes;
    
    $way_index = 0;
    foreach ($input_ways as $way_id => &$way)
    {
        $way_index += 1;
        if ($verbose&&(($way_index%100)===0))
            error_log("Bucketed $way_index/".count($input_ways));

        if ($way['is_closed']===true)
            continue;
            
        $nds = $way['nds'];
        $nds_count = count($nds);
        if ($nds_count<2)
            continue;
        
        $start_index = 0;
        $end_index = ($nds_count-1);
        
        $start_nd_ref = $nds[$start_index];
        $end_nd_ref = $nds[$end_index];
        
        $start_node = $input_nodes[$start_nd_ref];
        $end_node = $input_nodes[$end_nd_ref];
    
        $start_lat = $start_node['lat'];
        $start_lon = $start_node['lon'];
        
        $end_lat = $end_node['lat'];
        $end_lon = $end_node['lon'];
    
        $start_data = array(
            'way_id' => $way_id,
            'nd_ref' => $start_nd_ref,
            'index' => $start_index,
            'lat' => $start_lat,
            'lon' => $start_lon,
        );

        $end_data = array(
            'way_id' => $way_id,
            'nd_ref' => $end_nd_ref,
            'index' => $end_index,
            'lat' => $end_lat,
            'lon' => $end_lon,
        );
        
        if ($bucket_debug)
            error_log("insert_point($start_lon, $start_lat, ".print_r($start_data, true).")");
                
        $bucket_grid->insert_point($start_lon, $start_lat, $start_data, $bucket_debug);

        if ($bucket_debug)
            error_log("insert_point($end_lon, $end_lat, ".print_r($end_data, true).")");

        $bucket_grid->insert_point($end_lon, $end_lat, $end_data, $bucket_debug);
    }
    
    if ($bucket_debug)
        error_log("bucket_grid: ".print_r($bucket_grid->buckets, true));
    
    $result = new OSMWays();

    $way_index = 0;
    foreach ($input_ways as $way_id => &$way)
    {
        $way_index += 1;
        if ($verbose&&(($way_index%100)===0))
            error_log("Closing $way_index/".count($input_ways));

        if ($way['is_closed']===true)
        {
            if ($debug)
                error_log("Found pre-closed way");

            $result->copy_way($way, $input_osm_ways);
            continue;
        }

        if (!empty($way['is_used']))
        {
            if ($debug)
                error_log("Found already used way");
            continue;
        }

        if (count($way['nds'])<2)
        {
            if ($debug)
                error_log("Too few nodes found for $way_id");
            continue;
        }
        
        $start_index = 0;
                
        $start_way_id = $way_id;
        $start_nd_ref = $way['nds'][$start_index];
        $start_node = $input_nodes[$start_nd_ref];
        
        $follow_way_id = $way_id;
        $nds = $way['nds'];
        
        if ($debug)
            error_log("Looking at $follow_way_id");        
        
        $output_nds = array();
        $loop_count = 0;
        while (true)
        {
            $input_ways[$follow_way_id]['is_used'] = true;
        
            $nds_count = count($nds);
            $end_index = ($nds_count-1);
            
            $output_nds = array_merge($output_nds, $nds);
            
            $end_nd_ref = $nds[$end_index];        
            $end_node = $input_nodes[$end_nd_ref];
            
            if ($debug)
                error_log("End node is $end_nd_ref at (".$end_node['lat'].",".$end_node['lon'].")");
        
            $distance_to_start = get_node_distance($end_node, $start_node);
            
            // Have we looped back around to the start?
            if ($distance_to_start<$tolerance)
            {
                if ($debug)
                    error_log("Closed way starting with $start_way_id, ending with $follow_way_id");

                $output_nds[] = $start_nd_ref;
                
                $result->begin_way();
                foreach ($output_nds as $nd_ref)
                {
                    $node = $input_nodes[$nd_ref];
                    $result->add_vertex($node['lat'], $node['lon'], $bucket_debug);
                }
                $result->end_way();

                break;
            }
            
            // Figure out which lines we can connect to the end of this one
            $end_lat = $end_node['lat'];
            $end_lon = $end_node['lon'];
                        
            $nearby_points = $bucket_grid->find_points_near($end_lon, $end_lat, $tolerance, $bucket_debug);

            if ($bucket_debug)
                error_log("find_points_near($end_lon, $end_lat, $tolerance) returned ".print_r($nearby_points, true));
            
            $closest_distance = null;
            foreach ($nearby_points as $bucket_entry)
            {
                $point_data = $bucket_entry['data'];
                $point_lat = $point_data['lat'];
                $point_lon = $point_data['lon'];

                $point_way_id = $point_data['way_id'];
                $point_nd_ref = $point_data['nd_ref'];
                if (($point_nd_ref===$end_nd_ref)&&($point_way_id===$follow_way_id))
                {
                    if ($bucket_debug)
                        error_log("$point_nd_ref was the same as the start");
                    continue;
                }
                
                $distance = get_node_distance($point_data, $end_node);

                if ($bucket_debug)
                    error_log("$point_nd_ref was $distance away");

                if (($closest_distance===null)||($distance<$closest_distance))
                {
                    $closest_distance = $distance;
                }
            }
            
            // Have we reached the end of the line?
            if (!isset($closest_distance))
            {
                if ($debug)
                    error_log("No close points found for $follow_way_id");
                break;
            }
        
            $found_nodes = array();        
            foreach ($nearby_points as $bucket_entry)
            {
                $point_data = $bucket_entry['data'];
                $point_lat = $point_data['lat'];
                $point_lon = $point_data['lon'];

                $point_way_id = $point_data['way_id'];
                $point_nd_ref = $point_data['nd_ref'];
                if (($point_nd_ref===$end_nd_ref)&&($point_way_id===$follow_way_id))
                    continue;
                
                $distance = get_node_distance($point_data, $end_node);
                if ($distance<($closest_distance+EPSILON))
                {
                    $found_nodes[] = $point_data;
                }
            }
        
            // Figure out which way edge to follow
            $found_nodes_count = count($found_nodes);
            if ($found_nodes_count===1)
            {
                if ($debug)
                    error_log("A single close point found for $follow_way_id");
                $follow_node_index = 0;
            }
            else
            {
                if ($debug)
                    error_log("Too many close points found for $follow_way_id");
                break;
            }
            
            $follow_node = $found_nodes[$follow_node_index];

            $follow_way_id = $follow_node['way_id'];
            $follow_way = $input_ways[$follow_way_id];
            $do_reverse = ($follow_node['index']>0);
        
            $nds = $follow_way['nds'];
            if ($do_reverse)
                $nds = array_reverse($nds);
            
            $loop_count += 1;
            if ($loop_count>1000)
            {
                die("Looped too many times\n");
            }
                
        }
        
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
        'default' => 0.001,
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

$output_osm_ways = close_ways($input_osm_ways, $verbose, $tolerance, $debug);

if ($verbose)
    error_log("Starting save of '$output_file'");
    
$output_contents = $output_osm_ways->serialize_to_xml();
file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");

?>