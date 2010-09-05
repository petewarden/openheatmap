#!/usr/bin/env python

import os, sys, glob
try:
    import simplejson as json
except ImportError:
    import json

input_path = 'mapfileprocess/csv/input/'
expected_path = 'mapfileprocess/csv/expected/'
output_path = 'mapfileprocess/csv/actual/'

try:
    wildcard = sys.argv[1]
except:
    wildcard = '*.csv'

any_found = False
for input_file in glob.iglob(input_path+wildcard):
    any_found = True
    base_name = os.path.basename(input_file)
    expected_file = expected_path+base_name
    if os.access(expected_file, os.F_OK):
        continue
    output_file = output_path+base_name
    command_line = 'website/translatetest.php'
    command_line += ' -i '+input_file
    command_line += ' -o '+expected_file
    command_stdin, command_stdout = os.popen2(command_line)
    command_output = ''
    for line in command_stdout.readlines():
        command_output += line
    command_data = json.loads(command_output)
    if len(command_data['errors']) > 0:
        print 'Creation of '+base_name+' failed with:'
        for error in command_data['errors']:
            print '    '+error['message']+' on row '+str(error['row'])
    else:
        print base_name+' created'

if not any_found:
    print 'Failed: No input files found'

for expected_file in glob.iglob(expected_path+wildcard):
    base_name = os.path.basename(expected_file)
    input_file = input_path+base_name
    output_file = output_path+base_name
    command_line = 'website/translatetest.php'
    command_line += ' -i '+input_file
    command_line += ' -e '+expected_file
    command_line += ' -o '+output_file
    command_stdin, command_stdout = os.popen2(command_line)
    command_output = ''
    for line in command_stdout.readlines():
        command_output += line
    command_data = json.loads(command_output)
    
    if len(command_data['errors']) > 0:
        print base_name+' failed with:'
        for error in command_data['errors']:
            print '    '+error['message']+' on row '+str(error['row'])
    else:
        print base_name+' succeeded'
