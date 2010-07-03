#!/usr/bin/php
<?php

/*
OpenHeatMap processing
Copyright (C) 2010 Pete Warden <pete@petewarden.com>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once('cliargs.php');
require_once('osmways.php');

function add_country_tags(&$osm_ways, $verbose)
{
    if ($verbose)
        error_log("Starting add_country_tags()");

    $country_translation_table = array(
        "Aruba" => "ABW",
        "Afghanistan" =>"AFG",
        "Angola" =>"AGO",
        "Anguilla" =>"AIA",
        "Åland Islands" =>"ALA",
        "Albania" =>"ALB",
        "Andorra" =>"AND",
        "Netherlands Antilles" =>"ANT",
        "United Arab Emirates" =>"ARE",
        "Argentina" =>"ARG",
        "Armenia" =>"ARM",
        "American Samoa" =>"ASM",
        "Antarctica" =>"ATA",
        "French Southern Territories" =>"ATF",
        "French Southern and Antarctic Lands" =>"ATF",
        "Antigua and Barbuda" =>"ATG",
        "Australia" =>"AUS",
        "Austria" =>"AUT",
        "Azerbaijan" =>"AZE",
        "Burundi" =>"BDI",
        "Belgium" =>"BEL",
        "Benin" =>"BEN",
        "Burkina Faso" =>"BFA",
        "Bangladesh" =>"BGD",
        "Bulgaria" =>"BGR",
        "Bahrain" =>"BHR",
        "Bahamas" =>"BHS",
        "Bosnia and Herzegovina" =>"BIH",
        "Saint Barthélemy" =>"BLM",
        "Saint Barthelemy" =>"BLM",
        "Belarus" =>"BLR",
        "Belize" =>"BLZ",
        "Bermuda" =>"BMU",
        "Bolivia, Plurinational State of" =>"BOL",
        "Bolivia" =>"BOL",
        "Brazil" =>"BRA",
        "Barbados" =>"BRB",
        "Brunei Darussalam" =>"BRN",
        "Bhutan" =>"BTN",
        "Bouvet Island" =>"BVT",
        "Botswana" =>"BWA",
        "Central African Republic" =>"CAF",
        "Canada" =>"CAN",
        "Cocos (Keeling) Islands" =>"CCK",
        "Switzerland" =>"CHE",
        "Chile" =>"CHL",
        "China" =>"CHN",
        "Côte d'Ivoire" =>"CIV",
        "Cote d'Ivoire" =>"CIV",
        "Cameroon" =>"CMR",
        "Congo, the Democratic Republic of the" =>"COD",
        "Democratic Republic of the Congo" =>"COD",
        "Congo" =>"COG",
        "Cook Islands" =>"COK",
        "Colombia" =>"COL",
        "Comoros" =>"COM",
        "Cape Verde" =>"CPV",
        "Costa Rica" =>"CRI",
        "Cuba" =>"CUB",
        "Christmas Island" =>"CXR",
        "Cayman Islands" =>"CYM",
        "Cyprus" =>"CYP",
        "Czech Republic" =>"CZE",
        "Germany" =>"DEU",
        "Djibouti" =>"DJI",
        "Dominica" =>"DMA",
        "Denmark" =>"DNK",
        "Dominican Republic" =>"DOM",
        "Algeria" =>"DZA",
        "Ecuador" =>"ECU",
        "Egypt" =>"EGY",
        "Eritrea" =>"ERI",
        "Western Sahara" =>"ESH",
        "Spain" =>"ESP",
        "Estonia" =>"EST",
        "Ethiopia" =>"ETH",
        "Finland" =>"FIN",
        "Fiji" =>"FJI",
        "Falkland Islands (Malvinas)" =>"FLK",
        "Falkland Islands" =>"FLK",
        "Malvinas" =>"FLK",
        "France" =>"FRA",
        "Faroe Islands" =>"FRO",
        "Micronesia, Federated States of" =>"FSM",
        "Gabon" =>"GAB",
        "United Kingdom" =>"GBR",
        "Georgia" =>"GEO",
        "Guernsey" =>"GGY",
        "Ghana" =>"GHA",
        "Gibraltar" =>"GIB",
        "Guinea" =>"GIN",
        "Guadeloupe" =>"GLP",
        "Gambia" =>"GMB",
        "Guinea-Bissau" =>"GNB",
        "Equatorial Guinea" =>"GNQ",
        "Greece" =>"GRC",
        "Grenada" =>"GRD",
        "Greenland" =>"GRL",
        "Guatemala" =>"GTM",
        "French Guiana" =>"GUF",
        "Guam" =>"GUM",
        "Guyana" =>"GUY",
        "Hong Kong" =>"HKG",
        "Heard Island and McDonald Islands" =>"HMD",
        "Honduras" =>"HND",
        "Croatia" =>"HRV",
        "Haiti" =>"HTI",
        "Hungary" =>"HUN",
        "Indonesia" =>"IDN",
        "Isle of Man" =>"IMN",
        "India" =>"IND",
        "British Indian Ocean Territory" =>"IOT",
        "Ireland" =>"IRL",
        "Iran, Islamic Republic of" =>"IRN",
        "Iran" =>"IRN",
        "Iran (Islamic Republic of)" =>"IRN",
        "Iraq" =>"IRQ",
        "Iceland" =>"ISL",
        "Israel" =>"ISR",
        "Italy" =>"ITA",
        "Jamaica" =>"JAM",
        "Jersey" =>"JEY",
        "Jordan" =>"JOR",
        "Japan" =>"JPN",
        "Kazakhstan" =>"KAZ",
        "Kenya" =>"KEN",
        "Kyrgyzstan" =>"KGZ",
        "Kyrgyz Republic" => "KGZ",
        "Cambodia" =>"KHM",
        "Kiribati" =>"KIR",
        "Saint Kitts and Nevis" =>"KNA",
        "Korea, Republic of" =>"KOR",
        "Kuwait" =>"KWT",
        "Lao People's Democratic Republic" =>"LAO",
        "Laos" =>"LAO",
        "Lebanon" =>"LBN",
        "Liberia" =>"LBR",
        "Libyan Arab Jamahiriya" =>"LBY",
        "Libya" =>"LBY",
        "Saint Lucia" =>"LCA",
        "Liechtenstein" =>"LIE",
        "Sri Lanka" =>"LKA",
        "Lesotho" =>"LSO",
        "Lithuania" =>"LTU",
        "Luxembourg" =>"LUX",
        "Latvia" =>"LVA",
        "Macao" =>"MAC",
        "Macau" =>"MAC",
        "Saint Martin (French part)" =>"MAF",
        "Saint Martin" =>"MAF",
        "Morocco" =>"MAR",
        "Monaco" =>"MCO",
        "Moldova, Republic of" =>"MDA",
        "Moldova" =>"MDA",
        "Republic of Moldova" =>"MDA",
        "Madagascar" =>"MDG",
        "Maldives" =>"MDV",
        "Mexico" =>"MEX",
        "Marshall Islands" =>"MHL",
        "Macedonia, the former Yugoslav Republic of" => "MKD",
        "The former Yugoslav Republic of Macedonia" => "MKD",
        "Macedonia" => "MKD",
        "Mali" =>"MLI",
        "Malta" =>"MLT",
        "Myanmar" =>"MMR",
        "Burma" => "MMR",
        "Montenegro" =>"MNE",
        "Mongolia" =>"MNG",
        "Northern Mariana Islands" =>"MNP",
        "Mozambique" =>"MOZ",
        "Mauritania" =>"MRT",
        "Montserrat" =>"MSR",
        "Martinique" =>"MTQ",
        "Mauritius" =>"MUS",
        "Malawi" =>"MWI",
        "Malaysia" =>"MYS",
        "Mayotte" =>"MYT",
        "Namibia" =>"NAM",
        "New Caledonia" =>"NCL",
        "Niger" =>"NER",
        "Norfolk Island" =>"NFK",
        "Nigeria" =>"NGA",
        "Nicaragua" =>"NIC",
        "Niue" =>"NIU",
        "Netherlands" =>"NLD",
        "Norway" =>"NOR",
        "Nepal" =>"NPL",
        "Nauru" =>"NRU",
        "New Zealand" =>"NZL",
        "Oman" =>"OMN",
        "Pakistan" =>"PAK",
        "Panama" =>"PAN",
        "Pitcairn" =>"PCN",
        "Pitcairn Islands" =>"PCN",
        "Peru" =>"PER",
        "Philippines" =>"PHL",
        "Palau" =>"PLW",
        "Papua New Guinea" =>"PNG",
        "Poland" =>"POL",
        "Puerto Rico" =>"PRI",
        "Korea, Democratic People's Republic of" =>"PRK",
        "North Korea" =>"PRK",
        "Portugal" =>"PRT",
        "Paraguay" =>"PRY",
        "Palestinian Territory, Occupied" =>"PSE",
        "Palestine" =>"PSE",
        "French Polynesia" =>"PYF",
        "Qatar" =>"QAT",
        "Réunion" =>"REU",
        "Reunion" =>"REU",
        "Romania" =>"ROU",
        "Russian Federation" =>"RUS",
        "Russia" =>"RUS",
        "Rwanda" =>"RWA",
        "Saudi Arabia" =>"SAU",
        "Sudan" =>"SDN",
        "Senegal" =>"SEN",
        "Singapore" =>"SGP",
        "South Georgia and the South Sandwich Islands" => "SGS",
        "South Georgia South Sandwich Islands" => "SGS",
        "Saint Helena, Ascension and Tristan da Cunha" => "SHN",
        "Saint Helena" => "SHN",
        "Svalbard and Jan Mayen" =>"SJM",
        "Svalbard" =>"SJM",
        "Solomon Islands" =>"SLB",
        "Sierra Leone" =>"SLE",
        "El Salvador" =>"SLV",
        "San Marino" =>"SMR",
        "Somalia" =>"SOM",
        "Saint Pierre and Miquelon" =>"SPM",
        "Serbia" =>"SRB",
        "Sao Tome and Principe" =>"STP",
        "Suriname" =>"SUR",
        "Slovakia" =>"SVK",
        "Slovak Republic" =>"SVK",
        "Slovenia" =>"SVN",
        "Sweden" =>"SWE",
        "Swaziland" =>"SWZ",
        "Seychelles" =>"SYC",
        "Syrian Arab Republic" =>"SYR",
        "Syria" =>"SYR",
        "Turks and Caicos Islands" =>"TCA",
        "Chad" =>"TCD",
        "Togo" =>"TGO",
        "Thailand" =>"THA",
        "Tajikistan" =>"TJK",
        "Tokelau" =>"TKL",
        "Turkmenistan" =>"TKM",
        "Timor-Leste" =>"TLS",
        "Tonga" =>"TON",
        "Trinidad and Tobago" =>"TTO",
        "Tunisia" =>"TUN",
        "Turkey" =>"TUR",
        "Tuvalu" =>"TUV",
        "Taiwan, Province of China" =>"TWN",
        "Taiwan" =>"TWN",
        "Tanzania, United Republic of" =>"TZA",
        "Tanzania" =>"TZA",
        "United Republic of Tanzania" =>"TZA",
        "Uganda" =>"UGA",
        "Ukraine" =>"UKR",
        "United States Minor Outlying Islands" =>"UMI",
        "Uruguay" =>"URY",
        "United States" =>"USA",
        "USA" =>"USA",
        "America" =>"USA",
        "Uzbekistan" =>"UZB",
        "Holy See (Vatican City State)" =>"VAT",
        "Vatican" =>"VAT",
        "Holy See (Vatican City)" =>"VAT",
        "Saint Vincent and the Grenadines" =>"VCT",
        "Venezuela, Bolivarian Republic of" =>"VEN",
        "Venezuela" =>"VEN",
        "Virgin Islands, British" =>"VGB",
        "British Virgin Islands" =>"VGB",
        "Virgin Islands, U.S." =>"VIR",
        "United States Virgin Islands" =>"VIR",
        "Viet Nam" =>"VNM",
        "Vietnam" =>"VNM",
        "Vanuatu" =>"VUT",
        "Wallis and Futuna" =>"WLF",
        "Wallis and Futuna Islands" =>"WLF",
        "Samoa" =>"WSM",
        "Yemen" =>"YEM",
        "South Africa" =>"ZAF",
        "Zambia" =>"ZMB",
        "Zimbabwe" =>"ZWE",
    );

    foreach ($osm_ways->ways as $way_id => &$way)
    {
        $tags = &$way['tags'];
        if (!isset($tags['name']))
        {
            error_log("No name found for way $way_id");
            continue;
        }

        $name = $tags['name'];
        
        if (!isset($country_translation_table[$name]))
        {
            error_log("No code found for '$name'");
            continue;
        }
            
        $tags['country_code'] = $country_translation_table[$name];
    }
    
    if ($verbose)
        error_log("Finished add_country_tags()");
}

ini_set('memory_limit', '-1');

$cliargs = array(
	'inputfile' => array(
		'short' => 'i',
		'type' => 'optional',
		'description' => 'The file to read the input OSM XML data from - if unset, will read from stdin',
        'default' => 'php://stdout',
	),
	'outputfile' => array(
		'short' => 'o',
		'type' => 'optional',
		'description' => 'The file to write the output OSM XML data to - if unset, will write to stdout',
        'default' => 'php://stdout',
	),
    'verbose' => array(
        'short' => 'v',
        'type' => 'switch',
        'description' => 'Whether to show extra debugging information about the processing as it happens',
    ),
);	

$options = cliargs_get_options($cliargs);

$input_file = $options['inputfile'];
$output_file = $options['outputfile'];
$verbose = $options['verbose'];

if ($verbose)
    error_log("Starting load of '$input_file'");

$input_osm_ways = new OSMWays();
$input_contents = file_get_contents($input_file) or die("Couldn't read file '$input_file'");
$input_osm_ways->deserialize_from_xml($input_contents);

add_country_tags($input_osm_ways, $verbose);

if ($verbose)
    error_log("Starting save of '$output_file'");
    
$output_contents = $input_osm_ways->serialize_to_xml();
file_put_contents($output_file, $output_contents) or die("Couldn't write file '$output_file'");

?>