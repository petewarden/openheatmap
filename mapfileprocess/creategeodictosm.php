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

// See http://wiki.openstreetmap.org/wiki/Key:admin_level#admin_level for the admin level choices
$g_input_files = array(
  'us_congress.osm' => array(
    'country_code' => 'usa',
    'code' => 'district_code',
    'other_code' => 'state_code',
    'type' => 'constituency',
  ),
  'can_constituency.osm' => array(
    'country_code' => 'can',
    'code' => 'can_constituency_code',
    'type' => 'constituency',
  ),
  'dk_county.osm' => array(
    'country_code' => 'dnk',
    'code' => 'dk_county_code',
    'type' => 'admin7',
  ),
  'hk_constituency.osm' => array(
    'country_code' => 'hkg',
    'code' => 'hk_constituency_code',
    'type' => 'constituency',
  ),
  'ind_district.osm' => array(
    'country_code' => 'ind',
    'code' => 'ind_district_code',
    'type' => 'admin5',
  ),
  'ire_constituency.osm' => array(
    'country_code' => 'ire',
    'code' => 'ire_constituency_code',
    'type' => 'constituency',
  ),
  'ire_county.osm' => array(
    'country_code' => 'ire',
    'code' => 'ire_county_code',
    'type' => 'admin6',
  ),
  'mex_muni.osm' => array(
    'country_code' => 'mex',
    'code' => 'mex_muni_code',
    'type' => 'admin6',
  ),
  'nzl_electoral_district.osm' => array(
    'country_code' => 'nzl',
    'code' => 'nzl_electoral_district_code',
    'type' => 'constituency',
  ),
  'uk_constituencies.osm' => array(
    'country_code' => 'gbr',
    'code' => 'uk_constituency_code',
    'type' => 'constituency',
  ),
  'uk_districts.osm' => array(
    'country_code' => 'gbr',
    'code' => 'uk_district_code',
    'type' => 'admin8',
  ),
  'uk_region.osm' => array(
    'country_code' => 'gbr',
    'code' => 'uk_region_code',
    'type' => 'admin5',
  ),
  'us_cities.osm' => array(
    'country_code' => 'usa',
    'code' => 'city_code',
    'other_code' => 'state_code',
    'type' => 'admin5',
  ),
  'us_counties.osm' => array(
    'country_code' => 'usa',
    'code' => 'county_code',
    'other_code' => 'state_code',
    'type' => 'admin6',
  ),
);

$g_number_to_word = array(
    '00' => 'First',
    '1' => 'First',
    '2' => 'Second',
    '3' => 'Third',
    '4' => 'Fourth',
    '5' => 'Fifth',
    '6' => 'Sixth',
    '7' => 'Seventh',
    '8' => 'Eighth',
    '9' => 'Ninth',
    '01' => 'First',
    '02' => 'Second',
    '03' => 'Third',
    '04' => 'Fourth',
    '05' => 'Fifth',
    '06' => 'Sixth',
    '07' => 'Seventh',
    '08' => 'Eighth',
    '09' => 'Ninth',
    '10' => 'Tenth',
    '11' => 'Eleventh',
    '12' => 'Twelfth',
    '13' => 'Thirteenth',
    '14' => 'Fourteenth',
    '15' => 'Fifteenth',
    '16' => 'Sixteenth',
    '17' => 'Seventeenth',
    '18' => 'Eighteenth',
    '19' => 'Nineteenth',
    '20' => 'Twentieth',
    '21' => 'Twenty first',
    '22' => 'Twenty second',
    '23' => 'Twenty third',
    '24' => 'Twenty fourth',
    '25' => 'Twenty fifth',
    '26' => 'Twenty sixth',
    '27' => 'Twenty seventh',
    '28' => 'Twenty eighth',
    '29' => 'Twenty ninth',
    '30' => 'Thirtieth',
    '31' => 'Thirty first',
    '32' => 'Thirty second',
    '33' => 'Thirty third',
    '34' => 'Thirty fourth',
    '35' => 'Thirty fifth',
    '36' => 'Thirty sixth',
    '37' => 'Thirty seventh',
    '38' => 'Thirty eighth',
    '39' => 'Thirty ninth',
    '40' => 'Fortieth',
    '41' => 'Forty first',
    '42' => 'Forty second',
    '43' => 'Forty third',
    '44' => 'Forty fourth',
    '45' => 'Forty fifth',
    '46' => 'Forty sixth',
    '47' => 'Forty seventh',
    '48' => 'Forty eighth',
    '49' => 'Forty ninth',
    '50' => 'Fiftieth',
    '51' => 'Fifty first',
    '52' => 'Fifty second',
    '53' => 'Fifty third',
    '54' => 'Fifty fourth',
    '55' => 'Fifty fifth',
    '56' => 'Fifty sixth',
    '57' => 'Fifty seventh',
    '58' => 'Fifty eighth',
    '59' => 'Fifty ninth'
);

$g_fips_to_postal = array(
    '01' => 'AL',
    '29' => 'MO',
    '02' => 'AK',
    '30' => 'MT',
    '04' => 'AZ',
    '31' => 'NE',
    '05' => 'AR',
    '32' => 'NV',
    '06' => 'CA',
    '33' => 'NH',
    '08' => 'CO',
    '34' => 'NJ',
    '09' => 'CT',
    '35' => 'NM',
    '10' => 'DE',
    '36' => 'NY',
    '11' => 'DC',
    '37' => 'NC',
    '12' => 'FL',
    '38' => 'ND',
    '13' => 'GA',
    '39' => 'OH',
    '40' => 'OK',
    '41' => 'OR',
    '15' => 'HI',
    '42' => 'PA',
    '16' => 'ID',
    '44' => 'RI',
    '17' => 'IL',
    '45' => 'SC',
    '18' => 'IN',
    '46' => 'SD',
    '19' => 'IA',
    '47' => 'TN',
    '20' => 'KS',
    '48' => 'TX',
    '21' => 'KY',
    '49' => 'UT',
    '22' => 'LA',
    '50' => 'VT',
    '23' => 'ME',
    '51' => 'VA',
    '24' => 'MD',
    '53' => 'WA',
    '25' => 'MA',
    '54' => 'WV',
    '26' => 'MI',
    '55' => 'WI',
    '27' => 'MN',
    '56' => 'WY',
    '28' => 'MS',
    '60' => 'AS',
    '64' => 'FM',
    '66' => 'GU',
    '68' => 'MH',
    '69' => 'MP',
    '70' => 'PW',
    '72' => 'PR',
    '74' => 'UM',
    '78' => 'VI'
);

ini_set('memory_limit', '-1');

$cliargs = array(
	'inputpath' => array(
		'short' => 'i',
		'type' => 'optional',
		'description' => 'The folder containing the OSM files to merge',
    'default' => '../gzipped/uncompressed/',
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the output OSM XML data to - if unset, will write to stdout',
    'default' => 'php://stdout',
	),
);	

$options = cliargs_get_options($cliargs);

$input_path = $options['inputpath'];
$output_file = $options['outputfile'];

$provinces_pattern = $input_path.'*_provinces.osm';
foreach (glob($provinces_pattern) as $file_path)
{
    $filename = substr($file_path,strlen($input_path));
    $country_code = str_replace('_provinces.osm', '', $filename);
    $code = 'province_code';
    $type = 'admin4';
    $g_input_files[$filename] = array(
        'country_code' => $country_code,
        'code' => $code,
        'type' => $type,
    );
}

$output_osm_ways = null;
foreach ($g_input_files as $input_file => $transform_info)
{
    error_log('Working on '.$input_file."\n");

    $input_file_path = $input_path.$input_file;
    $input_osm_ways = new OSMWays();
    $input_contents = file_get_contents($input_file_path) or die("Couldn't read file '$input_file_path'");
    $input_osm_ways->deserialize_from_xml($input_contents);

    $country_code = $transform_info['country_code'];
    $code = $transform_info['code'];
    $type = $transform_info['type'];
    if (isset($transform_info['other_code']))
        $other_code = $transform_info['other_code'];
    else
        $other_code = NULL;

    if (!isset($output_osm_ways))
        $output_osm_ways = new OSMWays();

    $input_ways = $input_osm_ways->ways;
    
    foreach ($input_ways as $way)
    {
        $input_nodes = $input_osm_ways->nodes;
        
        $output_osm_ways->begin_way();
     
        $tags = $way['tags'];
        if ($input_file=='us_congress.osm') 
        {
            $state_code = $tags['state_code'];
            $district_code = $tags['district_code'];

            if ((empty($state_code))||($district_code=='98'))
                continue;

            if (!isset($g_fips_to_postal[$state_code]))
                error_log('No postal for '.$state_code);

            if (!isset($g_number_to_word[$district_code]))
                error_log('No number for '.$district_code);

            $state_name = $g_fips_to_postal[$state_code];
            $district_name = $g_number_to_word[$district_code]; 

            $name = $district_name.' district, '.$state_name;
            $code_value = $district_code;
        }
        else
        {
          if (!isset($tags[$code])||(!isset($tags['name'])))
          {
              error_log('Missing required tags in '.print_r($tags, true));
              continue;
          }
          $code_value = $tags[$code];
          $name = $tags['name'];
        }
          
        if (isset($other_code))
        {
            if (!isset($tags[$other_code])) {
                error_log('Missing required tags in '.print_r($tags, true));
                continue;
            }
            $other_code_value = $tags[$other_code];
            $code_value = $other_code_value.'_'.$code_value;
        }
     
        $output_osm_ways->add_tag('name', $name);
        $output_osm_ways->add_tag('code', $code_value);
        $output_osm_ways->add_tag('country_code', $country_code);
        $output_osm_ways->add_tag('type', $type);

        foreach ($way['nds'] as $nd_ref)
        {
            if (!isset($input_nodes[$nd_ref]))
                continue;
                
            $node = $input_nodes[$nd_ref];
            $output_osm_ways->add_vertex($node['lat'], $node['lon']);
        }
        
        $output_osm_ways->end_way();
    }
}
    
$output_contents = $output_osm_ways->serialize_to_xml();
file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");

?>