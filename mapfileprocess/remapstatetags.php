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

function remap_way_tags($tags, &$state_name_map)
{
    $new_tags = array();

    $to_delete = array(
        'OBJECTID' => true,
        'VertexCou' => true,
        'NL_NAME_1' => true,
        'HASC_1' => true,
        'TYPE_1' => true,
        'ENGTYPE_1' => true,
        'VALIDFR_1' => true,
        'VALIDTO_1' => true,
        'REMARKS_1' => true,
        'Region' => true,
        'RegionVar' => true,
        'ProvNumber' => true,
        'NEV_Countr' => true,
        'FIRST_FIPS' => true,
        'FIRST_HASC' => true,
        'gadm_level' => true,
        'CheckMe' => true,
        'Region_Cod' => true,
        'Region_C_1' => true,
        'ScaleRank' => true,
        'Region_C_2' => true,
        'Region_C_3' => true,
        'Country_Pr' => true,
        'Shape_Leng' => true,
        'Shape_Area' => true,
    );
    
    $to_rename = array(
        'ISO' => 'country_code',
        'NAME_0' => 'country_name',
        'NAME_1' => 'name',
        'VARNAME_1' => 'name_variants',
        'FIPS_1' => 'province_code',
    );

    foreach ($tags as $key=>$value)
    {
        if (($key=='FIPS_1')&&(empty($value)))
        {
            $state_name = $tags['NAME_1'];
            
            if (!isset($state_name_map[$state_name]))
            {
                $state_name_map[$state_name] = $tags['ISO'].count($state_name_map);
                error_log("No FIPS_1 for $state_name, setting to ".$state_name_map[$state_name]);
            }
                
            $value = $state_name_map[$state_name];
        }
        
        if (isset($to_rename[$key]))
        {
            $new_key = $to_rename[$key];
            $output_tags = array($new_key => $value);
        }
        else if (isset($to_delete[$key]))
        {
            $output_tags = array();
        }
        else
        {
            die("Unknown tag '$key' encountered");
        }
        
        foreach ($output_tags as $output_key => $output_value)
            $new_tags[$output_key] = $output_value;
    }
    
    return $new_tags;
}

function remap_all_tags(&$input_osm_ways)
{
    $input_nodes = $input_osm_ways->nodes;
    $input_ways = $input_osm_ways->ways;

    $result = new OSMWays();

    $state_name_map = array();

    foreach ($input_ways as $input_way)
    {
        $tags = $input_way['tags'];
        
        $new_tags = remap_way_tags($tags, $state_name_map);
        if (!isset($new_tags))
            continue;

        $result->begin_way();
     
        foreach ($input_way['nds'] as $nd_ref)
        {
            if (!isset($input_nodes[$nd_ref]))
                continue;
                
            $node = $input_nodes[$nd_ref];
            $result->add_vertex($node['lat'], $node['lon']);
        }

        foreach ($new_tags as $key => $value)
        {
            $result->add_tag($key, $value);
        }
        
        $result->end_way();    
    }

    return $result;
}

$cliargs = array(
	'inputfolder' => array(
		'short' => 'i',
		'type' => 'required',
		'description' => 'The folder containing the input state maps',
	),
	'outputfolder' => array(
		'short' => 'o',
		'type' => 'required',
		'description' => 'The folder to write the remapped files to',
	),
);	

$options = cliargs_get_options($cliargs);

$input_folder = $options['inputfolder'];
$output_folder = $options['outputfolder'];

foreach (glob($input_folder.'/*.osm') as $input_file)
{
    error_log("Processing '$input_file'");
    
    $input_osm_ways = new OSMWays();
    $input_contents = file_get_contents($input_file) or die("Couldn't read file '$input_file'");
    $input_osm_ways->deserialize_from_xml($input_contents);

    $output_osm_ways = remap_all_tags($input_osm_ways);

    $output_file = str_replace($input_folder, $output_folder, $input_file);
    $output_file = str_replace('_state.osm', '_provinces.osm', $output_file);
    $output_contents = $output_osm_ways->serialize_to_xml();
    file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");
}

?>