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

import flash.geom.Point;
import flash.geom.Rectangle;

public class Delaunay
{
	public static var EPSILON: Number = 1.0e-10;
	public static var GRID_SIZE: int = 64;
	public static var MAX_POINT_COUNT: int = 256;
	
	public var _triangles: Array;
	public var _convexHull: Array;
	
	public var _timers: Object = {};

	public static function makeVertex( x: Number, y: Number): Object
	{
		return {x: x, y: y};
	}
	
	public static function makeTriangle( v0: Object, v1: Object, v2: Object ): Object
	{
		var result: Object = {v: [v0, v1, v2]};
		
		var A: Number = result.v[1].x - result.v[0].x; 
		var B: Number = result.v[1].y - result.v[0].y; 
		var C: Number = result.v[2].x - result.v[0].x; 
		var D: Number = result.v[2].y - result.v[0].y; 
	
		var E: Number = A*(result.v[0].x + result.v[1].x) + B*(result.v[0].y + result.v[1].y); 
		var F: Number = C*(result.v[0].x + result.v[2].x) + D*(result.v[0].y + result.v[2].y); 
	
		var G: Number = 2.0*(A*(result.v[2].y - result.v[1].y)-B*(result.v[2].x - result.v[1].x)); 
		
		var dx: Number, dy: Number;
		
		if( Math.abs(G) < Delaunay.EPSILON )
		{
			// Collinear - find extremes and use the midpoint
		
			var minx: Number = min3( result.v[0].x, result.v[1].x, result.v[2].x );
			var miny: Number = min3( result.v[0].y, result.v[1].y, result.v[2].y );
			var maxx: Number = max3( result.v[0].x, result.v[1].x, result.v[2].x );
			var maxy: Number = max3( result.v[0].y, result.v[1].y, result.v[2].y );
	
			result.center = makeVertex( ( minx + maxx ) / 2, ( miny + maxy ) / 2);
	
			dx = result.center.x - minx;
			dy = result.center.y - miny;
		}
		else
		{
			var cx: Number = (D*E - B*F) / G; 
			var cy: Number = (A*F - C*E) / G;
	
			result.center = makeVertex( cx, cy );
	
			dx = result.center.x - result.v[0].x;
			dy = result.center.y - result.v[0].y;
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
		return {v: [v0, v1]};
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
	
					if( ( edge1.v[0] == edge2.v[0] && edge1.v[1] == edge2.v[1] ) ||
						( edge1.v[0] == edge2.v[1] && edge1.v[1] == edge2.v[0] ) )
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

	public static function max3( a: Number, b: Number, c: Number ): Number { return ( a >= b && a >= c ) ? a : ( b >= a && b >= c ) ? b : c; }
	public static function min3( a: Number, b: Number, c: Number ): Number { return ( a <= b && a <= c ) ? a : ( b <= a && b <= c ) ? b : c; }
	
	public static function getTriangleBoundingBox(triangle: Object): Rectangle
	{
		var radius: Number = triangle.radius;
				
		var result: Rectangle = new Rectangle(
			(triangle.center.x-radius), (triangle.center.y-radius), 
			(radius*2), (radius*2));
		
		return result;
	}

	public function Delaunay(vertices: Array)
	{
		testHullMerge();
		
		var startTime: Number = new Date().getTime();
		
		clearTimers();
		
		var result: Object = triangulate(vertices);

		_triangles = result.triangles;
		_convexHull = result.convexHull;
		
		var endTime: Number = new Date().getTime();
		var runTime: Number = (endTime-startTime);

		trace('Delaunay for '+vertices.length+' points took '+(runTime/1000)+'s');
		
		printTimers();
	}

	public var _verticesAdded: int = 0;
	public var _candidatesChecked: int = 0;
	public var _trianglesChanged: int = 0;

	public function AddVertex(vertex: Object, triangles: Array): void
	{
		_verticesAdded += 1;
		
		var edges: Array = [];
		
		var candidates: Array = triangles;
		
		var i: String;
		for( i in candidates )
		{
			_candidatesChecked += 1;

			var triangle: Object = candidates[i];
	
			if (isInCircumcircle(vertex, triangle))
			{
				_trianglesChanged += 1;
				
				edges.push( makeEdge(triangle.v[0], triangle.v[1]) );
				edges.push( makeEdge(triangle.v[1], triangle.v[2]) );
				edges.push( makeEdge(triangle.v[2], triangle.v[0]) );
	
				deleteTriangle(triangle.index, triangles);
			}
		}
	
		edges = UniqueEdges(edges);
	
		// Create new triangles from the unique edges and new vertex
		for( i in edges )
		{
			var edge: Object = edges[i];
	
			addTriangle( makeTriangle( edge.v[0], edge.v[1], vertex ), triangles );
		}	
		
//		trace('Added vertex - candidates/vertex= '+
//			(_candidatesChecked/(Number)(_verticesAdded))+
//			'changes/vertex='+(_trianglesChanged/(Number)(_verticesAdded)));
	}

	public function createBoundingTriangle( vertices: Array ): Object
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
	
		var boundingTriangle: Object = makeTriangle( stv0, stv1, stv2 );
		
		return boundingTriangle;
	}

	public function addTriangle(triangle: Object, triangles: Array): void
	{
		triangle.index = triangles.length;
		triangles.push(triangle);
	}

	public function deleteTriangle(index: String, triangles: Array): void
	{
		var triangle: Object = triangles[index];
		
		delete triangles[index];		
	}
	
	public function triangulateNaively(vertices: Array): Object
	{
		startTimer('triangulateNaively');
		
		var result: Object = {
			triangles: [],
			convexHull: []
		};
		
		var boundingTriangle: Object = createBoundingTriangle( vertices );
		
		addTriangle(boundingTriangle, result.triangles);
		
		var i: String;
		for( i in vertices )
		{
			var vertex: Object = vertices[i];
			AddVertex(vertex, result.triangles);
		}
		
		var hullEdges: Array = [];

		// Remove triangles that shared edges with "supertriangle"
		for( i in result.triangles )
		{
			var triangle: Object = result.triangles[i];
	
			var isBounding: Array = [];
			var boundingCount: int = 0;
			for (var index: int = 0; index<3; index+=1)
			{
				vertex = triangle.v[index];
				isBounding[index] = (vertex == boundingTriangle.v[0] || vertex == boundingTriangle.v[1] || vertex == boundingTriangle.v[2]);
				if (isBounding[index])
					boundingCount += 1;
			}
			
			if (boundingCount==0)
				continue;
			
			deleteTriangle(i, result.triangles);

			if (boundingCount!=1)
				continue;
			
			var hullVertices: Array;
			if (isBounding[0])
				hullVertices = [ triangle.v[1], triangle.v[2] ];
			else if (isBounding[1])
				hullVertices = [ triangle.v[2], triangle.v[0] ];
			else
				hullVertices = [ triangle.v[0], triangle.v[1] ];

			hullEdges.push({v: hullVertices});
		}

		var candidateHull: Array = [];
		
		var startingEdge: Object = hullEdges[0];
		
		candidateHull.push(startingEdge.v[0]);
		
		var currentEdge: Object = startingEdge;
		
		while(currentEdge.v[1]!=startingEdge.v[0])
		{
			candidateHull.push(currentEdge.v[1]);
			
			for each (var nextEdge: Object in hullEdges)
			{
				if (nextEdge.v[0]==currentEdge.v[1])
				{
					currentEdge = nextEdge;
					break;
				}
				
			}
			
		}

//		trace('var candidateHull=[');
//		for each (var hullVertex: Object in candidateHull)
//		trace('    {x:'+hullVertex.x+', y:'+hullVertex.y+'},');
//		trace('];');
		
		candidateHull.reverse();

		var resultHull: Array = [];
		for (var pointIndex: * in candidateHull)
		{
			var pointsLength: int = candidateHull.length;
			var previousIndex: int = (pointIndex+(pointsLength-1))%pointsLength;
			var nextIndex: int = (pointIndex+1)%pointsLength;
			
			var previous: Object = candidateHull[previousIndex];
			var current: Object = candidateHull[pointIndex];
			var next: Object = candidateHull[nextIndex];
			
			var previousToCurrent: Object = { x: current.x-previous.x, y: current.y-previous.y };
			var currentToNext: Object = { x: next.x-current.x, y: next.y-current.y };
			
			var cross: Number = ((previousToCurrent.x*currentToNext.y)-(previousToCurrent.y*currentToNext.x));
			
			if (cross<=0)
			{
				resultHull.push(current);
			}
			else
			{
				addTriangle( makeTriangle( previous, current, next ), result.triangles );				
			}
			
		}		
		
		result.convexHull = resultHull;
		
		checkConvexity(result.convexHull);

		endTimer('triangulateNaively');
		
		return result;
	}
	
	public function triangulate(vertices: Array, xSplit: Boolean = true): Object
	{
		var vertexCount: int = vertices.length;
		
		if (vertexCount<=MAX_POINT_COUNT)
		{
			return triangulateNaively(vertices);	
		}

		startTimer('triangulateOther');
		
		var result: Object = {
			triangles: [],
			convexHull: []
		};

		var splitProperty: String;
		if (xSplit)
			splitProperty = 'x';
		else
			splitProperty = 'y';
		
		startTimer('vertexSort');
		vertices.sortOn(splitProperty);
		endTimer('vertexSort');

		var splitIndex: int = Math.floor(vertexCount/2);
		
		var firstVertices: Array = vertices.slice(0, splitIndex);
		var secondVertices: Array = vertices.slice(splitIndex);

		endTimer('triangulateOther');

		var first: Object = triangulate(firstVertices, !xSplit);

		var second: Object = triangulate(secondVertices, !xSplit);

		startTimer('triangulateOther');
		
		for each (var triangle: Object in first.triangles)
		{
			addTriangle(triangle, result.triangles);
		}
		
		for each (triangle in second.triangles)
		{
			addTriangle(triangle, result.triangles);
		}
	
		endTimer('triangulateOther');
		
		result = mergeHulls(first.convexHull, second.convexHull, result.triangles);

		return result;
	}
	
	public function mergeHulls(hullA, hullB, triangles): Object
	{
		startTimer('mergeHulls');

//		trace('var hullA=[');
//		for each (var hullVertex: Object in hullA)
//		trace('    {x:'+hullVertex.x+', y:'+hullVertex.y+'},');
//		trace('];');
//		
//		trace('var hullB=[');
//		for each (hullVertex in hullB)
//		trace('    {x:'+hullVertex.x+', y:'+hullVertex.y+'},');
//		trace('];');
		
		var lengthA = hullA.length;
		var lengthB = hullB.length;
		
		var highestAIndex;
		var lowestAIndex;
		var currentIndex = 0;
		while (currentIndex<lengthA)
		{
			var previousIndex = (currentIndex+(lengthA-1))%lengthA;
			var nextIndex = (currentIndex+1)%lengthA;
			
			var previousVertex = hullA[previousIndex];
			var currentVertex = hullA[currentIndex];
			var nextVertex = hullA[nextIndex];
			
			if ((nextVertex.y<currentVertex.y)&&(previousVertex.y<=currentVertex.y))
				highestAIndex = currentIndex;
			
			if ((nextVertex.y>currentVertex.y)&&(previousVertex.y>=currentVertex.y))
				lowestAIndex = currentIndex;
			
			currentIndex += 1;
		}
		
		var highestBIndex;
		var lowestBIndex;
		currentIndex = 0;
		while (currentIndex<lengthB)
		{
			previousIndex = (currentIndex+(lengthB-1))%lengthB;
			nextIndex = (currentIndex+1)%lengthB;
			
			previousVertex = hullB[previousIndex];
			currentVertex = hullB[currentIndex];
			nextVertex = hullB[nextIndex];
			
			if ((nextVertex.y<currentVertex.y)&&(previousVertex.y<=currentVertex.y))
				highestBIndex = currentIndex;
			
			if ((nextVertex.y>currentVertex.y)&&(previousVertex.y>=currentVertex.y))
				lowestBIndex = currentIndex;
			
			currentIndex += 1;
		}
		
		var topA = highestAIndex;
		var topB = highestBIndex;
		
		var iterations = 0;
		
		while (true)
		{
			iterations += 1;
			if (iterations>100000)
			{
				trace('Infinite loop!');				
			}
			
			var a0 = hullA[(topA+(lengthA-1))%lengthA];
			var aI = hullA[topA];
			var aII = hullA[(topA+1)%lengthA];
			
			var b0 = hullB[(topB+1)%lengthB];
			var bI = hullB[topB];
			var bII = hullB[(topB+(lengthB-1))%lengthB];
			
			var aIbI = { x: bI.x-aI.x, y: bI.y-aI.y };
			var bIaII = { x: aII.x-bI.x, y: aII.y-bI.y };
			
			var nextCrossA = (aIbI.x*bIaII.y-aIbI.y*bIaII.x);
			
			var a0bI = { x: bI.x-a0.x, y: bI.y-a0.y };
			var bIaI = { x: aI.x-bI.x, y: aI.y-bI.y };
			
			var previousCrossA = (a0bI.x*bIaI.y-a0bI.y*bIaI.x);
			
			if ((nextCrossA>0)||(previousCrossA<=0))
			{
				topA = ((topA+1)%lengthA);
				continue;
			}
			
			var bIbII = { x: bII.x-bI.x, y: bII.y-bI.y };
			
			var nextCrossB = (aIbI.x*bIbII.y-aIbI.y*bIbII.x);
			
			var aIb0 = { x: b0.x-aI.x, y: b0.y-aI.y };
			var b0bI = { x: bI.x-b0.x, y: bI.y-b0.y };
			
			var previousCrossB = (aIb0.x*b0bI.y-aIb0.y*b0bI.x);
			
			if ((nextCrossB>0)||(previousCrossB<=0))
			{
				topB = ((topB+(lengthB-1))%lengthB);
				continue;
			}
			
			break;
		}
		
		var bottomA = lowestAIndex;
		var bottomB = lowestBIndex;
		
		iterations = 0;
		
		while (true)
		{
			iterations += 1;
			if (iterations>100000)
			{
				trace('Infinite loop!');				
			}
			
			a0 = hullA[(bottomA+1)%lengthA];
			aI = hullA[bottomA];
			aII = hullA[(bottomA+(lengthA-1))%lengthA];
			
			b0 = hullB[(bottomB+(lengthB-1))%lengthB];
			bI = hullB[bottomB];
			bII = hullB[(bottomB+1)%lengthB];
			
			var bIaI = { x: aI.x-bI.x, y: aI.y-bI.y };
			var aIaII = { x: aII.x-aI.x, y: aII.y-aI.y };
			
			nextCrossA = (bIaI.x*aIaII.y-bIaI.y*aIaII.x);
			
			var bIa0 = { x: a0.x-bI.x, y: a0.y-bI.y };
			var a0aI = { x: aI.x-a0.x, y: aI.y-a0.y };
			
			previousCrossA = (bIa0.x*a0aI.y-bIa0.y*a0aI.x);
			
			if ((nextCrossA>0)||(previousCrossA<=0))
			{
				bottomA = ((bottomA+(lengthA-1))%lengthA);
				continue;
			}
			
			var bIIbI = { x: bI.x-bII.x, y: bI.y-bII.y };
			
			nextCrossB = (bIIbI.x*bIaI.y-bIIbI.y*bIaI.x);
			
			var bIb0 = { x: b0.x-bI.x, y: b0.y-bI.y };
			var b0aI = { x: aI.x-b0.x, y: aI.y-b0.y };
			
			previousCrossB = (bIb0.x*b0aI.y-bIb0.y*b0aI.x);
			
			if ((nextCrossB>0)||(previousCrossB<=0))
			{
				bottomB = ((bottomB+1)%lengthB);
				continue;
			}
			
			break;
		}
		
//		trace('topA='+topA);
//		trace('topB='+topB);
//		trace('bottomA='+bottomA);
//		trace('bottomB='+bottomB);
		
		var hull = [];
		
		var aIndex = bottomA;
		while (true)
		{
			hull.push(hullA[aIndex]);
			if (aIndex===topA)
				break;
			
			aIndex = (aIndex+1)%lengthA;
		}
		
		var bIndex = topB;
		while (true)
		{
			hull.push(hullB[bIndex]);
			if (bIndex===bottomB)
				break;
			
			bIndex = (bIndex+1)%lengthB;
		}
		
//		trace('Intermediate result:');
//		for (var entryIndex in unsortedHull)
//		{
//			var entry = unsortedHull[entryIndex];
//			trace(entry.x+', '+entry.y);
//		}
		
		var result = {
			convexHull: hull,
			triangles: triangles
		};

		checkConvexity(result.convexHull);

		var topAPoint: Object = hullA[topA];
		var topBPoint: Object = hullB[topB];

		var bottomAPoint: Object = hullA[bottomA];
		var bottomBPoint: Object = hullB[bottomB];

		var bottomAToTopA: Object = { x: (topAPoint.x-bottomAPoint.x), y: (topAPoint.y-bottomAPoint.y) };
		var bottomBToTopB: Object = { x: (topBPoint.x-bottomBPoint.x), y: (topBPoint.y-bottomBPoint.y) };
		
		var direction: Object = { x: (bottomAToTopA.x+bottomBToTopB.x), y: (bottomAToTopA.y+bottomBToTopB.y) };
		
		var edges: Array = [[], []];
		aIndex = topA;
		while (true)
		{
			edges[0].push(hullA[aIndex]);
			if (aIndex===bottomA)
				break;
			
			aIndex = (aIndex+1)%lengthA;
		}
	
		bIndex = topB;
		while (true)
		{
			edges[1].push(hullB[bIndex]);
			if (bIndex===bottomB)
				break;
			
			bIndex = (bIndex+(lengthB-1))%lengthB;
		}

		var indices: Array = [0, 0];
		var indices = [0, 0];
		
		while (true)
		{
			var atEnd = [
				(indices[0]==(edges[0].length-1)),
				(indices[1]==(edges[1].length-1))
			];
			
			if (atEnd[0]&&atEnd[1])
				break;
			
			
			var advanceIndex;
			if (atEnd[0])
			{
				advanceIndex = 1;
			}
			else if (atEnd[1])
			{
				advanceIndex = 0;
			}
			else
			{
				var a0 = edges[0][indices[0]];
				var a1 = edges[0][indices[0]+1];
				var b0 = edges[1][indices[1]];
				var b1 = edges[1][indices[1]+1];
				
				var mainTriangles = [
					[ a0, a1, b0],
					[ a0, b1, b0]
				];
				
				var remainderTriangles = [
					[a1, b1, b0],
					[a0, a1, b1]
				];
				
				var mainCross = [
					cross(mainTriangles[0]),
					cross(mainTriangles[1])
				];
				
				var remainderCross = [
					cross(remainderTriangles[0]),
					cross(remainderTriangles[1])
				];
				
				if ((mainCross[0]>=0)&&(remainderCross[0]>=0))
					advanceIndex = 0;
				else
					advanceIndex = 1;
			}
			
			var otherIndex;
			if (advanceIndex==0)
				otherIndex = 1;
			else
				otherIndex = 0;
			
			var triangle = [
				edges[advanceIndex][indices[advanceIndex]],
				edges[advanceIndex][indices[advanceIndex]+1],
				edges[otherIndex][indices[otherIndex]]
			];
			
			addTriangle( makeTriangle( triangle[0], triangle[1], triangle[2] ), result.triangles );
			
			indices[advanceIndex] += 1;
		}
		
		endTimer('mergeHulls');
		
		return result;
	}

	public function cross(v: Array): Number
	{
		var aToB: Object = { x: (v[1].x-v[0].x), y: (v[1].y-v[0].y) };
		var bToC: Object = { x: (v[2].x-v[1].x), y: (v[2].y-v[1].y) };
		
		var result: Number = (aToB.x*bToC.y)-(aToB.y*bToC.x);
		
		return result;
	}


	
	public function sortConvexHull(unsortedHull: Array): Array
	{
		unsortedHull.sortOn('y', Array.NUMERIC);
		unsortedHull.sortOn('x', Array.NUMERIC);
		
		var topLeft: Object = unsortedHull[0];
		var bottomRight: Object = unsortedHull[unsortedHull.length-1];
		
		var lineDirection: Object = {
			x: (bottomRight.x-topLeft.x),
			y: (bottomRight.y-topLeft.y)
		};
		
		var planeNormal: Object = {
			x: -lineDirection.y,
			y: lineDirection.x
		};
		
		var planeConstant: Number = ((topLeft.x*planeNormal.x)+(topLeft.y*planeNormal.y));
		
		var topHull: Array = [];
		var bottomHull: Array = [];
		
		for each (var hullVertex: Object in unsortedHull)
		{
			var planeDistance: Number = ((hullVertex.x*planeNormal.x)+(hullVertex.y*planeNormal.y));
			
			if (planeDistance>=planeConstant)
				topHull.push(hullVertex);
			else
				bottomHull.push(hullVertex);
		}
		
		bottomHull.reverse();
		
		var result: Array = topHull.concat(bottomHull);
		
		checkConvexity(result);
		
		return result;
	}
	
	public function testHullMerge(): void
	{
		var hullA: Array = [
			{x: 0, y: 0},
			{x: 5, y: 5},
			{x: 10, y: 2},
			{x: 6, y: -5}
		];
			
		var hullB: Array = [
			{x: 20, y: -2},
			{x: 22, y: 7},
			{x: 27, y: 7},
			{x: 27, y: -5}
		];
		
		var expectedResult: Array = [
			{x: 0, y: 0},
			{x: 5, y: 5},
			{x: 22, y: 7},
			{x: 27, y: 7},
			{x: 27, y: -5},
			{x: 6, y: -5}
		];			

		var actualResult: Object = mergeHulls(hullA, hullB, []);
		
		trace('Expected result:');
		for each (var hullVertex: Object in expectedResult.convexHull)
		trace(hullVertex.x+','+hullVertex.y);
		
		trace('Actual result:');
		for each (hullVertex in actualResult.convexHull)
			trace(hullVertex.x+','+hullVertex.y);
	}
	
	public function checkConvexity(points: Array): void
	{
		return;
		
		var badFound: Boolean = false;
		for (var pointIndex: * in points)
		{
			var previousIndex: int = (pointIndex+(points.length-1))%points.length;
			var nextIndex: int = (pointIndex+1)%points.length;
			
			var previous: Object = points[previousIndex];
			var current: Object = points[pointIndex];
			var next: Object = points[nextIndex];
			
			var previousToCurrent: Object = { x: current.x-previous.x, y: current.y-previous.y };
			var currentToNext: Object = { x: next.x-current.x, y: next.y-current.y };
			
			var cross: Number = ((previousToCurrent.x*currentToNext.y)-(previousToCurrent.y*currentToNext.x));
			
			if (cross>0)
			{
//				trace('Bad vertex - index: '+pointIndex+' pos:('+current.x+', '+current.y+')');	
				badFound = true;
			}
			
		}
		
		if (badFound)
		{
//			trace('var badHull=[');
//			for (pointIndex in points)
//			{
//				current = points[pointIndex];
//				trace('    {x:'+current.x+', y:'+current.y+'},');
//			}
//			trace('];');
		}
		
	}
	
	public function clearTimers(): void
	{
		_timers = {};
	}

	public function printTimers(): void
	{
		for (var key: String in _timers)
		{
			var info: Object = _timers[key];
			
			var accumulated: Number = info.accumulated;
			
			trace(key+' took '+(accumulated/1000)+'s');
		}
	}

	public function startTimer(name: String): void
	{
		if (!_timers.hasOwnProperty(name))
			_timers[name] = { accumulated: 0 };
		
		_timers[name].start = new Date().getTime();	
	}

	public function endTimer(name: String): void
	{
		var end: Number = new Date().getTime();
		_timers[name].accumulated += (end-_timers[name].start);
	}
	
}

}
