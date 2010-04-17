<?php

/*
OpenGraphMap processing
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

class BucketGrid
{    
    public $bucket_size;
    public $buckets = array();

    public function __construct($bucket_size)
    {
        $this->bucket_size = $bucket_size;
    }
    
    public function round_coordinate($coordinate)
    {
        $coordinate_delta = fmod(($coordinate+($this->bucket_size/2)), $this->bucket_size);
        $rounded_coordinate = $coordinate - $coordinate_delta;
        
        return $rounded_coordinate;
    }
    
    public function insert_point($x, $y, $data)
    {
        $x_index = $this->round_coordinate($x);
        $y_index = $this->round_coordinate($y);
        $full_index = $x_index.','.$y_index;
        
        if (!isset($this->buckets[$full_index]))
            $this->buckets[$full_index] = array();
            
        $this->buckets[$full_index][] = array(
            'x' => $x,
            'y' => $y,
            'data' => $data,
        );
    }

    public function find_points_near($x, $y, $radius)
    {
        $x_left = ($x-$radius);
        $x_right = ($x+$radius);
        $y_top = ($y-$radius);
        $y_bottom = ($y+$radius);
        
        $radius_squared = ($radius*$radius);
        
        $x_index_left = $this->round_coordinate($x_left);
        $x_index_right = $this->round_coordinate($x_right);
        $y_index_top = $this->round_coordinate($y_top);
        $y_index_bottom = $this->round_coordinate($y_bottom);

        $result = array();

        for ($y_index = $y_index_top; $y_index<=$y_index_bottom; $y_index+=1)
        {        
            for ($x_index = $x_index_left; $x_index<=$x_index_right; $x_index+=1)
            {
                $full_index = $x_index.','.$y_index;
        
                if (!isset($this->buckets[$full_index]))
                    continue;
                    
                $bucket = $this->buckets[$full_index];
                
                foreach ($bucket as $bucket_entry)
                {
                    $x_entry = $bucket_entry['x'];
                    $y_entry = $bucket_entry['y'];
                    
                    $x_delta = ($x-$x_entry);
                    $y_delta = ($y-$y_entry);
                    
                    $x_delta_squared = ($x_delta*$x_delta);
                    $y_delta_squared = ($y_delta*$y_delta);
                    
                    $distance_squared = ($x_delta_squared+$y_delta_squared);
                
                    if ($distance_squared<=$radius_squared)
                        $result[] = $bucket_entry;
                }
                
            }
            
        }
        
        return $result;
    }
    
}

?>