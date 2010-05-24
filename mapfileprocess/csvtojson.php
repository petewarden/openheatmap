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

function process_csv_file($file_name, $output_file_name, $array_name, $seperator=',')
{
    $file_handle = fopen($file_name, "r") or die("Couldn't open $file_name\n");
    $output_file_handle = fopen($output_file_name, "w") or die("Couldn't open $output_file_name\n");

    fwrite($output_file_handle, "var $array_name = [\n");

    $column_names = array();

    $line_index = 0;
    while(!feof($file_handle))
    {
        $current_parts = fgetcsv($file_handle, 0, $seperator);
        
        $line_index += 1;
        if ($line_index<2)
        {
            $column_names = $current_parts;
            continue;
        }
        
        $row = array();

        $column_index = 0;
        foreach ($column_names as $column_name)
        {
            if (isset($current_parts[$column_index]))
                $row[$column_name] = $current_parts[$column_index];
            else
                $row[$column_name] = null;

            $column_index += 1;
        }

        if ($line_index>2)
            fwrite($output_file_handle, ",\n");
        fwrite($output_file_handle, json_encode($row));
    }

    fwrite($output_file_handle, "];\n");
    
    fclose($file_handle);
    fclose($output_file_handle);
}


$cliargs = array(
	'inputfile' => array(
		'short' => 'i',
		'type' => 'optional',
		'description' => 'The input CSV file',
        'default' => 'php://stdin',
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the converted JSON data to',
        'default' => 'php://stdout',
	),
	'arrayname' => array(
		'short' => 'a',
		'type' => 'required',
		'description' => 'The name of the variable to assign the data array to',
	),
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_file = $options['outputfile'];
$array_name = $options['arrayname'];

process_csv_file($input_file, $output_file, $array_name, ',');

?>