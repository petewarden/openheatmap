use blib;
use Test::More tests => 62;
use strict;
use warnings;
use Data::Dumper;

my $path = (-r "ORA.RT1" ? "." : "t");

use_ok( "Geo::Coder::US" );
Geo::Coder::US->set_db( "$path/sample.db" );
{
    no warnings; # i.e. no "used only once" warning
    isa_ok( $Geo::Coder::US::DBO, "DB_File", "BDB object" );
}

=pod 

my @address = (
    "1005 Gravenstein Hwy, Sebastopol CA",
    "1005 State Highway 116, Sebastopol CA",
    "1005 N Gravenstein Hwy, Sebastopol CA",
    "1005 Gravenstein Hwy N, 95472",
    "1005 Highway 116, 95472",
    "1005 Gravenstein Hwy N, Sebastopol CA",
    "1005 State Highway 116 North, Sebastopol CA",
    "7800 Mill Station Rd, Sebastopol CA",
    "Mill Station Rd & Gravenstein Hwy N, Sebastopol, CA",
    "Mill Station Rd & Gravenstein Hwy N, 95472",
);

for my $addr (@address) {
    my @spec = Geo::Coder::US->geocode($addr);
    my $thunk = Dumper \@spec;
    $thunk =~ s/\$VAR1 =/"$addr" =>/;
    $thunk =~ s/;$/,/;
    print $thunk;
}

=cut

my %address = (
"1005 Gravenstein Hwy, Sebastopol CA" => [
          {
            'number' => 1005,
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          },
          {
            'number' => 1005,
            'lat' => '38.390202',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => '',
            'long' => '-122.816010',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"1005 State Highway 116, 95472" => [
          {
            'number' => 1005,
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          },
          {
            'number' => 1005,
            'lat' => '38.390202',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => '',
            'long' => '-122.816010',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"1005 N Gravenstein Hwy, Sebastopol CA" => [
          {
            'number' => 1005,
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          },
          {
            'number' => 1005,
            'lat' => '38.390202',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => '',
            'long' => '-122.816010',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"1005 Gravenstein Hwy N, 95472" => [
          {
            'number' => '1005',
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"1005 Gravenstein Hwy N, Sebastopol, CA 95444" => [ #wrong zip
          {
            'number' => '1005',
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"1005 Gravenstein Hwy N, 95472-4019" => [
          {
            'number' => '1005',
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"1005 State Highway 116 North, 95472" => [
          {
            'number' => '1005',
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"1005 Gravenstein Hwy N, Sebastopol CA" => [
          {
            'number' => 1005,
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"1005 Gravenstein Hwy N, Graton CA" => [
          {
            'number' => 1005,
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"1005 Gravenstein Hwy N, Freestone CA" => [
          {
            'number' => 1005,
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"1005 Gravenstein Hwy N, Pine Grove CA" => [
          {
            'number' => 1005,
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"1005 Gravenstein Hwy N, Sebastopol CA 95472" => [
          {
            'number' => 1005,
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"3001 Railroad Ave, 95472" => [
          {
            'number' => 3001,
            'lat' => '38.435237',
            'street' => 'Railroad',
            'state' => 'CA',
            'city' => 'Graton',
            'zip' => '95472',
            'suffix' => '',
            'long' => '-122.871274',
            'type' => 'Ave',
            'prefix' => ''
          }
        ],
"1005 Highway 116, Sebastopol CA" => [ 
	# should return two results, only returns one. TIGER/Line sux.
          {
            'number' => 1005,
            'lat' => '38.411908',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => 'N',
            'long' => '-122.842232',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"9142 Gravenstein Hwy N, Sebastopol CA" => [ # closest match
          {
            'number' => 5101,
            'lat' => '38.458512',
            'street' => 'Gravenstein',
            'state' => 'CA',
            'zip' => '95472',
            'city' => 'Sebastopol',
            'suffix' => 'N',
            'long' => '-122.874204',
            'type' => 'Hwy',
            'prefix' => ''
          }
        ],
"7800 Mill Station Rd, Sebastopol CA" => [
          {
            'number' => 7800,
            'lat' => '38.412660',
            'street' => 'Mill Station',
            'state' => 'CA',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'suffix' => '',
            'long' => '-122.843046',
            'type' => 'Rd',
            'prefix' => ''
          }
        ],
"Mill Station Rd & Gravenstein Hwy N, Sebastopol, CA" => [
          {
            'type1' => 'Rd',
            'type2' => 'Hwy',
            'lat' => '38.41266',
            'street1' => 'Mill Station',
            'state' => 'CA',
            'suffix2' => 'N',
            'prefix2' => '',
            'suffix1' => '',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'prefix1' => '',
            'street2' => 'Gravenstein',
            'long' => '-122.843046'
          }
        ],
"Mill Station Rd & Gravenstein Hwy N, 95472" => [
          {
            'type1' => 'Rd',
            'type2' => 'Hwy',
            'lat' => '38.41266',
            'street1' => 'Mill Station',
            'state' => 'CA',
            'suffix2' => 'N',
            'prefix2' => '',
            'suffix1' => '',
            'city' => 'Sebastopol',
            'zip' => '95472',
            'prefix1' => '',
            'street2' => 'Gravenstein',
            'long' => '-122.843046'
          }
        ],
);

my @fail = (
    "42nd & Broadway, New York, NY",
    "Gravenstein Hwy N & Your Mom, 95472",
);

while (my ($addr, $spec) = each %address) {
    my @result = Geo::Coder::US->geocode($addr);
    is_deeply( \@result, $spec, "match: \"$addr\"" );

    @result = Geo::Coder::US->geocode( uc $addr );
    is_deeply( \@result, $spec, "match: uc \"$addr\"" );

    @result = Geo::Coder::US->geocode( lc $addr );
    is_deeply( \@result, $spec, "match: lc \"$addr\"" );
}

for my $addr (@fail) {
    my @result = Geo::Coder::US->geocode($addr);
    is( scalar(@result), 1, "parsed but no match: \"$addr\"" );
    ok( !$result[0]{lat}, "no latitude: \"$addr\"" );
    ok( !$result[0]{long}, "no longitude: \"$addr\"" );
}
