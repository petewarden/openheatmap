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
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the output OSM XML data to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
);	

$options = cliargs_get_options($cliargs);

$input_files = $options['unnamed'];
$output_file = $options['outputfile'];

if (empty($input_files))
{
    print "You need to supply at least one file to merge\n";
    cliargs_print_usage_and_exit($cliargs);
}

$output_osm_ways = null;
foreach ($input_files as $input_file)
{
error_log("Looking at $input_file");
    $input_osm_ways = new OSMWays();
    $input_contents = file_get_contents($input_file) or die("Couldn't read file '$input_file'");
    $input_osm_ways->deserialize_from_xml($input_contents);

    if (!isset($output_osm_ways))
        $output_osm_ways = new OSMWays();
    
    $output_osm_ways->copy_all_ways($input_osm_ways);
}
    
$output_contents = $output_osm_ways->serialize_to_xml();
file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");

?>