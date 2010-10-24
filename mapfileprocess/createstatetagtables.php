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

function generate_name_variants($name, $country_name)
{
    $result = array(
        $name,
        $name.', '.$country_name,
        $name.' '.$country_name,
    );
    
    return $result;
}

// http://ie2.php.net/manual/en/function.strtr.php#98669
function clean_name($name)
{
    $normalizeChars = array(
        'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
        'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
        'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
        'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
        'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
        'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
        'ú'=>'u', 'û'=>'u', 'ü' => 'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
    );
   
    return strtr($name, $normalizeChars);
}

//$new_name = clean_name("Finström");
//die($new_name."\n");

ini_set('memory_limit', '-1');

$cliargs = array(
	'inputfolder' => array(
		'short' => 'i',
		'type' => 'required',
		'description' => 'The folder containing the input state maps',
	),
	'phpoutput' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the translation table to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
);	

$options = cliargs_get_options($cliargs);

$input_folder = $options['inputfolder'];
$php_output = $options['phpoutput'];

$name_to_state_code = array();
$state_code_info = array();

$country_bounding_boxes = array();

foreach (glob($input_folder.'/*.osm') as $input_file)
{
    error_log("Looking at '$input_file'");
    
    $input_osm_ways = new OSMWays();
    $input_contents = file_get_contents($input_file) or die("Couldn't read file '$osm_input'");
    $input_osm_ways->deserialize_from_xml($input_contents);

    $code_map = array();

    $code_key = null;

    foreach ($input_osm_ways->ways as &$way)
    {
        $tags = &$way['tags'];
        
        if (empty($tags['name']))
            continue;

        $names = explode('|', $tags['name_variants']);
        $main_name = $tags['name'];//iconv("utf-8", "us-ascii//TRANSLIT", $tags['name']);
        $main_name = clean_name($main_name);

        if (preg_match('@[^a-zA-Z.\-\/ \',()]@', $main_name))
        {
//            error_log("Foreign characters in main name: ".$main_name);
            $main_name = preg_replace('@[^a-zA-Z.\-\/ \',()]@', '', $main_name);
//            error_log("Replaced with: ".$main_name);
        }
        $names[] = $main_name;
        
        $country_name = $tags['country_name'];
        $country_code = $tags['country_code'];

        $state_code = $tags['state_code'];
        
        $state_code_info[$state_code] = array('country_code' => $country_code);
        
        foreach ($names as $name)
        {
            if (empty($name))
                continue;
            
            if (preg_match('@[^a-zA-Z.\-\/ \']@', $name))
            {
//                error_log("Foreign characters: $name");
                continue;
            }
            
            $name_variants = generate_name_variants($name, $country_name);
            
            foreach ($name_variants as $name_variant)
            {
                $name_variant = strtolower(trim($name_variant));
                if (!isset($name_to_state_code[$name_variant]))
                    $name_to_state_code[$name_variant] = array();
                                
                $name_to_state_code[$name_variant][$state_code] = true;
            }
        
        }
    }
    
    $country_bounding_boxes[$country_code] = $input_osm_ways->calculate_bounding_box();
}

$php_output_handle = fopen($php_output, "w") or die("Couldn't open $php_output\n");

fwrite($php_output_handle, '$state_code_accepted_values = array('."\n");
foreach ($state_code_info as $code => $info)
    fwrite($php_output_handle, '    "'.$code.'",'."\n");
fwrite($php_output_handle, ');'."\n\n");

fwrite($php_output_handle, '$state_code_to_country = array('."\n");
foreach ($state_code_info as $code => $info)
    fwrite($php_output_handle, '    "'.$code.'" => "'.$info['country_code'].'",'."\n");
fwrite($php_output_handle, ');'."\n\n");

fwrite($php_output_handle, '$state_name_to_codes_translation_table = array('."\n");
foreach ($name_to_state_code as $name => $codes)
    fwrite($php_output_handle, '    "'.$name.'",'."\n");
fwrite($php_output_handle, ');'."\n\n");

fwrite($php_output_handle, '$state_name_to_codes_translation_table = array('."\n");
foreach ($name_to_state_code as $name => $codes)
{
    fwrite($php_output_handle, '    \''.addslashes($name).'\' => array(\''.implode("', '", array_keys($codes)).'\'),'."\n");    
}
fwrite($php_output_handle, ');'."\n\n");

fwrite($php_output_handle, '$state_map_bounds = array('."\n");
foreach ($country_bounding_boxes as $country_code => $box)
{
    fwrite($php_output_handle, '    \''.$country_code.'\' => array(\''.implode("', '", array_values($box)).'\'),'."\n");    
}
fwrite($php_output_handle, ');'."\n\n");

fclose($php_output_handle);

?>