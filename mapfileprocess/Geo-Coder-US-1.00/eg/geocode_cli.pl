#!/usr/bin/perl 

use Geo::Coder::US;
use Term::ReadLine;
use Data::Dumper;
use Time::HiRes qw(gettimeofday tv_interval);
use strict;
use warnings;

my $dbname = shift 
    or die "Usage: $0 <path_to.db>\n";

Geo::Coder::US->set_db( $dbname );

my $term = new Term::ReadLine 'Geo::Coder::US';
my $prompt = "> ";
my $OUT = $term->OUT || \*STDOUT;

print $OUT <<End;
Geo::Coder::US ver. $Geo::Coder::US::VERSION command line interface!
Enter a US address or intersection.
End

while ( defined ($_ = $term->readline($prompt)) ) {
    my $t0  = [gettimeofday];
    my @res = Geo::Coder::US->geocode($_);
    my $interval = tv_interval($t0);
    warn $@ if $@;
    unless ($@) {
	print $OUT Dumper(\@res), "\n";
	printf $OUT "(Query took %.3f seconds)\n", $interval;
    }
    $term->addhistory($_) if /\S/;
}

