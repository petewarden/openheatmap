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
    .html('<i>Upload</i>');

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

        $('#guidance_message').html('There were some errors uploading the data. You can try uploading again once you\'ve investigated them.');
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
    
    return dist
}

