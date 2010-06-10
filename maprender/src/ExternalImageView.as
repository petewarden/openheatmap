package
{
	import flash.display.Loader;
	import flash.events.Event;
	import flash.net.URLRequest;
	import mx.core.UIComponent;
	import mx.controls.Image;
	import mx.core.IDataRenderer;
    import flash.system.LoaderContext;
	import flash.filters.DropShadowFilter;
	import flash.events.MouseEvent;
	import flash.display.BitmapData;
	import flash.geom.Matrix;
	
	// This class only exists to force Flash to check the crossdomain.xml file on the image domain
	public class ExternalImageView extends UIComponent
	{
		private var _loader:Loader;
		private var _request:URLRequest;
		private var _width: Number;
		private var _height: Number;
		
		public var _isLoaded: Boolean = false;
		public var _bitmapData: BitmapData = null;
		
		public var _myParent: *;
    	
		public function ExternalImageView(imagePath:String, width: Number, height: Number, myParent: *)
		{
			_myParent = myParent;
			
			_loader=new Loader();
			_loader.contentLoaderInfo.addEventListener(Event.INIT, onInit);
			_loader.contentLoaderInfo.addEventListener(Event.COMPLETE,onComplete);

			_request=new URLRequest(imagePath);

			_width = width;
			_height = height;
	
	 		var loaderContext: LoaderContext = new LoaderContext(true);
	 		loaderContext.checkPolicyFile = true;
			_loader.load(_request, loaderContext);
		}

		private function onInit (e:Event):void{
			_loader.width = _width;
			_loader.height = _height;
		}
			
		private function onComplete(event:Event):void 
		{
			_bitmapData = new BitmapData(_width, _height);
    		var matrix:Matrix = new Matrix();
    		_bitmapData.draw(_loader, matrix);
    
    		_isLoaded = true;
    		
    		// I know, I know, I should really be sending up an event or something less hacky
    		_myParent._mapTilesDirty = true;
		}
	}
}