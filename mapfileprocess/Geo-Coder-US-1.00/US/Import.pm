=head1 NAME

Geo::Coder::US::Import - Import TIGER/Line data into a Geo::Coder::US database

=head1 SYNOPSIS

  use Geo::Coder::US::Import;

  Geo::Coder::US->set_db( "/path/to/geocoder.db", 1 );

  Geo::Coder::US::Import->load_tiger_data( "TGR06075" );

  Geo::Coder::US::Import->load_fips_data( "All_fips55.txt" );

=head1 DESCRIPTION

Geo::Coder::US::Import provides methods for importing TIGER/Line data
into a BerkeleyDB database for use with Geo::Coder::US.

Instead of using this module directly, you may want to use one of the
included utility scripts in the eg/ directory of this distribtion.
The import_tiger.pl script imports uncompresed TIGER/Line files from a
given location:

  $ perl eg/import_tiger.pl geocoder.db /path/to/tiger/files/TGRnnnnn

Be sure to leave off the .RT? extensions or import_tiger.pl will complain.

The import_tiger_zip.pl script imports compressed TIGER/Line data by
using L<Archive::Zip> to extract only the needed files from the ZIP file
into a temporary directory, which it cleans up for you afterwards. This
is the B<preferred> method of data import, as it can handle multiple
ZIP files at once:

  $ perl eg/import_tiger_zip.pl geocoder.db /path/to/tiger/zips/*.zip

Both of these import scripts need to cache a lot of data in memory, so
you may find that you need one or two hundred megs of RAM for the import
to run to completion. The import process takes about 6 hours to import
all 4 gigabytes of compressed TIGER/Line data on a 2 GHz Linux machine,
and it appears to be mostly processor bound. The final BerkeleyDB database
produced by such an import tops out around 750 megabytes.

One way of avoiding the RAM bloat on import is to use xargs to run
import_tiger_zip.pl on each TIGER/Line ZIP separately:

  $ find ~/tiger -name '*.zip' | \
	xargs -n1 perl eg/import_tiger_zip.pl geocoder.db

Similarly, you can import FIPS-55 place name data into a
Geo::Coder::US database with eg/import_fips.pl:

  $ perl eg/import_fips.pl geocoder.db All_fips55.txt

Note that you can make a perfectly good geocoder for a particular
region of the US by simply importing only the TIGER/Line and FIPS-55
files for the region you're interested in. You only need to import all
of the TIGER/Line data sets in the event that you want a geocoder for
the whole US.

=cut

package Geo::Coder::US::Import;

use Geo::Coder::US;
use Geo::StreetAddress::US;
use Geo::TigerLine::Record::1;
use Geo::TigerLine::Record::4;
use Geo::TigerLine::Record::5;
use Geo::TigerLine::Record::6;
use Geo::TigerLine::Record::C;
use Geo::Fips55;
use Carp;
use strict;
use warnings;

my (%place, %street, %seg, %tlid, %feat, %alt,
    %fips_to_zip, %zip_to_fips, 
    %place_type, %place_name);

=head1 CLASS METHODS

=over 4

=item load_tiger_data( $tiger_basename )

Loads all data from the specified TIGER/Line data set in order of the
following record types: C, 5, 1, 4, 6. This ordering ensures that record
references are set correctly. You may prefix $tiger_basename with an
absolute or relative path, but B<do not> provide the .RT? filename suffix
as part of $tiger_basename or load_tiger_data() will become cranky.

Note that you B<must> first call Geo::Coder::US->set_db() with a second
argument with a true value, or set_db() won't open the database for
writing.

=item load_fips_data( $fips_file )

Loads all the data from the specified FIPS-55 gazetteer file. This
provides additional or alternate place name data to supplement
TIGER/Line.

=cut

sub _fixup_directionals {
    my $record = shift;

    # fix up direction prefix embedded in feature name
    # either a full or abbreviated directional
    $record->{fedirp} =
	    $Geo::StreetAddress::US::Directional{lc $1} || uc $1
	if not $record->{fedirp} and $record->{fename} =~ 
	    s/^($Geo::StreetAddress::US::Addr_Match{direct})\s+(?=\S)//ios;

    # do the same for suffixes
    $record->{fedirs} =
	    $Geo::StreetAddress::US::Directional{lc $1} || uc $1
	if not $record->{fedirs} and $record->{fename} =~ 
	    s/(?<=\S)\s+($Geo::StreetAddress::US::Addr_Match{direct})$//ios;
}

sub _add_range {
    my ($tlid, $side, $from, $to) = @_;

    s/\D//go for ($from, $to);

    # each value in %seg is [lat, lon, lat, lon, [right side], [left side]]
    push @{$seg{$tlid}[$side eq "r" ? 4 : 5]}, $from, $to;
}

sub _type_1 {
    my $record = shift;
    return unless $record->{fename} and $record->{cfcc} =~ /^A/o;

    my $tlid = $record->{tlid};

    
    # each value in %seg is [lat, lon, lat, lon, [right side], [left side]]
    $seg{$tlid} ||= 
	[ map(abs, @$record{qw{ frlat frlong tolat tolong }}), [], [] ];

    # fix up direction prefix embedded in feature name
    _fixup_directionals($record);

    for my $side ("r", "l") {
	my $fips = $record->{"place$side"} || $record->{"cousub$side"}
	   or next;
	$fips = $record->{"state$side"} . $fips;

	my ($from, $to, $zip) = 
	    @$record{"fradd$side", "toadd$side", "zip$side"};

	next unless $from and $to and $zip 
		and $zip =~ /^\d{5}$/os
		and $zip ne '99999';

	_add_range( $tlid, $side, $from, $to );

	my $key = 
	    join("/", "", $zip, @$record{qw{ fename fetype fedirp fedirs }});
	$tlid{"$tlid$side"} = $key;
	$street{$key}{$tlid}++;
	$place{$key} ||= $fips;

	$fips_to_zip{$fips}{$zip}++;
	$zip_to_fips{$zip} = $fips 
	    if $place_type{$fips} and (
		$place_type{$fips} eq 'C'
		or not $zip_to_fips{$zip}
		or ($zip_to_fips{$zip} and 
		    $place_type{$zip_to_fips{$zip}} ne 'C'));

    }
}

sub _type_4 {
    my $record = shift;
    push @{$feat{$_}}, $record->{tlid} 
	for grep($_, map($record->{"feat$_"}, 1 .. 5));
}

sub _type_5 {
    my $record = shift;
    my $ids   = $feat{$record->{feat}} or return;
    for my $id (@$ids) {
	for my $side ("r", "l") {
	    my $main = $tlid{"$id$side"} or next;
	    next unless exists $Geo::Coder::US::DB{$main};
	    my ($zip, $rt1) = ($main =~ /^\/(\d+)(\/.+)/gos);
	    _fixup_directionals($record);
	    my $rt5 = join("/", 
		"", $zip, @$record{qw{ fename fetype fedirp fedirs }});
	    $alt{$rt5}{$rt1}++;
	}
    }
}

sub _type_6 {
    my $record = shift;
    my $tlid = $record->{tlid};
    return unless exists $seg{$tlid};

    for my $side ("r", "l") {
	my ($from, $to, $zip) = @$record{"fradd$side", "toadd$side"};
	next unless $from and $to;
	_add_range( $tlid, $side, $from, $to );
    }
}

sub _type_C {
    my $record = shift;
    return unless $record->{fipscc} =~ /^([CDEFTU])/o # inhabited place
	      and $record->{name} and $record->{fips} and $record->{state};

    my $fips = $record->{state} . $record->{fips};
    $place_type{$fips} = $1;

    $record->{name} =~ s/\s*\(.+\)\s*//gos; # cleanup bits with parens

    $place_name{$fips} = $record->{name};
    if (exists($Geo::StreetAddress::US::State_FIPS{$record->{state}})) {
	my $state = $Geo::StreetAddress::US::State_FIPS{$record->{state}};
	$place_name{$fips} .= ", $state" if ($state);
    }

    # map fips->name
    $Geo::Coder::US::DB{$fips} = $record->{name};
}

sub _compress_segments {
    my @segments = @_;
    my $thunk;
    while (my $item = shift @segments) {
	my ($frlat, $frlong, $tolat, $tolong, $right, $left) = @$item;
	$thunk .= pack("w*", $frlat, $frlong, @$right);
	$thunk .= pack("w*", 0, @$left) if @$left;
	next if @segments and $segments[0][0] == $tolat
			  and $segments[0][1] == $tolong;
	$thunk .= pack("w*", $tolat, $tolong);
    }
    return $thunk;
}

sub load_tiger_data {
    my ($class, $source) = @_;

    my $DB = \%Geo::Coder::US::DB;
    croak "No database specified" unless tied( %$DB );

    open TIGER, "<$source.RTC" or croak "can't read $source.RTC: $!";
    Geo::TigerLine::Record::C->parse_file( \*TIGER, \&_type_C );

    open TIGER, "<$source.RT1" or croak "can't read $source.RT1: $!";
    Geo::TigerLine::Record::1->parse_file( \*TIGER, \&_type_1 );

    if (open TIGER, "<$source.RT6") {
	Geo::TigerLine::Record::6->parse_file( \*TIGER, \&_type_6 );
    } else {
	carp "can't read $source.RT6: $!";
    }

    while (my ($path, $tlids) = each %street) {
	my @segments = @seg{keys %$tlids};
	my @thunk;

	# right side first, ascending
	$thunk[0] = _compress_segments( sort { 
	    ($a->[4][0] || $a->[5][0]) <=> ($b->[4][0] || $b->[5][0])
	    } @segments );
	# right side first, descending
	$thunk[1] = _compress_segments( sort { 
	    ($b->[4][0] || $b->[5][0]) <=> ($a->[4][0] || $a->[5][0])
	    } @segments );
	# left side first, ascending
	$thunk[2] = _compress_segments( sort { 
	    ($a->[5][0] || $a->[4][0]) <=> ($b->[5][0] || $b->[4][0])
	    } @segments );
	# left side first, descending
	$thunk[3] = _compress_segments( sort { 
	    ($b->[5][0] || $b->[4][0]) <=> ($a->[5][0] || $a->[4][0])
	    } @segments );

	@thunk = sort { length($a) <=> length($b) } @thunk;
	$DB->{$path} = pack("w", $place{$path}) . $thunk[0];
    }

    # place name -> zip codes mapping
    while (my ($fips, $zips) = each %fips_to_zip) {
	my $place = $place_name{$fips} or next;
	# make sure place->fips mapping doesn't get duplicates
	if ( exists $DB->{$place} ) {
	    $zips->{$_}++ for unpack("w*", $DB->{$place}) 
	}
	$DB->{$place} = pack("w*", keys %$zips);
    }

    # ZIP code -> FIPS mapping
    $DB->{$_} = pack "w", $zip_to_fips{$_} for keys %zip_to_fips;

    if (open TIGER, "<$source.RT4") {
	Geo::TigerLine::Record::4->parse_file( \*TIGER, \&_type_4 );
    } else {
	carp "can't read $source.RT4: $!";
    }

    if (open TIGER, "<$source.RT5") {
	Geo::TigerLine::Record::5->parse_file( \*TIGER, \&_type_5 );
    } else {
	carp "can't read $source.RT5: $!";
    }

    $DB->{$_} ||= join ",", keys %{$alt{$_}} for keys %alt;

    %tlid = %street = %place = %seg = %feat = %alt 
	  = %place_type = %place_name 
	  = %zip_to_fips = %fips_to_zip = ();
}

sub _fips55 {
    my $record = shift;
    my $DB = \%Geo::Coder::US::DB;
    return unless $record->{name} and $record->{state}
	    and $record->{class} =~ /^[CUT]|^Z1/o;

    for my $type ( "part_of", "other_name" ) {
	next unless $record->{$type};

	my $fips = sprintf("%02d%05d", $record->{state_fips}, $record->{$type});
	next unless exists $DB->{$fips}; 

	my $name = "$record->{name}, $record->{state}";
	$name =~ s/\s*\(.+\)\s*//gos; # cleanup bits with parens
	next if $name =~ /^\d/o or exists $DB->{$name};

	$DB->{$name} = pack "w", $fips;
    }
}

sub load_fips_data {
    my ($class, $source) = @_;
    croak "No database specified" unless tied( %Geo::Coder::US::DB );

    open TIGER, "<$source" or die "can't read $source: $!";
    Geo::Fips55->parse_file( \*TIGER, \&_fips55 );
}

=item load_rtC( $tiger_basename )

=item load_rt5( $tiger_basename )

=item load_rt1( $tiger_basename )

=item load_rt4( $tiger_basename )

=item load_rt6( $tiger_basename )

Each of these methods loads all records from the TIGER/Line record type
specified, with the following exceptions: Type C data is only loaded for
records with a FIPS-55 class code beginning with C, D, E, F, T, U or Z
(i.e. inhabited places). Type 1 data is only loaded for records with a
Census Feature Class Code beginning with A (i.e. street data). Also, Type
1 data for which no feature name or FIPS place and/or county subdivision
is found are not loaded. Finally, Type 6 data lacking a matching Type
1 record in the database are not loaded.

You may prefix $tiger_basename with an absolute or relative path, but
B<do not> provide the .RT? filename suffix as part of $tiger_basename
or the load_rt*() methods will become cranky.

=back 

=head1 BUGS

The import throws away probably useful data on the assumption that it's
not. Similarly, it imports a lot of data you may never use. Mea culpa.
Patches welcome.

Also, you will encounter from time to time errors from your DBI driver
about duplicate keys for certain records. I think the TIGER/Line data has
the odd duplicated TLID in Record Type 1, even though it's not supposed
to. These errors are annoying but not fatal, and can probably be ignored.

The import process can take up huge amounts of RAM. Be forewarned. If
anyone really needs it, the data cached in memory by the import process
could be buffered to disk, but this would slow down the import process
considerably (I think). Contact me if you really want to try this --
it might be faster for you to just download a binary version of the
fully imported database. 

Right now, I can't afford to make the full 750 megabyte database freely
downloadable from my website -- the bandwidth charges would eat me
alive. Contact me if you can offer funding or mirroring.

=head1 SEE ALSO

Geo::Coder::US(3pm), Geo::StreetAddress::US(3pm), Geo::TigerLine(3pm),
Geo::Fips55(3pm), DB_File(3pm), Archive::Zip(3pm)

eg/import_tiger.pl, eg/import_tiger_zip.pl, eg/import_fips.pl

You can download the latest TIGER/Line data (as of this writing) from:

L<http://www.census.gov/geo/www/tiger/tiger2004fe/tgr2004fe.html>

You can get the latest FIPS-55 data from:

L<http://geonames.usgs.gov/fips55/fips55.html>

If you have copious spare time, you can slog through the TIGER/Line 2003
and FIPS-55-3 technical manuals:

L<http://www.census.gov/geo/www/tiger/tiger2003/TGR2003.pdf>

L<http://www.itl.nist.gov/fipspubs/fip55-3.htm>

The TIGER/Line 2004 FE schema is more or less unchanged from 2003.

Finally, a few words about FIPS-55-3 class codes:

L<http://geonames.usgs.gov/classcode.html>

=head1 APPRECIATION

Considerable thanks are due to Michael Schwern <schwern@pobox.com>
for writing the very useful Geo::TigerLine package, which does all
the heavy lifting for this module.

=head1 AUTHOR

Schuyler Erle <schuyler@nocat.net>

=head1 LICENSE

See L<Geo::Coder::US(3pm)> for licensing details.

=cut

1;
