#!/usr/bin/perl 

use SOAP::Lite;
use Data::Dumper;
use strict;
use warnings;

# NOTE NOTE NOTE NOTE
#
# I would really love to provide a WSDL file for this service but
# I don't know WSDL well enough and don't have time to learn! If you
# have some expertise & might be willing to contribute help to come up
# with a simple static WSDL file for a one-method SOAP service,
# please email services@geocoder.us. Thanks a mil!

my $where = shift @ARGV
    or die "Usage: $0 \"111 Main St, Anytown, KS\"\n";

my $result = SOAP::Lite
  -> uri( 'http://rpc.geocoder.us/Geo/Coder/US' )
  -> proxy( 'http://rpc.geocoder.us/service/soap' )
  -> geocode( $where )
  -> result;

print Dumper $result;
