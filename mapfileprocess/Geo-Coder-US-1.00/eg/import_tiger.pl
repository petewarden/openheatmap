#!/usr/bin/perl 

use blib;
use Geo::Coder::US::Import;
use strict;
use warnings;

my ($database, $file) = @ARGV;
die "Usage: $0 <database> <TIGER/Line file>\n" unless $database and $file;

Geo::Coder::US->set_db($database, 1);
Geo::Coder::US::Import->load_tiger_data( $file );
