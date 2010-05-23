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

require_once('geomutils.php');

class BucketGrid
{    
    public $bucket_size;
    public $buckets = array();
    public $line_buckets = array();

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
    
    public function insert_line($start_x, $start_y, $end_x, $end_y, $data, $debug=false)
    {
        if ($start_x<$end_x)
        {
            $left_x = $start_x;
            $right_x = $end_x;
        }
        else
        {
            $left_x = $end_x;
            $right_x = $start_x;
        }
        
        if ($start_y<$end_y)
        {
            $top_y = $start_y;
            $bottom_y = $end_y;
        }
        else
        {
            $top_y = $end_y;
            $bottom_y = $start_y;
        }

        $left_x_index = $this->round_coordinate($left_x);
        $right_x_index = $this->round_coordinate($right_x);
        $top_y_index = $this->round_coordinate($top_y);
        $bottom_y_index = $this->round_coordinate($bottom_y);

        $line_direction_x = ($end_x-$start_x);
        $line_direction_y = ($end_y-$start_y);
    
        $line_direction_squared = 
            ($line_direction_x*$line_direction_x)+
            ($line_direction_y*$line_direction_y);
            
        $recip_direction_squared = (1.0/$line_direction_squared);
        
        $bucket_size_squared = ($this->bucket_size*$this->bucket_size);

        for ($y_index=$top_y_index; $y_index<$bottom_y_index; $y_index += 1)
        {
            $y_center = (($y_index+0.5)*$this->bucket_size);
            for ($x_index=$left_x_index; $x_index<$right_x_index; $x_index += 1)
            {
                $x_center = (($x_index+0.5)*$this->bucket_size);
			
                $s = (($x_center-$start_x)*$line_direction_x)+
                    (($y_center-$start_y)*$line_direction_y);
                $s *= $recip_direction_squared;
			
                $s = max($s, 0);
                $s = min($s, 1);
			
                $closest_point_x = ($start_x+($s*$line_direction_x));
                $closest_point_y = ($start_y+($s*$line_direction_y));

                $delta_x = ($center_x-$closest_point_x);
                $delta_y = ($center_y-$closest_point_y);
			
                $distance_squared = ($delta_x*$delta_x)+($delta_y*$delta_y);

                if ($distance_squared<$bucket_size_squared)
                {
                    $full_index = $x_index.','.$y_index;
                    
                    if ($debug) error_log("From $x, $y, at index $full_index inserting: ".print_r($data, true));

                    if (!isset($this->line_buckets[$full_index]))
                        $this->line_buckets[$full_index] = array();
                        
                    $this->line_buckets[$full_index][] = array(
                        'start_x' => $start_x,
                        'start_y' => $start_y,
                        'end_x' => $end_x,
                        'end_y' => $end_y,
                        'data' => $data,
                    );
                }

            }
        }
        
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

    public function find_lines_near($x_pos, $y_pos, $radius, $debug=false)
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
        
                if (!isset($this->line_buckets[$full_index]))
                {
                    if ($debug) error_log("No bucket found for $full_index");
                    continue;
                }
                    
                $bucket = $this->line_buckets[$full_index];
                
                foreach ($bucket as $bucket_entry)
                {
                    $start_x = $bucket_entry['start_x'];
                    $start_y = $bucket_entry['start_y'];
                    
                    $end_x = $bucket_entry['end_x'];
                    $end_y = $bucket_entry['end_y'];
                                        
                    $distance_squared = distance_squared_to_line(
                        $x_pos, $y_pos,
                        $start_x, $start_y,
                        $end_x, $end_y,
                        $output_s);

                    if ($distance_squared<$radius_squared)
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

    public function find_points_near_line($start_x, $start_y, $end_x, $end_y, $radius, $debug=false)
    {
        if ($debug)
            error_log("find_points_near_line($start_x, $start_y, $end_x, $end_y, $radius)");
    
        if ($start_x<$end_x)
        {
            $left_x = $start_x;
            $right_x = $end_x;
        }
        else
        {
            $left_x = $end_x;
            $right_x = $start_x;
        }
        
        if ($start_y<$end_y)
        {
            $top_y = $start_y;
            $bottom_y = $end_y;
        }
        else
        {
            $top_y = $end_y;
            $bottom_y = $start_y;
        }

        $left_x -= $radius;
        $right_x += $radius;
        
        $top_y -= $radius;
        $bottom_y += $radius;

        $left_x_index = $this->round_coordinate($left_x);
        $right_x_index = $this->round_coordinate($right_x);
        $top_y_index = $this->round_coordinate($top_y);
        $bottom_y_index = $this->round_coordinate($bottom_y);

        $line_direction_x = ($end_x-$start_x);
        $line_direction_y = ($end_y-$start_y);
    
        $line_direction_squared = 
            ($line_direction_x*$line_direction_x)+
            ($line_direction_y*$line_direction_y);
            
        if ($line_direction_squared<0.000000001)
            return array();
            
        $recip_direction_squared = (1.0/$line_direction_squared);
        
        $bucket_size_squared = ($this->bucket_size*$this->bucket_size);
        $radius_squared = ($radius*$radius);

        $result = array();

        for ($y_index=$top_y_index; $y_index<=$bottom_y_index; $y_index += 1)
        {
            $y_center = (($y_index+0.5)*$this->bucket_size);
            for ($x_index=$left_x_index; $x_index<=$right_x_index; $x_index += 1)
            {
                $x_center = (($x_index+0.5)*$this->bucket_size);
			
                $s = (($x_center-$start_x)*$line_direction_x)+
                    (($y_center-$start_y)*$line_direction_y);
                $s *= $recip_direction_squared;
			
                $s = max($s, 0);
                $s = min($s, 1);
			
                $closest_point_x = ($start_x+($s*$line_direction_x));
                $closest_point_y = ($start_y+($s*$line_direction_y));

                $delta_x = ($x_center-$closest_point_x);
                $delta_y = ($y_center-$closest_point_y);
			
                $distance_squared = ($delta_x*$delta_x)+($delta_y*$delta_y);

                if ($distance_squared<$bucket_size_squared)
                {
                    $full_index = $x_index.','.$y_index;
                    
                    if (!isset($this->buckets[$full_index]))
                    {
                        if ($debug&&false) error_log("No bucket found for $full_index");
                        continue;
                    }
                        
                    $bucket = $this->buckets[$full_index];
                    
                    foreach ($bucket as $bucket_entry)
                    {
                        $x_entry = $bucket_entry['x'];
                        $y_entry = $bucket_entry['y'];
                        
                        $line_distance_squared = distance_squared_to_line(
                            $x_entry, $y_entry,
                            $start_x, $start_y,
                            $end_x, $end_y,
                            $output_s);
                    
                        if ($line_distance_squared<=$radius_squared)
                        {
                            $bucket_entry['output_s'] = $output_s;
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
        }
        
        return $result;
    }

    
}

?>