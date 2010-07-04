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