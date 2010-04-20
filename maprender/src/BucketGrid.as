package
{
	
	import flash.geom.Point;
	import flash.geom.Rectangle;

	public class BucketGrid
	{
		public var _grid: Array;
		
		public var _boundingBox: Rectangle;
		public var _rows: int;
		public var _columns: int;
		
		public var _originLeft: Number;
		public var _originTop: Number;
		
		public var _columnWidth: Number;
		public var _rowHeight: Number;
		
		public function BucketGrid(boundingBox: Rectangle, rows: int, columns: int)
		{
			_boundingBox = boundingBox;
			_rows = rows;
			_columns = columns;
			
			_grid = [];
			
			_originLeft = boundingBox.left;
			_originTop = boundingBox.top;
			
			_columnWidth = _boundingBox.width/_columns;
			_rowHeight = _boundingBox.height/_rows;
			
			for (var rowIndex: int = 0; rowIndex<_rows; rowIndex+=1)
			{
				_grid[rowIndex] = [];
				
				var rowTop: Number = (_originTop+(_rowHeight*rowIndex));
				
				for (var columnIndex: int = 0; columnIndex<_columns; columnIndex+=1)
				{
					var columnLeft: Number = (_originLeft+(_columnWidth*columnIndex));
					_grid[rowIndex][columnIndex] = {
						contents: []
					};
				}
			}			

		}
		
		public function insertObjectAtPoint(point: Point, object: *): void
		{
			insertObjectAt(new Rectangle(point.x, point.y, 0, 0), object);
		}
		
		public function insertObjectAt(boundingBox: Rectangle, object: *): void
		{
			var leftIndex: int = Math.floor((boundingBox.left-_originLeft)/_columnWidth);
			var rightIndex: int = Math.floor((boundingBox.right-_originLeft)/_columnWidth);
			var topIndex: int = Math.floor((boundingBox.top-_originTop)/_rowHeight);
			var bottomIndex: int = Math.floor((boundingBox.bottom-_originTop)/_rowHeight);
	
			leftIndex = Math.max(leftIndex, 0);
			rightIndex = Math.min(rightIndex, (_columns-1));
			topIndex = Math.max(topIndex, 0);
			bottomIndex = Math.min(bottomIndex, (_rows-1));
	
			for (var rowIndex: int = topIndex; rowIndex<=bottomIndex; rowIndex+=1)
			{
				for (var columnIndex: int = leftIndex; columnIndex<=rightIndex; columnIndex+=1)
				{
					_grid[rowIndex][columnIndex].contents.push(object);
				}
			}
			
		}
		
		public function getContentsAtPoint(point: Point): Array
		{
			return getContentsAt(new Rectangle(point.x, point.y, 0, 0));
		}
		
		public function getContentsAt(boundingBox: Rectangle): Array
		{
			var result: Array = [];
	
			var leftIndex: int = Math.floor((boundingBox.left-_originLeft)/_columnWidth);
			var rightIndex: int = Math.floor((boundingBox.right-_originLeft)/_columnWidth);
			var topIndex: int = Math.floor((boundingBox.top-_originTop)/_rowHeight);
			var bottomIndex: int = Math.floor((boundingBox.bottom-_originTop)/_rowHeight);
	
			leftIndex = Math.max(leftIndex, 0);
			rightIndex = Math.min(rightIndex, (_columns-1));
			topIndex = Math.max(topIndex, 0);
			bottomIndex = Math.min(bottomIndex, (_rows-1));
	
			for (var rowIndex: int = topIndex; rowIndex<=bottomIndex; rowIndex+=1)
			{
				for (var columnIndex: int = leftIndex; columnIndex<=rightIndex; columnIndex+=1)
				{
					result = result.concat(_grid[rowIndex][columnIndex].contents);
				}
			}
			
			return result;
		}

	}
}