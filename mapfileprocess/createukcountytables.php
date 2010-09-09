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
);	

$options = cliargs_get_options($cliargs);

$input = $options['input'];
$output = $options['output'];

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($input) or die("Couldn't read file '$input'");
$input_osm_ways->deserialize_from_xml($input_contents);

$name_map = array();
$file_name_map = array();
$uk_district_code_map = array();

foreach ($input_osm_ways->ways as $way)
{
    $tags = $way['tags'];
    
    if (empty($tags['FILE_NAME'])||
        empty($tags['name'])||
        empty($tags['uk_district_code']))
        continue;

    $file_name = $tags['FILE_NAME'];
    $name = $tags['name'];
    $uk_district_code = $tags['uk_district_code'];
    
    $file_name = strtolower($file_name);
    $file_name = str_replace('_', ' ', $file_name);
    $name = strtolower($name);

    if (!isset($name_map[$name]))
        $name_map[$name] = array();
        
    $name_map[$name][$uk_district_code] = true;

    if (!isset($file_name_map[$file_name]))
        $file_name_map[$file_name] = array();
        
    $file_name_map[$file_name][$uk_district_code] = true;
    
    $uk_district_code_map[$uk_district_code] = true;
}

$output_file_handle = fopen($output, "w") or die("Couldn't open $output\n");

fwrite($output_file_handle, '$uk_district_code_accepted_values = array('."\n");
foreach ($uk_district_code_map as $uk_district_code => $value)
    fwrite($output_file_handle, '    "'.$uk_district_code.'" => true,'."\n");
fwrite($output_file_handle, ');'."\n\n");

fwrite($output_file_handle, '$uk_district_name_translation_table = array('."\n");
foreach ($name_map as $name => $district_codes)
{
    $name_variations = array();

    $split_names = explode(' - ', $name);
    foreach ($split_names as $name)
    {
        $name = trim(str_replace('(b)', '', $name));
        $name_variations[$name] = true;

        $name = trim(str_replace('london boro', 'borough', $name));
        $name_variations[$name] = true;
        
        $name = trim(str_replace(' borough', '', $name));
        $name_variations[$name] = true;

        $name = trim(str_replace('borough of', '', $name));
        $name_variations[$name] = true;

        $name = trim(str_replace('district', '', $name));
        $name_variations[$name] = true;

        $name = trim(str_replace('the city of', '', $name));
        $name_variations[$name] = true;

        $name = trim(str_replace('county of', '', $name));
        $name_variations[$name] = true;

        $name = trim(str_replace('city of', '', $name));
        $name_variations[$name] = true;

        $name = trim(str_replace(' city', '', $name));
        $name_variations[$name] = true;

        $name = trim(str_replace('st. ', 'saint ', $name));
        $name_variations[$name] = true;

        $name = trim(str_replace('saint ', 'st ', $name));
        $name_variations[$name] = true;

        $name = trim(str_replace('\'', '', $name));
        $name_variations[$name] = true;

        $name = trim(str_replace('-', ' ', $name));
        $name_variations[$name] = true;

        $name = trim(str_replace(' and ', ' & ', $name));
        $name_variations[$name] = true;
    }

    foreach ($name_variations as $name_variation => $value)
        fwrite($output_file_handle, '    \''.addslashes($name_variation).'\' => array(\''.implode('\', \'', array_keys($district_codes)).'\'),'."\n");    
}
fwrite($output_file_handle, ');'."\n\n");

$uk_county_name_translation_table = array();
foreach ($file_name_map as $file_name => $district_codes)
{
    $file_name_variations = array();

    $split_file_names = explode(' - ', $file_name);
    foreach ($split_file_names as $file_name)
    {
        $file_name = trim(str_replace('(b)', '', $file_name));
        $file_name_variations[$file_name] = true;

        $file_name = trim(str_replace('county of', '', $file_name));
        $file_name_variations[$file_name] = true;

        $file_name = trim(str_replace('county', '', $file_name));
        $file_name_variations[$file_name] = true;

        $file_name = trim(str_replace('district', '', $file_name));
        $file_name_variations[$file_name] = true;

        $file_name = trim(str_replace('the city and county of', '', $file_name));
        $file_name_variations[$file_name] = true;

        $file_name = trim(str_replace('the city of', '', $file_name));
        $file_name_variations[$file_name] = true;

        $file_name = trim(str_replace('city of', '', $file_name));
        $file_name_variations[$file_name] = true;

        $file_name = trim(str_replace('greater london authority', 'london', $file_name));
        $file_name_variations[$file_name] = true;
    }
    
    foreach ($file_name_variations as $file_name_variation => $value)
        $uk_county_name_translation_table[$file_name_variation] = array_keys($district_codes);    
}

$county_table = array(
    'bedfordshire' => array('luton', 'bedford', 'central bedfordshire'),
    'berkshire' => array('west berkshire', 'reading', 'wokingham', 'bracknell forest', 'windsor and maidenhead', 'slough'),
    'buckinghamshire' => array('milton keynes'),
    'cambridgeshire' => array('peterborough'),
    'cheshire' => array('cheshire west and chester', 'cheshire east', 'warrington', 'halton'),
    'cornwall' => array('isles of scilly'),
    'derbyshire' => array('derby'),
    'devon' => array('torbay', 'plymouth'),
    'dorset' => array('poole', 'bournemouth'),
    'durham' => array('darlington', 'stockton-on-tees', 'hartlepool'),
    'east riding of yorkshire' => array('kingston upon hull'),
    'east sussex' => array('brighton and hove'),
    'essex' => array('thurrock', 'southend-on-sea'),
    'gloucestershire' => array('south gloucestershire'),
    'greater manchester' => array('bolton', 'bury', 'manchester', 'oldham', 'rochdale', 'salford', 'stockport', 'tameside', 'trafford', 'wigan'),
    'hampshire' => array('southampton', 'portsmouth'),
    'kent' => array('medway'),
    'lancashire' => array('blackpool', 'blackburn with darwen'),
    'leicestershire' => array('leicester'),
    'lincolnshire' => array('north lincolnshire', 'north east lincolnshire'),
    'merseyside' => array('knowsley', 'liverpool', 'st helens', 'sefton', 'wirral'),
    'north yorkshire' => array('redcar and cleveland', 'middlesbrough', 'york'),
    'nottinghamshire' => array ('nottingham'),
    'shropshire' => array('telford and wrekin'),
    'somerset' => array('bath and north east somerset', 'north somerset'),
    'south yorkshire' => array('sheffield', 'barnsley', 'doncaster', 'rotherham'),
    'staffordshire' => array('stoke-on-trent'),
    'tyne and wear' => array('newcastle upon tyne', 'gateshead', 'north tyneside', 'south tyneside', 'sunderland'),
    'west midlands' => array('birmingham', 'coventry', 'dudley', 'sandwell', 'solihull', 'walsall', 'wolverhampton'),
    'west yorkshire' => array('wakefield', 'kirklees', 'calderdale', 'bradford', 'leeds'),
    'wiltshire' => array('swindon'),
);

foreach ($county_table as $county_name => $components)
{
    if (!isset($uk_county_name_translation_table[$county_name]))
        $uk_county_name_translation_table[$county_name] = array();

    foreach ($components as $component)
    {
        if (!isset($uk_county_name_translation_table[$component]))
            die("Couldn't find $component\n");
        
        $codes = $uk_county_name_translation_table[$component];
        
        foreach ($codes as $code)
        {
            if (!in_array($code, $uk_county_name_translation_table[$county_name]))
                $uk_county_name_translation_table[$county_name][] = $code;
        }
        
    }

}

fwrite($output_file_handle, '$uk_county_name_translation_table = array('."\n");
foreach ($uk_county_name_translation_table as $file_name_variation => $values)
    fwrite($output_file_handle, '    \''.addslashes($file_name_variation).'\' => array(\''.implode('\', \'', $values).'\'),'."\n");    

fwrite($output_file_handle, ');'."\n\n");

fclose($output_file_handle);

?>