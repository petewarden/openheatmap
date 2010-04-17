/* 
OpenGraphMap renderer - a flash component to display and explore map visualizations
Copyright (C) 2010 Pete Warden <pete@petewarden.com>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

package
{
	import flash.events.MouseEvent;
	
	import mx.containers.HBox;
	import mx.containers.VBox;
	import mx.controls.HSlider;
	import mx.controls.Image;
	import mx.controls.Label;
	import mx.controls.Spacer;
	import mx.core.BitmapAsset;
	import mx.core.ScrollPolicy;
	import mx.events.SliderEvent;

	public class TimelineControls extends HBox
	{
		[Embed(source="images/play.png")]
		public var PlayImage:Class;

		[Embed(source="images/pause.png")]
		public var PauseImage:Class;

		protected var _timeTextLabel: Label = new Label();
		protected var _playButton: Image = new Image();
		protected var _buttonContainer: HBox = new HBox();
		protected var _timerContainer: HBox = new HBox();
		protected var _titleContainer: VBox = new VBox();
		protected var _slider: HSlider = new HSlider();
		
		public var _wantedWidth: int = 700;
		public var _wantedFontSize: Number = 20;
		public var _wantedFontColor: Number = 0xffffff;
		public var _isPlaying: Boolean = false;
		public var _onUserInputCallback: Function = null;
		
		public function set timeText( value: String ): void
		{
			_timeTextLabel.text = value;
		}
		
		public function setTimeTextStyle( fontSize: Number, color: Number): void
		{
			_timeTextLabel.setStyle( "fontSize", fontSize );
			_timeTextLabel.setStyle( "color", color );
			
			_wantedFontSize = fontSize;
			_wantedFontColor = color;
		}
		
		public function get timeText(): String
		{
			return _timeTextLabel.text;
		}
			
		public function get sliderValue(): Number
		{
			return _slider.value;	
		}
	
		public function set sliderValue(sliderValue: Number): void
		{
			_slider.value = sliderValue;
		}

		public function set isPlaying( value: Boolean): void
		{
			_isPlaying = value;

			if (_isPlaying)
				var playAsset: BitmapAsset = BitmapAsset( new PauseImage() );
			else
				playAsset = BitmapAsset( new PlayImage() );
			_playButton.source = playAsset;
		}

		public function get isPlaying(): Boolean
		{
			return _isPlaying;
		}
		
		public function setWidth(width: int): void
		{
			_titleContainer.width = width;
			_wantedWidth = width;
			placeObjects();
		}

		public function setOnUserInputCallback(callback: Function): void
		{
			_onUserInputCallback = callback;
		}

		override public function initialize(): void
		{
			setStyle( "verticalAlign", "middle" );
			setStyle( "paddingLeft", 15 );
			setStyle( "paddingRight", 15 );
			setStyle( "paddingTop", 3 );
			setStyle( "paddingBottom", 3 );
			setStyle( "horizontalGap", 10 );
			
			_buttonContainer.setStyle( "horizontalGap", 3 );
			_buttonContainer.setStyle( "verticalAlign", "middle" );
			addChild( _buttonContainer );

			if (_isPlaying)
				var playAsset: BitmapAsset = BitmapAsset( new PauseImage() );
			else
				playAsset = BitmapAsset( new PlayImage() );
			_playButton.source = playAsset;
			_buttonContainer.addChild( _playButton );

			_titleContainer.setStyle( "verticalGap", 0 );
			_titleContainer.width = _wantedWidth;
			addChild( _titleContainer );

			_slider.percentWidth = 100;
			_slider.setStyle("horizontalAlign", "center");
			_slider.minimum = 0;
			_slider.maximum = 1;
			_slider.showDataTip = false;
			_slider.liveDragging = true;
			_titleContainer.addChild(_slider);
			
			_timerContainer.setStyle( "verticalAlign", "middle" );
			_timerContainer.setStyle( "horizontalAlign", "left" );
			_timerContainer.setStyle( "horizontalGap", 0 );

			_timeTextLabel.setStyle( "fontSize", _wantedFontSize );
			_timeTextLabel.setStyle( "color", _wantedFontColor );
			_timeTextLabel.setStyle( "horizontalAlign", "left" );
			_timerContainer.addChild( _timeTextLabel );
			addChild( _timerContainer );
			
			_playButton.addEventListener( MouseEvent.CLICK, onButtonClick );

			_slider.addEventListener( SliderEvent.CHANGE, onThumbDrag );
			_slider.addEventListener( SliderEvent.THUMB_DRAG, onThumbDrag );
			_slider.addEventListener( SliderEvent.THUMB_RELEASE, onThumbRelease );
			_slider.addEventListener( SliderEvent.THUMB_PRESS, onThumbRelease );

			super.initialize();
		}

		protected function placeObjects(): void
		{
			removeAllChildren();

			addChild( _buttonContainer );
			
			updateDisplayList( width, height );
		}
		
		protected function onButtonClick( event: MouseEvent ): void
		{
			this.isPlaying = !this.isPlaying;
			
			if (_onUserInputCallback !== null)
				_onUserInputCallback(false);
		}
		
		protected function onThumbDrag( event: * ): void
		{
			if (_onUserInputCallback !== null)
				_onUserInputCallback(true);
		}
		
		protected function onThumbRelease( event: * ): void
		{
			if (_onUserInputCallback !== null)
				_onUserInputCallback(false);
		}
	}
}