#!/usr/bin/perl 

use XMLRPC::Lite;
use Data::Dumper;
use strict;
use warnings;

my $where = shift @ARGV
    or die "Usage: $0 \"111 Main St, Anytown, KS\"\n";

my $result = XMLRPC::Lite
  -> proxy( 'http://rpc.geocoder.us/service/xmlrpc' )
  -> geocode( $where )
  -> result;

print Dumper $result;
