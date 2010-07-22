#!/usr/bin/perl

use Geo::Coder::US::Import;
use strict;
use warnings;

my ($database, $file) = @ARGV;
die "Usage: $0 <database> <FIPS-55 file>\n" unless $database and $file;

Geo::Coder::US->set_db($database, 1);
Geo::Coder::US::Import->load_fips_data( $file );
