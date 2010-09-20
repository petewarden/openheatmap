#!/usr/bin/php

<?php



require_once('locationextractor.php');

require_once('simple_html_dom.php');

require_once('utils.php');



ini_set('display_errors', true);

set_time_limit(0);



$result = array(

    'errors' => array(),

    'warnings' => array(),

    'output_id' => '',

    'use_base64' => false,

);



$base_url = 'http://andrewsullivan.theatlantic.com/the_daily_dish/';

$years = range(2009, 2010);

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



            error_log("Looking at $full_url");

                

            $input_string = curl_get($full_url);

            if (empty($input_string))

                break;



            $input_html = str_get_html($input_string);



            $title_element = $input_html->find('title', 0);

            if (isset($title_element))

                $title = $title_element->plaintext;

            else

                $title = '';



            $title = clean_whitespace($title);

                

//            print "Title: '$title'\n";



            $body = $input_html->find('body', 0);

            if (!isset($body))

                fatal_error(0, "Couldn't find a <body> in '$input_url'");

                

            $text = $body->text();



            $text = clean_whitespace($text);

                

//            print "Text: '$text'\n";



            $places = extract_locations($text);



//            print "Places: ".implode(', ', $places)."\n";



            $picture_url = null;

            foreach ($input_html->find('img') as $image)

            {

                if (!isset($image->attr['src']))

                    continue;

                $src = $image->attr['src'];

                

                if (preg_match('@http://andrewsullivan.theatlantic.com/\.a/@', $src))

                {

                    $picture_url = $src;

                    break;

                }

            }

            

            if (!isset($places[0])||!isset($picture_url))

                continue;

            

//            print "Picture URL: $picture_url\n";

  

            print $places[0].",$picture_url,$year,$month,$day,$full_url\n";

                                

//            exit();

        }

    }

}



?>