#!/usr/bin/python

import csv
import sys
import math

reader = csv.reader(open('/Users/petewarden/Downloads/rbcresults1.csv'))
writer = csv.writer(sys.stdout)

is_first = True
current_country = ''
totals = {}
for row in reader:
    if is_first:
        is_first = False
        writer.writerow(['country', 'time', 'value'])
        continue;
        
    country = row[0]
    
    if country != current_country:
        if current_country != '':
            grand_total = 0;
            for k, v in totals.items():
                grand_total += v
            for k, v in totals.items():
                writer.writerow([current_country, k, int(math.floor((v*100)/float(grand_total)))])
        current_country = country
        totals = {}
        
    currency = row[2]
    if currency not in totals:
        totals[currency] = 1
    else:
        totals[currency] += 1