#!/usr/bin/php

<?php

require_once('locationextractor.php');
require_once('simple_html_dom.php');
require_once('geoutils.php');

function get_opengraph_tag($tag, $input) {
  if (!preg_match('@<meta property="og:'.$tag.'" content="([^"]+)"@', $input, $matches)) {
    error_log("No '$tag' found");
    return null;
  }
  return $matches[1];
}

ini_set('display_errors', true);
set_time_limit(0);

$result = array(
    'errors' => array(),
    'warnings' => array(),
    'output_id' => '',
    'use_base64' => false,
);

$base_url = 'http://andrewsullivan.thedailybeast.com/';
$years = range(2008, 2011);
$months = range(1, 12);
$days = range(0,32);

foreach ($years as $year)
{
    foreach ($months as $month)
    {
        $month_url = $base_url.$year.'/'.str_pad($month, 2, '0', STR_PAD_LEFT).'/';

        foreach ($days as $day)
        {
            $full_url = $month_url.'the-view-from-your-window';
            if ($day>0)
                $full_url .= '-'.$day;
            $full_url .= '.html';

//            error_log("Looking at $full_url");

            $input_string = curl_get($full_url);
            if (empty($input_string))
                break;

            $image = get_opengraph_tag('image', $input_string);
            $description = get_opengraph_tag('description', $input_string);
            $canonical_url = get_opengraph_tag('url', $input_string);

            $place = text2places('at '.$description);
            if (!isset($place)) {
              if (preg_match('@(.+),(.+),@', $description, $matches)) {
                $place = street2coordinates($matches[1].','.$matches[2]);
              }
            }
            if (!isset($place)) {
              error_log("Couldn't place '$description'");
              continue;
            }

            if (preg_match('@(\\d\\d?)[:\\.](\\d\\d) ?([ap]m)@', $description, $matches)) {
              $time_hour = $matches[1]*1.0;
              $time_minute = $matches[2]*1.0;
              $time_ampm = $matches[3];
            } else if (preg_match('@(\\d\\d?) ?([ap]m)@', $description, $matches)) {
              $time_hour = $matches[1]*1.0;
              $time_minute = 0;              
              $time_ampm = $matches[2];
            } else {
              error_log("Couldn't extract time from '$description'");
              continue;
            }
            
            if ($time_hour == 12) {
              $time_hour = 0;
            }
            if ($time_ampm == 'pm') {
              $time_hour += 12;
            }

            $time_value = ($time_hour*1.0)+($time_minute/60.0);

            $latitude = $place['latitude'];
            $longitude = $place['longitude'];

            if (!isset($place)||!isset($image)||!isset($canonical_url))
                continue;

            print "\"$description\",$time_value,$latitude,$longitude,$image,$year,$month,$day,$canonical_url\n";

        }
    }

}

?>