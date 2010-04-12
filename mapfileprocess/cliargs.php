<?php

// A set of utility functions to make handling command line arguments in PHP easier
// To use them, pass in an array describing the expected arguments, in the form
//
// array(
// '<long name of argument>' => array(
//     'short' => '<single letter version of argument>',
//     'type' => <'switch' | 'optional' | 'required'>,
//     'description' => '<help text for the argument>',
//     'default' => '<value if this is an optional argument and it isn't specified>',
// ),
// ...
// );
//
// If the type is switch, then the result is a boolean that will be false if it's
// not present, or true if it is
//
// If the type is optional, then the result will be the default if it's not present
//
// If the type is required, then the script will print out the usage and exit if it's
// not found
//
// To use, call cliargs_print_usage_and_exit() with the array of argument descriptions
// The result will be an array with the argument names as keys to the found values
//
// by Pete Warden, http://petewarden.typepad.com but freely reusable with no restrictions

function cliargs_print_usage_and_exit($cliargs)
{
	print "Usage:\n";
	
	foreach ($cliargs as $long => $arginfo)
	{
		$short = $arginfo['short'];
		$type = $arginfo['type'];
		$required = ($type=='required');
		$optional = ($type=='optional');
		$description = $arginfo['description'];
		
		print "-$short/--$long ";

		if ($optional||$required)
			print "<value> ";

		print ": $description";

		if ($required)
			print " (required)";

		print "\n";
	}
	
	exit();
}

function cliargs_strstartswith($source, $prefix)
{
   return strncmp($source, $prefix, strlen($prefix)) == 0;
}

function cliargs_get_options($cliargs)
{
	global $argv;
	global $argc;

	$options = array('unnamed' => array());
	for ($index=1; $index<$argc; $index+=1)
	{
		$currentarg = strtolower($argv[$index]);
		$argparts = split('=', $currentarg);
		$namepart = $argparts[0];
		
		if (cliargs_strstartswith($namepart, '--'))
		{
			$longname = substr($namepart, 2);
		}
		else if (cliargs_strstartswith($namepart, '-'))
		{
			$shortname = substr($namepart, 1);
            $longname = $shortname;
			foreach ($cliargs as $name => $info)
			{
				if ($shortname===$info['short'])
				{
					$longname = $name;
					break;
				}
			}
		
		}
		else
		{
			$longname = 'unnamed';
		}
		
		if ($longname=='unnamed')
		{				
			$options['unnamed'][] = $namepart;
		}
		else
		{
			if (empty($cliargs[$longname]))
			{
				print "Unknown argument '$longname'\n";
				cliargs_print_usage_and_exit($cliargs);
			}
			
			$arginfo = $cliargs[$longname];
			$argtype = $arginfo['type'];
			if ($argtype==='switch')
			{
				$value = true;
			}
			else if (isset($argparts[1]))
			{
				$value = $argparts[1];
			}
			else if (($index+1)<$argc)
			{
				$value = $argv[$index+1];
				$index += 1;
			}
			else
			{
				print "Missing value after '$longname'\n";
				cliargs_print_usage_and_exit($cliargs);
			}
			
			$options[$longname] = $value;
		}
	}

	foreach ($cliargs as $longname => $arginfo)
	{
		$type = $arginfo['type'];

		if (!isset($options[$longname]))
		{
			if ($type=='required')
			{
				print("Missing required value for '$longname'\n");
				cliargs_print_usage_and_exit($cliargs);
			}
			else if ($type=='optional')
			{
				if (!isset($arginfo['default']))
					die('Missing default value for '.$long);
					
				$options[$longname] = $arginfo['default'];
			}
			else if ($type=='switch')
			{
				$options[$longname] = false;
			}
		}
	}
	
	return $options;
}

?>