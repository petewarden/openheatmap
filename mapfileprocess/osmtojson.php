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
	'inputfile' => array(
		'short' => 'i',
		'type' => 'optional',
		'description' => '',
    'default' => 'php://stdin'
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => '',
    'default' => 'php://stdout'
	),
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_file = $options['outputfile'];

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($input_file) or die("Couldn't read file '$input_file'");
$input_osm_ways->deserialize_from_xml($input_contents);

$output = array();

$input_nodes = $input_osm_ways->nodes;
$input_ways = $input_osm_ways->ways;

foreach ($input_ways as $way)
{

  $name_variants = $way['tags']['name_variants'];
  $state_parts = explode('|', $name_variants);
  $state_code = $state_parts[0];
  if (!isset($output[$state_code])) {
    $output[$state_code] = array();
  }
  
  $polygon = array();
  foreach ($way['nds'] as $nd_ref)
  {
    if (!isset($input_nodes[$nd_ref]))
      continue;

    $node = $input_nodes[$nd_ref];
    $polygon[] = array($node['lat'], $node['lon']);
  }
  
  $output[$state_code][] = $polygon;
}
    
$output_contents = json_encode($output);
file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");

?>