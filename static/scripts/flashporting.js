function Matrix(a, b, c, d, tx, ty)
{
    if (typeof a === 'undefined')
    {
        a = 1; b = 0;
        c = 0; d = 1;
        tx = 0; ty = 0;
    }
    
    this.a = a;
    this.b = b;
    this.c = c;
    this.d = d;
    this.tx = tx;
    this.ty = ty;
    
    this.transformPoint = function (p) {
        var result = {
            x: (p.x*this.a)+(p.y*this.c)+this.tx,
            y: (p.x*this.b)+(p.y*this.d)+this.ty
        };
    
        return result;
    };
    
    this.translate = function (x, y) {
        this.tx += x;
        this.ty += y;
        
        return this;
    };
    
    this.scale = function (x, y) {
    
        var scaleMatrix = new Matrix(x, 0, 0, y, 0, 0);
        this.concat(scaleMatrix);
        
        return this;
    };
    
    this.concat = function (m) {
    
        this.copy( new Matrix(
            (this.a*m.a)+(this.b*m.c), (this.a*m.b)+(this.b*m.d),
            (this.c*m.a)+(this.d*m.c), (this.c*m.b)+(this.d*m.d),
            (this.tx*m.a)+(this.ty*m.c)+m.tx, (this.tx*m.b)+(this.ty*m.d)+m.ty
        ));
        
        return this;
    };

    this.invert = function () {
    
        var adbc = ((this.a*this.d)-(this.b*this.c));
    
        this.copy(new Matrix(
            (this.d/adbc), (-this.b/adbc),
            (-this.c/adbc), (this.a/adbc),
            (((this.c*this.ty)-(this.d*this.tx))/adbc),
            -(((this.a*this.ty)-(this.b*this.tx))/adbc)
        ));
        
        return this;
    };

    this.clone = function () {
    
        var result = new Matrix(
            this.a, this.b,
            this.c, this.d,
            this.tx, this.ty
        );
        
        return result;
    };

    this.zoomAroundPoint = function (center, zoomFactor) {
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
        
        this.concat(zoom);
        return this;
    }
    
    this.copy = function(m) {
        this.a = m.a;
        this.b = m.b;
        this.c = m.c;
        this.d = m.d;
        this.tx = m.tx;
        this.ty = m.ty;
        
        return this;
    }
}

function Point(x, y)
{
    if (typeof x === 'undefined')
    {
        x = 0;
        y = 0;
    }
    
    this.x = x;
    this.y = y;
    
    this.add = function (p) {
        var result = new Point((this.x+p.x), (this.y+p.y));
        return result;
    };

    this.subtract = function (p) {
        var result = new Point((this.x-p.x), (this.y-p.y));
        return result;
    };
    
    this.dot = function (p) {
        var result = ((this.x*p.x)+(this.y*p.y));
        return result;
    };

    this.cross = function (p) {
        var result = ((this.x*p.y)-(this.y*p.x));
        return result;
    };

}

jQuery.fn.elementLocation = function() 
{
    var curleft = 0;
    var curtop = 0;

    var obj = this;

    do {
        curleft += obj.attr('offsetLeft');
        curtop += obj.attr('offsetTop');

        obj = obj.offsetParent();
    } while ( obj.attr('tagName') != 'BODY' );

    return ( {x:curleft, y:curtop} );
};

function Rectangle(x, y, width, height)
{
    if (typeof x==='undefined')
        x = 0;

    if (typeof y==='undefined')
        y = 0;
        
    if (typeof width==='undefined')
        width = 0;

    if (typeof height==='undefined')
        height = 0;

    this.x = x;
    this.y = y;
    this.width = width;
    this.height = height;

    this.bottom = function(newY) {
        if (typeof newY !== 'undefined')
            this.height = (newY-this.y);
        return (this.y+this.height);
    };
    
    this.bottomRight = function() {
        return new Point(this.right(), this.bottom());
    };

    this.left = function(newX) {
        if (typeof newX !== 'undefined')
        {
            this.width += (this.x-newX);
            this.x = newX;
        }
        return this.x;
    };
    
    this.right = function(newX) {
        if (typeof newX !== 'undefined')
            this.width = (newX-this.x);
        return (this.x+this.width);
    };
    
    this.size = function() {
        return new Point(this.width, this.height);
    };
    
    this.top = function(newY) {
        if (typeof newY !== 'undefined')
        {
            this.height += (this.y-newY);
            this.y = newY;
        }
        return this.y;
    };

    this.topLeft = function() {
        return new Point(this.x, this.y);
    };

    this.clone = function() {
        return new Rectangle(this.x, this.y, this.width, this,height);
    };
    
    this.contains = function(x, y) {
        var isInside = 
            (x>=this.x)&&
            (y>=this.y)&&
            (x<this.right())&&
            (y<this.bottom());
        return isInside;
    };
    
    this.containsPoint = function(point) {
        return this.contains(point.x, point.y);
    };
    
    this.containsRect = function(rect) {
        var isInside = 
            (rect.x>=this.x)&&
            (rect.y>=this.y)&&
            (rect.right()<=this.right())&&
            (rect.bottom()<=this.bottom());
        return isInside;    
    };
    
    this.equals = function(toCompare) {
        var isIdentical =
            (toCompare.x===this.x)&&
            (toCompare.y===this.y)&&
            (toCompare.width===this.width)&&
            (toCompare.height===this.height);
        return isIdentical;
    };
    
    this.inflate = function(dx, dy) {
        this.x -= dx;
        this.y -= dy;
        this.width += (2*dx);
        this.height += (2*dy);
    };
    
    this.inflatePoint = function(point) {
        this.inflate(point.x, point.y);
    };
    
    this.inclusiveRangeContains = function(value, min, max) {
        var isInside =
            (value>=min)&&
            (value<=max);
            
        return isInside;
    };
    
    this.intersectRange = function(aMin, aMax, bMin, bMax) {

        var maxMin = Math.max(aMin, bMin);
        if (!this.inclusiveRangeContains(maxMin, aMin, aMax)||
            !this.inclusiveRangeContains(maxMin, bMin, bMax))
            return null;
            
        var minMax = Math.min(aMax, bMax);
        
        if (!this.inclusiveRangeContains(minMax, aMin, aMax)||
            !this.inclusiveRangeContains(minMax, bMin, bMax))
            return null;
    
        return { min: maxMin, max: minMax };
    };
    
    this.intersection = function(toIntersect) {
        var xSpan = this.intersectRange(
            this.x, this.right(),
            toIntersect.x, toIntersect.right());
        
        if (!xSpan)
            return null;
            
        var ySpan = this.intersectRange(
            this.y, this.bottom(),
            toIntersect.y, toIntersect.bottom());
        
        if (!ySpan)
            return null;
            
        var result = new Rectangle(
            xSpan.min,
            ySpan.min,
            (xSpan.max-xSpan.min),
            (ySpan.max-ySpan.min));
        
        return result;
    };
    
    this.intersects = function(toIntersect) {
        var intersection = this.intersection(toIntersect);
        
        return (typeof intersection !== 'undefined');
    };
    
    this.isEmpty = function() {
        return ((this.width<=0)||(this.height<=0));
    };
    
    this.offset = function(dx, dy) {
        this.x += dx;
        this.y += dy;
    };
    
    this.offsetPoint = function(point) {
        this.offset(point.x, point.y);
    };
    
    this.setEmpty = function() {
        this.x = 0;
        this.y = 0;
        this.width = 0;
        this.height = 0;
    };
    
    this.toString = function() {
        var result = '{';
        result += '"x":'+this.x+',';
        result += '"y":'+this.y+',';
        result += '"width":'+this.width+',';
        result += '"height":'+this.height+'}';
        
        return result;
    };
    
    this.union = function(toUnion) {
        var minX = Math.min(toUnion.x, this.x);
        var maxX = Math.max(toUnion.right(), this.right());
        var minY = Math.min(toUnion.y, this.y);
        var maxY = Math.max(toUnion.bottom(), this.bottom());

        var result = new Rectangle(
            minX,
            minY,
            (maxX-minX),
            (maxY-minY));
        
        return result;
    };
    
    return this;
}

function BucketGrid(boundingBox, rows, columns)
{
    this.__constructor = function(boundingBox, rows, columns)
    {
        this._boundingBox = boundingBox;
        this._rows = rows;
        this._columns = columns;
        
        this._grid = [];
        
        this._originLeft = boundingBox.left();
        this._originTop = boundingBox.top();
        
        this._columnWidth = this._boundingBox.width/this._columns;
        this._rowHeight = this._boundingBox.height/this._rows;
        
        for (var rowIndex = 0; rowIndex<this._rows; rowIndex+=1)
        {
            this._grid[rowIndex] = [];
            
            var rowTop = (this._originTop+(this._rowHeight*rowIndex));
            
            for (var columnIndex = 0; columnIndex<this._columns; columnIndex+=1)
            {
                var columnLeft = (this._originLeft+(this._columnWidth*columnIndex));
                this._grid[rowIndex][columnIndex] = {
                    head_index: 0,
                    contents: { }
                };
            }
        }			

    };
    
    this.insertObjectAtPoint = function(point, object)
    {
        this.insertObjectAt(new Rectangle(point.x, point.y, 0, 0), object);
    }
    
    this.insertObjectAt = function(boundingBox, object)
    {
        var leftIndex = Math.floor((boundingBox.left()-this._originLeft)/this._columnWidth);
        var rightIndex = Math.floor((boundingBox.right()-this._originLeft)/this._columnWidth);
        var topIndex = Math.floor((boundingBox.top()-this._originTop)/this._rowHeight);
        var bottomIndex = Math.floor((boundingBox.bottom()-this._originTop)/this._rowHeight);

        leftIndex = Math.max(leftIndex, 0);
        rightIndex = Math.min(rightIndex, (this._columns-1));
        topIndex = Math.max(topIndex, 0);
        bottomIndex = Math.min(bottomIndex, (this._rows-1));

        for (var rowIndex = topIndex; rowIndex<=bottomIndex; rowIndex+=1)
        {
            for (var columnIndex = leftIndex; columnIndex<=rightIndex; columnIndex+=1)
            {
                var bucket = this._grid[rowIndex][columnIndex];
                bucket.contents[bucket.head_index] = object;
                bucket.head_index += 1;
            }
        }
        
    };

    this.removeObjectAt = function(boundingBox, object)
    {
        var leftIndex = Math.floor((boundingBox.left()-this._originLeft)/this._columnWidth);
        var rightIndex = Math.floor((boundingBox.right()-this._originLeft)/this._columnWidth);
        var topIndex = Math.floor((boundingBox.top()-this._originTop)/this._rowHeight);
        var bottomIndex = Math.floor((boundingBox.bottom()-this._originTop)/this._rowHeight);

        leftIndex = Math.max(leftIndex, 0);
        rightIndex = Math.min(rightIndex, (this._columns-1));
        topIndex = Math.max(topIndex, 0);
        bottomIndex = Math.min(bottomIndex, (this._rows-1));

        for (var rowIndex = topIndex; rowIndex<=bottomIndex; rowIndex+=1)
        {
            for (var columnIndex = leftIndex; columnIndex<=rightIndex; columnIndex+=1)
            {
                var bucket = this._grid[rowIndex][columnIndex];
                for (var index in bucket.contents)
                {
                    if (bucket.contents[index]==object)
                    {
                        delete bucket.contents[index];
                        break;
                    }
                }
            }
        }
        
    };
    
    this.getContentsAtPoint = function(point)
    {
        return this.getContentsAt(new Rectangle(point.x, point.y, 0, 0));
    };
    
    this.getContentsAt = function(boundingBox)
    {
        var result = [];

        var leftIndex = Math.floor((boundingBox.left()-this._originLeft)/this._columnWidth);
        var rightIndex = Math.floor((boundingBox.right()-this._originLeft)/this._columnWidth);
        var topIndex = Math.floor((boundingBox.top()-this._originTop)/this._rowHeight);
        var bottomIndex = Math.floor((boundingBox.bottom()-this._originTop)/this._rowHeight);

        leftIndex = Math.max(leftIndex, 0);
        rightIndex = Math.min(rightIndex, (this._columns-1));
        topIndex = Math.max(topIndex, 0);
        bottomIndex = Math.min(bottomIndex, (this._rows-1));

        for (var rowIndex = topIndex; rowIndex<=bottomIndex; rowIndex+=1)
        {
            for (var columnIndex = leftIndex; columnIndex<=rightIndex; columnIndex+=1)
            { 
                var bucket = this._grid[rowIndex][columnIndex];
                for (var objectIndex in bucket.contents)
                    result.push(bucket.contents[objectIndex]);
            }
        }
        
        return result;
    };

    this.__constructor(boundingBox, rows, columns);
    
    return this;
}

function ExternalImageView(imagePath, width, height, myParent)
{
    this.__constructor = function(imagePath, width, height, myParent)
    {
        this._myParent = myParent;
		this._isLoaded = false;
        this._image = new Image();
        
        var instance = this;
        this._image.onload = function() { instance.onComplete(); };
        this._image.src = imagePath;
    };

    this.onComplete = function() 
    {
        this._isLoaded = true;
        
        // I know, I know, I should really be sending up an event or something less hacky
        this._myParent._mapTilesDirty = true;
    };
    
    this.__constructor(imagePath, width, height, myParent);
}