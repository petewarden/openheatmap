<?php

require ('./GeoIP.php');

define('TIME_GRANULARITY', 60*60); // How to chunk up the timed results

function raise_error($message)
{
    $output = array(
        'was_error' => true,
        'error_message' => $message,
    );
    print json_encode($output);
    error_log("accesslogtocsv.php failed with '$message'");
    exit();
}

// See http://www.php.net/manual/en/function.fputcsv.php#96937
function sputcsv($row, $delimiter = ',', $enclosure = '"', $eol = "\n")
{
    static $fp = false;
    if ($fp === false)
    {
        $fp = fopen('php://temp', 'r+'); // see http://php.net/manual/en/wrappers.php.php - yes there are 2 '.php's on the end.
        // NB: anything you read/write to/from 'php://temp' is specific to this filehandle
    }
    else
    {
        rewind($fp);
    }
   
    if (fputcsv($fp, $row, $delimiter, $enclosure) === false)
    {
        return false;
    }
   
    rewind($fp);
    $csv = fgets($fp);
   
    if ($eol != PHP_EOL)
    {
        $csv = substr($csv, 0, (0 - strlen(PHP_EOL))) . $eol;
    }
   
    return $csv;
}

function suppress_date_warning()
{
    if (function_exists("date_default_timezone_set") && function_exists("date_default_timezone_get"))
        date_default_timezone_set(@date_default_timezone_get());
}

suppress_date_warning();

if (!isset($_FILES['input_file']))
    raise_error("No input file found");

$input_file = $_FILES['input_file'];
$server_file_name = $input_file['tmp_name'];
$input_text = file_get_contents($server_file_name) or raise_error("Couldn't access '$server_file_name'");

$input_lines = explode("\n", $input_text);

$geoip = Net_GeoIP::getInstance('/usr/local/share/GeoIP/GeoLiteCity.dat');

$csv_string = "time,lat,lon,city,url\n";
foreach ($input_lines as $current_line)
{
    if (!preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/',$current_line, $ip_matches)) 
        continue;

    $ip_address = $ip_matches[1];

    if (!preg_match('/\[(.+)\]/', $current_line, $time_matches))
        continue;
        
    $time_string = $time_matches[1];
    $timestamp = strtotime($time_string);

    $quantized_time = (int)(floor($timestamp/TIME_GRANULARITY)*TIME_GRANULARITY);
    
    $output_time_string = date('Y:m:d-H:i', $quantized_time);

    if (!preg_match('/"GET ([^"]+) HTTP\/1\./', $current_line, $url_matches))
    {
        error_log("Missing match on '$current_line'");
        continue;
    }
    
    $url = $url_matches[1];
    
    $location = $geoip->lookupLocation($ip_address);

    $output_row = array(
        $output_time_string,
        $location->latitude,
        $location->longitude,
        $location->city,
        $url,
    );
    
    $csv_string .= sputcsv($output_row);
}

$output = array(
    'was_error' => false,
    'csv_string' => utf8_encode($csv_string),
);

print json_encode($output);