package
{

<!--
OpenHeatMap renderer - a flash component to display and explore map visualizations
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

	Based on code released into the public domain by Joshua Bell:
	http://www.travellermap.com/tmp/delaunay.htm

-->

import flash.external.ExternalInterface; 

import BucketGrid;

public class Delaunay
{
	public static var EPSILON: Number = 1.0e-6;

	public var _triangles: Array;
	public var _boundingTriangle: Object;

	public static function makeVertex( x: Number, y: Number): Object
	{
		return {x: x, y: y};
	}
	
	public static function makeTriangle( v0: Object, v1: Object, v2: Object ): Object
	{
		var result: Object = {v0: v0, v1: v1, v2: v2};
		
		var A: Number = result.v1.x - result.v0.x; 
		var B: Number = result.v1.y - result.v0.y; 
		var C: Number = result.v2.x - result.v0.x; 
		var D: Number = result.v2.y - result.v0.y; 
	
		var E: Number = A*(result.v0.x + result.v1.x) + B*(result.v0.y + result.v1.y); 
		var F: Number = C*(result.v0.x + result.v2.x) + D*(result.v0.y + result.v2.y); 
	
		var G: Number = 2.0*(A*(result.v2.y - result.v1.y)-B*(result.v2.x - result.v1.x)); 
		
		var dx: Number, dy: Number;
		
		if( Math.abs(G) < Delaunay.EPSILON )
		{
			// Collinear - find extremes and use the midpoint
	
			function max3( a: Number, b: Number, c: Number ): Number { return ( a >= b && a >= c ) ? a : ( b >= a && b >= c ) ? b : c; }
			function min3( a: Number, b: Number, c: Number ): Number { return ( a <= b && a <= c ) ? a : ( b <= a && b <= c ) ? b : c; }
	
			var minx: Number = min3( result.v0.x, result.v1.x, result.v2.x );
			var miny: Number = min3( result.v0.y, result.v1.y, result.v2.y );
			var maxx: Number = max3( result.v0.x, result.v1.x, result.v2.x );
			var maxy: Number = max3( result.v0.y, result.v1.y, result.v2.y );
	
			result.center = makeVertex( ( minx + maxx ) / 2, ( miny + maxy ) / 2);
	
			dx = result.center.x - minx;
			dy = result.center.y - miny;
		}
		else
		{
			var cx: Number = (D*E - B*F) / G; 
			var cy: Number = (A*F - C*E) / G;
	
			result.center = makeVertex( cx, cy );
	
			dx = result.center.x - result.v0.x;
			dy = result.center.y - result.v0.y;
		}
	
		result.radius_squared = dx * dx + dy * dy;
		result.radius = Math.sqrt( result.radius_squared );
		
		return result;
	}

	public static function isInCircumcircle(v: Object, triangle: Object): Boolean
	{
		var dx: Number = triangle.center.x - v.x;
		var dy: Number = triangle.center.y - v.y;
		var dist_squared: Number = dx * dx + dy * dy;

		return ( dist_squared <= triangle.radius_squared );
	}

	public static function makeEdge( v0: Object, v1: Object ): Object
	{
		return {v0: v0, v1: v1};
	}
	
	public static function CreateBoundingTriangle( vertices: Array ): Object
	{
		// NOTE: There's a bit of a heuristic here. If the bounding triangle 
		// is too large and you see overflow/underflow errors. If it is too small 
		// you end up with a non-convex hull.
		
		var minx: Number, miny: Number, maxx: Number, maxy: Number;

		var isFirst: Boolean = true;
		for(var i: String in vertices )
		{
			var vertex: Object = vertices[i];
			if( isFirst || vertex.x < minx ) { minx = vertex.x; }
			if( isFirst || vertex.y < miny ) { miny = vertex.y; }
			if( isFirst || vertex.x > maxx ) { maxx = vertex.x; }
			if( isFirst || vertex.y > maxy ) { maxy = vertex.y; }
			
			isFirst = false;
		}
	
		var dx: Number = ( maxx - minx ) * 10;
		var dy: Number = ( maxy - miny ) * 10;
		
		var stv0: Object = makeVertex( minx - dx,   miny - dy*3 );
		var stv1: Object = makeVertex( minx - dx,   maxy + dy );
		var stv2: Object = makeVertex( maxx + dx*3, maxy + dy );
	
		return makeTriangle( stv0, stv1, stv2 );	
	}

	public static function UniqueEdges(edges: Array): Array
	{
		// TODO: This is O(n^2), make it O(n) with a hash or some such
		var uniqueEdges: Array = [];
		for( var i: String in edges )
		{
			var edge1: Object = edges[i];
			var unique: Boolean = true;
	
			for( var j: String in edges )
			{
				if( i != j )
				{
					var edge2: Object = edges[j];
	
					if( ( edge1.v0 == edge2.v0 && edge1.v1 == edge2.v1 ) ||
						( edge1.v0 == edge2.v1 && edge1.v1 == edge2.v0 ) )
					{
						unique = false;
						break;
					}
				}
			}
			
			if( unique )
			{
				uniqueEdges.push( edge1 );
			}
		}
	
		return uniqueEdges;	
	}

	public function Delaunay(vertices: Array)
	{
		_triangles = [];

		//
		// First, create a "supertriangle" that bounds all vertices
		//
		_boundingTriangle = CreateBoundingTriangle( vertices );
	
		_triangles.push( _boundingTriangle );
	
		//
		// Next, begin the triangulation one vertex at a time
		//
		var i: String;
		for( i in vertices )
		{
			// NOTE: This is O(n^2) - can be optimized by sorting vertices
			// along the x-axis and only considering triangles that have 
			// potentially overlapping circumcircles
	
			var vertex: Object = vertices[i];
			AddVertex( vertex );
		}
	
		//
		// Remove triangles that shared edges with "supertriangle"
		//
		for( i in _triangles )
		{
			var triangle: Object = _triangles[i];
	
			if( triangle.v0 == _boundingTriangle.v0 || triangle.v0 == _boundingTriangle.v1 || triangle.v0 == _boundingTriangle.v2 ||
				triangle.v1 == _boundingTriangle.v0 || triangle.v1 == _boundingTriangle.v1 || triangle.v1 == _boundingTriangle.v2 ||
				triangle.v2 == _boundingTriangle.v0 || triangle.v2 == _boundingTriangle.v1 || triangle.v2 == _boundingTriangle.v2 )
			{
				delete _triangles[i];
			}
		}
	}

	public function AddVertex(vertex: Object): void
	{
		var edges: Array = [];
		
		// Remove triangles with circumcircles containing the vertex
		var i: String;
		for( i in _triangles )
		{
			var triangle: Object = _triangles[i];
	
			if (isInCircumcircle(vertex, triangle))
			{
				edges.push( makeEdge(triangle.v0, triangle.v1) );
				edges.push( makeEdge(triangle.v1, triangle.v2) );
				edges.push( makeEdge(triangle.v2, triangle.v0) );
	
				delete _triangles[i];
			}
		}
	
		edges = UniqueEdges(edges);
	
		// Create new triangles from the unique edges and new vertex
		for( i in edges )
		{
			var edge: Object = edges[i];
	
			_triangles.push( makeTriangle( edge.v0, edge.v1, vertex ) );
		}	
	}

}

}
