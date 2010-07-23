#!/usr/bin/perl
use Geo::Coder::US;
Geo::Coder::US->set_db( "geocoder.db" );

if ($#ARGV>-1) {
    for my $address (@ARGV) {
        my ($match) = Geo::Coder::US->geocode($address);
        print "\"$address\", $match->{lat}, $match->{long}\n";
    }
}
else
{
    @userinput = <STDIN>;
    for my $address (@userinput) {
        chomp($address);
        my ($match) = Geo::Coder::US->geocode($address);
        print "\"$address\", $match->{lat}, $match->{long}\n";
    }
}