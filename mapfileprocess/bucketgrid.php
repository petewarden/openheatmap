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
        $rounded_coordinate = (int)(floor($coordinate/$this->bucket_size));
        
        return $rounded_coordinate;
    }
    
    public function insert_point($x, $y, $data, $debug=false)
    {
        $x_index = $this->round_coordinate($x);
        $y_index = $this->round_coordinate($y);
        $full_index = $x_index.','.$y_index;
        
        if ($debug) error_log("From $x, $y, at index $full_index inserting: ".print_r($data, true));
        
        if (!isset($this->buckets[$full_index]))
            $this->buckets[$full_index] = array();
            
        $this->buckets[$full_index][] = array(
            'x' => $x,
            'y' => $y,
            'data' => $data,
        );
    }

    public function find_points_near($x_pos, $y_pos, $radius, $debug=false)
    {
        $x_left = ($x_pos-$radius)-$this->bucket_size;
        $x_right = ($x_pos+$radius)+$this->bucket_size;
        $y_top = ($y_pos-$radius)-$this->bucket_size;
        $y_bottom = ($y_pos+$radius)+$this->bucket_size;

        if ($debug) error_log("bounding box: ($x_left, $y_top) - ($x_right, $y_bottom)");
        
        $radius_squared = ($radius*$radius);

        $result = array();

        for ($y = $y_top; $y<=$y_bottom; $y+=$this->bucket_size)
        {        
            $y_index = $this->round_coordinate($y);
            
            for ($x = $x_left; $x<=$x_right; $x+=$this->bucket_size)
            {
                $x_index = $this->round_coordinate($x);
                
                $full_index = $x_index.','.$y_index;
        
                if ($debug) error_log("Looking in $full_index");
        
                if (!isset($this->buckets[$full_index]))
                {
                    if ($debug) error_log("No bucket found for $full_index");
                    continue;
                }
                    
                $bucket = $this->buckets[$full_index];
                
                foreach ($bucket as $bucket_entry)
                {
                    $x_entry = $bucket_entry['x'];
                    $y_entry = $bucket_entry['y'];
                    
                    $x_delta = ($x_pos-$x_entry);
                    $y_delta = ($y_pos-$y_entry);
                    
                    $x_delta_squared = ($x_delta*$x_delta);
                    $y_delta_squared = ($y_delta*$y_delta);
                    
                    if ($debug) error_log("entry: $x_entry, $y_entry - delta: $x_delta, $y_delta - delta squared: $x_delta_squared, $y_delta_squared");
                    
                    $distance_squared = ($x_delta_squared+$y_delta_squared);
                
                    if ($distance_squared<=$radius_squared)
                    {
                        if ($debug) error_log("At $distance_squared found entry: ".print_r($bucket_entry, true));
                        $result[] = $bucket_entry;
                    }
                    else
                    {
                        if ($debug) error_log("At $distance_squared entry was too far: ".print_r($bucket_entry, true));                    
                    }
                }
                
            }
            
        }
        
        return $result;
    }
    
}

?>