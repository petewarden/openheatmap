var _kmq = _kmq || [];
function _kms(u){
    setTimeout(function(){
        var s = document.createElement('script'); var f = document.getElementsByTagName('script')[0]; s.type = 'text/javascript'; s.async = true;
        s.src = u; f.parentNode.insertBefore(s, f);
    }, 1);
}
_kms('//i.kissmetrics.com/i.js');_kms('//doug1izaerwt3.cloudfront.net/7e2410e274ede95acd5551c3260ff85fba13f337.1.js');

var g_hasSetResultMessage = false;

function onUploadComplete(responseString64)
{
    var messageHtml = '';
    var wasSuccessful = false;
    if (responseString64 === '')
    {
        messageHtml = 'There was an unknown error trying to upload this CSV file. Please contact <a href="mailto:pete@petewarden.com">pete@petewarden.com</a> to report this bug if you see this repeatedly';
        wasSuccessful = false;
    }
    else
    {
        var responseString = $.base64.decode(responseString64);
        
        var response = eval('('+responseString+')');

        wasSuccessful = ((response.output_id!=='')&&
            (response.errors.length===0));
                        
        var errors = response.errors;
        
        if (errors.length>0)
        {
            messageHtml += '<i>';
            messageHtml += '<b>';
            messageHtml += 'Errors:';
            messageHtml += '</b>';
            messageHtml += '</i>';
            messageHtml += '<br>';
            
            messageHtml += formatErrors(errors);
        }
        
        var warnings = response.warnings;
        
        if (warnings.length>0)
        {
            messageHtml += '<i>';
            messageHtml += '<b>';
            messageHtml += 'Warnings:';
            messageHtml += '</b>';
            messageHtml += '</i>';
            messageHtml += '<br>';

            messageHtml += formatErrors(warnings);
        }
        
    }

    // enable upload button
    $('#upload_button')
    .html('Upload');

    if ((messageHtml!=='')||g_hasSetResultMessage)
    {
        $('#result_message').html(messageHtml);
        g_hasSetResultMessage = true;
    }

    if (wasSuccessful)
    {
        $.cookie('last_output_id', response.output_id);
        $.cookie('last_editing_password', response.editing_password);

        $('#next_button').show();
        $('#upload_button').hide();
        
        $('#guidance_message').html('Your data was uploaded successfully, you can move onto the next step.');
    }
    else
    {
        $('#next_button').hide();
        $('#upload_button').show();

        $('#guidance_message').html('There were some errors uploading the data.<br> Email the file to <a href="mailto:pete@mailana.com">pete@mailana.com</a> and I\'ll be happy to investigate what\'s going wrong.<br> I\'m so keen to get bug reports, I\'ll even send you a t-shirt!');

        SnapABug.startChat('<b>Pete Warden:</b> Sorry your upload didn\'t work!<br> If I\'m online I\'d be happy to look at your spreadsheet and figure out what went wrong');
    }
}

function formatErrors(errors)
{
    var messageMap = {};

    var result = '';

    for (var index in errors)
    {
        var error = errors[index];
        var message = error.message;
        var row = error.row;

        if (row<=0)
        {
            result += message;
            result += '<br>';
        }
        else
        {
            if (typeof messageMap[message] === 'undefined')
                messageMap[message] = [];
                
            messageMap[message].push(row);
        }
    }

    for (var message in messageMap)
    {
        var rows = messageMap[message];
        
        var prefix = '';
        if (rows.length===1)
        {
            prefix += 'Row ';
            prefix += rows[0];
        }
        else
        {
            rows.sort(function(a,b){return a - b});
            
            var sequences = [];
            var currentSequence = null;
            for (var rowIndex in rows)
            {
                var currentRow = rows[rowIndex];
                
                if ((currentSequence===null)||
                    (currentRow!=(currentSequence.end+1)))
                {
                    if (currentSequence!==null)
                        sequences.push(currentSequence);
                    
                    currentSequence = {
                        start: currentRow,
                        end: currentRow
                    };
                }
                else
                {
                    currentSequence.end = currentRow;
                }
            
            }
            
            if (currentSequence!==null)
                sequences.push(currentSequence);
            
            var sequenceStrings = [];
            for (var sequenceIndex in sequences)
            {
                var sequence = sequences[sequenceIndex];
                var start = sequence.start;
                var end = sequence.end;
                
                var sequenceString = '';
                if (start===end)
                    sequenceString += start;
                else
                    sequenceString += start+'-'+end;
                
                sequenceStrings.push(sequenceString);
            }
            
            prefix += 'Rows ';
            prefix += sequenceStrings.join(', ');
        }
        
        result += prefix;
        result += ': ';
        result += message;
        result += '<br>';
    }
    
    return result;
}

function htmlspecialchars(string)
{ 
    return $('<span>').text(string).html()
}

function onStartClick()
{
    $('#choose_dialog').dialog({
        modal: true,
        draggable: false,
        resizable: false,
        title: 'Where is your spreadsheet?',
        width: 600,
        autoOpen: false
        });
    $('#choose_dialog').dialog('open');
}

function onVideoClick()
{
    $('#choose_dialog').dialog('close');
    $('#video_dialog').dialog({
        modal: true,
        draggable: false,
        resizable: false,
        title: 'What\'s this all about?',
        width: 700,
        height: 450,
        autoOpen: true
        });
}

function updateOpenHeatMapMessage(event)
{
    if (!g_areWaysLoaded||!g_areValuesLoaded)
    {
        $('#openheatmap_message').html('Loading map data <img src="http://static.openheatmap.com/images/loading.gif"/>');
        return;
    }

    var map = $.getOpenHeatMap();
    var infoHTML = '';

    if (g_mapSettings.general.type=='colored areas')
    {
        map.removeAllPopups();
        var valueHeaders = map.getValueHeaders();
        
        var hasState = false;
        var hasCounty = false;
        for (var valueHeaderIndex in valueHeaders)
        {
            var valueHeader = valueHeaders[valueHeaderIndex];
            if (valueHeader==='state_code')
                hasState = true;
                
            if (valueHeader==='county_code')
                hasCounty = true;
        }

        var ways = map.getWaysContainingLatLon(event.lat, event.lon);
        var waysLength = ways.length;
        
        infoHTML += g_defaultMessage;
        
        if (waysLength>=1)
        {
            var popupHTML = '';
            var wayNames = [];
            for (var wayIndex in ways)
            {
                var way = ways[wayIndex];
                
                var wayTags = way.tags;
                
                var areaName;
                if (hasState&&!hasCounty)
                {
                    var stateCode = wayTags.state_code;
                    areaName = g_fipsToStateName[stateCode];
                }
                else if (hasState&&hasCounty)
                {
                    var stateCode = wayTags.state_code;
                    var stateName = g_fipsToStateName[stateCode];
                    var countyName = wayTags.name;
                    areaName = countyName+', '+stateName;
                }
                else if (typeof wayTags.name !== 'undefined')
                {
                    areaName = wayTags.name;
                }
                else if (typeof wayTags.zip_code !== 'undefined')
                {
                    areaName = wayTags.zip_code;
                }
                
                var description = areaName;

                var wayId = way.id;
                var value = map.getValueForWayId(wayId);
                
                if (value!==null)
                {
                    description += ' - ';
                    var valueAsNumber = Number(value);
                    if (valueAsNumber<0.1)
                        description += valueAsNumber.toPrecision(3);
                    else
                        description += valueAsNumber.toFixed(2);
                }
                else
                {
                    description += ' - NA';
                }
                
                wayNames.push(description);
            }
        
            popupHTML += wayNames.join(',');

            map.addPopupAtScreenPosition(event.x, event.y, popupHTML);  
        }

    }
    else
    {
        map.removeAllPopups();

        infoHTML = g_defaultMessage;

        if (g_mapSettings.component.is_value_distance)
        {
            var pickRadius = g_mapSettings.component.point_blob_radius;
        
            for (var pass=0; pass<6; pass+=1)
            {        
                var points = map.getValuePointsNearLatLon(event.lat, event.lon, pickRadius);
                var pointsLength = points.length;
                if (pointsLength>=1)
                    break;
                    
                pickRadius *= 2;
            }
            
            var closestDistance = 10000000;
            var closestName = '';
            for (var pointIndex in points)
            {
                var point = points[pointIndex];
                
                var distance = distanceFromLatLon(event.lat, event.lon, point.lat, point.lon, 'M');                
                if (distance<closestDistance)
                {
                    closestDistance = distance;
                    if (typeof point.name !== 'undefined')
                        closestName = point.name;
                }
            }

            if (closestDistance<10000000)
            {
                var popupHTML = '';
                popupHTML += closestDistance.toFixed(1)+' miles';

                if (closestName!=='')
                    popupHTML += ' to '+closestName;

                map.addPopup(event.lat, event.lon, popupHTML);        
            }

        }
        else
        {
            var pickRadius = g_mapSettings.component.point_blob_radius;
        
            var points = map.getValuePointsNearLatLon(event.lat, event.lon, pickRadius);
            var pointsLength = points.length;
            if (pointsLength>=1)
            {
                var pointNames = [];
                var closestIndex;
                
                if (pointsLength===1)
                {
                    closestIndex = 0;
                }
                else
                {
                    var closestDistance = 10000000;
                    for (var pointIndex in points)
                    {
                        var point = points[pointIndex];
                        var deltaLat = (event.lat-point.lat);
                        var deltaLon = (event.lon-point.lon);
                        var distanceSquared = ((deltaLat*deltaLat)+(deltaLon*deltaLon));
                        
                        if (distanceSquared<closestDistance)
                        {
                            closestDistance = distanceSquared;
                            closestIndex = pointIndex;
                        }
                    }
                    
                }

                var point = points[closestIndex];

                var popupHTML = '';
                for (var key in point)
                {
                    if ((key==='lat')||(key==='lon'))
                        continue;

                    var value = point[key];
                    if (value==='')
                        continue;
                    
                    popupHTML += key;
                    popupHTML += ': ';
                    popupHTML += value;
                    popupHTML += ' ';
                }

                map.addPopup(point.lat, point.lon, popupHTML);        
            }
        }
    }

    $('#openheatmap_message').html(infoHTML);
    
    return true;
}

// From http://www.zipcodeworld.com/samples/distance.js.html
function distanceFromLatLon(lat1, lon1, lat2, lon2, unit) 
{
    var radlat1 = Math.PI * lat1/180
    var radlat2 = Math.PI * lat2/180
    var radlon1 = Math.PI * lon1/180
    var radlon2 = Math.PI * lon2/180
    var theta = lon1-lon2
    var radtheta = Math.PI * theta/180
    
    var dist = Math.sin(radlat1) * Math.sin(radlat2) + Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta);
    dist = Math.acos(dist)
    dist = dist * 180/Math.PI
    dist = dist * 60 * 1.1515
    
    if (unit=="K") { dist = dist * 1.609344 }
    if (unit=="N") { dist = dist * 0.8684 }
    
    return dist;
}

function onEmbed(event) {

    var location = window.location.href;
    location = location.replace('view.html', 'embed.html');
    
    var embedCode = '<iframe '
        +'width="600" '
        +'height="450" '
        +'src="'+location+'" '
        +'></iframe>';
    
    var inputElement = $('<input type="text" size="80">');
    inputElement.val(embedCode);
    
    $('#ck_embedthis_span')
    .empty()
    .append($('<br>'))
    .append(inputElement);

    return false;
};    

var g_openHeatMap = null;

function commonOnMapCreated()
{
    var mapSettings = g_mapSettings;
    
    var openHeatMap = $.getOpenHeatMap();
    
    g_openHeatMap = openHeatMap;
    
    setMapSize(mapSettings.general.width, mapSettings.general.height);

    openHeatMap.bind('mousemove', 'onMouseMove');
    openHeatMap.bind('datachange', 'onDataChange');
    openHeatMap.bind('waysload', 'onWaysLoad');
    openHeatMap.bind('valuesload', 'onValuesLoad');
    openHeatMap.bind('error', 'onError');

    if (mapSettings.general.author_name!=='')
    {
        var authorHtml = '<i>Map created by ';
        if (mapSettings.general.author_url!=='')
        {
            authorHtml += '<a rel="nofollow" href="http://';
            authorHtml += mapSettings.general.author_url;
            authorHtml += '">';
        }
        authorHtml += mapSettings.general.author_name;
        if (mapSettings.general.author_url!=='')
        {
            authorHtml += '</a>';
        }
        authorHtml += '</i>';
        $('#author_message').html(authorHtml);
    }

    document.title = 'OpenHeatMap - '+mapSettings.component.title_text;

    if (mapSettings.component.title_text!=='')
        $('#title_text').text(mapSettings.component.title_text);
    else
        $('#title_text').html('&nbsp;');

    updateMapWithSettings(mapSettings);

    loadMapGeometryForSettings(mapSettings);

    if ((typeof mapSettings.general.external_source === 'undefined')||
        (mapSettings.general.external_source === ''))
    {
        var valuesUrl = '/data/';
        valuesUrl += g_currentMapId;
        valuesUrl += '/values.csv';    
    }
    else
    {
        var valuesUrl = 'retrieveonlinedata.php';
        valuesUrl += '?map='+g_currentMapId;
        
        if ($(document).getUrlParam('refresh')==='true')
            valuesUrl += '&refresh=true';
    }
    
    openHeatMap.loadValuesFromFile(valuesUrl);
}

function onWaysLoad(event)
{
    g_areWaysLoaded = true;

    if (g_areWaysLoaded&&g_areValuesLoaded)
        $('#openheatmap_message').html(g_defaultMessage);
    
    return true;
}

function onValuesLoad(event)
{
    g_areValuesLoaded = true;
    
    if (g_areWaysLoaded&&g_areValuesLoaded)
        $('#openheatmap_message').html(g_defaultMessage);

    var openHeatMap = $.getOpenHeatMap();

    if ((typeof g_mapSettings.general.animation_time !== 'undefined') &&
        (g_mapSettings.general.animation_time != null) &&
        (g_mapSettings.general.animation_time != ''))
    {
        openHeatMap.setAnimationTime(g_mapSettings.general.animation_time);
    }
    
    return true;
}

function onError(message)
{
    setInfoDisplayHTML(message);
}

g_lastEvent = null;

function onMouseMove(event)
{
    g_lastEvent = event;

    updateOpenHeatMapMessage(event);
    
    return true;
}

function onDataChange()
{
    updateOpenHeatMapMessage(g_lastEvent);
    
    return true;
}

function updateMapWithSettings(mapSettings)
{
    var openHeatMap = $.getOpenHeatMap();

    for (var key in mapSettings.component)
    {
        if (key==='title_text')
            continue;
            
        var value = mapSettings.component[key];
        openHeatMap.setSetting(key, value);
    }
    
    openHeatMap.setColorGradient(mapSettings.general.gradient_with_alpha);
    
    openHeatMap.setWayDefault('color', 0xf0f0f0);
}

function loadMapGeometryForSettings(mapSettings)
{
    var openHeatMap = $.getOpenHeatMap();

    setMapSize(mapSettings.general.width, mapSettings.general.height);

    openHeatMap.setLatLonViewingArea(
        mapSettings.general.top_lat,
        mapSettings.general.left_lon,
        mapSettings.general.bottom_lat,
        mapSettings.general.right_lon
    );
 
    openHeatMap.removeAllInlays();   
    var inlays = mapSettings.general.inlays;
    for (var inlayIndex in inlays)
    {
        var inlay = inlays[inlayIndex];
        openHeatMap.addInlay(
            inlay.left_x,
            inlay.top_y,
            inlay.right_x,
            inlay.bottom_y,
            inlay.top_lat,
            inlay.left_lon,
            inlay.bottom_lat,
            inlay.right_lon
        );
    }
        

    $('#openheatmap_message').html('Loading map data <img src="http://static.openheatmap.com/images/loading.gif"/>');
    g_areWaysLoaded = false;
    
    openHeatMap.removeAllWays();

    if (mapSettings.general.ways_file!='')
        openHeatMap.loadWaysFromFile(mapSettings.general.ways_file);
    else
        g_areWaysLoaded = true;
}

function setMapSize(width, height)
{
    var openHeatMap = $.getOpenHeatMap();
    
    openHeatMap.setSize(width, height);
    
    $('#openheatmap_container')
    .width(width+'px')
    .height(height+'px');

    $('#openheatmap')
    .width(width+'px')
    .height(height+'px');
}

function createGradientFromSettings()
{
    if (typeof g_mapSettings.general.gradient_with_alpha!== 'undefined')
        return;

    var gradient = [];
    gradient.push(g_mapSettings.general.gradient_start_color);
    gradient.push(g_mapSettings.general.gradient_mid_color);
    gradient.push(g_mapSettings.general.gradient_end_color);
      
    var gradientWithAlpha = [];

    if (g_mapSettings.general.type!='colored areas')
    {
        gradientWithAlpha.push('#00000000');
    }

    for (var gradientIndex in gradient)
    {
        var color = gradient[gradientIndex];
        color = color.replace('#', '');
        
        var alpha = 255;        
        var colorString = '#'+alpha.toString(16)+color;
        
        gradientWithAlpha.push(colorString);
    }
    
    g_mapSettings.general.gradient_with_alpha = gradientWithAlpha;
}

function createColorKeyHTML(mapSettings)
{
    var result = '';
    
    var keyDescription = 'Color Key';
    if (typeof mapSettings.general.key_description !== 'undefined')
        keyDescription = mapSettings.general.key_description;

    result += '<ul class="key">';
    result += '<li class="keyUnits">';
    result += keyDescription;
    result += '</li>';
    
    if (!g_mapSettings.component.is_value_distance)
    {
        result += '<li ';
        result += 'style="background-color: ';
        result += mapSettings.general.gradient_start_color+';';
        result += '">';
        result += parseInt(mapSettings.component.gradient_value_min).toPrecision(3);
        result += '</li>';

        var midValue = (parseInt(mapSettings.component.gradient_value_min)+
            parseInt(mapSettings.component.gradient_value_max))/2;

        result += '<li ';
        result += 'style="background-color: ';
        result += mapSettings.general.gradient_mid_color+';';
        result += '">';
        result += parseInt(midValue).toPrecision(3);
        result += '</li>';

        result += '<li ';
        result += 'style="background-color: ';
        result += mapSettings.general.gradient_end_color+';';
        result += '">';
        result += parseInt(mapSettings.component.gradient_value_max).toPrecision(3);
        result += '</li>';
    }
    
    result += '</ul>';
    
    return result;
}

g_fipsToStateName = {
    '01': 'Alabama',
    '29': 'Missouri',
    '02': 'Alaska',
    '30': 'Montana',
    '04': 'Arizona',
    '31': 'Nebraska',
    '05': 'Arkansas',
    '32': 'Nevada',
    '06': 'California',
    '33': 'New Hampshire',
    '08': 'Colorado',
    '34': 'New Jersey',
    '09': 'Connecticut',
    '35': 'New Mexico',
    '10': 'Delaware',
    '36': 'New York',
    '11': 'Washington DC',
    '37': 'North Carolina',
    '12': 'Florida',
    '38': 'North Dakota',
    '13': 'Georgia',
    '39': 'Ohio',
    '40': 'Oklahoma',
    '41': 'Oregon',
    '15': 'Hawaii',
    '42': 'Pennsylvania',
    '16': 'Idaho',
    '44': 'Rhode Island',
    '17': 'Illinois',
    '45': 'South Carolina',
    '18': 'Indiana',
    '46': 'South Dakota',
    '19': 'Iowa',
    '47': 'Tennessee',
    '20': 'Kansas',
    '48': 'Texas',
    '21': 'Kentucky',
    '49': 'Utah',
    '22': 'Louisiana',
    '50': 'Vermont',
    '23': 'Maine',
    '51': 'Virginia',
    '24': 'Maryland',
    '53': 'Washington',
    '25': 'Massachusetts',
    '54': 'West Virginia',
    '26': 'Michigan',
    '55': 'Wisconsin',
    '27': 'Minnesotta',
    '56': 'Wyoming',
    '28': 'Mississippi'
};
