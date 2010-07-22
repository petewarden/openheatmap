#!/usr/bin/perl -w

use Geo::Coder::US;
use DB_File;
use Data::Dumper;
use strict;
use vars qw(%db *db);

my $filename = shift @ARGV or die "Usage: $0 <database>\n";

Geo::Coder::US->set_db($filename);

for my $arg (@ARGV) {
    my $val;
    $Geo::Coder::US::DBO->seq( $arg, $val, R_CURSOR );
    print "$arg -> ";
    if (!defined($val)) {
	print "(undef)";
    } elsif ($arg =~ /^\d{7}$/ or $val =~ /^\//) {
	print $val;
    } else {
	print join(" ", unpack("w*", $val));
    }
    print "\n";
}
