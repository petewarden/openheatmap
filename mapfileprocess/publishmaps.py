#!/usr/bin/env python

import os, sys, glob

uncompressed_path = 'gzipped/uncompressed/'
compressed_path = 'gzipped/compressed/'
s3_path = 's3://static.openheatmap.com/'

for uncompressed_file in glob.iglob(uncompressed_path+'*'):
    base_name = os.path.basename(uncompressed_file)
    command_line = 'gzip -c -9 '
    command_line += uncompressed_file
    command_line += ' > '+compressed_path+base_name
    os.system(command_line)

for compressed_file in glob.iglob(compressed_path+'*'):
    command_line = 's3cmd -P --add-header "Content-Encoding: gzip" put '
    command_line += compressed_file
    command_line += ' '+s3_path
    os.system(command_line)
