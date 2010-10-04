<?php

require_once('geoutils.php');

$g_ending_locations = array(
    'possible_prefixes' => array(
        'get_world_country_matches',
        'get_us_state_matches',
    ),
);

function extract_locations($text, $verbose = false)
{
    global $g_ending_locations;
    
    $words = explode(' ', $text);
    $word_count = count($words);
    
    $result = array();
    for ($word_index=($word_count-1); $word_index>=0; $word_index-=1)
    {    
        $matches = location_matches($words, $word_index, $g_ending_locations);
        
        if (!isset($matches))
            continue;
        
        if ($verbose) print "First words matched: '".implode(' ', $matches['matched_words'])."'\n";

        $matched_words_count = count($matches['matched_words']);
        $previous_index = ($word_index-$matched_words_count);
        
        if ($previous_index<0)
            continue;
        
        $previous_matches = location_matches($words, $previous_index, $matches);
        
        if (!isset($previous_matches))
        {
            if ($verbose) print "Second words didn't match: '".implode(' ', $previous_matches['matched_words'])."'\n";
            continue;
        }

        if ($verbose) print "Second words matched: '".implode(' ', $previous_matches['matched_words'])."'\n";
        
        $current_result = implode(' ', $previous_matches['matched_words'])
            .' '
            .implode(' ', $matches['matched_words']);
        
        while (true)
        {
            $previous_matched_words_count = count($previous_matches['matched_words']);
            $previous_index = ($word_index-$previous_matched_words_count);
            if ($previous_index<0)
                break;

            $previous_matches = location_matches($words, $previous_index, $previous_matches);
            
            if (!isset($previous_matches))
                break;
                
            $current_result = implode(' ', $previous_matches['matched_words'])
                .' '
                .$current_result;
        }
        
        $result[] = $current_result;
    }
    
    return $result;
}

function location_matches($words, $word_index, $previous_matches)
{
    $possible_prefixes = $previous_matches['possible_prefixes'];
    foreach ($possible_prefixes as $location_function)
    {
        $result = $location_function($words, $word_index, $previous_matches);
        if (isset($result))
            return $result;
    }
    
    return null;
}

$g_world_country_data = null;
function load_world_country_data()
{
    global $g_world_country_data;

    $g_world_country_data = load_names_data('data/world/countries.csv');
}

function get_world_country_matches($words, $word_index, $previous_matches)
{
    global $g_world_country_data;
    if (!isset($g_world_country_data))
        load_world_country_data();

    $match_info = get_matching_words($words, $word_index, $g_world_country_data);
    if (empty($match_info))
        return null;

    $matched_words = $match_info['matched_words'];
    $location_data = $match_info['location_data'];

    $result = array(
        'matched_words' => $matched_words,
        'country_code' => strtolower($location_data['country_code']),
        'possible_prefixes' => array('get_world_city_matches'),
    );
    
    return $result;
}

$g_world_city_data = array();
function get_world_city_matches($words, $word_index, $previous_matches)
{
    global $g_world_city_data;
          
    if (!isset($previous_matches['country_code']))
        return null;
        
    $country_code = $previous_matches['country_code'];
    if (!isset($g_world_city_data[$country_code]))
        load_world_city_data($country_code);

    if (!isset($g_world_city_data[$country_code]))
        return null;

    $match_info = get_matching_words(
        $words, 
        $word_index, 
        $g_world_city_data[$country_code],
        'test_state', 
        $previous_matches);
    
    if (empty($match_info))
        return null;

    $matched_words = $match_info['matched_words'];
    $location_data = $match_info['location_data'];

    $result = array(
        'country_code' => $country_code,
        'matched_words' => $matched_words,
        'possible_prefixes' => array(),
    );
    
    return $result;
}

function get_matching_words($words, $word_index, $match_data, $additional_test = null, $previous_matches = null)
{
    $first_word = $words[$word_index];
    $first_word = normalize_name($first_word);
    
    if (!isset($match_data[$first_word]))
        return null;
    
    $names_data = $match_data[$first_word];

    $sortfunction = create_function('$a, $b', 'if (count($a["preceding_words"])<count($b["preceding_words"])) return 1; else return -1;'); 
    usort($names_data, $sortfunction);
    
    foreach ($names_data as $name_data)
    {
        $preceding_words = $name_data['preceding_words'];
        $actual_index = ($word_index-1);
        $all_match = true;
        $matching_words = array($first_word);
        foreach ($preceding_words as $expected_word)
        {
            if ($actual_index<0)
            {
                $all_match = false;
                break;
            }
            $actual_word = $words[$actual_index];
            $actual_word = normalize_name($actual_word);
            $actual_index -= 1;

            if ($actual_word!=$expected_word)
            {
                $all_match = false;
                break;
            }
            
            $matching_words[] = $actual_word;
        }

        $matching_words = array_reverse($matching_words);

        if ($all_match&&isset($additional_test))
        {
            $all_match = $additional_test($name_data, $previous_matches);
        }

        if ($all_match)
        {
            $result = array(
                'matched_words' => $matching_words,
                'location_data' => $name_data,
            );
            
            return $result;
        }
    }
    
    return null;
}

function load_world_city_data($country_code)
{
    global $g_world_city_data;

    $city_file = 'data/countries/'.$country_code.'/cities.csv';
    
    $g_world_city_data[$country_code] = load_names_data($city_file);
}

$g_us_state_data = null;
function get_us_state_matches($words, $word_index, $previous_matches)
{
    global $g_us_state_data;
    if (!isset($g_us_state_data))
        load_us_state_data();

    // Special case to skip the lower-case 'in' code for Indiana, since that's also a common word
    if ($words[$word_index]=='in')
        return null;

    $match_info = get_matching_words($words, $word_index, $g_us_state_data);
    if (empty($match_info))
        return null;

    $matched_words = $match_info['matched_words'];
    $location_data = $match_info['location_data'];

    $result = array(
        'matched_words' => $matched_words,
        'country_code' => 'us',
        'state_code' => $location_data['state_code'],
        'possible_prefixes' => array('get_world_city_matches'),
    );
    
    return $result;
}

function load_us_state_data()
{
    global $g_us_state_data;

    $state_file = 'data/countries/us/states.csv';
    
    $g_us_state_data = load_names_data($state_file);
}

function test_state($current_data, $previous_matches)
{
    if (!isset($current_data['state_code'])||!isset($previous_matches['state_code']))
        return true;
        
    $current_state = $current_data['state_code'];
    $previous_state = $previous_matches['state_code'];
    
    return ($current_state==$previous_state);
}

function load_names_data($file_name)
{
    if (!file_exists($file_name))
    {
        log_warning(0, "Couldn't find file '$file_name'");
        return;
    }

    $csv_data = read_csv_file($file_name);
    
    $output = array();
    
    foreach ($csv_data as $row)
    {
        $names_string = $row['names'];
        
        $names = explode('|', $names_string);
        foreach ($names as $name)
        {
            $name = normalize_name($name);
            $name_words = explode(' ', $name);
            $name_words = array_reverse($name_words);
            
            $first_word = $name_words[0];
            if (!isset($output[$first_word]))
                $output[$first_word] = array();
                
            $preceding_words = array_slice($name_words, 1);
            
            $output_row = array(
                'name' => $name,
                'preceding_words' => $preceding_words,
            );
            
            foreach ($row as $key => $value)
            {
                if ($key!=='name')
                    $output_row[$key] = $value;
            }
            
            $output[$first_word][] = $output_row;
        }
    }
    
    return $output;
}

?>