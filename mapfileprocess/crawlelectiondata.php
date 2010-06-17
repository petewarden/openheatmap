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

define('ELECTION_URL_BASE', 'http://www.presidency.ucsb.edu/showelection.php?year=');

$cliargs = array(
	'year' => array(
		'short' => 'y',
		'type' => 'required',
		'description' => 'The year of the election to fetch data for',
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the output OSM XML data to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
);	

$state_translation_table = array(
    'AK' => '02',
    'Alaska' => '02',
    'AL' => '01',
    'Alabama' => '01',
    'AR' => '05',
    'Arkansas' => '05',
    'AZ' => '04',
    'Arizona' => '04',
    'CA' => '06',
    'California' => '06',
    'CO' => '08',
    'Colorado' => '08',
    'CT' => '09',
    'Connecticut' => '09',
    'DE' => '10',
    'Delaware' => '10',
    'DC' => '11',
    'District of Columbia' => '11',
    'FL' => '12',
    'Florida' => '12',
    'GA' => '13',
    'Georgia' => '13',
    'HI' => '15',
    'Hawaii' => '15',
    'ID' => '16',
    'Idaho' => '16',
    'IL' => '17',
    'Illinois' => '17',
    'IN' => '18',
    'Indiana' => '18',
    'IA' => '19',
    'Iowa' => '19',
    'KS' => '20',
    'Kansas' => '20',
    'KY' => '21',
    'Kentucky' => '21',
    'LA' => '22',
    'Louisiana' => '22',
    'ME' => '23',
    'Maine' => '23',
    'MD' => '24',
    'Maryland' => '24',
    'MA' => '25',
    'Massachusetts' => '25',
    'MI' => '26',
    'Michigan' => '26',
    'MN' => '27',
    'Minnesota' => '27',
    'MS' => '28',
    'Mississippi' => '28',
    'MO' => '29',
    'Missouri' => '29',
    'MT' => '30',
    'Montana' => '30',
    'NE' => '31',
    'Nebraska' => '31',
    'NV' => '32',
    'Nevada' => '32',
    'NH' => '33',
    'New Hampshire' => '33',
    'NJ' => '34',
    'New Jersey' => '34',
    'NM' => '35',
    'New Mexico' => '35',
    'NY' => '36',
    'New York' => '36',
    'NC' => '37',
    'North Carolina' => '37',
    'ND' => '38',
    'North Dakota' => '38',
    'OH' => '39',
    'Ohio' => '39',
    'OK' => '40',
    'Oklahoma' => '40',
    'OR' => '41',
    'Oregon' => '41',
    'PA' => '42',
    'Pennsylvania' => '42',
    'RI' => '44',
    'Rhode Island' => '44',
    'SC' => '45',
    'South Carolina' => '45',
    'SD' => '46',
    'South Dakota' => '46',
    'TN' => '47',
    'Tennessee' => '47',
    'TX' => '48',
    'Texas' => '48',
    'UT' => '49',
    'Utah' => '49',
    'VT' => '50',
    'Vermont' => '50',
    'VA' => '51',
    'Virginia' => '51',
    'WA' => '53',
    'Washington' => '53',
    'WV' => '54',
    'West Virginia' => '54',
    'WI' => '55',
    'Wisconsin' => '55',
    'WY' => '56',
    'Wyoming' => '56',
);

function curl_get($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'OpenHeatMap (curl)');
	$result = curl_exec($ch) or die("Couldn't fetch election data from $url\n");
	curl_close($ch);

	return $result;
}


ini_set('memory_limit', '-1');

$options = cliargs_get_options($cliargs);

$year = $options['year'];
$output_file = $options['outputfile'];

$output_file_handle = fopen($output_file, 'w') or die("Couldn't open $output_file for writing");

$input_url = ELECTION_URL_BASE.$year;
$html_string = curl_get($input_url);
$html_lines = explode("\n", $html_string);

$party_order_index = 0;

$lines_count = count($html_lines);
for ($line_index=0; $line_index<$lines_count; $line_index+=1)
{
    $line = $html_lines[$line_index];

    $party_res = array(
        '/<span class="style1">(.*)<\/span><\/td>/',
        '/<\/strong>(.*)<\/em><\/td>/',
        '/<td height="20" class="docdate">(.*)<\/td>/',
    );
    
    foreach ($party_res as $party_re)
    {
        if (preg_match($party_re, $line, $matches))
        {
            $party_name = $matches[1];
            if ($party_name=='Republican')
            {
                if (!isset($republican_index))
                {
                    $republican_index = $party_order_index;
                    $party_order_index += 1;
                }
            }
            else if (($party_name=='Democratic')||($party_name=='Democrat'))
            {
                if (!isset($democratic_index))
                {
                    $democratic_index = $party_order_index;
                    $party_order_index += 1;
                }
            }
        }
    }
    
    if (!preg_match('/<td[^>]*>(.*)<\/td>/', $line, $matches))
        continue;
        
    $state_name = $matches[1];
    if (!isset($state_translation_table[$state_name]))
        continue;
    
    if (!isset($republican_index)||!isset($democratic_index))
        die("Couldn't figure out the order of the reported results\n");
    
    $first_vote_line = $html_lines[$line_index+3];
    $second_vote_line = $html_lines[$line_index+6];
    
    if (!preg_match('/<td[^>]*>(.*)<\/td>/', $first_vote_line, $first_vote_matches))
    {
        error_log("Vote value line not understood: $first_vote_line");
        continue;
    }
    $first_vote_value = str_replace('%', '', $first_vote_matches[1]);

    if (!preg_match('/<td[^>]*>(.*)<\/td>/', $second_vote_line, $second_vote_matches))
    {
        error_log("Vote value line not understood: $second_vote_line");
        continue;
    }
    $second_vote_value = str_replace('%', '', $second_vote_matches[1]);
    
    $vote_values = array($first_vote_value, $second_vote_value);
    
    $output_data = array();
    $output_data[0] = $state_name;
    $output_data[1] = $vote_values[$democratic_index];
    $output_data[2] = $vote_values[$republican_index];
    $output_data[3] = $year;
    
    fputcsv($output_file_handle, $output_data);
}

fclose($output_file_handle);

?>