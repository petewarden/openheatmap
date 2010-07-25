#!/usr/bin/php
<?php

/**
*************WARNING*****************
By running this script you're using resources on Yellow Page's servers, so be respectful of their 
commitment to an open and crawlable web. Check http://yellowpages.com/robots.txt at least once a week to
ensure you're abiding by their site rules, don't fire too many requests at once, and make sure you
supply a valid contact email so they can get in touch in case of problems.
*************************************

This script allows you to download Yellow Pages listings and extract structured information from
the HTML. 

To test it, go to the command line, cd to this folder and run

./yellowpagescrawl.php -q "Liquor Stores" -l "Denver, CO" -e <email> -o <organization>

where <email> and <organization> are replaced by your contact email address and company, so that
Yellow Pages can get in touch if your crawling causes any problems. You should see it output a CSV
file containing information about all the businesses it finds

The script fetches the HTML for the page from Yellow Page's servers, and then runs a set of regular
expressions to extract the microformatted information for that business. The profiles mostly use hcard
to help robots like us understand what the meaning of the different elements is.
 
(c) Pete Warden <pete@petewarden.com> http://petewarden.typepad.com/ Jan 8th 2010

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
require_once('parallelcurl.php');

define('YELLOW_PAGES_DOMAIN', 'http://www.yellowpages.com');

// These are the REs used to extract the information from the raw HTML. Most of the
// elements are defined by the hCard microformat, for more details see
// http://microformats.org/wiki/hcard
define('NAME_RE', '@<h3 class="business-name fn org"><a href="[^"]+" class="[^"]+">([^<]*)</a></h3>@');
define('STREET_ADDRESS_RE', '@<span class="street-address">([^<]*)</span>@');
define('LOCALITY_RE', '@<span class="locality">([^<]*)</span>@');
define('REGION_RE', '@<span class="region">([^<]*)</span>@');
define('ZIPCODE_RE', '@<span class="postal-code">([^<]*)</span>@');
define('NEXT_RE', '@<li class="next"><a href="([^"]*)">Next</a>@');

$contentrelist = array(
    NAME_RE => array('name' => 'name', 'multiple' => true),
    STREET_ADDRESS_RE => array('name' => 'street_address', 'multiple' => true),
    LOCALITY_RE => array('name' => 'locality', 'multiple' => true),
    REGION_RE => array('name' => 'region', 'multiple' => true),
    ZIPCODE_RE => array('name' => 'zipcode', 'multiple' => true),
    NEXT_RE => array('name' => 'next'),
);

define('FETCH_TIMEOUT', 30);

// This function is called when ParallelCurl completes a page fetch, and it handles converting
// the HTML into CSV data that's printed to stdout.
function parse_page($content, $url, $ch, $userdata)
{
    error_log("Parsing '$url'");

	global $contentrelist;
    global $parallelcurl;

    $output_handle = $userdata['output_handle'];

    $redirecturl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

	if (empty($content))
		return null;

    $content = str_replace("\n", "", $content);
    
    $result = array();
    
	foreach ($contentrelist as $currentre => $reinfo)
	{
		if (!preg_match_all($currentre, $content, $matches))
            continue;

        if (isset($reinfo['multiple']))
        {
            $matcharray = $matches[1];
            $output = array();
            foreach ($matcharray as $matchtext)
                $output[] = htmlspecialchars_decode($matchtext, ENT_QUOTES);
        }
        else if (isset($reinfo['list']))
        {
            $matchtext = htmlspecialchars_decode($matches[1][0], ENT_QUOTES);
            $output = explode('; ', $matchtext);
        }
        else
        {
            $matchtext = htmlspecialchars_decode($matches[1][0], ENT_QUOTES);
            $output = $matchtext;;
        }
        $name = $reinfo['name'];
        
        if (!empty($output))
            $result[$name] = $output;
	}
    
    if (!isset($result['name']))
    {
        die("Couldn't parse the page: $content\n");
    }
    
    $found_count = count($result['name']);
    
    if (count($result['street_address'])!==$found_count)
        error_log('Only '.count($result['street_address']).' found for street_address');

    if (count($result['locality'])!==$found_count)
        error_log('Only '.count($result['locality']).' found for locality');

    if (count($result['region'])!==$found_count)
        error_log('Only '.count($result['region']).' found for region');

    if (count($result['zipcode'])!==$found_count)
        error_log('Only '.count($result['zipcode']).' found for zipcode');

    for ($index=0; $index<$found_count; $index+=1)
    {
        $name = $result['name'][$index];
        $street_address = $result['street_address'][$index];
        $locality = $result['locality'][$index];
        $region = $result['region'][$index];
        $zipcode = $result['zipcode'][$index];

        $output_data = array(
            $name,
            $street_address.' '.$locality.', '.$region.' '.$zipcode,
        );
        
        fputcsv($output_handle, $output_data);
    }

    if (isset($result['next']))
    {
        $next_url = YELLOW_PAGES_DOMAIN.$result['next'];
        error_log("Starting next url '$next_url'");
        $parallelcurl->startRequest($next_url, 'parse_page', $userdata);
    }
}

$cliargs = array(
	'query' => array(
		'short' => 'q',
		'type' => 'required',
		'description' => 'The type of business to search for, eg "Liquor Stores"',
	),
    'location' => array(
        'short' => 'l',
        'type' => 'required',
        'description' => 'The location to search, eg "Denver, CO"',
    ),
    'organization' => array(
        'short' => 'o',
        'type' => 'required',
        'description' => 'The name of the organization or company running this crawler',
    ),
    'email' => array(
        'short' => 'e',
        'type' => 'required',
        'description' => 'An email address where server owners can report any problems with this crawler',
    ),
	'output' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the output CSV data to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
);

$options = cliargs_get_options($cliargs);
$query = $options['query'];
$location = $options['location'];
$organization = $options['organization'];
$email = $options['email'];
$output = $options['output'];
$threads = 2;

if (empty($organization) || empty($email) || (!strpos($email, '@')))
    die("You need to specify a valid organization and email address\n");

$agent = 'Crawler from '.$organization;
$agent .= ' - contact '.$email;
$agent .= ' to report any problems with my crawling. Based on code from http://petewarden.typepad.com';

$curloptions = array(
	CURLOPT_FOLLOWLOCATION => TRUE,
	CURLOPT_USERAGENT => $agent,
	CURLOPT_TIMEOUT => FETCH_TIMEOUT,
);

$location_path = strtolower($location);
$location_path = str_replace(',', '', $location_path);
$location_path = str_replace(' ', '-', $location_path);

$query_path = strtolower($query);
$query_path = str_replace(',', '', $query_path);
$query_path = str_replace(' ', '-', $query_path);

$search_url = YELLOW_PAGES_DOMAIN;
$search_url .= '/';
$search_url .= $location_path;
$search_url .= '/';
$search_url .= $query_path;
$search_url .= '?g='.urlencode($location);
$search_url .= '&q='.urlencode($query);

$output_handle = fopen($output, 'w') or die("Couldn't open output file '$output'\n");

fputcsv($output_handle, array('name', 'address'));

$parallelcurl = new ParallelCurl($threads, $curloptions);
    
error_log("Starting with '$search_url'");
    
$parallelcurl->startRequest($search_url, 'parse_page', array('output_handle' => $output_handle));

// Important - if you remove this any pending requests may not be processed
$parallelcurl->finishAllRequests();

fclose($output_handle);

?>