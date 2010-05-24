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

function distance_squared_to_line($x_pos, $y_pos, $start_x, $start_y, $end_x, $end_y, &$output_s)
{
    $line_direction_x = ($end_x-$start_x);
    $line_direction_y = ($end_y-$start_y);

    $line_direction_squared = 
        ($line_direction_x*$line_direction_x)+
        ($line_direction_y*$line_direction_y);
        
    $recip_direction_squared = (1.0/$line_direction_squared);

    $output_s = (($x_pos-$start_x)*$line_direction_x)+
        (($y_pos-$start_y)*$line_direction_y);
    $output_s *= $recip_direction_squared;

    $s = max($output_s, 0);
    $s = min($s, 1);

    $closest_point_x = ($start_x+($s*$line_direction_x));
    $closest_point_y = ($start_y+($s*$line_direction_y));

    $delta_x = ($x_pos-$closest_point_x);
    $delta_y = ($y_pos-$closest_point_y);

    $distance_squared = ($delta_x*$delta_x)+($delta_y*$delta_y);

    return $distance_squared;
}

?>