
# Run on the data provided by Dharmesh, with the lat/lon positions provided by me, to 
# produce a list of users for each 0.2x0.2 degree block in the US
reader = csv.reader(open('/Users/petewarden/Downloads/us_results_lat_lon.csv', 'rb'))
locations = {}
for row in reader:
    try:
        handle = row[0]
        time = row[1]
        city = row[3]
        state = row[4]
        country = row[5]
        lat = row[6]
        lon = row[7]
    except:
        continue
    if lat == '' or lon == '':
        continue
    lat = round(float(lat)*5)/5
    lon = round(float(lon)*5)/5
    key = str(lat)+','+str(lon)
    if not key in locations:
        locations[key] = []
    locations[key].append({'screen_name':handle, 'creation_date':time, 'city':city, 'state':state})


# Turns the list of users for each location into a time-based animation
import datetime
import dateutil.parser
writer = csv.writer(open('/Users/petewarden/Downloads/us_results_anim.csv', 'wb'))
writer.writerow(['lat', 'lon', 'time', 'value', 'tooltip'])
for key, value in locations.items():
    lat, lon = key.split(',')
    by_month = {}
    for info in value:
        try:
            time_value = dateutil.parser.parse(info['creation_date'])
        except:
            continue
        year = time_value.year
        month = time_value.month
        year = str(year)
        month = str(month)
        info['time_value'] = time_value
        output_key = year+'-'+month
        if not output_key in by_month:
            by_month[output_key] = { 'added_count': 0, 'infos': [] }
        by_month[output_key]['added_count'] += 1
        by_month[output_key]['infos'].append(info)
    running_total = 0
    earliest = None
    for year in range(2006,2011):
        for month in range(1, 13):
            if year == 2006 and month<3:
                continue
            if year==2010 and month>7:
                continue
            output_key = str(year)+'-'+str(month)
            if output_key in by_month:
                month_data = by_month[output_key]
            else:
                month_data = { 'added_count': 0, 'infos': [] }
            running_total += month_data['added_count']
            for info in month_data['infos']:
                if earliest is None:
                    earliest = info
                if info['time_value'] < earliest['time_value']:
                    earliest = info
            if month % 3 != 0:
                continue;
            if running_total < 5:
                continue
            time_string = str(year)
            time_string += '-'
            if month<10:
                time_string += '0'
            time_string += str(month)
            tooltip = str(running_total)+' users'
            if earliest is not None:
                tooltip += ', first was @'+earliest['screen_name']
            else:
                tooltip += ''
            writer.writerow([lat, lon, time_string, running_total, tooltip])


# Walks through the specified twitter ids and grabs their user information
start = 3457
end = (start+150)

import os, sys

for i in range(start, end):
    command_line = 'curl "http://api.twitter.com/1/users/show.json?user_id='
    command_line += str(i)+'" >> userlist.txt'
    os.system(command_line)
    command_line = 'echo "" >> userlist.txt'
    os.system(command_line)
    
    
    
# Takes the raw list of twitter user information, and turns it into a CSV file
import os, sys, csv
try:
    import simplejson as json
except ImportError:
    import json

input = open('/Users/petewarden/Documents/userlist.txt', 'rb')
writer = csv.writer(open('/Users/petewarden/Documents/userlist.csv', 'wb'))

writer.writerow(['id', 'screen_name', 'name', 'created_at', 'location', 'position'])

position = 1
for line in input.readlines():
    try:
        data = json.loads(line)
    except:
        continue
    if not 'id' in data:
        continue
        
    id = data['id']
    screen_name = data['screen_name']    
    name = data['name']
    if name is None:
        name = ''
    created_at = data['created_at']    
    location = data['location']    
    if location is None:
        location = ''
    output = [
        id,
        screen_name.encode('ascii', 'replace'),
        name.encode('ascii', 'replace'),
        created_at.encode('utf-8', 'replace'),
        location.encode('utf-8', 'replace'),
        position
    ]
    writer.writerow(output)
    position += 1






# Turns the twitter user information CSV file into a growth curve for the time period
import os, sys, csv
import datetime
import dateutil.parser

reader = csv.reader(open('/Users/petewarden/Documents/userlist.csv', 'rb'))

total_per_day = {}

line_number = 0
for row in reader:
    line_number += 1
    if line_number == 1:
        continue
    try:
        time_value = dateutil.parser.parse(row[3])
    except:
        continue
    year = time_value.year
    month = time_value.month
    day = time_value.day
    year = str(year)
    if month<10:
        month = '0'+str(month)
    else:
        month = str(month)    
    if day<10:
        day = '0'+str(day)
    else:
        day = str(day)    
    output_key = year+'/'+month+'/'+day
    if not output_key in total_per_day:
            total_per_day[output_key] = { 'total_users': 0, 'increase': 0 }
    total_per_day[output_key]['increase'] += 1

writer = csv.writer(open('/Users/petewarden/Documents/userlist_count.csv', 'wb'))
writer.writerow(['day','total','added_daily'])
running_total = 0
for output_key in sorted(total_per_day.iterkeys()):
    increase = total_per_day[output_key]['increase']
    running_total += increase
    total_per_day[output_key]['total_users'] = running_total
    writer.writerow([output_key, running_total, increase])


# Walks through the range of twitter ids, skipping by 'step', and grabs their user information
start = 16513
step = 10
end = (start+(150*step))

import os, sys, random

for i in range(start, end, step):
    id = i + random.randint(0, step)
    command_line = 'curl "http://api.twitter.com/1/users/show.json?user_id='
    command_line += str(id)+'" >> userlist.txt'
    os.system(command_line)
    command_line = 'echo "" >> userlist.txt'
    os.system(command_line)


# Takes the raw list of twitter user information, and turns it into a CSV file
import os, sys, csv
try:
    import simplejson as json
except ImportError:
    import json

step = 10

input = open('/Users/petewarden/Documents/userlist_'+str(step)+'.txt', 'rb')
writer = csv.writer(open('/Users/petewarden/Documents/userlist_'+str(step)+'.csv', 'wb'))

writer.writerow(['id', 'screen_name', 'name', 'created_at', 'location', 'position'])

position = 1
for line in input.readlines():
    try:
        data = json.loads(line)
    except:
        continue
    if not 'id' in data:
        continue
        
    id = data['id']
    screen_name = data['screen_name']    
    name = data['name']
    if name is None:
        name = ''
    created_at = data['created_at']    
    location = data['location']    
    if location is None:
        location = ''
    output = [
        id,
        screen_name.encode('ascii', 'replace'),
        name.encode('ascii', 'replace'),
        created_at.encode('utf-8', 'replace'),
        location.encode('utf-8', 'replace'),
        position
    ]
    writer.writerow(output)
    position += 1





# Turns the twitter user information CSV file into a growth curve for the time period
import os, sys, csv
import datetime
import dateutil.parser

step = 10

reader = csv.reader(open('/Users/petewarden/Documents/userlist_'+str(step)+'.csv', 'rb'))

total_per_month = {}

line_number = 0
for row in reader:
    line_number += 1
    if line_number == 1:
        continue
    try:
        time_value = dateutil.parser.parse(row[3])
    except:
        continue
    year = time_value.year
    month = time_value.month
    day = time_value.day
    year = str(year)
    if month<10:
        month = '0'+str(month)
    else:
        month = str(month)    
    if day<10:
        day = '0'+str(day)
    else:
        day = str(day)    
    output_key = year+'/'+month
    if not output_key in total_per_month:
            total_per_month[output_key] = { 'total_users': 0, 'increase': 0 }
    total_per_month[output_key]['increase'] += (1*step)

writer = csv.writer(open('/Users/petewarden/Documents/userlist_count_'+str(step)+'.csv', 'wb'))
writer.writerow(['month','total','added_monthly'])
running_total = 0
for output_key in sorted(total_per_month.iterkeys()):
    increase = total_per_month[output_key]['increase']
    running_total += increase
    total_per_month[output_key]['total_users'] = running_total
    writer.writerow([output_key, running_total, increase])

# Walks through the range of twitter ids, skipping by 'step', and grabs their user information
# After 2am on Nov 22nd 2006 Twitter appears to switch to incrementing the ids by 10, so that
# the only valid ids are ones ending with the last digit 3
start = 78013
step = 100
end = (start+(150*step))

import os, sys, random

for i in range(start, end, step):
    id = i + (random.randint(0, (step/10)-1)*10)
    command_line = 'curl "http://api.twitter.com/1/users/show.json?user_id='
    command_line += str(id)+'" >> userlist.txt'
    os.system(command_line)
    command_line = 'echo "" >> userlist.txt'
    os.system(command_line)

# Takes the raw list of company information, and turns it into a CSV file
import os, sys, csv
try:
    import simplejson as json
except ImportError:
    import json

step = 10

input = open('/Users/petewarden/Documents/userlist_'+str(step)+'.txt', 'rb')
writer = csv.writer(open('/Users/petewarden/Documents/userlist_'+str(step)+'.csv', 'wb'))

writer.writerow(['id', 'screen_name', 'name', 'created_at', 'location', 'position'])

position = 1
for line in input.readlines():
    try:
        data = json.loads(line)
    except:
        continue
    if not 'id' in data:
        continue

