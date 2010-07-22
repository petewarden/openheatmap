use blib;
use Test::More tests => 12;
use strict;
use warnings;

### This script is intended to test "features" of Geo::Coder::US::Import
### that are meant to work around observed flaws/oddities in TIGER/Line.

no warnings 'once';

use_ok("Geo::Coder::US::Import");

my $path = (-r "TIGRRR.RT1" ? "." : "t");

unlink "$path/sample.db"; # in case tests ran previously

Geo::Coder::US->set_db( "$path/sample.db", 1 );
isa_ok( $Geo::Coder::US::DBO, "DB_File", "BDB object" );

Geo::Coder::US::Import->load_tiger_data( "$path/TIGRRR" );

# print "$_\n" for keys %Geo::Coder::US::DB;

my %expected = (
    "/94931/Gravenstein/Way/W/" =>  "test _fixup_directionals prefix",
    "/94931/Gravenstein/Way/E/" =>  "test _fixup_directionals prefix (abbr.)",
    "/84107/400//S/E"		=>  "test _fixup_directionals suffix",
    "/95436/Gravensteen/Hwy//N" =>  "street with alias",
    "/95436/Gravenstein/Hwy//N" =>  "existing street",
);

is( exists $Geo::Coder::US::DB{$_}, 1, $expected{$_} )
    for keys %expected;

unlike( $Geo::Coder::US::DB{"/95436/Gravenstein/Hwy//N"},
	qr#^/#, "existing street not overwritten by alias to another street" );

isnt( exists Geo::Coder::US->db->{"//NoZipCode/Ave//"}, 1,
    "rejected missing ZIP code" );

isnt( exists Geo::Coder::US->db->{"/     /NoZipCode/Ave//"}, 1,
    "rejected blank ZIP code" );

isnt( exists Geo::Coder::US->db->{"/ABCDE/BadZipCode/Ave//"}, 1,
    "rejected bad ZIP code" );

isnt( exists Geo::Coder::US->db->{"/    7/IncompleteZip/Ave//"}, 1,
    "rejected incomplete ZIP code" );
