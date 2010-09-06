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

$state_translation_table = array(
    '02' => 'AK',
    '01' => 'AL',
    '05' => 'AR',
    '04' => 'AZ',
    '06' => 'CA',
    '08' => 'CO',
    '09' => 'CT',
    '10' => 'DE',
    '11' => 'DC',
    '12' => 'FL',
    '13' => 'GA',
    '15' => 'HI',
    '16' => 'ID',
    '17' => 'IL',
    '18' => 'IN',
    '19' => 'IA',
    '20' => 'KS',
    '21' => 'KY',
    '22' => 'LA',
    '23' => 'ME',
    '24' => 'MD',
    '25' => 'MA',
    '26' => 'MI',
    '27' => 'MN',
    '28' => 'MS',
    '29' => 'MO',
    '30' => 'MT',
    '31' => 'NE',
    '32' => 'NV',
    '33' => 'NH',
    '34' => 'NJ',
    '35' => 'NM',
    '36' => 'NY',
    '37' => 'NC',
    '38' => 'ND',
    '39' => 'OH',
    '40' => 'OK',
    '41' => 'OR',
    '42' => 'PA',
    '44' => 'RI',
    '45' => 'SC',
    '46' => 'SD',
    '47' => 'TN',
    '48' => 'TX',
    '49' => 'UT',
    '50' => 'VT',
    '51' => 'VA',
    '53' => 'WA',
    '54' => 'WV',
    '55' => 'WI',
    '56' => 'WY',
);

ini_set('memory_limit', '-1');

$cliargs = array(
	'input' => array(
		'short' => 'i',
		'type' => 'required',
		'description' => 'The file to read the OSM ways from',
	),
	'output' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the translation table to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
    'acceptedvalues' => array(
        'short' => 'a',
        'type' => 'switch',
        'description' => 'Whether to output the data for the accepted values table',
    ),
    'namesonly' => array(
        'short' => 'n',
        'type' => 'switch',
        'description' => 'Whether to output the data as a list of city names only',
    ),
);	

$options = cliargs_get_options($cliargs);

$input = $options['input'];
$output = $options['output'];
$accepted_values = $options['acceptedvalues'];
$names_only = $options['namesonly'];

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($input) or die("Couldn't read file '$input'");
$input_osm_ways->deserialize_from_xml($input_contents);

$output_file_handle = fopen($output, "w") or die("Couldn't open $output\n");

foreach ($input_osm_ways->ways as $way)
{
    $tags = $way['tags'];
    
    if (empty($tags['state_code'])||
        empty($tags['city_code'])||
        empty($tags['name']))
        continue;
        
    $state_code = $tags['state_code'];
    $city_code = $tags['city_code'];
    $name = $tags['name'];
    
    if (!isset($state_translation_table[strtolower($state_code)]))
        continue;
        
    $state_name = $state_translation_table[strtolower($state_code)];
    
    $full_name = $name.', '.$state_name;
    $full_name = strtolower($full_name);
    $full_name = addslashes($full_name);
    
    $name = strtolower($name);
    $name = addslashes($name);

    if ($accepted_values)
        fwrite($output_file_handle, "    '$city_code',\n");
    else if ($names_only)
        fwrite($output_file_handle, "    '$name' => true,\n");
    else
        fwrite($output_file_handle, "    '$full_name' => array('$state_code', '$city_code'),\n");
}

fclose($output_file_handle);

?>