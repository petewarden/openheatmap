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

