#!/usr/bin/php
<?php

/*
OpenGraphMap processing
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

define('SERIES_CODE_STRING', 'Series Code');
define('SERIES_NAME_STRING', 'Series Name');
define('COUNTRY_CODE_STRING', 'Country Code');
define('COUNTRY_NAME_STRING', 'Country Name');

function process_csv_file($file_name, $output_folder, $seperator=',')
{
    $file_handle = fopen($file_name, "r") or die("Couldn't open $file_name\n");

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

        handle_row($row, $output_folder);
    }
    
    fclose($file_handle);
}

function handle_row($row, $output_folder)
{
    if (empty($row[SERIES_NAME_STRING])||empty($row[COUNTRY_CODE_STRING]))
        return;
        
    $series_name = $row[SERIES_NAME_STRING];
    $country_code = $row[COUNTRY_CODE_STRING];
    
    $series_name = str_replace('/', '\\', $series_name);
    
    $file_name = $output_folder.$series_name;
    $file_already_exists = file_exists($file_name);
    
    $file_handle = fopen($file_name, 'a') or die("Couldn't open $file_name for appending");
    
    if (!$file_already_exists)
        fputcsv($file_handle, array('country_code', 'time', 'value'));
        
    foreach ($row as $column_name => $column_value)
    {
        if (is_numeric($column_name))
        {
            $year = $column_name;
            $value = $column_value;
            
            if ($value==='')
                continue;
            
            fputcsv($file_handle, array($country_code, $year, $value));
        }
    
    }
    
    fclose($file_handle);
}

$cliargs = array(
	'inputfile' => array(
		'short' => 'i',
		'type' => 'optional',
		'description' => 'The World Bank data file holding all the series in CSV format',
        'default' => 'php://stdin',
	),
	'outputfolder' => array(
		'short' => 'o',
		'type' => 'required',
		'description' => 'The directory to write out all the individual data files to',
	),
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_folder = $options['outputfolder'];

process_csv_file($input_file, $output_folder, ',');

?>