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

ini_set('memory_limit', '-1');

function process_twitter_file($file_name, $output_file_name, $place_cache)
{
    $file_handle = fopen($file_name, "r") or die("Couldn't open $file_name\n");
    $output_file_handle = fopen($output_file_name, "w") or die("Couldn't open $output_file_name\n");

    $previous_key = '';
    
    $overall_total = 0;
    
    while(!feof($file_handle))
    {
        $current_line = trim(fgets($file_handle));
        
        if (empty($current_line))
            continue;
        
        $current_parts = explode("\t", $current_line, 2);

        if (count($current_parts)!==2)
            continue;
        
        $current_key = trim($current_parts[0]);
        $current_count = trim($current_parts[1]);

        if ($current_key!==$previous_key)
        {
            if (!empty($previous_key))
            {
                fwrite($output_file_handle, $previous_key.",".$current_total."\n");
            }
            $current_total = 0;
        }

        $previous_key = $current_key;
        $current_total += $current_count;
        
        $overall_total += $current_count;
    }

    if (!empty($previous_key))
    {
        fwrite($output_file_handle, $previous_key.",".$current_total."\n");
    }

    error_log("Overall total: $overall_total");

    fclose($file_handle);
    fclose($output_file_handle);
}


$cliargs = array(
	'inputfile' => array(
		'short' => 'i',
		'type' => 'optional',
		'description' => 'The input file containing the raw twitter data',
        'default' => 'php://stdin',
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the location data to',
        'default' => 'php://stdout',
	),
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_file = $options['outputfile'];

$place_cache_string = file_get_contents('placecache.json');
$place_cache = json_decode($place_cache_string, true) or error_log("Couldn't decode placecache");

process_twitter_file($input_file, $output_file, $place_cache);

?>