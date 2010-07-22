#!/usr/bin/perl

use RDF::Simple::Parser;
use URI::Escape;
use Data::Dumper;
use strict;
use warnings;

my $where = shift @ARGV
    or die "Usage: $0 \"111 Main St, Anytown, KS\"\n";

my $addr = uri_escape($where);

my @result = RDF::Simple::Parser
    ->new
    ->parse_uri( "http://rpc.geocoder.us/service/rest?address=$addr" );

print Dumper \@result;

