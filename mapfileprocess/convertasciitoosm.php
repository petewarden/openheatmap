#!/usr/bin/php
<?php

require_once('cliargs.php');
require_once('osmways.php');

function parse_ascii_description_file($file_name)
{
    $result = array();

    $file_handle = fopen($file_name, "r") or die("Couldn't open $file_name\n");

    $line_index = 0;
    while(!feof($file_handle))
    {
        $current_line = fgets($file_handle);
        $current_line = trim($current_line, " \t\"\n");

        if ($line_index===0)
        {
            $current_id = $current_line;
            $result[$current_id] = array();
        }
        else
        {
            $field_names = array(
                'state_code',
                'county_code',
                'name',
                'admin_level',
                'admin_level_name',
                '',
            );
            
            $key = $field_names[$line_index-1];
            if (!empty($key))
                $result[$current_id][$key] = $current_line;
        }
        
        $line_index += 1;
        if ($line_index>6)
            $line_index = 0;
    }
    
    fclose($file_handle);
    
    return $result;
}

function parse_ascii_vertex_file($file_name, $description_data, &$result)
{
    $file_handle = fopen($file_name, "r") or die("Couldn't open $file_name\n");

    $on_first_line = true;
    $last_source_id = null;
    while(!feof($file_handle))
    {
        $current_line = fgets($file_handle);
        $current_line = trim($current_line, " \t\n\"");
        
        $current_line = preg_replace('/ +/', ' ', $current_line);
        $current_data = split(' ', $current_line);
        
        if ($on_first_line)
        {
            if ($current_line==='END')
                break;

            $source_id = $current_data[0];
            
            if ($source_id==='-99999')
                $source_id = $last_source_id;
            
            $last_source_id = $source_id;
            
            $output_id = $result->begin_way();
        
            $current_description = $description_data[$source_id];
            foreach ($current_description as $key => $value)
            {
                $result->add_tag($key, $value);
            }
        
            $on_first_line = false;
        }
        else
        {
            if ($current_line==='END')
            {
                $result->end_way();
                $on_first_line = true;
                continue;
            }
            
            $lat = (float)($current_data[1]);
            $lon = (float)($current_data[0]);
            
            $result->add_vertex($lat, $lon);
        }
    }
    
    return $result;
}

$cliargs = array(
	'inputdirectory' => array(
		'short' => 'i',
		'type' => 'required',
		'description' => 'The folder containing the ASCII files',
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the output OSM XML data to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
);	

$options = cliargs_get_options($cliargs);

$input_directory = $options['inputdirectory'];
$output_file = $options['outputfile'];

$input_path = $input_directory.'/*a.dat';

$osm_ways = new OSMWays();

foreach (glob($input_path) as $description_file)
{
    $vertex_file = str_replace('a.dat', '.dat', $description_file);

    $description_data = parse_ascii_description_file($description_file);

    parse_ascii_vertex_file($vertex_file, $description_data, $osm_ways);
}

$osm_xml = $osm_ways->serialize_to_xml();

$output_file_handle = fopen($output_file, 'w') or die("Couldn't open $output_file for writing");

fwrite($output_file_handle, $osm_xml);

fclose($output_file_handle);

?>