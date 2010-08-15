/**
* JQuery plugin to allow a series of horizontally-scrolling elements to slide in and out of the
* containing object.
* author Pete Warden
*/

(function ($) {
    $.fn.slidefade = function(elements, settings) {

        this.__constructor = function(elements, settings) {

            var defaults = {
                leftX: 0,
                midX: 260,
                rightX: 520,
                topY: 10,
                fadeTime: 500,
                holdTime: 3000
            };
     
            if (settings) 
                settings = $.extend(defaults, settings);
            else
                settings = defaults;

            this.settings = settings;

            this.animationFunctions = [];
            
            var self = this;
            this.each(function() {
            
                $(this).empty();

                for (var index in elements) {
                    var element = elements[index];
                                        
                    var isLast = (index==(elements.length-1));
                    var nextIndex;
                    if (isLast) {
                        nextIndex = 0;
                    } else {
                        nextIndex = ((Number)(index)+1);
                    }

                    self.animationFunctions[index] = function() {
                        var myElement = element;
                        var mySelf = self;
                        var myNextIndex = nextIndex;

                        return function() {

                            myElement
                            .stop()
                            .css({display:'block'})
                            .animate({left:mySelf.settings.midX+'px', opacity:1.0 }, mySelf.settings.fadeTime )
                            .animate({opacity:1.0}, mySelf.settings.holdTime)
                            .animate({left:mySelf.settings.leftX+'px', opacity:0.0 }, mySelf.settings.fadeTime, 'swing', function() {
                                mySelf.animationFunctions[myNextIndex]();
                            })
                            .animate({left:mySelf.settings.rightX+'px', opacity:0.0}, 0, 'swing', function() { myElement.css({display:'none'}); });
                        };
                    }();
                }
                
                var isFirst = true;
                for (index in elements) {
                    var element = elements[index];
                    
                    if (isFirst) {
                        element.css({
                            opacity: 1.0,
                            position: 'absolute',
                            left: self.settings.midX+'px',
                            top: self.settings.topY+'px'
                        });
                    } else {
                        element.css({
                            opacity: 0.0,
                            display: 'none',
                            position: 'absolute',
                            left: self.settings.rightX+'px',
                            top: self.settings.topY+'px'
                        });
                    }
                    
                    $(this).append(element);
                    
                    isFirst = false;
                }
                
                self.animationFunctions[0]();
            });
            
        };

        this.__constructor(elements, settings);
        
        return this;
    };
}(jQuery));

