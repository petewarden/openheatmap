#!/usr/bin/perl

use blib;
use Benchmark ':hireswallclock';
use Geo::Coder::US;
use strict;
use warnings;

my $database = shift @ARGV or die "Usage: $0 <database>\n";
Geo::Coder::US->set_db( $database );

my %addr = (
    addr_by_zip => "300 Elsie St, 94110",
    addr_by_place => "300 Elsie St, San Francisco, CA",
    inter_by_zip => "Mission & Valencia Sts, 94110",
    inter_by_place => "Mission & Valencia Sts, San Francisco CA"
);

my %code;

$code{$_} = eval qq/sub { Geo::Coder::US->geocode("$addr{$_}") }/
    for keys %addr;

# prime the database connection
Geo::Coder::US->geocode( $addr{inter_by_zip} );

timethese( 500, \%code );
