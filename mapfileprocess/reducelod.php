#!/usr/bin/php
<?php

require_once('cliargs.php');
require_once('osmways.php');

ini_set('memory_limit', '-1');

function has_hit_target($vertex_count, $vertex_target, $total_area_error, $area_target)
{
    if ($vertex_count>0)
        $hit_target = ($vertex_count<=$vertex_target);
    else
        $hit_target = ($total_area_error>=$area_target);
        
    return $hit_target;
}

function calculate_node_usage(&$nodes, &$ways)
{
    foreach ($nodes as $node_id => &$node_data)
    {
        $node_data['used_by'] = array();
    }

    foreach ($ways as $way_id => &$way_data)
    {
        $way_data['original_nds_count'] = count($way_data['nds']);
    
        foreach ($way_data['nds'] as $nd_index => $nd_ref)
        {
            $nodes[$nd_ref]['used_by'][] = array(
                'way_id' =>$way_id,
                'nd_index' => $nd_index,
            );

            $nodes[$nd_ref]['area_error'] = 0;
            
//            if ($nd_ref==1768)
//            {
//                error_log(print_r($node_data, true));
//            }
            
        }
        
//        if ($way_id==1746)
//        {
//            error_log('Found '.print_r($way_data, true));
//        }
    }

}

function sort_nodes_by_area(&$nodes, &$ways)
{
    foreach ($nodes as $node_id => &$node_data)
    {
        $used_by = $node_data['used_by'];
        if (count($used_by)>2)
        {
            $area = 999999999;
        }
        else
        {
            $way_id = $used_by[0]['way_id'];
            $nd_index = $used_by[0]['nd_index'];
            
            $way = $ways[$way_id];
            $nds = $way['nds'];
            
            $nds_count = $way['original_nds_count'];
            
            $previous_index = ($nd_index-1);
            while (true)
            {
                $previous_index = (($previous_index+$nds_count)%$nds_count);
                if (isset($nds[$previous_index]))
                    break;
                $previous_index -= 1;
                if ($previous_index===($nd_index-1))
                    break;
            }

            $next_index = ($nd_index+1);
            while (true)
            {
                $next_index = (($next_index+$nds_count)%$nds_count);
                if (isset($nds[$next_index]))
                    break;
                $next_index += 1;
                if ($next_index===($nd_index+1))
                    break;
            }

            if (!isset($nds[$previous_index])||
                !isset($nds[$next_index]))
            {
                $area = 0;
            }
            else
            {
                $previous_ref = $nds[$previous_index];
                $next_ref = $nds[$next_index];
            
                if (!isset($nodes[$previous_ref]))
                {
                    error_log('Bad node '.$previous_ref.' found in '.$way_id);
                    die();
                }
            
                $previous_data = $nodes[$previous_ref];
                $next_data = $nodes[$next_ref];
                
                $current_lat = $node_data['lat'];
                $current_lon = $node_data['lon'];
                
                $previous_lat = $previous_data['lat'];
                $previous_lon = $previous_data['lon'];
                
                $next_lat = $next_data['lat'];
                $next_lon = $next_data['lon'];
                
                $previous_lat_delta = ($current_lat-$previous_lat);
                $previous_lon_delta = ($current_lon-$previous_lon);
                
                $next_lat_delta = ($next_lat-$current_lat);
                $next_lon_delta = ($next_lon-$current_lon);

                $cross = 
                    ($next_lon_delta*$previous_lat_delta)-
                    ($next_lat_delta*$previous_lon_delta);
                
                $area = abs($cross);
            }

        }
        
        $area_error = $node_data['area_error'];
        
        $node_data['area'] = ($area+$area_error);
    }
    
    $sortfunction = create_function('$a, $b', 'if ($a["area"]>$b["area"]) return 1; else return -1;'); 
    uasort($nodes, $sortfunction);
}

function reduce_lod(&$osm_ways, $vertex_target, $area_target)
{
    $nodes = &$osm_ways->nodes;
    $ways = &$osm_ways->ways;

    calculate_node_usage($nodes, $ways);
    
    $vertex_count = count($nodes);
    $total_area_error = 0;
    
    while (true)
    {
        $hit_target = has_hit_target($vertex_count, $vertex_target, $total_area_error, $area_target);
        
        error_log('vertex_count: '.$vertex_count);
                    
        if ($hit_target)
            break;

        sort_nodes_by_area($nodes, $ways);
        
        $nodes_removed = 0;
        
        foreach ($nodes as $node_id => &$node_data)
        {
//            error_log('Foo: '.print_r($nodes['1768'], true));

            $area = $node_data['area'];
            
//            error_log('Removing '.$node_id);
            
            $vertex_count -= 1;
            $total_area_error += $area;
                    
            $used_by = $node_data['used_by'];

            if ($node_id==1768)
            {
                error_log(print_r($used_by, true));
            }
            
            if ($vertex_target>0)
                $nodes_per_pass = max(100, ceil(($vertex_count-$vertex_target)/10));
            else
                $nodes_per_pass = 100;

            foreach ($used_by as $used_by_index => $used_by_entry)
            {
                $way_id = $used_by_entry['way_id'];
                $nd_index = $used_by_entry['nd_index'];
                
                $way = &$ways[$way_id];
                $nds = &$way['nds'];
                                                                        
                if ($used_by_index===0)
                {                                
//                    $way_string = '';
//                    foreach ($nds as $i => $r)
//                        $way_string .= $i.':'.$r.',';
//                    error_log("Vertices: ".$way_string);

                    $nds_count = $way['original_nds_count'];

                    $previous_index = ($nd_index-1);
                    while (true)
                    {
                        $previous_index = (($previous_index+$nds_count)%$nds_count);
                        if (isset($nds[$previous_index]))
                            break;
                        $previous_index -= 1;
                        if ($previous_index===($nd_index-1))
                            break;
                    }

                    $next_index = ($nd_index+1);
                    while (true)
                    {
                        $next_index = (($next_index+$nds_count)%$nds_count);
                        if (isset($nds[$next_index]))
                            break;
                        $next_index += 1;
                        if ($next_index===($nd_index+1))
                            break;
                    }
                    
                    if (isset($nds[$previous_index])&&
                        isset($nds[$next_index]))
                    {
                        $previous_ref = $nds[$previous_index];
                        $next_ref = $nds[$next_index];
                    
                        $nodes[$previous_ref]['area_error'] += ($area/4);
                        $nodes[$next_ref]['area_error'] += ($area/4);
                    }
                }

                unset($nds[$nd_index]);
            }
                
            unset($nodes[$node_id]);
            $nodes_removed += 1;

            $hit_target = has_hit_target($vertex_count, $vertex_target, $total_area_error, $area_target);
                
            if ($hit_target || ($nodes_removed>=$nodes_per_pass))
                break;
        }
    }
}

$cliargs = array(
	'vertextarget' => array(
		'short' => 'v',
		'type' => 'optional',
		'description' => 'If set, the target number of vertices to reduce the map to',
        'default' => 0,
	),
	'areatarget' => array(
		'short' => 'a',
		'type' => 'optional',
		'description' => 'If set, the maximum acceptable error in degrees-squared for a way before reduction is stopped',
        'default' => 0,
	),
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
);	

$options = cliargs_get_options($cliargs);

$vertex_target = $options['vertextarget'];
$area_target = $options['areatarget'];
$input_file = $options['inputfile'];
$output_file = $options['outputfile'];

if (($vertex_target===0)&&($area_target===0))
{
    print 'You must specify either -v/--vertextarget or -a/--areatarget'."\n";
    cliargs_print_usage_and_exit($cliargs);
}

$osm_ways = new OSMWays();
$input_contents = file_get_contents($input_file) or die("Couldn't read file '$input_file'");
$osm_ways->deserialize_from_xml($input_contents);

reduce_lod($osm_ways, $vertex_target, $area_target);

$output_contents = $osm_ways->serialize_to_xml();
file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");

?>