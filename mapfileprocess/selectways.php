#!/usr/bin/php
<?php

/*
OpenGraphMap processing
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

ini_set('memory_limit', '-1');

define('START_PARENS_RE', '/\(/');
define('END_PARENS_RE', '/\)/');
define('KEY_CHAR_RE', '/[a-zA-Z0-9_\- \':]/');
define('VALUE_CHAR_RE', '/[a-zA-Z0-9_\- \':]/');
define('COMPARE_RE', '/[=!<>]/');
define('OPERATOR_RE', '/[&|]/');

function tokenize_expression_string($expression_string)
{
    $expression_characters = preg_split('//', $expression_string, -1, PREG_SPLIT_NO_EMPTY);
    
    $state = 'START';
    $parens_depth = 0;
    
    $result = array();
    $current = null;
    
    $char_position = 0;
    foreach($expression_characters as $char)
    {
        switch ($state)
        {
            case 'START':
            {
                if (preg_match(START_PARENS_RE, $char))
                {
                    $current = array(
                        'type' => 'SUBEXPRESSION',
                        'value' => '',
                    );
                    $state = 'IN_PARENS';
                    $parens_depth = 1;
                }
                else if (preg_match(OPERATOR_RE, $char))
                {
                    if ($current!==null)
                        $result[] = $current;
                
                    $current = array(
                        'type' => 'OPERATOR',
                        'value' => $char,
                    );
                    $result[] = $current;
                    
                    $current = null;
                                        
                    $state = 'START';
                }
                else if (preg_match(KEY_CHAR_RE, $char))
                {
                    $current = array(
                        'type' => 'KEY',
                        'value' => $char,
                    );
                    $state = 'IN_KEY';
                }
                else
                {
                    die("Unexpected character '$char' found at index $char_position ($state)\n");
                }
                
            } break;

            case 'IN_PARENS':
            {
                if (preg_match(START_PARENS_RE, $char))
                {
                    $parens_depth += 1;
                    $current['value'] .= $char;
                }
                else if (preg_match(END_PARENS_RE, $char))
                {
                    $parens_depth -= 1;
                    if ($parens_depth===0)
                    {
                        $state = 'START';
                        $current['value'] = tokenize_expression_string($current['value']);    
                        $result[] = $current;
                        $current = null;
                    }
                    else
                    {
                        $current['value'] .= $char;
                    }
                }
                else
                {
                    $current['value'] .= $char;
                }
                
            } break;
            
            case 'IN_KEY':
            {
                if (preg_match(COMPARE_RE, $char))
                {
                    $result[] = $current;
                
                    $current = array(
                        'type' => 'COMPARE',
                        'value' => $char,
                    );
                    $result[] = $current;
                    
                    $current = array(
                        'type' => 'VALUE',
                        'value' => '',
                    );
                    
                    $state = 'IN_VALUE';
                }
                else if (preg_match(KEY_CHAR_RE, $char))
                {
                    $current['value'] .= $char;
                }
                else
                {
                    die("Unexpected character '$char' found at index $char_position ($state)\n");
                }
                
            } break;
            
            case 'IN_VALUE':
            {
                if (preg_match(OPERATOR_RE, $char))
                {
                    $result[] = $current;
                
                    $current = array(
                        'type' => 'OPERATOR',
                        'value' => $char,
                    );
                    $result[] = $current;
                    
                    $current = null;
                                        
                    $state = 'START';
                }
                else if (preg_match(VALUE_CHAR_RE, $char))
                {
                    $current['value'] .= $char;
                }
                else
                {
                    die("Unexpected character '$char' found at index $char_position ($state)\n");
                }
                
            } break;
    
            default: {
                die("Bad state '$state' - should never get here\n");
            } break;
            
        }
        
        $char_position += 1;
    }
    
    if (isset($current))
        $result[] = $current;
    
    return $result;
}

function convert_tokens_to_expression_tree($expression_tokens)
{
    $result = array();
    $current = null;
    
    $index = 0;
    while ($index<count($expression_tokens))
    {
        $token = $expression_tokens[$index];
        $token_type = $token['type'];
        
        if ($token_type==='SUBEXPRESSION')
        {
            $subexpression_tokens = $token['value'];
            $subexpression_tree = convert_tokens_to_expression_tree($subexpression_tokens);
        
            $result[] = array(
                'type' => 'SUBEXPRESSION',
                'value' => $subexpression_tree,
            );
            $index += 1;
        }
        else if ($token_type==='OPERATOR')
        {
            $result[] = array(
                'type' => 'OPERATOR',
                'value' => $token['value'],
            );
            $index += 1;
        }
        else if ($token_type==='KEY')
        {
            if (($index+2)>=count($expression_tokens))
                die("Syntax error - missing value at end of expression\n");
                
            $comparison_token = $expression_tokens[$index+1];
            $value_token = $expression_tokens[$index+2];
        
            $result[] = array(
                'type' => 'COMPARISON',
                'key' => $token['value'],
                'comparison' => $comparison_token['value'],
                'value' => $value_token['value'],
            );
            $index += 3;
        }
    
    }

    return $result;
}

function parse_expression_string($expression_string)
{
    $expression_tokens = tokenize_expression_string($expression_string);
    
    $result = convert_tokens_to_expression_tree($expression_tokens);
    
    return $result;
}

function evaluate_token_value($tags, $token)
{
    $token_type = $token['type'];
    switch ($token_type)
    {
        case 'SUBEXPRESSION':
        {
            $result = evaluate_match_expression($tags, $token['value']);
        } break;
        
        case 'COMPARISON':
        {
            $key = $token['key'];
            $comparison = $token['comparison'];
            $value = $token['value'];
            
            if (!isset($tags[$key]))
            {
                $result = false;
            }
            else
            {
                $tag_value = $tags[$key];
                switch ($comparison)
                {
                    case '=':
                        $result = ($tag_value==$value);
                    break;

                    case '!':
                        $result = ($tag_value!=$value);
                    break;
                    
                    case '<':
                        $result = ($tag_value<$value);
                    break;

                    case '>':
                        $result = ($tag_value>$value);
                    break;
                    
                    default:
                        die("Bad comparison operator '$comparison' found\n");
                    break;
                }
            }
        } break;
        
        default:
        {
            die("Bad token type '$token_type' encountered when evaluating expression\n");
        } break;
    }

    return $result;
}

function evaluate_match_expression($tags, $match_expression)
{
    if (empty($match_expression))
        return false;
        
    $first_token = $match_expression[0];

    $result = evaluate_token_value($tags, $first_token);
    
    $index = 1;
    while ($index<count($match_expression))
    {
        if (($index+1)>=count($match_expression))
            die("Expecting an operator (& or |) between all expressions\n");
            
        $operator_token = $match_expression[$index];
        $next_token = $match_expression[$index+1];
        
        if ($operator_token['type']!=='OPERATOR')
            die("Expecting an operator (& or |) between all expressions\n");
            
        $operator_value = $operator_token['value'];
        $next_value = evaluate_token_value($tags, $next_token);
        
        switch ($operator_value)
        {
            case '&':
                $result = ($result&&$next_value);
            break;
            
            case '|':
                $result = ($result||$next_value);
            break;
            
            default:
                die("Bad operator type '$operator' found in expression\n");
            break;
        }
    
        $index += 2;
    }

    return $result;
}

function copy_way_into_osm_ways($way, $input_osm_ways, &$output_osm_ways)
{
    $input_nodes = $input_osm_ways->nodes;
    
    $output_osm_ways->begin_way($way['id']);
 
    foreach ($way['tags'] as $key => $value)
    {
        $output_osm_ways->add_tag($key, $value);
    }

    foreach ($way['nds'] as $nd_ref)
    {
        if (!isset($input_nodes[$nd_ref]))
            continue;
            
        $node = $input_nodes[$nd_ref];
        $output_osm_ways->add_vertex($node['lat'], $node['lon']);
    }
    
    $output_osm_ways->end_way();
}

function extract_ways_matching_keys(&$input_osm_ways, $match_expression, $verbose)
{
    if ($verbose)
        error_log("Starting way filtering");

    $input_nodes = $input_osm_ways->nodes;
    $input_ways = $input_osm_ways->ways;

    $result = new OSMWays();

    $count = 0;
    foreach ($input_ways as $input_way)
    {
        $tags = $input_way['tags'];
        
        if (evaluate_match_expression($tags, $match_expression))
        {
            copy_way_into_osm_ways($input_way, $input_osm_ways, $result);
        }
        
        $count +=1;
        
        if ($verbose&&(($count%1000)===0))
            error_log("Processed $count/".count($input_ways));
    }

    if ($verbose)
        error_log("Finished way filtering");

    return $result;
}

$cliargs = array(
	'inputfile' => array(
		'short' => 'i',
		'type' => 'optional',
		'description' => 'The file to read the input OSM XML data from - if unset, will read from stdin',
        'default' => 'php://stdout',
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the output OSM XML data to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
	'expression' => array(
		'short' => 'e',
		'type' => 'required',
		'description' => 'The logical expression to filter the ways by',
	),
	'verbose' => array(
		'short' => 'v',
		'type' => 'switch',
		'description' => 'Enables debugging output about the job\'s progress',
	),
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_file = $options['outputfile'];
$expression_string = $options['expression'];
$verbose = $options['verbose'];

$expression = parse_expression_string($expression_string);

if ($verbose)
    error_log("Starting load of '$input_file'");

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($input_file) or die("Couldn't read file '$input_file'");
$input_osm_ways->deserialize_from_xml($input_contents);

$output_osm_ways = extract_ways_matching_keys($input_osm_ways, $expression, $verbose);

if ($verbose)
    error_log("Starting save of '$output_file'");
    
$output_contents = $output_osm_ways->serialize_to_xml();
file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");

?>