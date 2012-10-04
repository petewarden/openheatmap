<?php
/*
OpenHeatMap
Copyright (C) 2010 Pete Warden <pete@petewarden.com>
*/

function internal_error($message)
{
    fatal_error(0, '<a href="http://wiki.github.com/petewarden/openheatmap/internal-error">Internal error</a>: '.$message);
}

function fatal_error($row, $message, $url='')
{
    error_log('fatal_error: '.$message.': '.$url);

    log_error($row, $message, $url);
    print_result_and_exit();
}

function log_error($row, $message, $url='')
{
    global $result;
    
    $result['errors'][] = array(
        'row' => $row,
        'message' => $message,
        'url' => $url,
    );
}

function log_warning($row, $message, $url='')
{
    global $result;
    
    error_log("$row: $message");
    
    $result['warnings'][] = array(
        'row' => $row,
        'message' => $message,
        'url' => $url,
    );
}

function print_result_and_exit()
{
    global $result;

    if ($result['use_base64'])
        print base64_encode(json_encode($result));
    else
        print json_encode($result);    
    
    exit();
}

function read_csv_file($file_name, $seperator=',')
{
    ini_set('auto_detect_line_endings', true);
    $file_handle = fopen($file_name, "r") or fatal_error(0, "Couldn't open $file_name\n");

    $result = read_csv_file_from_handle($file_handle, $seperator);
    
    fclose($file_handle);

    return $result;
}
    
function read_csv_file_from_handle($file_handle, $seperator)
{
    $result = array();

    $column_names = array();

    $line_index = 0;
    while(!feof($file_handle))
    {
        $current_parts = fgetcsv($file_handle, 0, $seperator);
        
        if (empty($current_parts))
            continue;
        
        $line_index += 1;
        if ($line_index<2)
        {
            $seperators = array( "\t", ";", "|");

            foreach ($seperators as $seperator_candidate)
            {
                if (count($current_parts)>=2)
                    continue;

                $seperator = $seperator_candidate;
                rewind($file_handle);
                $current_parts = fgetcsv($file_handle, 0, $seperator);                
            }
        
            $column_names = array_map('trim', $current_parts);
            continue;
        }
        
        $row = array();

        $column_index = 0;
        foreach ($column_names as $column_name)
        {
            if (!empty($column_name))
            {
                if (isset($current_parts[$column_index]))
                {
                    $row[$column_name] = $current_parts[$column_index];
                }
                else
                {
                    $row[$column_name] = null;
                    log_warning($line_index, "No value found in the '$column_name' column", 'http://wiki.github.com/petewarden/openheatmap/no-value-found-in-column');
                }
            }
            $column_index += 1;
        }

        $result[] = $row;
    }
    
    return $result;
}

function suppress_date_warning()
{
    if (function_exists("date_default_timezone_set") && function_exists("date_default_timezone_get"))
        date_default_timezone_set(@date_default_timezone_get());
}

function curl_get($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'OpenHeatMap (curl)');
	$result = curl_exec($ch);
    if (!$result)
    {
        error_log("Couldn't load from '$url' - response was '$result'");
        return FALSE;
    }
    
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

    if ($http_status!==200)
    {
        error_log("Couldn't load from '$url' - response was '$result'");
        return FALSE;
    }

	return $result;
}

function clean_whitespace($input)
{
    $output = preg_replace('/\n/', ' ', $input);
    $output = preg_replace('/\s+/', ' ', $input);
    $output = trim($output);
    
    return $output;
}

function remove_punctuation($input)
{
    $output = preg_replace('/[^a-zA-Z0-9]+/', ' ', $input);
    $output = clean_whitespace($output);
    
    return $output;
}

function normalize_name($input)
{
    $output = remove_punctuation($input);
    $output = strtolower($output);
    
    return $output;
}

  define('DSTK_BASE_URL', 'http://www.datasciencetoolkit.org');
  

function text2places($text) {
  
  $dstk_url = DSTK_BASE_URL.'/text2places';
  
  $post_data = $text;

  $ch = curl_init();
  
  curl_setopt($ch, CURLOPT_URL, $dstk_url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,30);
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
  
  $output = curl_exec($ch);
  
  $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  
  if ($response_code!=200)
  {
    $this->_last_error = 'Bad response code: ' . $response_code . ' : '. curl_error($ch);
    $output = false;
  }
  else if(curl_errno($ch))
  {
    $this->_last_error = 'Curl error: ' . curl_error($ch);
    $output = false;
  }
  curl_close($ch);
  
  $result_hash = json_decode($output, true);
  if (count($result_hash) < 1) {
      return null;
  }

  $found_place = $result_hash[0];
  
  return $found_place;
}

function street2coordinates($text) {
  
  $dstk_url = DSTK_BASE_URL.'/street2coordinates';
  
  $post_data = $text;
  
  $ch = curl_init();
  
  curl_setopt($ch, CURLOPT_URL, $dstk_url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,30);
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
  
  $output = curl_exec($ch);
  
  $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  
  if ($response_code!=200)
  {
    error_log('Bad response code: ' . $response_code . ' : '. curl_error($ch));
    $output = false;
  }
  else if(curl_errno($ch))
  {
    $this->_last_error = 'Curl error: ' . curl_error($ch);
    $output = false;
  }
  curl_close($ch);
  
  $result_hash = json_decode($output, true);
  if (count($result_hash) < 1) {
    return null;
  }
  
  $found_place = $result_hash[$text];
  
  return $found_place;  
}

function text2times($text) {
  
  $dstk_url = DSTK_BASE_URL.'/text2times';
  
  $post_data = $text;
  
  $ch = curl_init();
  
  curl_setopt($ch, CURLOPT_URL, $dstk_url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,30);
  curl_setopt($ch, CURLOPT_FAILONERROR, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
  
  $output = curl_exec($ch);
  
  $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  
  if ($response_code!=200)
  {
    $this->_last_error = 'Bad response code: ' . $response_code . ' : '. curl_error($ch);
    $output = false;
  }
  else if(curl_errno($ch))
  {
    $this->_last_error = 'Curl error: ' . curl_error($ch);
    $output = false;
  }
  curl_close($ch);
  
  $result_hash = json_decode($output, true);
  if (count($result_hash) < 1) {
    return null;
  }
  
  $found_time = $result_hash[0];
  
  return $found_time;
}


?>