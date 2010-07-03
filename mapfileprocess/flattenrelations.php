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

function flatten_relations(&$osm_ways, $verbose)
{
    if ($verbose)
        error_log("Starting flatten_relationships()");

    foreach ($osm_ways->relations as $relation_id => $relation)
    {
        foreach ($relation['members'] as $member)
        {
            $type = $member['type'];
            
            if ($type!=='way')
                continue;
            
            $ref = $member['ref'];
            $way = &$osm_ways->ways[$ref];
            foreach ($relation['tags'] as $key => $value)
            {
                $way['tags'][$key] = $value;
            }
        }
    }
    
    if ($verbose)
        error_log("Finished flatten_relationships()");
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
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_file = $options['outputfile'];
$verbose = $options['verbose'];

if ($verbose)
    error_log("Starting load of '$input_file'");

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($input_file) or die("Couldn't read file '$input_file'");
$input_osm_ways->deserialize_from_xml($input_contents);

flatten_relations($input_osm_ways, $verbose);

if ($verbose)
    error_log("Starting save of '$output_file'");
    
$output_contents = $input_osm_ways->serialize_to_xml();
file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");

?>