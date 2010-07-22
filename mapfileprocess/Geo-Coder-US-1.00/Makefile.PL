use 5.6.1;
use ExtUtils::MakeMaker;
# See lib/ExtUtils/MakeMaker.pm for details of how to influence
# the contents of the Makefile that is written.
WriteMakefile(
    NAME              => 'Geo::Coder::US',
    VERSION_FROM      => 'US.pm', # finds $VERSION
    PREREQ_PM         => {
	Geo::TigerLine		=> "0.02",
	Geo::Fips55		=> "0.01",
	Geo::StreetAddress::US	=> "0.99",
	DB_File			=> 0,
	Test::More		=> "0.47"
    }, 
    AUTHOR	      => 'Schuyler Erle <schuyler@nocat.net>, ' .
			 'Jo Walsh <jo@frot.org>'
);
