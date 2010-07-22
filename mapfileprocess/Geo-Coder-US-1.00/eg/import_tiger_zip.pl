#!/usr/bin/perl

use File::Temp 'tempdir';
use File::Basename qw(fileparse basename);
use Archive::Zip ':ERROR_CODES';
use Geo::Coder::US::Import;
use strict;
use warnings;

my $database = shift @ARGV
    or die "Usage: $0 <path_to_database.db> <tgrNNNNN.zip> ...\n";
Geo::Coder::US->set_db( $database, 1 );

my $dir = tempdir( CLEANUP => 1 );
die "Can't write files to temp dir '$dir': $!\n" unless -d $dir;

for my $file (map(glob, @ARGV)) {
    warn "Reading $file...\n";
    eval {
	my $zip = Archive::Zip->new;
	die "Can't read ZIP file $file" unless $zip->read($file) == AZ_OK;

	my @members = $zip->membersMatching( ".*\.RT[1456C]" );
	die "Can't find matching TIGER/Line data in $file" unless @members;
	
	warn "Extracting ", scalar(@members), " TIGER/Line files...\n";
	$_->extractToFileNamed("$dir/" . basename($_->fileName)) for @members;

	my ($base) = fileparse( $members[0]->fileName, qr/\.RT./ );
	warn "Importing TIGER/Line data from $base...\n";
	Geo::Coder::US::Import->load_tiger_data( "$dir/$base" );
    };
    warn $@ if $@;
    unlink for glob "$dir/*.RT?";
}
