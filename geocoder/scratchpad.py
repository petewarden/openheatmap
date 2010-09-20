
import csv, os, os.path

root_folder = '/Users/petewarden/Projects/openheatmap/website/geocoder/'
reader = csv.reader(open(root_folder+'data/sources/worldcitiespop.csv', 'rb'))

Country,City,AccentCity,Region,Population,Latitude,Longitude

previous_country = ''
line_index = 0
for row in reader:
    line_index += 1
    if line_index<2:
        continue
    try:
        country = row[0]
        city = row[1]
        state_code = row[3]
        population = row[4]
        lat = row[5]
        lon = row[6]
    except:
        continue
    if country != previous_country:
        country_folder = root_folder+'data/countries/'+country
        if not os.path.exists(country_folder):
            os.mkdir(country_folder)
        country_file = country_folder+'/cities.csv'
        writer = csv.writer(open(country_file, 'wb'))
        writer.writerow(['names', 'population', 'state_code', 'lat', 'lon'])
        previous_country = country
    writer.writerow([city, population, state_code, lat, lon ])


