/**
 * This script provides the interface to the OpenHeatMap rendering component
 *
 * To use it, call $('#yourelement').insertOpenHeatMap({ width: 800, height:400}) to add the
 * component to your page, and then call getOpenHeatMap() to grab the API
 * object to continue construction
 *
 *
 **/

g_openHeatMapObjects = {};

(function($) {
 
    $.fn.insertOpenHeatMap = function(settings) {
        var defaults = {
            source: 'http://static.openheatmap.com.s3.amazonaws.com/openheatmap.swf',
            mapName: 'openheatmap',
            width: 800,
            height: 600
        };
 
        if (settings) 
            settings = $.extend(defaults, settings);
        else
            settings = defaults;
 
        this.each(function() {

            $(this).empty();

			var canvas = $(
                '<canvas '
                +'width="'+settings.width+'" '
                +'height="'+settings.height+'"'
                +'id="'+settings.mapName+'_canvas"'
                +'"></canvas>'
            );

            var openHeatMap = new OpenHeatMap(canvas);
            
            openHeatMap.setSize(settings.width, settings.height);

            g_openHeatMapObjects[settings.mapName] = openHeatMap;

            $(this).append(canvas);
            
            onMapCreated();
        });
 
        return this;
    };
    
    $.getOpenHeatMap = function(mapName) {
        if (!mapName)
            mapName = 'openheatmap';
            
        return g_openHeatMapObjects[mapName];
    };
 
})(jQuery);

function OpenHeatMap(canvas)
{
    this.__constructor = function(canvas)
    {
        this.initializeMembers();

        this.setSize(800, 600);

        this.createViewerElements();

        this.setLatLonViewingArea(80, -180, -75, 180);

        this._canvas = canvas;

        this._canvas
        .bind('click', this, this.mapMouseClickHandler)
        .bind('dblclick', this, this.mapMouseDoubleClickHandler)
        .bind('mousedown', this, this.mapMouseDownHandler)
        .bind('mousemove', this, this.mapMouseMoveHandler)
        .bind('mouseout', this, this.mapMouseOutHandler)
        .bind('mouseover', this, this.mapMouseOverHandler)
        .bind('mouseup', this, this.mapMouseUpHandler);

        _dirty = true;

        var instance = this;

        window.setInterval(function() { instance.doEveryFrame(); }, 30);
    };
    
    this.initializeMembers = function() {
    
        this._mainCanvas = null;
        this._dirty = true;
        this._redrawCountdown = 0;

        this._wayDefaults = {
            color: 0x000000,
            alpha: 1.0,
            line_thickness: 0
        };

        this._colorGradient = [
            {alpha: 0x00, red: 0x00, green: 0xb0, blue: 0x00},
            {alpha: 0x7f, red: 0xe0, green: 0xe0, blue: 0x00},
            {alpha: 0xff, red: 0xff, green: 0x00, blue: 0x00},
        ];

        this._onClickFunction = null;
        this._onDoubleClickFunction = null;
        this._onMouseDownFunction = null;
        this._onMouseUpFunction = null;
        this._onMouseOverFunction = null;
        this._onMouseOutFunction = null;
        this._onMouseMoveFunction = null;
        this._onFrameRenderFunction = null;
        this._onDataChangeFunction = null;
        this._onWaysLoadFunction = null;
        this._onValuesLoadFunction = null;
        this._onErrorFunction = null;
        this._onViewChangeFunction = null;

        this._nodes = {};
        this._ways = {};

        this._waysLoader;
        this._waysFileName = "";

        this._valuesLoader;
        this._valuesFileName = "";

        this._valueHeaders = null;
        this._valueData = null;
        this._timeColumnIndex;
        this._valueColumnIndex;

        this._smallestValue;
        this._largestValue;

        this._hasTime = false;
        this._frameTimes = [];
        this._frameIndex = 0;

        this._tagMap = {};

        this._latLonToXYMatrix = new Matrix();
        this._xYToLatLonMatrix = new Matrix();

        this._worldBoundingBox = new Rectangle();
        this._waysGrid = null;

        this._timelineControls = null;

        this._inlays = [];

        this._valuesDirty = false;

        this._mainBitmapTopLeftLatLon = null;
        this._mainBitmapBottomRightLatLon = null;

        this._isDragging = false;
        this._lastDragPosition = null;
        this._lastClickTime = 0;

        this._zoomSlider = null;

        this._foundTimes = {};

        this._hasBitmapBackground = false;

        this._hasPointValues = false;
        this._latitudeColumnIndex = -1;
        this._longitudeColumnIndex = -1;

        this._pointsGrid = null;

        this._mapTiles = {};

        this._settings = {
            width: 800,
            height: 600,
            zoom_slider_power: 5.0,
            zoomed_out_degrees_per_pixel: -180,
            zoomed_in_degrees_per_pixel: -0.01,
            is_gradient_value_range_set: false,
            gradient_value_min: 0,
            gradient_value_max: 0,
            point_blob_radius: 0.001,
            point_blob_value: 1.0,
            credit_text: '<a href="http://openheatmap.com"><u>OpenHeatMap</u></a>',
            credit_color: '0x303030',
            title_text: '',
            title_size: 15,
            title_color: '0x000000',
            title_background_color: '0xd0e0ff',
            title_background_alpha: 1.0,
            time_range_start: null,
            time_range_end: null,
            force_outlines: false,
            show_map_tiles: false,
            map_server_root: 'http://a.tile.openstreetmap.org/',
            map_tile_width: 256,
            map_tile_height: 256,
            map_tile_origin_lat: 85.05112877980659,
            map_tile_origin_lon: -180,
            map_tile_match_factor: 1.2,
            world_lat_height: -170.102258,
            world_lon_width: 360,
            inlay_border_color: 0x000000,
            ocean_color: 0xd0e0ff,
            information_alpha: 1.0,
            is_point_blob_radius_in_pixels: false,
            point_bitmap_scale: 2,
            tab_height: 15
        };

        this._lastSetWayIds = {};

        this._credit = null;
        this._title = null;

        this._popups = [];

        this._informationLayerCanvas = null;

        this._mapTilesDirty = true;
	
        this._tabColumnIndex = -1;
        this._hasTabs = false;
        this._tabNames = [];
        this._tabInfo = {};
        this._selectedTabIndex = 0;
        this._hoveredTabIndex = -1;
	
        this._pointBlobCanvas = null;
        this._pointBlobBitmapWidth = 0;
        this._pointBlobBitmapHeight = 0;
        this._pointBlobTileX = 0;
        this._pointBlobTileY = 0;
        this._pointBlobStillRendering = false;    
        
        this._viewerElements = [];
        this._plusImage = null;
        this._minusImage = null;
        
        this._timelineSlider = null;
        this._timelineText = null;
        this._timelineButton = null;
    };

    this.getXYFromLatLon = function(latLon, latLonToXYMatrix) {
        var latLonPoint = new Point(latLon.lon, this.latitudeToMercatorLatitude(latLon.lat));
	
        var result = latLonToXYMatrix.transformPoint(latLonPoint);

        return result;
    };

    this.getLatLonFromXY = function(xYPoint, xYToLatLonMatrix) {
        var latLonPoint = xYToLatLonMatrix.transformPoint(xYPoint);
	
        var result = {
			lat: this.mercatorLatitudeToLatitude(latLonPoint.y),
			lon: latLonPoint.x
        };
	
        return result;
    };
    
    this.setWayDefault = function(propertyName, propertyValue)
    {
        this._wayDefaults[propertyName] = propertyValue;
        this._dirty = true;
    };

    this.getWayProperty = function(propertyName, wayInfo)
    {
        if ((typeof wayInfo !== 'undefined') && (wayInfo.hasOwnProperty(propertyName)))
            return wayInfo[propertyName];
        else if (this._wayDefaults.hasOwnProperty(propertyName))
            return this._wayDefaults[propertyName];
        else
            return null;
    };

    this.doTagsMatch = function(tags, lineInfo)
    {
        var result = false;
        if (tags === null)
        {
            result = true;
        }
        else
        {
            if (lineInfo.hasOwnProperty('tags'))
            {
                var myTags = lineInfo.tags;
                
                for (var myTagIndex in myTags)
                {
                    var myTag = myTags[myTagIndex];
                    for (var tagIndex in tags)
                    {
                        var tag = tags[tagIndex];
                        if (myTag === tag)
                            result = true;
                    }
                }
                
            }
        }
            
        return result;
    };

    this.getTagsFromArgument = function(tagsArgument)
    {
        if (tagsArgument === null)
            return null;
		
        if (tagsArgument instanceof Array)
            return tagsArgument;
        else
            return [ tagsArgument ];
    };

    this.setEventHandler = function(eventName, functionName) {
        eventName = eventName.toLowerCase();
	
        if (eventName == 'click')
            this._onClickFunction = functionName;
        else if (eventName == 'doubleclick')
            this._onDoubleClickFunction = functionName;
        else if (eventName == 'mousedown')
            this._onMouseDownFunction = functionName;
        else if (eventName == 'mouseup')
            this._onMouseUpFunction = functionName;
        else if (eventName == 'mouseover')
            this._onMouseOverFunction = functionName;
        else if (eventName == 'mouseout')
            this._onMouseOutFunction = functionName;
        else if (eventName == 'mousemove')
            this._onMouseMoveFunction = functionName;
        else if (eventName == 'framerender')
            this._onFrameRenderFunction = functionName;
        else if (eventName == 'datachange')
            this._onDataChangeFunction = functionName;
        else if (eventName == 'waysload')
            this._onWaysLoadFunction = functionName;
        else if (eventName == 'valuesload')
            this._onValuesLoadFunction = functionName;
        else if (eventName == 'error')
            this._onErrorFunction = functionName;
        else if (eventName == 'viewchange')
            this._onViewChangeFunction = functionName;
        else
            this.logError( 'Unknown event name passed to MapRender::setEventHandler - "'+
                eventName+'" (expected click, doubleclick, mousedown, mouseup, mouseover, mouseout, framerender, datachange, waysload, valuesload, error or viewchange)');
    };

    this.setSize = function(width, height) {
        this.width = width;
        this.height = height;
        
        this._settings.width = width;
        this._settings.height = height;
        
    //	if (_timelineControls !== null)
    //		_timelineControls.setWidth(width);

        this._mainCanvas = this.createCanvas(width, height);

        this._informationLayerCanvas = this.createCanvas(width, height);
/*
        repositionMoveableElements();
*/        
        _dirty = true;	
    };
    
    this.setLatLonViewingArea = function(topLat, leftLon, bottomLat, rightLon) {
        topLat = this.latitudeToMercatorLatitude(topLat);
        bottomLat = this.latitudeToMercatorLatitude(bottomLat);
        
        var widthLon = (rightLon-leftLon);
        var heightLat = (bottomLat-topLat);
        
        var scaleX = (this._settings.width/widthLon);
        var scaleY = (this._settings.height/heightLat);

        var newMatrix = new Matrix();
        newMatrix.translate(-leftLon, -topLat);
        newMatrix.scale(scaleX, scaleY);

        this.setLatLonToXYMatrix(newMatrix);
    };

    this.setLatLonToXYMatrix = function (newMatrix)
    {
        this._latLonToXYMatrix = newMatrix;
        this._xYToLatLonMatrix = this._latLonToXYMatrix.clone();
        this._xYToLatLonMatrix.invert();
        
        this.updateZoomSliderDisplay();
    };

    this.makeEventArgument = function(event)
    {
        var currentPosition = this.getLocalPosition($(event.target), event.pageX, event.pageY);
        var mouseX = currentPosition.x;
        var mouseY = currentPosition.y;

        var mainLatLon = this.getLatLonFromXY(new Point(mouseX, mouseY), this._xYToLatLonMatrix);
        
        var mouseLatLon = null;
        for (var inlayIndex in this._inlays)
        {
            var inlay = this._inlays[inlayIndex];
            
            var screenTopLeft = this.getXYFromLatLon(inlay.worldTopLeftLatLon, this._latLonToXYMatrix);
            var screenBottomRight = this.getXYFromLatLon(inlay.worldBottomRightLatLon, this._latLonToXYMatrix);

            if ((mouseX>=screenTopLeft.x)&&
                (mouseX<screenBottomRight.x)&&
                (mouseY>=screenTopLeft.y)&&
                (mouseY<screenBottomRight.y))
            {
                var localX = (mouseX-screenTopLeft.x);
                var localY = (mouseY-screenTopLeft.y);
                mouseLatLon = this.getLatLonFromXY(new Point(localX, localY), inlay.xYToLatLonMatrix);
            }
        }
        
        if (mouseLatLon === null)
            mouseLatLon = mainLatLon;
        
        var mapPointData = {};
        mapPointData.lon = mouseLatLon.lon;
        mapPointData.lat = mouseLatLon.lat;
        mapPointData.x = mouseX;
        mapPointData.y = mouseY;

        return mapPointData;
    };
	
    this.mapMouseClickHandler = function(event)
    {
        var ohmThis = event.data;
    
        if (ohmThis.isEventInTopBar(event))
            return ohmThis.onTopBarClick(event);
        
        if (!ohmThis.handleViewerElementEvent(event, 'click'))
            return false;
        
        var continueHandling;
        if (ohmThis._onClickFunction !== null)
            continueHandling = ohmThis.externalInterfaceCall(ohmThis._onClickFunction, ohmThis.makeEventArgument(event));
        else
            continueHandling = true;
            
        return true;
    };

    this.mapMouseDoubleClickHandler = function(event)
    { 
        var ohmThis = event.data;
    
        if (ohmThis.isEventInTopBar(event))
            return ohmThis.onTopBarDoubleClick(event);

        if (!ohmThis.handleViewerElementEvent(event, 'doubleclick'))
            return false;

        var continueHandling;
        if (ohmThis._onDoubleClickFunction !== null)
            continueHandling = ohmThis.externalInterfaceCall(ohmThis._onDoubleClickFunction, ohmThis.makeEventArgument(event));
        else
            continueHandling = true;
            
        if (continueHandling)
        {
            var center = ohmThis.getLocalPosition($(event.target), event.pageX, event.pageY);
            var zoomFactor = 2.0;
            
            ohmThis.zoomMapByFactorAroundPoint(zoomFactor, center, false);
            
            ohmThis.onViewChange();	
        }
            
        return true;
    };

    this.mapMouseDownHandler = function(event) 
    { 
        var ohmThis = event.data;
    
        if (ohmThis.isEventInTopBar(event))
            return ohmThis.onTopBarMouseDown(event);

        if (!ohmThis.handleViewerElementEvent(event, 'mousedown'))
            return false;

        var continueHandling;
        if (ohmThis._onMouseDownFunction !== null)
            continueHandling = ohmThis.externalInterfaceCall(ohmThis._onMouseDownFunction, ohmThis.makeEventArgument(event));
        else
            continueHandling = true;
        
        if (continueHandling)
        {
            var mousePosition = ohmThis.getLocalPosition($(event.target), event.pageX, event.pageY);

            ohmThis._isDragging = true;
            ohmThis._lastDragPosition = mousePosition; 
        }
        
        return true;
    };

    this.mapMouseUpHandler = function(event) 
    { 
        var ohmThis = event.data;
    
        if (ohmThis.isEventInTopBar(event))
            return ohmThis.onTopBarMouseUp(event);

        if (!ohmThis.handleViewerElementEvent(event, 'mouseup'))
            return false;

        var continueHandling;
        if (ohmThis._onMouseUpFunction !== null)
            continueHandling = ohmThis.externalInterfaceCall(ohmThis._onMouseUpFunction, ohmThis.makeEventArgument(event));
        else
            continueHandling = true;
        
        if (continueHandling)
        {
            if (ohmThis._isDragging)
            {
                var mousePosition = ohmThis.getLocalPosition($(event.target), event.pageX, event.pageY);
        
                var positionChange = mousePosition.subtract(ohmThis._lastDragPosition);
        
                ohmThis.translateMapByScreenPixels(positionChange.x, positionChange.y, false);
        
                ohmThis._isDragging = false;
                
                ohmThis.onViewChange();
            }
        }
        
        return true;
    };

    this.mapMouseOverHandler = function(event)
    { 
        var ohmThis = event.data;
    
        if (ohmThis.isEventInTopBar(event))
            return ohmThis.onTopBarMouseOver(event);

        if (!ohmThis.handleViewerElementEvent(event, 'mouseover'))
            return false;

        var continueHandling;
        if (ohmThis._onMouseOverFunction !== null)
            continueHandling = ohmThis.externalInterfaceCall(ohmThis._onMouseOverFunction, ohmThis.makeEventArgument(event));
        else
            continueHandling = true;
            
        return true;
    };

    this.mapMouseOutHandler = function(event)
    { 
        var ohmThis = event.data;
    
        if (ohmThis.isEventInTopBar(event))
            return ohmThis.onTopBarMouseOut(event);

        if (!ohmThis.handleViewerElementEvent(event, 'mouseout'))
            return false;

        var continueHandling;
        if (ohmThis._onMouseOutFunction !== null)
            continueHandling = ohmThis.externalInterfaceCall(ohmThis._onMouseOutFunction, ohmThis.makeEventArgument(event));
        else
            continueHandling = true;
            
        return true;
    };

    this.mapMouseMoveHandler = function(event)
    { 
        var ohmThis = event.data;
    
        if (ohmThis.isEventInTopBar(event))
            return ohmThis.onTopBarMouseMove(event);

        if (!ohmThis.handleViewerElementEvent(event, 'mousemove'))
            return false;

        var continueHandling;
        if (ohmThis._onMouseMoveFunction !== null)
            continueHandling = ohmThis.externalInterfaceCall(ohmThis._onMouseMoveFunction, ohmThis.makeEventArgument(event));
        else
            continueHandling = true;

        if (continueHandling)
        {
            if (ohmThis._isDragging)
            {
                var mousePosition = ohmThis.getLocalPosition($(event.target), event.pageX, event.pageY);
        
                var positionChange = mousePosition.subtract(ohmThis._lastDragPosition);
        
                ohmThis.translateMapByScreenPixels(positionChange.x, positionChange.y, true);
        
                ohmThis._lastDragPosition = mousePosition;
            }
        }
                
        return true;
    }

    this.doEveryFrame = function()
    {		
        if (this._redrawCountdown>0)
        {
            this._redrawCountdown -= 1;
            if (this._redrawCountdown===0)
                this._dirty = true;
        }
        
        if (this._valuesDirty&&(this._redrawCountdown===0))
        {
            if (!this._hasPointValues)
            {
                this.setWaysFromValues();
                this._dirty = true;
            }
            this._valuesDirty = false;		
        }
        
        if (this._dirty||this._pointBlobStillRendering||(this._mapTilesDirty&&(this._redrawCountdown===0)))
        {		
            this.drawMapIntoMainBitmap();

            this._dirty = false;
            this._redrawCountdown = 0;
        }
        
        this.drawMainBitmapIntoViewer();
        
        this.drawViewerElements(this._canvas);

        if (this._hasTabs)
        {
            /*this.drawTabsIntoViewer();*/
        }	
        
        if (this._hasTime)
        {
            if (this._timelineButton.getIsOn())
            {
                this._frameIndex += 1;
                if (this._frameIndex>=this._frameTimes.length)
                {
                    this._frameIndex = (this._frameTimes.length-1);
                    this._timelineButton.setIsOn(false);
                }
                
                this.updateTimelineDisplay();
                
                this._dirty = true;
                this._valuesDirty = true;
                this.onDataChange();
            }
        }

        if (this._onFrameRenderFunction !== null)
            this.externalInterfaceCall(this._onFrameRenderFunction, null);	
    };

    this.blankWay = function()
    {
        var result = {};
        
        result.boundingBox = new Rectangle();
        result.nds = [];
        result.tags = {};
        result.isClosed = false;
        
        for (var keyIndex in this._wayDefaults)
        {
            var key = this._wayDefaults[keyIndex];
            result.tags[key] = this._wayDefaults[key];
        }

        return result;	
    };

    this.onWaysLoad = function(data)
    { 	  		  	
        var waysData = $(data);
  	
        this._tagMap = {};

        var instance = this;

        waysData.find('node').each(function() {
            var newNode = {
                'lon': $(this).attr('lon'),
                'lat': $(this).attr('lat')
            };
            
            instance._nodes[$(this).attr('id')] = newNode;
        });

        waysData.find('way').each(function() {
            
            var wayId = $(this).attr('id');

            var newWay = instance.blankWay();
            newWay.id = wayId;

            var ndCount = 0;
            var firstNd = null;
            var lastNd = null;

            $(this).find('nd').each(function() {

                var ref = $(this).attr('ref');

                if (typeof instance._nodes[ref] === 'undefined')
                    return;

                ndCount += 1;
                newWay.nds.push(ref);
	  		
                if (firstNd===null)
                    firstNd = ref;
                lastNd = ref;
	  			  			
                var thisNode = instance._nodes[ref];
                var nodePos = new Point(thisNode.lon, thisNode.lat);
                newWay.boundingBox = instance.enlargeBoxToContain(newWay.boundingBox, nodePos);
            });
	  	
            newWay.isClosed = ((firstNd===lastNd)&&(!instance._settings.force_outlines));

            $(this).find('tag').each(function() {
                
                var key = $(this).attr('k');
                var value = $(this).attr('v');
	  		
                newWay.tags[key] = value;
	  		
                if (typeof instance._tagMap[key] === 'undefined')
                    instance._tagMap[key] = {};
	  			
                if (typeof instance._tagMap[key][value] === 'undefined')
                    instance._tagMap[key][value] = [];
	  			
                instance._tagMap[key][value].push(newWay.id);
            });
 		
            instance._ways[wayId] = newWay;
  		
            if (!newWay.boundingBox.isEmpty())
            {
                instance._worldBoundingBox = instance.enlargeBoxToContain(instance._worldBoundingBox, newWay.boundingBox.topLeft());
                instance._worldBoundingBox = instance.enlargeBoxToContain(instance._worldBoundingBox, newWay.boundingBox.bottomRight());
            }
        });

        this.buildWaysGrid();
        this._dirty = true;
        this._valuesDirty = true;
        if (this._onWaysLoadFunction!==null)
            this.externalInterfaceCall(this._onWaysLoadFunction, this._waysFileName);
    };
 	  
    this.loadWaysFromFile = function(waysFileName) 
    {
        var instance = this;
        this._waysFileName = waysFileName;
        $.get(waysFileName, function(data) {
            instance.onWaysLoad(data);
        });
    }

    this.decodeCSVRow = function(line, columnSeperator)
    {
        var inQuotes = false;
        var inEscape = false;
        
        var result = [];

        var currentValue = '';

        for( var i = 0; i < line.length; i+=1)
        {
            var currentChar = line.charAt(i);
        
            if (!inQuotes)
            {
                if (currentChar==='"')
                {
                    inQuotes = true;
                }
                else if (currentChar===columnSeperator)
                {
                    result.push(currentValue);
                    currentValue = '';
                }
                else
                {
                    currentValue += currentChar;
                }
            }
            else
            {
                if (!inEscape)
                {
                    if (currentChar==='\\')
                    {
                        inEscape = true;
                    }
                    else if (currentChar==='"')
                    {
                        inQuotes = false;
                    }
                    else
                    {
                        currentValue += currentChar;
                    }
                    
                }
                else
                {
                    currentValue += currentChar;
                    inEscape = false;
                }
                
            }
            
        }
        
        result.push(currentValue);
        
        return result;
    }

    this.onValuesLoad = function(data)
    {
        this.loadValuesFromCSVString(data);

        if (this._onValuesLoadFunction!==null)
            this.externalInterfaceCall(this._onValuesLoadFunction, this._valuesFileName);
    };

    this.loadValuesFromCSVString = function(valuesString)
    {
        var lineSeperator = '\n';
        var columnSeperator = ',';		  	

        var linesArray = valuesString.split(lineSeperator);
        
        var headerLine = linesArray[0];

        this._valueHeaders = this.decodeCSVRow(headerLine, columnSeperator);

        this._timeColumnIndex = -1;
        this._valueColumnIndex = -1;
        this._latitudeColumnIndex = -1;
        this._longitudeColumnIndex = -1;
        this._tabColumnIndex = -1;
        for(var headerIndex = 0; headerIndex < this._valueHeaders.length; headerIndex++ )
        {
            var header = this._valueHeaders[headerIndex].toLowerCase();
            if (header==='time')
                this._timeColumnIndex = headerIndex;	
            else if (header==='value')
                this._valueColumnIndex = headerIndex;
            else if ((header==='latitude')||(header==='lat'))
                this._latitudeColumnIndex = headerIndex;
            else if ((header==='longitude')||(header==='lon'))
                this._longitudeColumnIndex = headerIndex;
            else if ((header==='tab')||(header==='category'))
                this._tabColumnIndex = headerIndex;
        }
        
        var hasLatitude = (this._latitudeColumnIndex!==-1);
        var hasLongitude = (this._longitudeColumnIndex!==-1);
        
        if ((hasLatitude||hasLongitude)&&(hasLatitude!=hasLongitude))
        {
            this.logError( 'Error loading CSV file "'+this._valuesFileName+'" - only found one of longitude or latitude in "'+headerLine+'"');
            return;		
        }
        
        this._hasPointValues = hasLatitude;
        this._hasTime = (this._timeColumnIndex!==-1);
        this._hasTabs = (this._tabColumnIndex!==-1);
        
        this._hasBitmapBackground = this._hasPointValues;
        
        if (!this._hasPointValues)
            this.loadAreaValues(linesArray, headerLine, columnSeperator);
        else
            this.loadPointValues(linesArray, headerLine, columnSeperator);
            
        if (this._hasTime)
        {
            this.calculateFrameTimes();
            this._frameIndex = 0;
            this.addTimelineControls();
        }
        
        this._valuesDirty = true;
        this._dirty = true;			
    };

    this.loadValuesFromFile = function(valuesFileName)
    {
        this._valuesFileName = valuesFileName;
        var instance = this;
        $.get(valuesFileName, function(data) {
            instance.onValuesLoad(data);
        });
    };

    this.drawInformationLayer = function(canvas, width, height, latLonToXYMatrix, xYToLatLonMatrix)
    {    
        var viewingArea = this.calculateViewingArea(width, height, xYToLatLonMatrix);

        var bitmapBackground = this.drawPointBlobBitmap(width, height, viewingArea, latLonToXYMatrix, xYToLatLonMatrix);
        
        this.drawWays(canvas, width, height, viewingArea, latLonToXYMatrix, bitmapBackground);
    };

    this.drawWays = function(canvas, width, height, viewingArea, latLonToXYMatrix, bitmapBackground)
    {
        var hasBitmap = (bitmapBackground!==null);
        var bitmapMatrix = new Matrix();
        bitmapMatrix.scale(this._settings.point_bitmap_scale, this._settings.point_bitmap_scale);
        
        var waysEmpty = true;
        for (var wayId in this._ways)
        {
            waysEmpty = false;
            break;
        }
        
        if (hasBitmap&&waysEmpty)
        {
            this.drawImage(canvas, bitmapBackground.get(0), 0, 0, width, height);
            return;
        }
        
        var context = this.beginDrawing(canvas);
        
        for (wayId in this._ways)
        {
            var way = this._ways[wayId];
            var wayColor;
            var wayAlpha;
            if (this.getWayProperty('highlighted', way)==true)
            {
                wayColor = Number(this.getWayProperty('highlightColor', way));
                wayAlpha = Number(this.getWayProperty('highlightAlpha', way));
            }
            else
            {
                wayColor = Number(this.getWayProperty('color', way.tags));
                wayAlpha = Number(this.getWayProperty('alpha', way.tags));
            }

            if (way.nds.length<1)
                continue;
            
            if (!viewingArea.intersects(way.boundingBox))
                continue;

            var isClosed = way.isClosed;

            context.beginPath();

            if (isClosed)
            {		
                var finalNd = way.nds[way.nds.length-1];
                var finalNode = this._nodes[finalNd];
                
                var finalPos = this.getXYFromLatLon(finalNode, latLonToXYMatrix);

                if (hasBitmap)
                    context.fillStyle = context.createPattern(bitmapBackground, 'no-repeat');
                else
                    context.fillStyle = this.colorStringFromNumber(wayColor, wayAlpha);
                
                context.moveTo(finalPos.x, finalPos.y);
            }
            else
            {
                var firstNd = way.nds[0];
                var firstNode = this._nodes[firstNd];
                
                var firstPos = this.getXYFromLatLon(firstNode, latLonToXYMatrix);

                context.lineStyle = this.colorStringFromNumber(wayColor,wayAlpha);

                context.moveTo(firstPos.x, firstPos.y);
            }

            for (var currentNdIndex in way.nds)
            {
                var currentNd = way.nds[currentNdIndex];
                var currentNode = this._nodes[currentNd];
                var currentPos = this.getXYFromLatLon(currentNode, latLonToXYMatrix);
                
                context.lineTo(currentPos.x, currentPos.y);
            }

            context.closePath();

            if (isClosed)
                context.fill();
            else
                context.stroke();
        }
        
        this.endDrawing(context);
    };

    this.setWaysFromValues = function()
    {	
        if (this._valueData === null)
            return;

        if (this._settings.is_gradient_value_range_set)
        {
            var minValue = this._settings.gradient_value_min;
            var maxValue = this._settings.gradient_value_max;	
        }
        else
        {
            minValue = this._smallestValue;
            maxValue = this._largestValue;
        }
        var valueScale = (1.0/(maxValue-minValue));

        var currentValues = this.getCurrentValues();
        
        var thisSetWayIds = {};
        
        if (this._hasTime)
            var currentTime = this._frameTimes[this._frameIndex];
        
        for (var valuesIndex in currentValues)
        {
            var values = currentValues[valuesIndex];
            if (this._hasTime)
            {
                var thisTime = values[this._timeColumnIndex];
                if (thisTime !== currentTime)
                    continue;
            }

            var matchKeys = {};
            var thisValue = 0;		
            for (var i = 0; i<values.length; i+=1)
            {
                if (i===this._valueColumnIndex)
                {
                    thisValue = values[i];
                }
                else if ((i!==this._timeColumnIndex)&&(i!==this._tabColumnIndex))
                {
                    var headerName = this._valueHeaders[i];
                    matchKeys[headerName] = values[i];	
                }
            }
            
            var setColor = this.getColorForValue(thisValue, minValue, maxValue, valueScale);
            
            this.setAttributeForMatchingWays(matchKeys, 'color', setColor, thisSetWayIds);
        }
        
        var defaultColor = this.getWayProperty('color');
        
        for (var lastWayId in this._lastSetWayIds)
        {
            if (thisSetWayIds.hasOwnProperty(lastWayId))
                continue;
                
            this._ways[lastWayId]['color'] = defaultColor;
        }
        
        this._lastSetWayIds = thisSetWayIds;
    };

    this.setColorGradient = function(colorList)
    {
        this._colorGradient = [];
        
        for (var colorStringIndex in colorList)
        {
            var colorString = colorList[colorStringIndex];
            colorString = colorString.replace('#', '0x');
            
            var colorNumber = Math.floor(colorString);
            
            var alpha;
            if (colorString.length>8)
                alpha = (colorNumber>>24)&0xff;
            else
                alpha = 0x7f;		
            
            var red = (colorNumber>>16)&0xff;
            var green = (colorNumber>>8)&0xff;
            var blue = (colorNumber>>0)&0xff;
            
            var premultRed = Math.floor((red*alpha)/255.0);
            var premultGreen = Math.floor((green*alpha)/255.0);
            var premultBlue = Math.floor((blue*alpha)/255.0);
            
            this._colorGradient.push({
                alpha: alpha,
                red: premultRed,
                green: premultGreen,
                blue: premultBlue
            });
        }

        this._valuesDirty = true;
        this._redrawCountdown = 5;
    }

    this.setAttributeForMatchingWays = function(matchKeys, attributeName, attributeValue, setWays)
    {
        var matchingWayIds = null;
        for (var key in matchKeys)
        {
            var value = matchKeys[key];
            
            var currentMatches;
            if (!this._tagMap.hasOwnProperty(key)||!this._tagMap[key].hasOwnProperty(value))
                currentMatches = [];
            else
                currentMatches = this._tagMap[key][value];
             
            if (matchingWayIds === null)
            {
                matchingWayIds = {};
                for (var wayIdIndex in currentMatches)
                {
                    var wayId = currentMatches[wayIdIndex];
                    matchingWayIds[wayId] = true;
                }
            }
            else
            {
                var previousMatchingWayIds = matchingWayIds;
                matchingWayIds = {};
                for (var wayIdIndex in currentMatches)
                {
                    var wayId = currentMatches[wayIdIndex];
                    if (typeof previousMatchingWayIds[wayId] !== 'undefined')
                        matchingWayIds[wayId] = true;
                }
            }
        }
            
        var foundCount = 0;
        for (wayId in matchingWayIds)
        {
            this._ways[wayId]['tags'][attributeName] = attributeValue;
            foundCount += 1;
            setWays[wayId] = true;
        }

    //	if (foundCount===0)
    //	{
    //		trace('No match found for');
    //		for (key in matchKeys)
    //		{
    //			value = matchKeys[key];	
    //			trace(key+':'+value);
    //		}
    //	}

    };

    this.enlargeBoxToContain = function(box, pos)
    {
        if (box.containsPoint(pos))
            return box;
	
        if ((box.x==0)&&
            (box.y==0)&&
            (box.width==0)&&
            (box.height==0))
            return new Rectangle(pos.x, pos.y, 0, 0);
		
        if (box.left()>pos.x)
            box.left(pos.x);

        if (box.right()<pos.x)
            box.right(pos.x);

        if (box.top()>pos.y)
            box.top(pos.y);
            
        if (box.bottom()<pos.y)
            box.bottom(pos.y);
            
        return box;
    };

    this.buildWaysGrid = function()
    {
        this._waysGrid = new BucketGrid(this._worldBoundingBox, 16, 16);
        
        for (var wayId in this._ways)
        {
            var way = this._ways[wayId];

            var boundingBox = way.boundingBox;
            if (boundingBox.isEmpty())
                continue;
            
            this._waysGrid.insertObjectAt(boundingBox, wayId);
        }
    };

    this.getWaysContainingLatLon = function(lat, lon)
    {
        var result = [];

        var pos = new Point(lon, lat);

        if (!this._worldBoundingBox.containsPoint(pos))
            return result;
        
        if (this._waysGrid===null)
            return result;
        
        var pixelsPerDegree = this.getPixelsPerDegreeLatitude();
        var pixelsToDegreeScale = (1.0/pixelsPerDegree);
        var ways = this._waysGrid.getContentsAtPoint(pos);
        
        for (var wayIdIndex in ways)
        {
            var wayId = ways[wayIdIndex];
            
            var way = this._ways[wayId];
            var isInside = false;
            if (way.isClosed)
            {
                if (way.boundingBox.containsPoint(pos))
                {
                    isInside = this.isPointInsideClosedWay(pos, way);
                }
            }
            else
            {
                var lineThickness = (Number)(this.getWayProperty('line_thickness', way));
                
                var thicknessInDegrees = Math.abs((lineThickness+1)*pixelsToDegreeScale);
                
                var boundingBox = way.boundingBox.clone();
    //			boundingBox.inflate(thicknessInDegrees/2, thicknessInDegrees/2);
                
                if (boundingBox.containsPoint(pos))
                {
                    isInside = this.isPointOnWayLine(pos, way, thicknessInDegrees);	
                }			
            }
            
            if (isInside)
            {
                var wayResult = {};
                wayResult.id = wayId;
                wayResult.tags = {};
                
                for (var key in way.tags)
                {
                    // Pete - Safari really doesn't like colons in member names! 
                    key = key.replace(':', '_colon_');
                    var value = way.tags[key];
                    wayResult.tags[key] = value;
                }
                
                result.push(wayResult);
            }
        }
        
        return result;
    };

    this.addTimelineControls = function()
    {
        if (this._timelineSlider === null)
        {
            var instance = this;
            this._timelineSlider = new Slider(
                80, (this._settings.height-30),
                (this._settings.width-250), 10,
                function(isDragging) { instance.onTimelineSliderChange(isDragging) });
        
            this.addChild(this._timelineSlider);
            
            this._timelineText = new UIText('', '16px lucida grande, verdana', 
                (this._settings.width-160), (this._settings.height-25));
            this.addChild(this._timelineText);
            
            this._timelineButton = new UIButton(
                42, (this._settings.height-45),
                32, 32,
                'http://localhost/static.openheatmap.com/images/pause.png',
                'http://localhost/static.openheatmap.com/images/play.png');
            this.addChild(this._timelineButton);
        }
        
        this.updateTimelineDisplay();
    };

    this.onTimelineSliderChange = function(dragging)
    {
        var sliderValue = this._timelineSlider.getSliderValue();

        var totalFrames = this._frameTimes.length;

        this._frameIndex = Math.round(sliderValue*totalFrames);
        this._frameIndex = Math.min(this._frameIndex, (totalFrames-1));
        this._frameIndex = Math.max(this._frameIndex, 0);
        
        this.updateTimelineDisplay();
        
        if (dragging)
            this._redrawCountdown = 5;
        else
            this._dirty = true;
            
        this._valuesDirty = true;
        this.onDataChange();
    };

    this.updateTimelineDisplay = function()
    {
        if (this._frameTimes.length>0)
        {
            var currentTime = this._frameTimes[this._frameIndex];
            this._timelineText.setText(currentTime);
            
            var totalFrames = this._frameTimes.length;
            this._timelineSlider.setSliderValue(this._frameIndex/totalFrames);
        }
    }

    this.getValueForWayId = function(wayId)
    {
        if (typeof this._ways[wayId] === 'undefined')
            return null;
            
        var way = this._ways[wayId];

        if (this._valueData === null)
            return null;

        var currentValues = this.getCurrentValues();
        
        var resultFound = false;
        var result;
        for (var valuesIndex in currentValues)
        {
            var values = currentValues[valuesIndex];
            
            var matchKeys = {};
            var thisValue = null;		
            for (var i = 0; i<values.length; i+=1)
            {
                if (i===this._valueColumnIndex)
                {
                    thisValue = values[i];
                }
                else if ((i!==this._timeColumnIndex)&&(i!==this._tabColumnIndex))
                {
                    var headerName = this._valueHeaders[i];
                    matchKeys[headerName] = values[i];	
                }
            }
            
            var allMatch = true;
            for (var key in matchKeys)
            {
                var value = matchKeys[key];
                
                if (way.tags[key]!==value)
                    allMatch = false;	
            }
            
            if (allMatch)
            {
                resultFound = true;
                result = thisValue;
            }
        }

        if (resultFound)
            return result;
        else
            return null;
    };

    this.addInlay = function(leftX, topY, rightX, bottomY, topLat, leftLon, bottomLat, rightLon)
    {
        var mercatorTopLat = this.latitudeToMercatorLatitude(topLat);
        var mercatorBottomLat = this.latitudeToMercatorLatitude(bottomLat);
        
        var width = (rightX-leftX);
        var height = (bottomY-topY);
        
        var widthLon = (rightLon-leftLon);
        var heightLat = (mercatorBottomLat-mercatorTopLat);
        
        var scaleX = (width/widthLon);
        var scaleY = (height/heightLat);

        var latLonToXYMatrix = new Matrix();
        latLonToXYMatrix.translate(-leftLon, -mercatorTopLat);
        latLonToXYMatrix.scale(scaleX, scaleY);	

        var xYToLatLonMatrix = latLonToXYMatrix.clone();
        xYToLatLonMatrix.invert();
        
        var worldTopLeftLatLon = this.getLatLonFromXY(new Point(leftX, topY), this._xYToLatLonMatrix);
        var worldBottomRightLatLon = this.getLatLonFromXY(new Point(rightX, bottomY), this._xYToLatLonMatrix);
        
        this._inlays.push({
            latLonToXYMatrix: latLonToXYMatrix,
            xYToLatLonMatrix: xYToLatLonMatrix,
            worldTopLeftLatLon: worldTopLeftLatLon,
            worldBottomRightLatLon: worldBottomRightLatLon,
            topLat: topLat,
            leftLon: leftLon,
            bottomLat: bottomLat,
            rightLon: rightLon
        });
    };

    this.cropPoint = function(input, area)
    {
        var result = input.clone();
        
        if (result.x<area.left)
            result.x = area.left;
        
        if (result.x>area.right)
            result.x = area.right;	
        
        if (result.y<area.top)
            result.y = area.top;
        
        if (result.y>area.bottom)
            result.y = area.bottom;	

        return result;	
    };

    this.drawMapIntoMainBitmap = function()
    {
        this.clearCanvas(this._mainCanvas);
        this.fillRect(this._mainCanvas, 0, 0, this._settings.width, this._settings.height, this._settings.ocean_color);

        if (this._settings.show_map_tiles)
        {
    		this.trackMapTilesUsage();
            this.drawMapTiles(this._mainCanvas, this._settings.width, this._settings.height, this._latLonToXYMatrix, this._xYToLatLonMatrix);
        }

        if (this._dirty||this._pointBlobStillRendering)
        {			
            this.clearCanvas(this._informationLayerCanvas);
            this.drawInformationLayer(this._informationLayerCanvas, this._settings.width, this._settings.height, this._latLonToXYMatrix, this._xYToLatLonMatrix);
        }

        this.drawImage(this._mainCanvas, this._informationLayerCanvas.get(0), 0, 0, this._settings.width, this._settings.height);
                
        for (var inlayIndex in this._inlays)
        {
            var inlay = this._inlays[inlayIndex];
            
            var screenTopLeft = this.getXYFromLatLon(inlay.worldTopLeftLatLon, this._latLonToXYMatrix);
            var screenBottomRight = this.getXYFromLatLon(inlay.worldBottomRightLatLon, this._latLonToXYMatrix);
            
            var screenArea = new Rectangle(0, 0, this._settings.width, this._settings.height);
            
            var croppedScreenTopLeft = this.cropPoint(screenTopLeft, screenArea);
            var croppedScreenBottomRight = this.cropPoint(screenBottomRight, screenArea);
            
            var inlayWidth = (croppedScreenBottomRight.x-croppedScreenTopLeft.x);
            var inlayHeight = (croppedScreenBottomRight.y-croppedScreenTopLeft.y);
            
            if ((inlayWidth<1)||(inlayHeight<1))
                continue;
            
            var inlayScreenLeftX = croppedScreenTopLeft.x;
            var inlayScreenTopY = croppedScreenTopLeft.y;
            
            var localTopLeft = croppedScreenTopLeft.subtract(screenTopLeft);

            var croppedLatLonToXYMatrix = inlay.latLonToXYMatrix.clone();
            croppedLatLonToXYMatrix.translate(-localTopLeft.x, -localTopLeft.y);
            
            var croppedXYToLatLonMatrix = croppedLatLonToXYMatrix.clone();
            croppedXYToLatLonMatrix.invert();
            
            drawingSurface = this.createCanvas(inlayWidth, inlayHeight);
            
            if (this._settings.show_map_tiles)	
                this.drawMapTiles(drawingSurface, inlayWidth, inlayHeight, croppedLatLonToXYMatrix, croppedXYToLatLonMatrix);

            this.drawInformationLayer(drawingSurface, inlayWidth, inlayHeight, croppedLatLonToXYMatrix, croppedXYToLatLonMatrix);
            
            var borderTopLeft = screenTopLeft.subtract(croppedScreenTopLeft);
            var borderBottomRight = screenBottomRight.subtract(croppedScreenTopLeft).subtract(new Point(1, 1));
            
            borderTopLeft.x = Math.floor(borderTopLeft.x);
            borderTopLeft.y = Math.floor(borderTopLeft.y);
            
            borderBottomRight.x = Math.floor(borderBottomRight.x);
            borderBottomRight.y = Math.floor(borderBottomRight.y);
            
            if (this._settings.show_map_tiles)
            {
                var context = this.beginDrawing(drawingSurface);
                context.lineWidth = 1.0;
                context.strokeStyle = this.colorStringFromNumber(this._settings.inlay_border_color, 1.0);

                context.beginPath();
                context.moveTo(borderTopLeft.x, borderTopLeft.y);
                context.lineTo(borderBottomRight.x, borderTopLeft.y);
                context.lineTo(borderBottomRight.x, borderBottomRight.y);
                context.lineTo(borderTopLeft.x, borderBottomRight.y);
                context.lineTo(borderTopLeft.x, borderTopLeft.y);
                context.closePath();
                context.stroke();
                
                this.endDrawing(context);
            }
        
            this.drawImage(this._mainCanvas, drawingSurface.get(0), inlayScreenLeftX, inlayScreenTopY, inlayWidth, inlayHeight);
        }

        this._mainBitmapTopLeftLatLon = this.getLatLonFromXY(new Point(0, 0), this._xYToLatLonMatrix);
        this._mainBitmapBottomRightLatLon = this.getLatLonFromXY(new Point(this._settings.width, this._settings.height), this._xYToLatLonMatrix);

        if (this._settings.show_map_tiles)
        {
            this.deleteUnusedMapTiles();
        }
    };

    this.drawMainBitmapIntoViewer = function()
    {
        this.clearCanvas(this._canvas);
        
        if ((this._mainBitmapTopLeftLatLon===null)||
            (this._mainBitmapBottomRightLatLon===null))
            return;
            
        var screenBitmapTopLeft = this.getXYFromLatLon(this._mainBitmapTopLeftLatLon, this._latLonToXYMatrix);
        var screenBitmapBottomRight = this.getXYFromLatLon(this._mainBitmapBottomRightLatLon, this._latLonToXYMatrix);	

        var screenBitmapLeft = screenBitmapTopLeft.x;
        var screenBitmapTop = screenBitmapTopLeft.y;
        
        var screenBitmapWidth = (screenBitmapBottomRight.x-screenBitmapTopLeft.x);
        var screenBitmapHeight = (screenBitmapBottomRight.y-screenBitmapTopLeft.y);
        
        this.drawImage(this._canvas, this._mainCanvas.get(0), screenBitmapLeft, screenBitmapTop, screenBitmapWidth, screenBitmapHeight);
    };

    this.translateMapByScreenPixels = function(x, y, dragging)
    {
        this._latLonToXYMatrix.translate(x, y);
        this._xYToLatLonMatrix = this._latLonToXYMatrix.clone();
        this._xYToLatLonMatrix.invert();
        
        if (dragging)
            this._redrawCountdown = 5;
        else
            this._dirty = true;
    };

    this.zoomMapByFactorAroundPoint = function(zoomFactor, center, dragging)
    {
        var translateToOrigin = new Matrix();
        translateToOrigin.translate(-center.x, -center.y);
        
        var scale = new Matrix();
        scale.scale(zoomFactor, zoomFactor);
        
        var translateFromOrigin = new Matrix();
        translateFromOrigin.translate(center.x, center.y);

        var zoom = new Matrix();
        zoom.concat(translateToOrigin);
        zoom.concat(scale);
        zoom.concat(translateFromOrigin);
        
        this._latLonToXYMatrix.concat(zoom);
        this._xYToLatLonMatrix = this._latLonToXYMatrix.clone();
        this._xYToLatLonMatrix.invert();

        for (var inlayIndex in this._inlays)
        {
            var inlay = this._inlays[inlayIndex];
            var newLatLonToXYMatrix = inlay.latLonToXYMatrix.clone();
            newLatLonToXYMatrix.concat(scale);
            
            var newXYToLatLonMatrix = newLatLonToXYMatrix.clone();
            newXYToLatLonMatrix.invert();
            
            inlay.latLonToXYMatrix = newLatLonToXYMatrix;
            inlay.xYToLatLonMatrix = newXYToLatLonMatrix;
        }
        
        if (dragging)
            this._redrawCountdown = 5;
        else
            this._dirty = true;
            
        this.updateZoomSliderDisplay();
    };

    this.createViewerElements = function()
    {
        this._viewerElements = [];

        this._mainCanvas = this.createCanvas(this._settings.width, this._settings.height);

        this._informationLayerCanvas = this.createCanvas(this._settings.width, this._settings.height);

        this._plusImage = new UIImage('http://localhost/static.openheatmap.com/images/plus.gif', 9, 35);
        this.addChild(this._plusImage);

        this._minusImage = new UIImage('http://localhost/static.openheatmap.com/images/minus.gif', 9, 197);
        this.addChild(this._minusImage);
        
        var instance = this;
        
        this._zoomSlider = new Slider(15, 50, 10, 150, 
            function(isDragging) { instance.onZoomSliderChange(isDragging); });
        this.addChild(this._zoomSlider);

        /*        
        _credit = new Label();
        _credit.htmlText = _settings.credit_text;
        _credit.width = 150;
        _credit.height = 20;
        _credit.setStyle('text-align', 'right');
        _credit.setStyle('color', _settings.credit_color);
        
        _credit.addEventListener( MouseEvent.CLICK, function(): void {
            var url:String = "http://"+_credit.text;
            var request:URLRequest = new URLRequest(url);
            navigateToURL(request); 	  	
        });
        
        viewer.addChild(_credit);

        _title = new TextField();
        _title.htmlText = '<p align="center"><u>'+_settings.title_text+'</u></p>';
        _title.width = _settings.width;
        _title.height = (_settings.title_size*1.5);
        _title.textColor = _settings.title_color;
        _title.background = true;
        _title.backgroundColor = _settings.title_background_color;
    //	_title.fontSize = _settings.title_size;
        _title.y = -1000;

        var titleFormat: TextFormat = _title.defaultTextFormat;
        titleFormat.size = _settings.title_size;
        titleFormat.font = 'Verdana';
        _title.defaultTextFormat = titleFormat;
        
        viewer.addChild(_title);

        repositionMoveableElements();
        */
    };

    this.onZoomSliderChange = function(isDragging)
    {
        var pixelsPerDegreeLatitude = this.calculatePixelsPerDegreeLatitudeFromZoomSlider();
	
        this.setPixelsPerDegreeLatitude(pixelsPerDegreeLatitude, isDragging);

        this.onViewChange();
    };

    this.getPixelsPerDegreeLatitude = function()
    {
        var pixelsPerDegreeLatitude = this._latLonToXYMatrix.d;
	
        return pixelsPerDegreeLatitude;
    };

    this.setPixelsPerDegreeLatitude = function(newPixelsPerDegreeLatitude, dragging)
    {
        var oldPixelsPerDegreeLatitude = this.getPixelsPerDegreeLatitude();
        
        var zoomFactor = (newPixelsPerDegreeLatitude/oldPixelsPerDegreeLatitude);
        
        var center = new Point((this._settings.width/2), (this._settings.height/2));
        
        this.zoomMapByFactorAroundPoint(zoomFactor, center, dragging);
    }

    this.calculatePixelsPerDegreeLatitudeFromZoomSlider = function()
    {
        var sliderValue = this._zoomSlider.getSliderValue();
        
        var lerpValue = Math.pow(sliderValue, this._settings.zoom_slider_power);

        var minPixelsPerDegreeLatitude = (this._settings.height/this._settings.zoomed_out_degrees_per_pixel);
        var maxPixelsPerDegreeLatitude = (this._settings.height/this._settings.zoomed_in_degrees_per_pixel);

        var oneMinusLerp = (1-lerpValue);
        
        var result = (minPixelsPerDegreeLatitude*oneMinusLerp)+
            (maxPixelsPerDegreeLatitude*lerpValue);
        
        return result;
    };

    this.updateZoomSliderDisplay = function()
    {
        var pixelsPerDegreeLatitude = this.getPixelsPerDegreeLatitude();

        var minPixelsPerDegreeLatitude = (this._settings.height/this._settings.zoomed_out_degrees_per_pixel);
        var maxPixelsPerDegreeLatitude = (this._settings.height/this._settings.zoomed_in_degrees_per_pixel);

        var lerpValue = ((pixelsPerDegreeLatitude-minPixelsPerDegreeLatitude)/
            (maxPixelsPerDegreeLatitude-minPixelsPerDegreeLatitude));
        
        var sliderValue = Math.pow(lerpValue, (1/this._settings.zoom_slider_power));

        this._zoomSlider.setSliderValue(sliderValue);
    };

    this.setGradientValueRange = function(min, max)
    {
        this._settings.is_gradient_value_range_set = true;
        this._settings.gradient_value_min = min;
        this._settings.gradient_value_max = max;
    };

    this.calculateFrameTimes = function()
    {
        this._frameTimes = [];
        
        for (var thisTime in this._foundTimes)
        {
            if ((this._settings.time_range_start!==null)&&(thisTime<this._settings.time_range_start))
                continue;

            if ((this._settings.time_range_end!==null)&&(thisTime>this._settings.time_range_end))
                continue;
            
            this._frameTimes.push(thisTime);
        }
        this._frameTimes.sort();
        
        if (this._frameIndex>(this._frameTimes.length-1))
            this._frameIndex = (this._frameTimes.length-1);
    };

    this.onDataChange = function()
    {
        if (this._onDataChangeFunction!==null)
            this.externalInterfaceCall(this._onDataChangeFunction, null);	
    };

    this.logError = function(message) {
        alert('Error: '+message);
        if (_onErrorFunction!==null)
            this.externalInterfaceCall(_onErrorFunction, message);	
    };

    this.onViewChange = function()
    {
        if (this._onViewChangeFunction!==null)
            this.externalInterfaceCall(this._onViewChangeFunction, null);	
    };

    this.getWayForWayId = function(wayId)
    {
        var result = this._ways[wayId];
        
        return result;	
    };

    this.isPointInsideClosedWay = function(pos, way)
    {
        var xIntersections = [];

        var lineStart = null;
        var isFirst = true;
        
        for (var currentNdIndex in way.nds)
        {
            var currentNd = way.nds[currentNdIndex];
            
            var currentNode = this._nodes[currentNd];
            var lineEnd = new Point(currentNode.lon, currentNode.lat);
            
            if (isFirst)
            {
                isFirst = false;
            }
            else
            {
                if (((lineStart.y>pos.y)&&(lineEnd.y<pos.y))||
                    ((lineStart.y<pos.y)&&(lineEnd.y>pos.y)))
                {
                    var lineDirection = new Point(lineEnd.x-lineStart.x, lineEnd.y-lineStart.y);
                    var yDelta = (pos.y-lineStart.y);
                    var yProportion = (yDelta/lineDirection.y);
                    
                    var xIntersect = (lineStart.x+(lineDirection.x*yProportion));
                    xIntersections.push(xIntersect);
                }
                
            }
            
            lineStart = lineEnd;
        }
        
        xIntersections.sort(function(a, b) {
            if (a<b) return -1;
            else if (a>b) return 1;
            else return 0; 
        });
        
        var isInside = false;
        for (var index = 0; index<(xIntersections.length-1); index += 2)
        {
            var leftX = xIntersections[index];
            var rightX = xIntersections[(index+1)];

            if ((leftX<=pos.x)&&(rightX>pos.x))
                isInside = true;
            
        }
                    
        return isInside;
    }

    this.isPointOnWayLine = function(pos, way, thickness)
    {
        var lineStart = null;
        var isFirst = true;
        
        var thicknessSquared = (thickness*thickness);
        
        var isInside = false;
        for (var currentNdIndex in way.nds)
        {
            var currentNd = way.nds[currentNdIndex];
            
            var currentNode = this._nodes[currentNd];
            var lineEnd = new Point(currentNode.lon, currentNode.lat);
            
            if (isFirst)
            {
                isFirst = false;
            }
            else
            {
                var lineDirection = new Point(lineEnd.x-lineStart.x, lineEnd.y-lineStart.y);
                
                var lineDirectionSquared = ((lineDirection.x*lineDirection.x)+(lineDirection.y*lineDirection.y));
                
                var s = ((pos.x-lineStart.x)*lineDirection.x)+((pos.y-lineStart.y)*lineDirection.y);
                s /= lineDirectionSquared;
                
                s = Math.max(s, 0);
                s = Math.min(s, 1);
                
                var closestPoint = new Point((lineStart.x+s*lineDirection.x), (lineStart.y+s*lineDirection.y));
                
                var delta = pos.subtract(closestPoint);
                
                var distanceSquared = ((delta.x*delta.x)+(delta.y*delta.y));
                
                if (distanceSquared<thicknessSquared)
                {
                    isInside = true;
                    break;
                }
            }
            
            lineStart = lineEnd;
        }
        
        return isInside;
    };

    this.drawPointBlobBitmap = function(width, height, viewingArea, latLonToXYMatrix, xYToLatLonMatrix)
    {
        if (!this._hasPointValues)
            return null;
        
        if (this._dirty)
        {
            this.createPointsGrid(viewingArea, latLonToXYMatrix);
        
            this._pointBlobBitmapWidth = (width/this._settings.point_bitmap_scale);
            this._pointBlobBitmapHeight = (height/this._settings.point_bitmap_scale);
        
            this._pointBlobCanvas = this.createCanvas(this._pointBlobBitmapWidth, this._pointBlobBitmapHeight);
            
            this._pointBlobTileX = 0;
            this._pointBlobTileY = 0;
            
            this._pointBlobStillRendering = true;
        }

        var tileSize = 128;	
        
        while (this._pointBlobTileY<this._pointBlobBitmapHeight)
        {
            var distanceFromBottom = (this._pointBlobBitmapHeight-this._pointBlobTileY);
            var tileHeight = Math.min(tileSize, distanceFromBottom);
            
            while (this._pointBlobTileX<this._pointBlobBitmapWidth)
            {	
                var distanceFromRight = (this._pointBlobBitmapWidth-this._pointBlobTileX);
                var tileWidth = Math.min(tileSize, distanceFromRight);
                
                this.drawPointBlobTile(width, height, viewingArea, latLonToXYMatrix, xYToLatLonMatrix, this._pointBlobTileX, this._pointBlobTileY, tileWidth, tileHeight);
                
                this._pointBlobTileX+=tileSize;

                return this._pointBlobCanvas;
            }
            
            this._pointBlobTileX = 0;
            this._pointBlobTileY+=tileSize
        }
        
        this._pointBlobStillRendering = false;
        
        return this._pointBlobCanvas;
    };

    this.loadAreaValues = function(linesArray, headerLine, columnSeperator)
    {
        if (this._valueColumnIndex===-1)
        {
            logError( 'Error loading CSV file "'+this._valuesFileName+'" - missing value column from header "'+headerLine+'"');
            return;
        }
        
        this._foundTimes = {};
        this._tabNames = [];
        this._tabInfo = {};
        
        this._valueData = [];
        
        for(var i = 1; i < linesArray.length; i++ )
        {
            var lineString = linesArray[i];
            var lineValues = this.decodeCSVRow(lineString, columnSeperator);
            
            var thisValue = (Number)(lineValues[this._valueColumnIndex]);
            
            if ((i===1)||(thisValue<this._smallestValue))
                this._smallestValue = thisValue;
                
            if ((i===1)||(thisValue>this._largestValue))
                this._largestValue = thisValue;
            
            var dataDestination = this._valueData;

            if (this._hasTabs)
            {
                var thisTab = lineValues[this._tabColumnIndex];
                if (thisTab !== null)
                {
                    if (typeof this._tabInfo[thisTab] === 'undefined')
                    {
                        this._tabInfo[thisTab] = {};
                        this._tabNames.push(thisTab);
                    }
                    
                    if (typeof dataDestination[thisTab]==='undefined')
                    {
                        dataDestination[thisTab] = [];
                    }
                    
                    dataDestination = dataDestination[thisTab];
                }			
            }		
            
            if (this._hasTime)
            {
                var thisTime = lineValues[this._timeColumnIndex];
                if ((thisTime !== null)&&(thisTime!=''))
                {
                    if (typeof this._foundTimes[thisTime] === 'undefined')
                    {
                        this._foundTimes[thisTime] = true;
                    }
                    
                    if (typeof dataDestination[thisTime] === 'undefined')
                    {				
                        dataDestination[thisTime] = [];
                    }

                    dataDestination = dataDestination[thisTime];
                }
            }

            dataDestination.push(lineValues);	
        }
        
    };

    this.loadPointValues = function(linesArray, headerLine, columnSeperator)
    {	
        this._foundTimes = {};
        this._tabInfo = {};
        this._tabNames = [];
            
        this._valueData = [];
        
        for(var i = 1; i < linesArray.length; i++ )
        {
            var lineString = linesArray[i];
            var lineValues = this.decodeCSVRow(lineString, columnSeperator);
            
            var thisLatitude = (Number)(lineValues[this._latitudeColumnIndex]);
            var thisLongitude = (Number)(lineValues[this._longitudeColumnIndex]);

            lineValues[this._latitudeColumnIndex] = thisLatitude;
            lineValues[this._longitudeColumnIndex] = thisLongitude;

            if (this._valueColumnIndex!==-1)
            {
                var thisValue = (Number)(lineValues[this._valueColumnIndex]);
                lineValues[this._valueColumnIndex] = thisValue;
                
                if ((i===1)||(thisValue<this._smallestValue))
                    this._smallestValue = thisValue;
                
                if ((i===1)||(thisValue>this._largestValue))
                    this._largestValue = thisValue;
            }
            
            var dataDestination = this._valueData;
            
            if (this._hasTabs)
            {
                var thisTab = lineValues[this._tabColumnIndex];
                if (thisTab !== null)
                {
                    if (typeof this._tabInfo[thisTab] === 'undefined')
                    {
                        this._tabInfo[thisTab] = {};
                        this._tabNames.push(thisTab);					
                        dataDestination[thisTab] = [];
                    }
                    
                    dataDestination = dataDestination[thisTab];
                }			
            }		
            
            if (this._hasTime)
            {
                var thisTime = lineValues[this._timeColumnIndex];
                if ((thisTime !== null)&&(thisTime!=''))
                {
                    if (typeof this._foundTimes[thisTime] === 'undefined')
                    {
                        this._foundTimes[thisTime] = true;
                        dataDestination[thisTime] = [];
                    }
                    
                    dataDestination = dataDestination[thisTime];
                }
            }
            
            dataDestination.push(lineValues);	
        }		
    };

    this.getColorForValue = function(thisValue, minValue, maxValue, valueScale)
    {	
        var normalizedValue = ((thisValue-minValue)*valueScale); 
        normalizedValue = Math.min(normalizedValue, 1.0);
        normalizedValue = Math.max(normalizedValue, 0.0);
        
        var fractionalIndex = (normalizedValue*(this._colorGradient.length-1));
        
        var lowerIndex = Math.floor(fractionalIndex);
        var higherIndex = Math.ceil(fractionalIndex);
        var lerpValue = (fractionalIndex-lowerIndex);
        var oneMinusLerp = (1.0-lerpValue);
        
        var lowerValue = this._colorGradient[lowerIndex];
        var higherValue = this._colorGradient[higherIndex];
        
        var alpha = ((lowerValue.alpha*oneMinusLerp)+(higherValue.alpha*lerpValue));
        var red = ((lowerValue.red*oneMinusLerp)+(higherValue.red*lerpValue));
        var green = ((lowerValue.green*oneMinusLerp)+(higherValue.green*lerpValue));
        var blue = ((lowerValue.blue*oneMinusLerp)+(higherValue.blue*lerpValue));
        
        var setColor = ((alpha<<24)|(red<<16)|(green<<8)|(blue<<0));
        
        return setColor;
    };

    this.getValuePointsNearLatLon = function(lat, lon, radius)
    {
        if (radius===0)
            radius = this._settings.point_blob_radius;
        
        var radiusSquared = (radius*radius);

        var currentValues = this.getCurrentValues();
            
        var result = [];
        for (var valuesIndex in currentValues)
        {
            var values = currentValues[valuesIndex];
            
            var valueLat = this.values[_latitudeColumnIndex];
            var valueLon = this.values[_longitudeColumnIndex];
            
            var deltaLat = (valueLat-lat);
            var deltaLon = (valueLon-lon);
            
            var distanceSquared = ((deltaLat*deltaLat)+(deltaLon*deltaLon));
            
            if (distanceSquared<radiusSquared)
            {
                var output = {};
                for(var headerIndex = 0; headerIndex < this._valueHeaders.length; headerIndex++ )
                {
                    var header = '"'+this._valueHeaders[headerIndex].toLowerCase()+'"';

                    output[header] = values[headerIndex];
                }
                
                result.push(output);
            }
        
        }
        
        return result;
    };

    this.setSetting = function(key, value)
    {
        if (!this._settings.hasOwnProperty(key))
        {
            this.logError('Unknown key in setSetting('+key+')');
            return;
        }

        if (typeof this._settings[key] === "boolean")
        {	
            if (typeof value === 'string')
            {
                value = (value==='true');
            }
                
            this._settings[key] = (Boolean)(value);
        }
        else
        {
            this._settings[key] = value;
        }
            
        var changeHandlers =
        {
            'title_text': function(instance) {
                instance._title.htmlText = '<p align="center"><u>'+instance._settings.title_text+'</u></p>';
                if (instance._settings.title_text!=='')
                    instance._title.y = 0;
                else
                    instance._title.y = -1000;
            },
            'time_range_start': function(instance) {
                instance.calculateFrameTimes();
                instance.updateTimelineDisplay();
            },
            'time_range_end': function(instance) {
                instance.calculateFrameTimes();
                instance.updateTimelineDisplay();
            },
            'point_blob_radius': function(instance) {
                instance._valuesDirty = true;
                instance._dirty = true;
            },
            'point_blob_value': function(instance) {
                instance._valuesDirty = true;
                instance._dirty = true;
            },
            'gradient_value_min': function(instance) {
                instance._settings.is_gradient_value_range_set =
                    ((instance._settings.gradient_value_min!=0)||
                    (instance._settings.gradient_value_max!=0));
                instance._valuesDirty = true;
                instance._dirty = true;
            },
            'gradient_value_max': function(instance) {
                instance._settings.is_gradient_value_range_set =
                    ((instance._settings.gradient_value_min!=0)||
                    (instance._settings.gradient_value_max!=0));
                instance._valuesDirty = true;
                instance._dirty = true;
            },
            'ocean_color': function(instance) {
                if (typeof instance._settings.ocean_color === 'string')
                {
                    instance._settings.ocean_color = instance._settings.ocean_color.replace('#', '0x');
                    instance._settings.ocean_color = (Number)(instance._settings.ocean_color);
                }
            },
            'title_background_color': function(instance) {
                if (typeof instance._settings.title_background_color === 'string')
                {
                    instance._settings.title_background_color = instance._settings.title_background_color.replace('#', '0x');
                    instance._settings.title_background_color = (Number)(instance._settings.title_background_color);
                }
                instance._title.backgroundColor = instance._settings.title_background_color;
            },
            'show_map_tiles': function(instance) {
                if (typeof instance._settings.show_map_tiles==='string')
                    instance._settings.show_map_tiles = (Boolean)(instance._settings.show_map_tiles);
                instance._mapTilesDirty = true;
            },
            'information_alpha': function(instance) {
                instance.setWayDefault('alpha', instance._settings.information_alpha);
            }
        }
        
        if (changeHandlers.hasOwnProperty(key))
            changeHandlers[key](this);
    };

    this.repositionMoveableElements = function()
    {/*
        if (_credit !== null)
        {
            _credit.x = (_settings.width-120);
            _credit.y = (_settings.height-20);
        }
            
        if (_title !== null)
        {
            _title.width = _settings.width;
            _title.x = 0;
        }

        if (_timelineControls !== null)
        {
            var verticalCenter: Number = ((_settings.height/2)-40);
            _timelineControls.y = (_settings.height-50);
        }
    */
    };

    this.getLatLonViewingArea = function()
    {
        var topLeftScreen = new Point(0, 0);
        var bottomRightScreen = new Point(this._settings.width, this._settings.height);
            
        var topLeftLatLon = this.getLatLonFromXY(topLeftScreen, _xYToLatLonMatrix);
        var bottomRightLatLon = this.getLatLonFromXY(bottomRightScreen, _xYToLatLonMatrix);

        var result = {
            topLat: topLeftLatLon.lat,
            leftLon: topLeftLatLon.lon,
            bottomLat: bottomRightLatLon.lat,
            rightLon: bottomRightLatLon.lon
        };
        
        return result;
    };

    this.removeAllInlays = function()
    {
        this._inlays	= [];
        
        this._dirty = true;
    };

    this.removeAllWays = function()
    {
        this._ways = {};
        this._nodes = {};

        this._tagMap = {};
        this._lastSetWayIds = {};
        
        this._dirty = true;
    };

    this.getAllInlays = function()
    {
        var result = [];
        
        for (var inlayIndex in this._inlays)
        {
            var inlay = this._inlays[inlayIndex];
        
            var topLeftScreen = this.getXYFromLatLon(inlay.worldTopLeftLatLon, _latLonToXYMatrix);
            var bottomRightScreen = this.getXYFromLatLon(inlay.worldBottomRightLatLon, _latLonToXYMatrix);
            
            var outputInlay =
            {
                left_x: topLeftScreen.x,
                top_y: topLeftScreen.y,
                right_x: bottomRightScreen.x,
                bottom_y: bottomRightScreen.y,
                top_lat: inlay.topLat,
                left_lon: inlay.leftLon,
                bottom_lat: inlay.bottomLat,
                right_lon: inlay.rightLon
            };

            result.push(outputInlay);
        }
        
        return result;
    };
/*
private function addPopup(lat: Number, lon: Number, text: String): void
{
	var popup: Object =
	{
		originLatLon: { lat: lat, lon: lon },
		text: text
	};

	popup.uiComponent = new TextArea();
	popup.uiComponent.htmlText = text;

	popup.uiComponent.wordWrap = false;
	popup.uiComponent.horizontalScrollPolicy = "ScrollPolicy.OFF";
	popup.uiComponent.verticalScrollPolicy = "ScrollPolicy.OFF";
	
	var dropShadowFilter: DropShadowFilter = new DropShadowFilter (5,65,0x000000,0.3,5,10,2,3,false,false,false);
	popup.uiComponent.filters = [dropShadowFilter];

	viewer.addChild(popup.uiComponent);
	
	var screenPos: Point = getXYFromLatLon(popup.originLatLon, _latLonToXYMatrix);
	
	popup.uiComponent.validateNow();
	
	popup.uiComponent.width = (popup.uiComponent.textWidth+10);
	popup.uiComponent.height = (popup.uiComponent.textHeight+20);
	popup.uiComponent.x = (screenPos.x-popup.uiComponent.width);
	popup.uiComponent.y = (screenPos.y-popup.uiComponent.height);

	if (popup.uiComponent.x<0)
	{
		popup.uiComponent.x = 0;
		popup.uiComponent.wordWrap = true;
		popup.uiComponent.width = screenPos.x;
		popup.uiComponent.validateNow();
		popup.uiComponent.height = (popup.uiComponent.textHeight+5);
	}
	
	if (popup.uiComponent.y<0)
	{
		popup.uiComponent.y = 0;
	}

	if ((popup.uiComponent.y+popup.uiComponent.height)>_settings.height)
	{
		popup.uiComponent.y = (_settings.height-popup.uiComponent.height);		
	}

	_popups.push(popup);
}

private function removeAllPopups(): void
{
	for each (var popup: Object in _popups)
	{
		viewer.removeChild(popup.uiComponent);	
	}
	
	_popups = [];
}*/

    this.createURLForTile = function(latIndex, lonIndex, zoomIndex)
    {
        var result = this._settings.map_server_root;
        result += zoomIndex;
        result += '/';
        result += lonIndex;
        result += '/';
        result += latIndex;
        result += '.png';

        return result;	
    };

    this.drawMapTiles = function(canvas, width, height, latLonToXYMatrix, xYToLatLonMatrix)
    {
        var viewingArea = this.calculateViewingArea(width, height, xYToLatLonMatrix);
        
        var wantedTiles = this.prepareMapTiles(viewingArea, latLonToXYMatrix, xYToLatLonMatrix, width, height);

        var areAllLoaded = true;

        for (var currentURLIndex in wantedTiles)
        {
            var currentURL = wantedTiles[currentURLIndex];
            if (!this._mapTiles[currentURL].imageLoader._isLoaded)
                areAllLoaded = false;
        }

        var mapTilesURLs = [];
        if (areAllLoaded)
        {
            mapTilesURLs = wantedTiles;
        }
        else
        {
            for (currentURL in this._mapTiles)
            {
                mapTilesURLs.push(currentURL);
            }
        }

        for (currentURLIndex in mapTilesURLs)
        {
            var currentURL = mapTilesURLs[currentURLIndex];
            
            var tile = this._mapTiles[currentURL];

            if (!viewingArea.intersects(tile.boundingBox))
                continue;

            if (!tile.imageLoader._isLoaded)
                continue;
            
            var screenTopLeft = this.getXYFromLatLon(tile.topLeftLatLon, latLonToXYMatrix);
            var screenBottomRight = this.getXYFromLatLon(tile.bottomRightLatLon, latLonToXYMatrix);
            
            var screenLeft = screenTopLeft.x;
            var screenTop = screenTopLeft.y;
        
            var screenWidth = (screenBottomRight.x-screenTopLeft.x);
            var screenHeight = (screenBottomRight.y-screenTopLeft.y);

            this.drawImage(canvas, tile.imageLoader._image, screenLeft, screenTop, screenWidth, screenHeight);
        }
    };

    this.getTileIndicesFromLatLon = function(lat, lon, zoomLevel)
    {
        var mercatorLatitudeOrigin = this.latitudeToMercatorLatitude(this._settings.map_tile_origin_lat);
        var mercatorLatitudeHeight = this.latitudeToMercatorLatitude(this._settings.world_lat_height+this._settings.map_tile_origin_lat)-mercatorLatitudeOrigin;
        
        var zoomTileCount = (1<<zoomLevel);
        var zoomPixelsPerDegreeLatitude = ((this._settings.map_tile_height/mercatorLatitudeHeight)*zoomTileCount);
        var zoomPixelsPerDegreeLongitude = ((this._settings.map_tile_width/this._settings.world_lon_width)*zoomTileCount);

        var tileWidthInDegrees = (this._settings.map_tile_width/zoomPixelsPerDegreeLongitude);
        var tileHeightInDegrees = (this._settings.map_tile_height/zoomPixelsPerDegreeLatitude);

        var latIndex = ((this.latitudeToMercatorLatitude(lat)-mercatorLatitudeOrigin)/tileHeightInDegrees);
        latIndex = Math.max(latIndex, 0);
        latIndex = Math.min(latIndex, (zoomTileCount-1));
        
        var lonIndex = ((lon-this._settings.map_tile_origin_lon)/tileWidthInDegrees);
        lonIndex = Math.max(lonIndex, 0);
        lonIndex = Math.min(lonIndex, (zoomTileCount-1));
        
        var result = {
            latIndex: latIndex,
            lonIndex: lonIndex
        };
        
        return result;
    };

    this.getLatLonFromTileIndices = function(latIndex, lonIndex, zoomLevel)
    {
        var mercatorLatitudeOrigin = this.latitudeToMercatorLatitude(this._settings.map_tile_origin_lat);
        var mercatorLatitudeHeight = this.latitudeToMercatorLatitude(this._settings.world_lat_height+this._settings.map_tile_origin_lat)-mercatorLatitudeOrigin;
        
        var zoomTileCount = (1<<zoomLevel);
        var zoomPixelsPerDegreeLatitude = ((this._settings.map_tile_height/mercatorLatitudeHeight)*zoomTileCount);
        var zoomPixelsPerDegreeLongitude = ((this._settings.map_tile_width/this._settings.world_lon_width)*zoomTileCount);

        var tileWidthInDegrees = (this._settings.map_tile_width/zoomPixelsPerDegreeLongitude);
        var tileHeightInDegrees = (this._settings.map_tile_height/zoomPixelsPerDegreeLatitude);

        var lat = ((latIndex*tileHeightInDegrees)+mercatorLatitudeOrigin);
        var lon = ((lonIndex*tileWidthInDegrees)+this._settings.map_tile_origin_lon);
        
        var result = {
            lat: this.mercatorLatitudeToLatitude(lat),
            lon: lon
        };
        
        return result;
    };

    this.prepareMapTiles = function(viewingArea, latLonToXYMatrix, xYToLatLonMatrix, width, height)
    {	
        var pixelsPerDegreeLatitude = latLonToXYMatrix.d;
        
        var zoomPixelsPerDegreeLatitude = (this._settings.map_tile_height/this._settings.world_lat_height);
        var zoomLevel = 0;
        while (Math.abs(zoomPixelsPerDegreeLatitude*this._settings.map_tile_match_factor)<Math.abs(pixelsPerDegreeLatitude))
        {
            zoomLevel += 1;
            zoomPixelsPerDegreeLatitude *= 2;	
        }

        var zoomTileCount = (1<<zoomLevel);
        var zoomPixelsPerDegreeLongitude = ((this._settings.map_tile_width/this._settings.world_lon_width)*zoomTileCount);
        
        var tileWidthInDegrees = (this._settings.map_tile_width/zoomPixelsPerDegreeLongitude);
        var tileHeightInDegrees = (this._settings.map_tile_height/zoomPixelsPerDegreeLatitude);

        var start = this.getTileIndicesFromLatLon(viewingArea.bottom(), viewingArea.left(), zoomLevel);
        start.latIndex = Math.floor(start.latIndex);
        start.lonIndex = Math.floor(start.lonIndex);

        var end = this.getTileIndicesFromLatLon(viewingArea.top(), viewingArea.right(), zoomLevel);
        end.latIndex = Math.ceil(end.latIndex);
        end.lonIndex = Math.ceil(end.lonIndex);

        var wantedTiles = [];

        for (var latIndex = start.latIndex; latIndex<=end.latIndex; latIndex+=1)
        {
            for (var lonIndex = start.lonIndex; lonIndex<=end.lonIndex; lonIndex+=1)
            {
                var wantedTile = {};
            
                wantedTile.latIndex = latIndex;
                wantedTile.lonIndex = lonIndex;
                wantedTile.zoomIndex = zoomLevel;
                
                wantedTile.topLeftLatLon = this.getLatLonFromTileIndices(latIndex, lonIndex, zoomLevel);
                wantedTile.bottomRightLatLon = this.getLatLonFromTileIndices((latIndex+1), (lonIndex+1), zoomLevel);

                wantedTile.boundingBox = new Rectangle();			
                wantedTile.boundingBox = this.enlargeBoxToContain(wantedTile.boundingBox, new Point(wantedTile.topLeftLatLon.lon, wantedTile.topLeftLatLon.lat));
                wantedTile.boundingBox = this.enlargeBoxToContain(wantedTile.boundingBox, new Point(wantedTile.bottomRightLatLon.lon, wantedTile.bottomRightLatLon.lat));	
            
                wantedTiles.push(wantedTile);
            }
        }
        
        var result = [];
        
        for (var wantedTileIndex in wantedTiles)
        {
            var wantedTile = wantedTiles[wantedTileIndex];
            
            var wantedURL = this.createURLForTile(wantedTile.latIndex, wantedTile.lonIndex, wantedTile.zoomIndex);
            
            if (!this._mapTiles.hasOwnProperty(wantedURL))
            {
                this._mapTiles[wantedURL] = {};
                
                this._mapTiles[wantedURL].imageLoader = new ExternalImageView(wantedURL, this._settings.map_tile_width, this._settings.map_tile_height, this);
                
                this._mapTiles[wantedURL].topLeftLatLon = wantedTile.topLeftLatLon;
                this._mapTiles[wantedURL].bottomRightLatLon = wantedTile.bottomRightLatLon;
                this._mapTiles[wantedURL].boundingBox = wantedTile.boundingBox;
            }
            
            this._mapTiles[wantedURL].isUsedThisFrame = true;
            
            result.push(wantedURL);
        }
        
        return result;
    }

    this.mercatorLatitudeToLatitude = function(mercatorLatitude) {
        var result = (180/Math.PI) * (2 * Math.atan(Math.exp((mercatorLatitude*2)*Math.PI/180)) - Math.PI/2);
	
        return result;
    };

    this.latitudeToMercatorLatitude = function(latitude) { 
        var result = (180/Math.PI) * Math.log(Math.tan(Math.PI/4+latitude*(Math.PI/180)/2));
	
        return (result/2);
    };

    this.calculateViewingArea = function(width, height, xYToLatLonMatrix)
    {
        var viewingArea = new Rectangle();
        
        var topLeftScreen = new Point(0, 0);
        var bottomRightScreen = new Point(width, height);
            
        var topLeftLatLon = this.getLatLonFromXY(topLeftScreen, xYToLatLonMatrix);
        var bottomRightLatLon = this.getLatLonFromXY(bottomRightScreen, xYToLatLonMatrix);
        
        viewingArea = this.enlargeBoxToContain(viewingArea, new Point(topLeftLatLon.lon, topLeftLatLon.lat));
        viewingArea = this.enlargeBoxToContain(viewingArea, new Point(bottomRightLatLon.lon, bottomRightLatLon.lat));	

        return viewingArea;	
    };

    this.trackMapTilesUsage = function()
    {
        for (var currentURL in this._mapTiles)
        {
            this._mapTiles[currentURL].isUsedThisFrame = false;	
        }	
    };

    this.deleteUnusedMapTiles = function()
    {
        var areAllLoaded = true;

        for (var currentURL in this._mapTiles)
        {
            if (this._mapTiles[currentURL].isUsedThisFrame&&
                !this._mapTiles[currentURL].imageLoader._isLoaded)
                areAllLoaded = false;
        }

        this._mapTilesDirty = false;
        
        if (areAllLoaded)
        {
            for (var currentURL in this._mapTiles)
            {
                if (!this._mapTiles[currentURL].isUsedThisFrame)
                {
                    this._mapTiles[currentURL].imageLoader = null;
                    delete this._mapTiles[currentURL];
                    this._mapTilesDirty = true;
                }	
            }
        }			
    };

    this.getValueHeaders = function()
    {
        return this._valueHeaders;	
    };

    this.addPopupAtScreenPosition = function(x, y, text)
    {
        var latLon = this.getLatLonFromXY(new Point(x, y), this._xYToLatLonMatrix);
        
        this.addPopup(latLon.lat, latLon.lon, text);	
    };

    this.getCurrentValues = function()
    {
        var currentValues = this._valueData;	

        if (this._hasTabs)
        {
            var currentTab = this._tabNames[_selectedTabIndex];
            currentValues = currentValues[currentTab];
        }
        
        if (this._hasTime)
        {
            var currentTime = this._frameTimes[this._frameIndex];
            currentValues = currentValues[currentTime];
        }

        return currentValues;
    };
/*
private function drawTabsIntoViewer(): void
{
	var tabCount: int = _tabNames.length;
		
	var tabHeight: Number = _settings.tab_height;
	
	var tabTopY: Number;
	if (_settings.title_text!=='')
		tabTopY = (_settings.title_size*1.5);
	else
		tabTopY = 0;
	
	var tabBottomY: Number = (tabTopY+tabHeight);
	
	var graphics: Graphics = viewer.graphics;
	
	var tabLeftX: Number = 0;
	
	for (var tabIndex:int = 0; tabIndex<tabCount; tabIndex+=1)
	{
		var isLast: Boolean = (tabIndex==(tabCount-1));
		var isSelected: Boolean = (tabIndex===_selectedTabIndex);
		var isHovered: Boolean = (tabIndex===_hoveredTabIndex);

		var tabName: String = _tabNames[tabIndex];
		var tabInfo: Object = _tabInfo[tabName];
		
		var textfield:TextField = new TextField;
		textfield.text = tabName;
		var tabWidth: int = (textfield.textWidth+5);		
		textfield.width = tabWidth;
		
		var tabRightX: Number = (tabLeftX+tabWidth);
		var distanceFromEdge: Number = (_settings.width-tabRightX);
		var addExtraTab: Boolean = (isLast&&(distanceFromEdge>50));
		
		if (isLast&&!addExtraTab)
		{
			tabRightX = (_settings.width-1);
			tabWidth = (tabRightX-tabLeftX);
		}

		tabInfo.leftX = tabLeftX;
		tabInfo.rightX = tabRightX;
		tabInfo.topY = tabTopY;
		tabInfo.bottomY = tabBottomY;
		
		if (tabWidth<1)
			continue;
		
		var fillColor: uint;
		if (isSelected)
			fillColor = _settings.title_background_color;
		else if (isHovered)
			fillColor = scaleColorBrightness(_settings.title_background_color, 0.95);
		else
			fillColor = scaleColorBrightness(_settings.title_background_color, 0.9);
		
		graphics.lineStyle();

		graphics.beginFill(fillColor, 1.0);	
		graphics.moveTo(tabLeftX, tabTopY);
		graphics.lineTo(tabRightX, tabTopY);
		graphics.lineTo(tabRightX, tabBottomY);
		graphics.lineTo(tabLeftX, tabBottomY);
		graphics.lineTo(tabLeftX, tabTopY);
		graphics.endFill();

		var bitmapdata:BitmapData = new BitmapData(tabWidth, tabHeight, true, 0x00000000);
		bitmapdata.draw(textfield);
		
		var textMatrix: Matrix = new Matrix();
		textMatrix.translate(tabLeftX, tabTopY);
		
		graphics.beginBitmapFill(bitmapdata, textMatrix);
		graphics.drawRect(tabLeftX, tabTopY, tabWidth, tabHeight);
		graphics.endFill();

		graphics.lineStyle(0, 0x000000, 1.0);
		graphics.moveTo(tabLeftX, tabBottomY);
		graphics.lineTo(tabLeftX, tabTopY);
		graphics.lineTo(tabRightX, tabTopY);
		graphics.lineTo(tabRightX, tabBottomY);
		if (!isSelected)
			graphics.lineTo(tabLeftX, tabBottomY);

		tabLeftX = tabRightX;

		if (addExtraTab)
		{
			tabRightX = (_settings.width-1);
			
			fillColor = scaleColorBrightness(_settings.title_background_color, 0.9);
			
			graphics.beginFill(fillColor, 1.0);	
			graphics.moveTo(tabLeftX, tabTopY);
			graphics.lineTo(tabRightX, tabTopY);
			graphics.lineTo(tabRightX, tabBottomY);
			graphics.lineTo(tabLeftX, tabBottomY);
			graphics.lineTo(tabLeftX, tabTopY);
			graphics.endFill();

			graphics.lineStyle(0, 0x000000, 1.0);
			graphics.moveTo(tabLeftX, tabBottomY);
			graphics.lineTo(tabLeftX, tabTopY);
			graphics.lineTo(tabRightX, tabTopY);
			graphics.lineTo(tabRightX, tabBottomY);
		}
		
	}
	
	graphics.lineStyle(0, 0x000000, 1.0);
	graphics.moveTo(0, tabBottomY);
	graphics.lineTo(0, (_settings.height-1));
	graphics.lineTo((_settings.width-1), (_settings.height-1));
	graphics.lineTo((_settings.width-1), tabBottomY);
}

private function scaleColorBrightness(colorNumber: uint, scale: Number): uint
{
	var alpha: uint = (colorNumber>>24)&0xff;
	var red: uint = (colorNumber>>16)&0xff;
	var green: uint = (colorNumber>>8)&0xff;
	var blue: uint = (colorNumber>>0)&0xff;
	
	var resultAlpha: uint = alpha; // We'll end up with 'illegal' premult color values, but this shouldn't be a proble for our uses
	var resultRed: uint = Math.floor(red*scale);
	var resultGreen: uint = Math.floor(green*scale);
	var resultBlue: uint = Math.floor(blue*scale);
	
	resultRed = Math.max(0, resultRed);
	resultGreen = Math.max(0, resultGreen);
	resultBlue = Math.max(0, resultBlue);
		
	resultRed = Math.min(255, resultRed);
	resultGreen = Math.min(255, resultGreen);
	resultBlue = Math.min(255, resultBlue);
	
	var result: uint =
		(resultAlpha<<24)|
		(resultRed<<16)|
		(resultGreen<<8)|
		(resultBlue<<0);
	
	return result;
}
*/
    this.isEventInTopBar = function(event)
    {
        var hasTitle = (this._settings.title_text!=='');
        
        if ((!hasTitle)&&(!this._hasTabs))
            return false;
        
        var tabHeight = this._settings.tab_height;
        
        var tabTopY;
        if (hasTitle)
            tabTopY = (this._settings.title_size*1.5);
        else
            tabTopY = 0;
        
        var tabBottomY = (tabTopY+tabHeight);
        
        var localPosition = this.getLocalPosition($(event.target), event.pageX, event.pageY);
        
        return (localPosition.y<tabBottomY);
    };

    this.onTopBarClick = function(event)
    {
        var tabIndex = this.getTabIndexFromEvent(event);
        
        if (tabIndex!==-1)
        {
            this._selectedTabIndex = tabIndex;
            this._valuesDirty = true;
            this._dirty = true;
        }
        
        return true;
    };
	
    this.onTopBarDoubleClick = function(event)
    {
        var tabIndex = this.getTabIndexFromEvent(event);
        
        if (tabIndex!==-1)
        {
            this._selectedTabIndex = tabIndex;
            this._valuesDirty = true;
            this._dirty = true;
        }
        
        return true;
    };
	
    this.onTopBarMouseDown = function(event)
    {	
        return true;	
    };

    this.onTopBarMouseUp = function(event)
    {	
        return true;		
    };

    this.onTopBarMouseOver = function(event)
    {
        return true;	
    };

    this.onTopBarMouseOut = function(event)
    {
        return true;	
    };
	
    this.onTopBarMouseMove = function(event)
    {
        var tabIndex = this.getTabIndexFromEvent(event);
        
        this._hoveredTabIndex = tabIndex;
        
        return true;	
    };
	
    this.getTabIndexFromEvent = function(event)
    {
        var localPosition = this.getLocalPosition($(event.target), event.pageX, event.pageY);

        var x = localPosition.x;
        var y = localPosition.y;
        
        for (var tabIndex = 0; tabIndex<this._tabNames.length; tabIndex+=1)
        {
            var tabName = this._tabNames[tabIndex];
            var tabInfo = this._tabInfo[tabName];
            
            if ((x>=tabInfo.leftX)&&
                (x<tabInfo.rightX)&&
                (y>=tabInfo.topY)&&
                (y<tabInfo.bottomY))
                return tabIndex;
        }
        
        return -1;
    };

    this.createPointsGrid = function(viewingArea, latLonToXYMatrix)
    {
        if (!this._hasPointValues)
            return;

        var blobRadius;
        if (this._settings.is_point_blob_radius_in_pixels)
        {	
            var pixelsPerDegreeLatitude = latLonToXYMatrix.d;
            blobRadius = Math.abs(this._settings.point_blob_radius/pixelsPerDegreeLatitude);
        }
        else
        {
            blobRadius = this._settings.point_blob_radius;	
        }
        var twoBlobRadius = (2*blobRadius);
        var pointBlobValue = this._settings.point_blob_value;

        this._pointsGrid = new BucketGrid(viewingArea, 64, 64);
        
        var currentValues = this.getCurrentValues();
        
        var hasValues = (this._valueColumnIndex!==-1);
        
        var index = 0;
        for (var valuesIndex in currentValues)
        {
            var values = currentValues[valuesIndex];
            
            var lat = values[this._latitudeColumnIndex];
            var lon = values[this._longitudeColumnIndex];
            var pointValue;
            if (hasValues)
                pointValue = values[this._valueColumnIndex];
            else
                pointValue = pointBlobValue;
            
            var boundingBox = new Rectangle(lon-blobRadius, lat-blobRadius, twoBlobRadius, twoBlobRadius);
            
            if (!viewingArea.intersects(boundingBox))
                continue;
            
            var latLon = { 
                pos: new Point(lon, lat),
                index: index,
                value: pointValue
            };
            
            this._pointsGrid.insertObjectAt(boundingBox, latLon);
            
            index += 1;
        }		
    };

    this.drawPointBlobTile = function(width, 
        height, 
        viewingArea, 
        latLonToXYMatrix, 
        xYToLatLonMatrix, 
        leftX,
        topY,
        tileWidth, 
        tileHeight)
    {
        var bitmapWidth = this._pointBlobBitmapWidth;
        var bitmapHeight = this._pointBlobBitmapHeight;
        
        var rightX = (leftX+tileWidth);
        var bottomY = (topY+tileHeight);
        
        var blobRadius;
        if (this._settings.is_point_blob_radius_in_pixels)
        {	
            var pixelsPerDegreeLatitude = latLonToXYMatrix.d;
            blobRadius = Math.abs(this._settings.point_blob_radius/pixelsPerDegreeLatitude);
        }
        else
        {
            blobRadius = this._settings.point_blob_radius;	
        }
        var twoBlobRadius = (2*blobRadius);
        var blobRadiusSquared = (blobRadius*blobRadius);
        
        if (this._settings.is_gradient_value_range_set)
        {
            var minValue = this._settings.gradient_value_min;
            var maxValue = this._settings.gradient_value_max;	
        }
        else
        {
            minValue = 0;
            maxValue = 1.0;
        }
        var valueScale = (1/(maxValue-minValue));
        
        var hasValues = (this._valueColumnIndex!==-1);
        
        var leftLon = viewingArea.left();
        var rightLon = viewingArea.right();
        var widthLon = (rightLon-leftLon);
        var stepLon = (widthLon/bitmapWidth);
        
        var topLat = viewingArea.bottom();
        var bottomLat = viewingArea.top();
        
        var topLatMercator = this.latitudeToMercatorLatitude(topLat);
        var bottomLatMercator = this.latitudeToMercatorLatitude(bottomLat);
        var heightLat = (bottomLatMercator-topLatMercator);
        var stepLat = (heightLat/bitmapHeight);
        
        var context = this.beginDrawing(this._pointBlobCanvas);
        var imageData = context.createImageData(tileWidth, tileHeight);
        
        var pixelData = imageData.data;
        var pixelDataIndex = 0;
        
        var zeroColor = this.getColorForValue(0, minValue, maxValue, valueScale);
        var fullColor = this.getColorForValue(maxValue, minValue, maxValue, valueScale);
        
        var worldPoint = new Point();
        for (var bitmapY = topY; bitmapY<bottomY; bitmapY+=1)
        {
            worldPoint.y = this.mercatorLatitudeToLatitude(topLatMercator+(stepLat*bitmapY));
            for (var bitmapX = leftX; bitmapX<rightX; bitmapX+=1)
            {			
                worldPoint.x = (leftLon+(stepLon*bitmapX));
                
                var candidatePoints = this._pointsGrid.getContentsAtPoint(worldPoint);
                
                if (candidatePoints.length<1)
                {
                    this.writePixel(pixelData, pixelDataIndex, zeroColor);
                    pixelDataIndex += 4;
                    continue;
                }
                
                var value = 0;
                var lerpTotal = 0;
                
                for (var pointIndex in candidatePoints)
                {
                    var point = candidatePoints[pointIndex];
                    
                    var pos = point.pos;
                    var delta = worldPoint.subtract(pos);
                    var distanceSquared = ((delta.x*delta.x)+(delta.y*delta.y));
                    if (distanceSquared>blobRadiusSquared)
                        continue;
                    
                    var distance = Math.sqrt(distanceSquared);
                    var lerp = (1-(distance/blobRadius));
                    
                    value += (point.value*lerp);
                    lerpTotal += lerp;
                }
                
                var color;
                if (hasValues)
                {
                    if (lerpTotal>0)
                    {
                        value = (value/lerpTotal);	
                    }
                    else
                    {
                        value = 0;
                    }
                    
                    var alpha = Math.floor(255*(Math.min(lerpTotal, 1.0)));
                    
                    color = this.getColorForValue(value, minValue, maxValue, valueScale);
                    
                    var colorAlpha = (color>>24)&0xff;
                    var outputAlpha = ((colorAlpha*alpha)>>8)&0xff;
                    
                    color = (color&0x00ffffff)|(outputAlpha<<24);
                }
                else
                {
                    if (value>=maxValue)
                    {
                        this.writePixel(pixelData, pixelDataIndex, fullColor);
                        pixelDataIndex += 4;
                        continue;
                    }
                    
                    color = getColorForValue(value, minValue, maxValue, valueScale);
                }

                this.writePixel(pixelData, pixelDataIndex, color);
                pixelDataIndex += 4;
            }	
        }

        context.putImageData(imageData, leftX, topY);
        
        this.endDrawing(context);
    };

    this.beginDrawing = function(canvas) {
        if (!canvas)
            canvas = this._canvas;
            
        var context = canvas.get(0).getContext('2d');
        context.save();
        return context;
    };

    this.endDrawing = function(context) {
        context.restore();
    };
    
    this.getLocalPosition = function(element, pageX, pageY) {
        var elementPosition = element.elementLocation();

        var result = new Point(
            (pageX-elementPosition.x),
            (pageY-elementPosition.y)
        );

        return result;
    };

    this.clearCanvas = function(canvas) {
        var context = this.beginDrawing(canvas);
        
        context.clearRect(0, 0, this._settings.width, this._settings.height);
        
        this.endDrawing(context);
    };

    // From http://stackoverflow.com/questions/359788/javascript-function-name-as-a-string   
    this.externalInterfaceCall = function(functionName) {
        var args = Array.prototype.slice.call(arguments).splice(1);
        var namespaces = functionName.split(".");
        var func = namespaces.pop();
        var context = window;
        for(var i = 0; i < namespaces.length; i++) {
            context = context[namespaces[i]];
        }
        return context[func].apply(this, args);
    };
    
    this.createCanvas = function(width, height) {
        return $(
            '<canvas '
            +'width="'+width+'" '
            +'height="'+height+'"'
            +'"></canvas>'
        );
    };
    
    this.colorStringFromNumber = function(colorNumber, alpha)
    {
        var red = (colorNumber>>16)&0xff;
        var green = (colorNumber>>8)&0xff;
        var blue = (colorNumber>>0)&0xff;

        if (typeof alpha === 'undefined')
            alpha = 1.0;
            
        var result = 'rgba(';
        result += red;
        result += ',';
        result += green;
        result += ',';
        result += blue;
        result += ',';
        result += alpha;
        result += ')';
        
        return result;
    };
    
    this.drawImage = function(destination, source, x, y, w, h)
    {
        var context = this.beginDrawing(destination);
        context.drawImage(source, x, y, w, h);
        this.endDrawing(context);
    };

    this.fillRect = function(destination, x, y, width, height, color)
    {
        var context = this.beginDrawing(destination);
        context.fillStyle = this.colorStringFromNumber(color);
        context.fillRect(x, y, width, height);
        this.endDrawing(context);
    };
    
    this.writePixel = function(pixelData, index, color)
    {
        var alpha = ((color>>24)&0xff);
        var red = ((color>>16)&0xff);
        var green = ((color>>8)&0xff);
        var blue = ((color>>0)&0xff);
        
        pixelData[index+0] = red;
        pixelData[index+1] = green;
        pixelData[index+2] = blue;
        pixelData[index+3] = alpha;
    };
    
    this.addChild = function(element)
    {
        this._viewerElements.push(element);
    };

    this.drawViewerElements = function(canvas)
    {
        var context = this.beginDrawing(canvas);
        
        for (var elementIndex in this._viewerElements)
        {
            var element = this._viewerElements[elementIndex];
            
            element.draw(context);
        }
        
        this.endDrawing(context);    
    };
    
    this.handleViewerElementEvent = function(event, callback)
    {
        var currentPosition = this.getLocalPosition($(event.target), event.pageX, event.pageY);
        event.localX = currentPosition.x;
        event.localY = currentPosition.y;

        for (var elementIndex in this._viewerElements)
        {
            var element = this._viewerElements[elementIndex];
            if (typeof element[callback] === 'undefined')
                continue;
                
            var result = element[callback](event);
            if (!result)
                return false;
        }
    
        return true;
    };

    this.__constructor(canvas);

    return this;
}
