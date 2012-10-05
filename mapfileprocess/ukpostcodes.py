#!/usr/bin/env python

import os, sys, glob, math
import csv
import glob
import uktolatlon

input_path = 'test_data/uk_admin/postcodes/input/'

output_writer = csv.writer(sys.stdout)

for input_file in glob.iglob(input_path+'*'):
  input_reader = csv.reader(open(input_file, 'r'))
  
  for row in input_reader:
    postcode = row[0]
    easting = row[10]
    northing = row[11]
    country_code = row[12]
    nhs_regional_code = row[13]
    nhs_code = row[14]
    county_code = row[15]
    district_code = row[16]
    ward_code = row[17]
    
    coords = uktolatlon.NEtoLL(float(easting), float(northing))
    lat = coords['latitude']
    lon = coords['longitude']
  
    output_writer.writerow([postcode, lat, lon, country_code, nhs_regional_code, nhs_code, county_code, district_code, ward_code])
