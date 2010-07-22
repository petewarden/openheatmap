#!/usr/bin/perl

use Geo::Coder::US;
use Template;
use CGI;
use CGI::Carp qw(fatalsToBrowser);
use URI::Escape;
use warnings;
use strict;

Geo::Coder::US->set_db( "/www/geocoder.us/geocoder.db" );

my $tmpl = Template->new({
    INCLUDE_PATH => "/www/geocoder.us/htdocs/template" });
my $cgi  = CGI->new;
my %data = $cgi->Vars;
my $file;

$data{width} = .035;
$data{pixels} = 320;

if (my $addr = $data{address}) {
    my @matches = Geo::Coder::US->geocode($addr);
    my $n = $data{match} || 0;

    @matches = $matches[$n] if $n and $matches[--$n];

    if (@matches == 1) {
	my $match = shift @matches;
	if ($match and $match->{lat}) {
	    %data = ( %data, %$match );
	    $data{map} = "http://tiger.census.gov/cgi-bin/mapgen?" .
			    "lon=$data{long}&lat=$data{lat}" .
			    "&wid=$data{width}&ht=$data{width}" .
			    "&iht=$data{pixels}&iwd=$data{pixels}" .
			    "&mark=$data{long},$data{lat},redpin";
	    $file = "single.html";	
	} else {
	    $data{reason} = "missing";
	    $file = "error.html";
	}
    } elsif (@matches) {
	for my $n (0 .. $#matches) {
	    no warnings "uninitialized";
	    my $match = $matches[$n];
	    my $dest;
	    if ($match->{street}) {
		$dest = "@$match{qw{ number prefix street type suffix }}";
	    } else {
		$dest = "@$match{qw{ number1 prefix1 street1 type1 suffix1 }}"
		   . " & @$match{qw{ number2 prefix2 street2 type2 suffix2 }}";
	    }
	    $dest .= ", @$match{qw{ city state zip }}";
	    $dest =~ s/^\s+|\s+$|\s+(?=,)//gos;
	    $dest =~ s/\s+/ /gos;
	    $match->{address} = uri_escape( $dest );
	    $match->{match} = $n + 1;
	}
	$data{matches} = \@matches;
	$file = "multiple.html";
    } else {
	$data{reason} = "parse";
	$file = "error.html";
    }
} else {
    $file = "form.html";
}

print $cgi->header;
$tmpl->process( $file, \%data );
