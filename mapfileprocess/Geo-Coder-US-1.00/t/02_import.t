use blib;
use Test::More tests => 37;
use strict;
use warnings;

use_ok("Geo::Coder::US");
use_ok("Geo::Coder::US::Import");

my $path = (-r "ORA.RT1" ? "." : "t");

unlink "$path/sample.db"; # in case tests ran previously

my $db = Geo::Coder::US->set_db( "$path/sample.db", 1 );
isa_ok( Geo::Coder::US->db_file, "DB_File", "BDB object" );
is( tied(%$db), Geo::Coder::US->db_file, "BDB hash is tied correctly" );
is( $db, Geo::Coder::US->db, "->db returns the right thing" );
is( keys %$db, 0, "Database is empty before import" );

Geo::Coder::US::Import->load_tiger_data( "$path/ORA" );
Geo::Coder::US::Import->load_fips_data( "$path/ORA.FIPS" );

my @expected = (
    "/94931/Gravenstein/Hwy//",
    "/94931/Gravenstein/Hwy//S",
    "/94931/Gravenstein/Way//",
    "/94931/State Highway 116///",
    "/95436/Gravenstein/Hwy//N",
    "/95436/Highway 116///",
    "/95436/State Highway 116///",
    "/95472/Gravenstein/Ave//",
    "/95472/Gravenstein/Hwy//",
    "/95472/Gravenstein/Hwy//N",
    "/95472/Gravenstein/Hwy//S",
    "/95472/Highway 116///",
    "/95472/Mill Station/Rd//",
    "/95472/Old Gravenstein/Hwy//",
    "/95472/State Highway 116///",
    "/95931/Gravenstein/Hwy//",
    "/95931/Gravenstein/Hwy//S",
    "/95931/State Highway 116///",
    "/95472/Railroad/St//",
    "/95444/Railroad/St//",
    "/95472/Railroad/Ave//",
    "0670770",
    "0630812",
    "95472",
    "95444",
    "Sebastopol, CA",
    "Graton, CA",
    "Freestone, CA",
    "Pine Grove, CA",
);

is( scalar keys %Geo::Coder::US::DB, scalar @expected,
    "Database has correct number of entries after import" );
is( exists $Geo::Coder::US::DB{$_}, 1, "Database has key $_"  )
    for @expected;

my @stuff = unpack "w*", $Geo::Coder::US::DB{"/95472/Gravenstein/Hwy//N"};
is( scalar(@stuff), 277, "Gravenstein Hwy N has correct number of items" );


