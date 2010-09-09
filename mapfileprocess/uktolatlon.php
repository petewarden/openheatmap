<?php

// Converted from Javascript version by Roger Muggleton:
// http://www.dorcus.co.uk/carabus/ngr_ll.html


function NEtoLL($east, $north)
{
    // converts NGR easting and nothing to lat, lon.
    // input metres, output radians
    $nX = (float)($north);
    $eX = (float)($east);
    $a = 6377563.396;       // OSGB semi-major
    $b = 6356256.91;        // OSGB semi-minor
    $e0 = 400000;           // OSGB easting of false origin
    $n0 = -100000;          // OSGB northing of false origin
    $f0 = 0.9996012717;     // OSGB scale factor on central meridian
    $e2 = 0.0066705397616;  // OSGB eccentricity squared
    $lam0 = -0.034906585039886591;  // OSGB false east
    $phi0 = 0.85521133347722145;    // OSGB false north
    $af0 = $a * $f0;
    $bf0 = $b * $f0;
    $n = ($af0 - $bf0) / ($af0 + $bf0);
    $Et = $east - $e0;
    $phid = InitialLat($north, $n0, $af0, $phi0, $n, $bf0);
    $nu = $af0 / (sqrt(1 - ($e2 * (sin($phid) * sin($phid)))));
    $rho = ($nu * (1 - $e2)) / (1 - ($e2 * (sin($phid)) * (sin($phid))));
    $eta2 = ($nu / $rho) - 1;
    $tlat2 = tan($phid) * tan($phid);
    $tlat4 = pow(tan($phid), 4);
    $tlat6 = pow(tan($phid), 6);
    $clatm1 = pow(cos($phid), -1);
    $VII = tan($phid) / (2 * $rho * $nu);
    $VIII = (tan($phid) / (24 * $rho * ($nu * $nu * $nu))) * (5 + (3 * $tlat2) + $eta2 - (9 * $eta2 * $tlat2));
    $IX = ((tan($phid)) / (720 * $rho * pow($nu, 5))) * (61 + (90 * $tlat2) + (45 * pow(tan($phid), 4) ));
    $phip = ($phid - (($Et * $Et) * $VII) + (pow($Et, 4) * $VIII) - (pow($Et, 6) * $IX)); 
    $X = pow(cos($phid), -1) / $nu;
    $XI = ($clatm1 / (6 * ($nu * $nu * $nu))) * (($nu / $rho) + (2 * ($tlat2)));
    $XII = ($clatm1 / (120 * pow($nu, 5))) * (5 + (28 * $tlat2) + (24 * $tlat4));
    $XIIA = $clatm1 / (5040 * pow($nu, 7)) * (61 + (662 * $tlat2) + (1320 * $tlat4) + (720 * $tlat6));
    $lambdap = ($lam0 + ($Et * $X) - (($Et * $Et * $Et) * $XI) + (pow($Et, 5) * $XII) - (pow($Et, 7) * $XIIA));
    $rad2deg = 180.0 / 3.141582;
    $geo = array( 'latitude' => ($phip*$rad2deg), 'longitude' => ($lambdap*$rad2deg) );
    return($geo);
}

function Marc($bf0, $n, $phi0, $phi)
{
    $Marc = $bf0 * (((1 + $n + ((5 / 4) * ($n * $n)) + ((5 / 4) * ($n * $n * $n))) * ($phi - $phi0))
        - (((3 * $n) + (3 * ($n * $n)) + ((21 / 8) * ($n * $n * $n))) * (sin($phi - $phi0)) * (cos($phi + $phi0)))
        + ((((15 / 8) * ($n * $n)) + ((15 / 8) * ($n * $n * $n))) * (sin(2 * ($phi - $phi0))) * (cos(2 * ($phi + $phi0))))
        - (((35 / 24) * ($n * $n * $n)) * (sin(3 * ($phi - $phi0))) * (cos(3 * ($phi + $phi0)))));
    
    return($Marc);
}

function InitialLat($north, $n0, $af0, $phi0, $n, $bf0)
{
    $phi1 = (($north - $n0) / $af0) + $phi0;
    $M = Marc($bf0, $n, $phi0, $phi1);
    $phi2 = (($north - $n0 - $M) / $af0) + $phi1;
    $ind = 0;
    while ((abs($north - $n0 - $M) > 0.00001) && ($ind < 20))  // max 20 iterations in case of error
    {
        $ind = $ind + 1;
        $phi2 = (($north - $n0 - $M) / $af0) + $phi1;
        $M = Marc($bf0, $n, $phi0, $phi2);
        $phi1 = $phi2;
    }
    return($phi2);
}

//$east = 651409;
//$north = 313177;
//$geo = NEtoLL($east, $north);
//
//print print_r($geo, true)."\n";
//
// // Should give this output:
// // Array
// // (
// //     [latitude] => 52.657746867913
// //     [longitude] => 1.7179138776668
// // )



?>
