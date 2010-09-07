#!/usr/bin/env python

# Converted from Javascript version by Roger Muggleton:
# http://www.dorcus.co.uk/carabus/ngr_ll.html

import os, sys, glob, math

def NEtoLL(east, north):
    # converts NGR easting and nothing to lat, lon.
    # input metres, output radians
    nX = float(north)
    eX = float(east)
    a = 6377563.396       # OSGB semi-major
    b = 6356256.91        # OSGB semi-minor
    e0 = 400000           # OSGB easting of false origin
    n0 = -100000          # OSGB northing of false origin
    f0 = 0.9996012717     # OSGB scale factor on central meridian
    e2 = 0.0066705397616  # OSGB eccentricity squared
    lam0 = -0.034906585039886591  # OSGB false east
    phi0 = 0.85521133347722145    # OSGB false north
    af0 = a * f0
    bf0 = b * f0
    n = (af0 - bf0) / (af0 + bf0)
    Et = east - e0
    phid = InitialLat(north, n0, af0, phi0, n, bf0)
    nu = af0 / (math.sqrt(1 - (e2 * (math.sin(phid) * math.sin(phid)))))
    rho = (nu * (1 - e2)) / (1 - (e2 * (math.sin(phid)) * (math.sin(phid))))
    eta2 = (nu / rho) - 1
    tlat2 = math.tan(phid) * math.tan(phid)
    tlat4 = math.pow(math.tan(phid), 4)
    tlat6 = math.pow(math.tan(phid), 6)
    clatm1 = math.pow(math.cos(phid), -1)
    VII = math.tan(phid) / (2 * rho * nu)
    VIII = (math.tan(phid) / (24 * rho * (nu * nu * nu))) * (5 + (3 * tlat2) + eta2 - (9 * eta2 * tlat2))
    IX = ((math.tan(phid)) / (720 * rho * math.pow(nu, 5))) * (61 + (90 * tlat2) + (45 * math.pow(math.tan(phid), 4) ))
    phip = (phid - ((Et * Et) * VII) + (math.pow(Et, 4) * VIII) - (math.pow(Et, 6) * IX)) 
    X = math.pow(math.cos(phid), -1) / nu
    XI = (clatm1 / (6 * (nu * nu * nu))) * ((nu / rho) + (2 * (tlat2)))
    XII = (clatm1 / (120 * math.pow(nu, 5))) * (5 + (28 * tlat2) + (24 * tlat4))
    XIIA = clatm1 / (5040 * math.pow(nu, 7)) * (61 + (662 * tlat2) + (1320 * tlat4) + (720 * tlat6))
    lambdap = (lam0 + (Et * X) - ((Et * Et * Et) * XI) + (math.pow(Et, 5) * XII) - (math.pow(Et, 7) * XIIA))
    deg2rad = math.pi / 180;
    rad2deg = 180.0 / math.pi;
    geo = { 'latitude': (phip*rad2deg), 'longitude': (lambdap*rad2deg) }
    return(geo)

def Marc(bf0, n, phi0, phi):
    Marc = bf0 * (((1 + n + ((5 / 4) * (n * n)) + ((5 / 4) * (n * n * n))) * (phi - phi0))
    - (((3 * n) + (3 * (n * n)) + ((21 / 8) * (n * n * n))) * (math.sin(phi - phi0)) * (math.cos(phi + phi0)))
    + ((((15 / 8) * (n * n)) + ((15 / 8) * (n * n * n))) * (math.sin(2 * (phi - phi0))) * (math.cos(2 * (phi + phi0))))
    - (((35 / 24) * (n * n * n)) * (math.sin(3 * (phi - phi0))) * (math.cos(3 * (phi + phi0)))))
    
    return(Marc)

def InitialLat(north, n0, af0, phi0, n, bf0):
    phi1 = ((north - n0) / af0) + phi0
    M = Marc(bf0, n, phi0, phi1)
    phi2 = ((north - n0 - M) / af0) + phi1
    ind = 0
    while ((math.fabs(north - n0 - M) > 0.00001) and (ind < 20)):  # max 20 iterations in case of error
        ind = ind + 1
        phi2 = ((north - n0 - M) / af0) + phi1
        M = Marc(bf0, n, phi0, phi2)
        phi1 = phi2
    return(phi2)  

east = 651409
north = 313177
geo = NEtoLL(east, north)

print geo
