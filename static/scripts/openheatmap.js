function insertOpenHeatMapInto(selector, width, height, source, id)
{
    if (typeof source === 'undefined')
        source = 'http://static.openheatmap.com.s3.amazonaws.com/openheatmap.swf';
    if (typeof id === 'undefined')
        id = 'openheatmap';

    var params = {};
    params.src = source;
    params.id = id;
    params.name = id;
    params.allowscriptaccess = "always";
    params.menu = false;

    $(selector).empty();
    if (typeof width !== 'undefined')
    {
        var widthString = width+'px';
        var heightString = height+'px';
        
        $(selector).width(widthString);
        $(selector).height(heightString);
        
        params.width = widthString;
        params.height = heightString;
    }

    $(selector).flash(params);
}

function getOpenHeatMap(mapName) 
{
    if (typeof mapName === 'undefined')
        mapName='openheatmap';
        
    var isIE = navigator.appName.indexOf("Microsoft") != -1;
    return (isIE) ? window[mapName] : document.getElementById(mapName);
}
