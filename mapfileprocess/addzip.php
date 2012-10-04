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

  $zip_code_state_ranges = array(
  'AL' => array(array(35000,36999)),
  'AK' => array(array(99500,99999)),
  'AS' => array(array(96700,96799)),
  'AZ' => array(array(85000,86599)),
  'AR' => array(array(71600,72999),array(75500,75599)),
  'CA' => array(array(90000,96699)),
  'CO' => array(array(80000,81699)),
  'CT' => array(array(6000,6999)),
  'DE' => array(array(19700,19999)),
  'DC' => array(array(20000,20599)),
  'MF' => array(array(96900,96999)),
  'FL' => array(array(32000,34999)),
  'GA' => array(array(30000,31999),array(39800,39999)),
  'GU' => array(array(96900,96999)),
  'HI' => array(array(96700,96899)),
  'ID' => array(array(83200,83899)),
  'IL' => array(array(60000,62999)),
  'IN' => array(array(46000,47999)),
  'IA' => array(array(50000,52899)),
  'KS' => array(array(66000,67999)),
  'KY' => array(array(40000,42799)),
  'LA' => array(array(70000,71499)),
  'ME' => array(array(3900,4999)),
  'MH' => array(array(96900,96999)),
  'MD' => array(array(20600,21999)),
  'MA' => array(array(1000,2799),array(5500,5599)),
  'MI' => array(array(48000,49999)),
  'MN' => array(array(55000,56799)),
  'MS' => array(array(38600,39799)),
  'MO' => array(array(63000,65899)),
  'MT' => array(array(59000,59999)),
  'NE' => array(array(68000,69399)),
  'NV' => array(array(88900,89899)),
  'NH' => array(array(3000,3999)),
  'NJ' => array(array(7000,8999)),
  'NM' => array(array(87000,88499)),
  'NY' => array(array(500,599),array(6300,6399),array(9000,14999)),
  'NC' => array(array(26900,28999)),
  'ND' => array(array(58000,58899)),
  'MP' => array(array(96900,96999)),
  'OH' => array(array(43000,45999)),
  'OK' => array(array(73000,74999)),
  'OR' => array(array(97000,97999)),
  'PW' => array(array(96900,96999)),
  'PA' => array(array(15000,19699)),
  'PR' => array(array(600,999)),
  'RI' => array(array(2800,2999)),
  'SC' => array(array(29000,29999)),
  'SD' => array(array(57000,57799)),
  'TN' => array(array(37000,38599)),
  'TX' => array(array(75000,79999),array(88500,88599)),
  'UT' => array(array(84000,84799)),
  'VT' => array(array(5000,5999)),
  'VA' => array(array(20100,20199),array(22000,24699)),
  'VI' => array(array(800,899)),
  'WA' => array(array(98000,99499)),
  'WI' => array(array(53000,54999)),
  'WV' => array(array(24700,26899)),
  'WY' => array(array(82000,83199)),
);


function process_csv_file($file_name, $output_file_name, $zip_column)
{
  global $zip_code_state_ranges;
  $file_handle = fopen($file_name, "r") or die("Couldn't open $file_name\n");
  $output_file_handle = fopen($output_file_name, "w") or die("Couldn't open $output_file_name\n");
  
  $line_index = 0;
  while(!feof($file_handle)) {
    $current_parts = fgetcsv($file_handle, 0);
    
    $naked_zip = $current_parts[$zip_column];
    
    $full_zip = '';
    foreach ($zip_code_state_ranges as $state => $ranges) {
      foreach ($ranges as $range) {
        $start = $range[0];
        $end = $range[1];
        if (($naked_zip >= $start) && ($naked_zip <= $end)) {
          $full_zip = $state." ".$naked_zip;
          break;
        }
      }
    }
    
    $current_parts[$zip_column] = $full_zip;
    
    fputcsv($output_file_handle, $current_parts);
  }
  
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
  'zip_column' => array(
    'short' => 'z',
    'type' => 'optional',
    'description' => 'The name of the variable to assign the data array to',
    'default' => '1',
  ),
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_file = $options['outputfile'];
$zip_column = $options['zip_column'];

process_csv_file($input_file, $output_file, $zip_column);

?>