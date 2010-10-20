<?php

/*
OpenHeatMap processing
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

/****************************************************************************
The following code is a direct port of PROJ4 coordinate transformation code
from C to Javascript.  For more information go to http://proj.maptools.org/
Currently suppported projections include: Lambert Conformal Conic (LCC), 
Lat/Long, Polar Stereographic.
Porting C to Javascript is fairly straightforward so other support for more 
projections is easy to add.
*/

define('PI', '3.141582');
define('HALF_PI', PI*0.5);
define('TWO_PI', PI*2.0);
define('EPSLN', 1.0e-10);
define('R2D', 57.2957795131);
define('D2R', 0.0174532925199);
define('R', 6370997.0);        // Radius of the earth (sphere)

class ProjConvert
{
    // Initialize the Lambert Conformal conic projection
    // -----------------------------------------------------------------
    public function __construct($params) 
    {
        // array of:  r_maj,r_min,lat1,lat2,c_lon,c_lat,false_east,false_north
        //double c_lat;                   /* center latitude                      */
        //double c_lon;                   /* center longitude                     */
        //double lat1;                    /* first standard parallel              */
        //double lat2;                    /* second standard parallel             */
        //double r_maj;                   /* major axis                           */
        //double r_min;                   /* minor axis                           */
        //double false_east;              /* x offset in meters                   */
        //double false_north;             /* y offset in meters                   */

        $this->r_major = $params['r_major'];
        $this->r_minor = $params['r_minor'];
        $lat1 = $params['lat1'] * D2R;
        $lat2 = $params['lat2'] * D2R;
        $this->center_lon = $params['center_lon'] * D2R;
        $this->center_lat = $params['center_lat'] * D2R;
        $this->false_easting = $params['false_easting'];
        $this->false_northing = $params['false_northing'];

        // Standard Parallels cannot be equal and on opposite sides of the equator
        if (abs($lat1+$lat2) < EPSLN) {
            die("Equal Latitiudes for St. Parallels on opposite sides of equator - ProjConvert");
            return;
        }

        $temp = $this->r_minor / $this->r_major;
        $this->e = sqrt(1.0 - $temp*$temp);

        $sin1 = sin($lat1);
        $cos1 = cos($lat1);
        $ms1 = msfnz($this->e, $sin1, $cos1);
        $ts1 = tsfnz($this->e, $lat1, $sin1);

        $sin2 = sin($lat2);
        $cos2 = cos($lat2);
        $ms2 = msfnz($this->e, $sin2, $cos2);
        $ts2 = tsfnz($this->e, $lat2, $sin2);

        $ts0 = tsfnz($this->e, $this->center_lat, sin($this->center_lat));

        if (abs($lat1 - $lat2) > EPSLN) {
            $this->ns = log($ms1/$ms2)/log($ts1/$ts2);
        } else {
            $this->ns = $sin1;
        }
        $this->f0 = $ms1 / ($this->ns * pow($ts1, $this->ns));
        $this->rh = $this->r_major * $this->f0 * pow($ts0, $this->ns);
    }


    // Lambert Conformal conic forward equations--mapping lat,long to x,y
    // -----------------------------------------------------------------
    public function ll2lcc($coords) {

        $lon = $coords[0];
        $lat = $coords[1];

        // convert to radians
        if ( $lat <= 90.0 && $lat >= -90.0 && $lon <= 180.0 && $lon >= -180.0) {
            $lat *= D2R;
            $lon *= D2R;
        } else {
            die("*** Input out of range ***: lon: "+lon+" - lat: "+lat);
            return null;
        }

        $con  = abs( abs($lat) - HALF_PI);
        $ts;
        if ($con > EPSLN) {
            $ts = tsfnz($this->e, $lat, sin($lat) );
            $rh1 = $this->r_major * $this->f0 * pow($ts, $this->ns);
        } else {
            $con = $lat * $this->ns;
            if ($con <= 0) {
                die("Point can not be projected - ll2lcc");
                return null;
            }
            $rh1 = 0;
        }
        $theta = $this->ns * adjust_lon($lon - $this->center_lon);
        $x = $rh1 * sin($theta) + $this->false_easting;
        $y = $this->rh - $rh1 * cos($theta) + $this->false_northing;

        return array($x, $y);
    }

    // Lambert Conformal Conic inverse equations--mapping x,y to lat/long
    // -----------------------------------------------------------------
    public function lcc2ll($coords) {

        $x = $coords[0] - $this->false_easting;
        $y = $this->rh - $coords[1] + $this->false_northing;
        if ($this->ns > 0) {
            $rh1 = sqrt ($x * $x + $y * $y);
            $con = 1.0;
        } else {
            $rh1 = -sqrt ($x * $x + $y * $y);
            $con = -1.0;
        }
        $theta = 0.0;
        if ($rh1 != 0) {
            $theta = atan2(($con * $x),($con * $y));
        }
        if (($rh1 != 0) || ($this->ns > 0.0)) {
            $con = 1.0/$this->ns;
            $ts = pow(($rh1/($this->r_major * $this->f0)), $con);
            $lat = phi2z($this->e, $ts);
            if ($lat == -9999) return null;
        } else {
            $lat = -HALF_PI;
        }
        $lon = adjust_lon($theta/$this->ns + $this->center_lon);
        return array(R2D*$lon, R2D*$lat);
    }

//*******************************************************************************
//NAME                            POLAR STEREOGRAPHIC 
// converted from the GCTPC package
//* Variables common to all subroutines in this code file
//  static double r_major;		/* major axis			*/
//  static double r_minor;		/* minor axis			*/
//  static double e;			/* eccentricity			*/
//  static double e4;			/* e4 calculated from eccentricity*/
//  static double center_lon;		/* center longitude		*/
//  static double center_lat;		/* center latitude		*/
//  static double fac;			/* sign variable		*/
//  static double ind;			/* flag variable		*/
//  static double mcs;			/* small m			*/
//  static double tcs;			/* small t			*/
//  static double false_northing;		/* y offset in meters		*/
//  static double false_easting;		/* x offset in meters		*/

//
//    //* Initialize the Polar Stereographic projection
//    function psinit(param) {
//    // array consisting of:  r_maj,r_min,c_lon,c_lat,false_east,false_north) 
//    //double c_lon;				/* center longitude	in degrees	*/
//    //double c_lat;				/* center latitude in degrees		*/
//    //double r_maj;				/* major axis			*/
//    //double r_min;				/* minor axis			*/
//    //double false_east;			/* x offset in meters		*/
//    //double false_north;			/* y offset in meters		*/
//
//        this.r_major = param[0];
//        this.r_minor = param[1];
//        this.center_lon = param[2] * D2R;
//        this.center_lat = param[3] * D2R;
//        this.false_easting = param[4];
//        this.false_northing = param[5];
//
//        var temp = this.r_minor / this.r_major;
//        this.e = 1.0 - temp*temp;
//        this.e = Math.sqrt(this.e);
//        var con = 1.0 + this.e;
//        var com = 1.0 - this.e;
//        this.e4 = Math.sqrt( Math.pow(con,con) * Math.pow(com,com) );
//      this.fac = (this.center_lat < 0) ? -1.0 : 1.0;
//        this.ind = 0;
//        if (Math.abs(Math.abs(this.center_lat) - HALF_PI) > EPSLN) {
//            this.ind = 1;
//            var con1 = this.fac * this.center_lat; 
//            var sinphi = Math.sin(con1);
//            this.mcs = msfnz(this.e, sinphi, Math.cos(con1));
//            this.tcs = tsfnz(this.e, con1, sinphi);
//        }
//    }
//
//    //* Polar Stereographic forward equations--mapping lat,long to x,y
//    //  --------------------------------------------------------------*/
//    function ll2ps(coords) {
//
//        var lon = coords[0];
//        var lat = coords[1];
//
//        var con1 = this.fac * adjust_lon(lon - this.center_lon);
//        var con2 = this.fac * lat;
//        var sinphi = Math.sin(con2);
//        var ts = tsfnz(this.e, con2, sinphi);
//        if (this.ind != 0) {
//            rh = this.r_major * this.mcs * ts / this.tcs;
//        } else {
//            rh = 2.0 * this.r_major * ts / this.e4;
//        }
//        var x = this.fac * rh * Math.sin(con1) + this.false_easting;
//        var y = -this.fac * rh * Math.cos(con1) + this.false_northing;;
//
//        return new Array(x,y);
//    }
//
//    //* Polar Stereographic inverse equations--mapping x,y to lat/long
//    //  --------------------------------------------------------------*/
//    function ps2ll(coords) {
//
//        x = (coords[0] - this.false_easting) * this.fac;
//        y = (coords[1] - this.false_northing) * this.fac;
//        var rh = Math.sqrt(x * x + y * y);
//        if (this.ind != 0) {
//            ts = rh * this.tcs/(this.r_major * this.mcs);
//        } else {
//            ts = rh * this.e4/(this.r_major * 2.0);
//        }
//        var lat = this.fac * phi2z(this.e, ts);
//        if (lat == -9999) return null;
//        var lon = 0;
//        if (rh == 0) {
//            lon = this.fac * this.center_lon;
//        } else {
//            lon = adjust_lon(this.fac * Math.atan2(x, -y) + this.center_lon);
//        }
//        return new Array(R2D*lon, R2D*lat);
//    }

}
// Function to compute the constant small m which is the radius of
//   a parallel of latitude, phi, divided by the semimajor axis.
// -----------------------------------------------------------------
function msfnz($eccent, $sinphi, $cosphi) {
      $con = $eccent * $sinphi;
      return $cosphi/(sqrt(1.0 - $con * $con));
}

// Function to compute the constant small t for use in the forward
//   computations in the Lambert Conformal Conic and the Polar
//   Stereographic projections.
// -----------------------------------------------------------------
function tsfnz($eccent, $phi, $sinphi) {
    $con = $eccent * $sinphi;
    $com = .5 * $eccent; 
    $con = pow(((1.0 - $con) / (1.0 + $con)), $com);
    return (tan(.5 * (HALF_PI - $phi))/$con);
}


// Function to compute the latitude angle, phi2, for the inverse of the
//   Lambert Conformal Conic and Polar Stereographic projections.
// ----------------------------------------------------------------
function phi2z($eccent, $ts) {
    $eccnth = .5 * $eccent;
    $phi = HALF_PI - 2 * atan($ts);
    for ($i = 0; $i <= 15; $i++) {
        $con = $eccent * sin($phi);
        $dphi = HALF_PI - 2 * atan($ts *(pow(((1.0 - $con)/(1.0 + $con)),$eccnth))) - $phi;
        $phi += $dphi; 
        if (abs($dphi) <= .0000000001) return $phi;
    }
    die("Convergence error - phi2z");
    return -9999;
}

// Function to return the sign of an argument
function sign($x) { if ($x < 0.0) return(-1); else return(1);}

// Function to adjust longitude to -180 to 180; input in radians
function adjust_lon($x) {$x=(abs($x)<PI)?$x:($x-(sign($x)*TWO_PI));return($x);}

?>