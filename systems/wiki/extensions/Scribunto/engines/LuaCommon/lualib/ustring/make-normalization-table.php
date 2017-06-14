#!/usr/bin/php
<?php

if ( PHP_SAPI !== 'cli' ) {
	die( "This script may only be executed from the command line.\n" );
}

$datafile = null;
if ( count( $argv ) > 1 ) {
	$datafile = $argv[1];
	if ( !file_exists( $datafile ) ) {
		die( "The specified file '$datafile' does not exist\n" );
	}
} else {
	foreach ( array(
		__DIR__ . '/../../../../../core/vendor/wikimedia/utfnormal/src/UtfNormalData.inc',
		__DIR__ . '/../../../../../vendor/wikimedia/utfnormal/src/UtfNormalData.inc',
		__DIR__ . '/../../../../../core/includes/libs/normal/UtfNormalData.inc',
		__DIR__ . '/../../../../../includes/libs/normal/UtfNormalData.inc',
	) as $tryfile ) {
		$tryfile = realpath( $tryfile );
		if ( file_exists( $tryfile ) ) {
			$datafile = $tryfile;
			break;
		}
	}
	if ( !$datafile ) {
		die( "Cannot find UtfNormalData.inc. Please specify the path explicitly.\n" );
	}
}

$datafileK = null;
if ( count( $argv ) > 2 ) {
	$datafileK = $argv[2];
	if ( !file_exists( $datafileK ) ) {
		die( "The specified file '$datafileK' does not exist\n" );
	}
} else {
	foreach ( array(
		dirname( $datafile ) . '/UtfNormalDataK.inc',
		__DIR__ . '/../../../../../core/vendor/wikimedia/utfnormal/src/UtfNormalData.inc',
		__DIR__ . '/../../../../../vendor/wikimedia/utfnormal/src/UtfNormalData.inc',
		__DIR__ . '/../../../../../core/includes/libs/normal/UtfNormalData.inc',
		__DIR__ . '/../../../../../includes/libs/normal/UtfNormalData.inc',
	) as $tryfile ) {
		$tryfile = realpath( $tryfile );
		if ( file_exists( $tryfile ) ) {
			$datafileK = $tryfile;
			break;
		}
	}
	if ( !$datafileK ) {
		die( "Cannot find UtfNormalDataK.inc. Please specify the path explicitly.\n" );
	}
}

class UtfNormal {
	public static $utfCheckNFC = null;
	public static $utfCombiningClass = null;
	public static $utfCanonicalDecomp = null;
	public static $utfCanonicalComp = null;
	public static $utfCompatibilityDecomp = null;
}
class_alias( 'UtfNormal', 'UtfNormal\\Validator' );

echo "Loading data file $datafile...\n";
require_once $datafile;

echo "Loading data file $datafileK...\n";
require_once $datafileK;

if ( !UtfNormal::$utfCheckNFC ||
	!UtfNormal::$utfCombiningClass ||
	!UtfNormal::$utfCanonicalDecomp ||
	!UtfNormal::$utfCanonicalComp
) {
	die( "Data file $datafile did not contain needed data.\n" );
}
if ( !UtfNormal::$utfCompatibilityDecomp ) {
	die( "Data file $datafileK did not contain needed data.\n" );
}

// @codingStandardsIgnoreLine MediaWiki.NamingConventions.PrefixedGlobalFunctions
function uord( $c, $firstOnly ) {
	$ret = unpack( 'N*', mb_convert_encoding( $c, 'UTF-32BE', 'UTF-8' ) );
	return $firstOnly ? $ret[1] : $ret;
}

echo "Creating normalization table...\n";
$X = fopen( __DIR__ . '/normalization-data.lua', 'w' );
if ( !$X ) {
	die( "Failed to open normalization-data.lua\n" );
}
fprintf( $X, "-- This file is automatically generated by make-normalization-table.php\n" );
fprintf( $X, "local normal = {\n" );
fprintf( $X, "\t-- Characters that might change depending on the following combiner\n" );
fprintf( $X, "\t-- (minus any that are themselves combiners, those are added later)\n" );
fprintf( $X, "\tcheck = {\n" );
foreach ( UtfNormal::$utfCheckNFC as $k => $v ) {
	if ( isset( UtfNormal::$utfCombiningClass[$k] ) ) {
		// Skip, because it's in the other table already
		continue;
	}
	fprintf( $X, "\t\t[0x%06x] = 1,\n", uord( $k, true ) );
}
fprintf( $X, "\t},\n\n" );
fprintf( $X, "\t-- Combining characters, mapped to combining class\n" );
fprintf( $X, "\tcombclass = {\n" );
$comb = array();
foreach ( UtfNormal::$utfCombiningClass as $k => $v ) {
	$cp = uord( $k, true );
	$comb[$cp] = 1;
	fprintf( $X, "\t\t[0x%06x] = %d,\n", $cp, $v );
}
fprintf( $X, "\t},\n\n" );
fprintf( $X, "\t-- Characters mapped to what they decompose to\n" );
fprintf( $X, "\t-- Note Hangul to Jamo is done separately below\n" );
fprintf( $X, "\tdecomp = {\n" );
foreach ( UtfNormal::$utfCanonicalDecomp as $k => $v ) {
	fprintf( $X, "\t\t[0x%06x] = { ", uord( $k, true ) );
	$fmt = "0x%06x";
	foreach ( uord( $v, false ) as $c ) {
		fprintf( $X, $fmt, $c );
		$fmt = ", 0x%06x";
	}
	fprintf( $X, " },\n" );
}
fprintf( $X, "\t},\n\n" );

fprintf( $X, "\tdecompK = {\n" );
foreach ( UtfNormal::$utfCompatibilityDecomp as $k => $v ) {
	if ( isset( UtfNormal::$utfCanonicalDecomp[$k] ) && UtfNormal::$utfCanonicalDecomp[$k] === $v ) {
		// Skip duplicates
		continue;
	}
	fprintf( $X, "\t\t[0x%06x] = { ", uord( $k, true ) );
	$fmt = "0x%06x";
	foreach ( uord( $v, false ) as $c ) {
		fprintf( $X, $fmt, $c );
		$fmt = ", 0x%06x";
	}
	fprintf( $X, " },\n" );
}
fprintf( $X, "\t},\n\n" );

fprintf( $X, "\t-- Character-pairs mapped to what they compose to\n" );
fprintf( $X, "\t-- Note Jamo to Hangul is done separately below\n" );
$t = array();
foreach ( UtfNormal::$utfCanonicalComp as $k => $v ) {
	$k = uord( $k, false );
	if ( count( $k ) == 1 ) {
		// No idea why these are in the file
		continue;
	}
	if ( isset( $comb[$k[1]] ) ) {
		// Non-starter, no idea why these are in the file either
		continue;
	}
	$t[$k[1]][$k[2]] = uord( $v, true );
}
fprintf( $X, "\tcomp = {\n" );
ksort( $t );
foreach ( $t as $k1 => $v1 ) {
	fprintf( $X, "\t\t[0x%06x] = {\n", $k1 );
	ksort( $v1 );
	foreach ( $v1 as $k2 => $v2 ) {
		if ( $k2 < 0 ) {
			fprintf( $X, "\t\t\t[-1] = 0x%06x,\n", $v2 );
		} else {
			fprintf( $X, "\t\t\t[0x%06x] = 0x%06x,\n", $k2, $v2 );
		}
	}
	fprintf( $X, "\t\t},\n" );
}
fprintf( $X, "\t},\n" );

fprintf( $X, "}\n" );

fprintf( $X, "\n%s\n", <<<LUA
-- All combining characters need to be checked, so just do that
setmetatable( normal.check, { __index = normal.combclass } )

-- Handle Hangul to Jamo decomposition
setmetatable( normal.decomp, { __index = function ( _, k )
	if k >= 0xac00 and k <= 0xd7a3 then
		-- Decompose a Hangul syllable into Jamo
		k = k - 0xac00
		local ret = {
			0x1100 + math.floor( k / 588 ),
			0x1161 + math.floor( ( k % 588 ) / 28 )
		}
		if k % 28 ~= 0 then
			ret[3] = 0x11a7 + ( k % 28 )
		end
		return ret
	end
	return nil
end } )

-- Handle Jamo to Hangul composition
local jamo_l_v_mt = { __index = function ( t, k )
	if k >= 0x1161 and k <= 0x1175 then
		-- Jamo leading + Jamo vowel
		return t.base + 28 * ( k - 0x1161 )
	end
	return nil
end }
local hangul_jamo_mt = { __index = function ( t, k )
	if k >= 0x11a7 and k <= 0x11c2 then
		-- Hangul + jamo final
		return t.base + k - 0x11a7
	end
	return nil
end }
setmetatable( normal.comp, { __index = function ( t, k )
	if k >= 0x1100 and k <= 0x1112 then
		-- Jamo leading, return a second table that combines with a Jamo vowel
		local t2 = { base = 0xac00 + 588 * ( k - 0x1100 ) }
		setmetatable( t2, jamo_l_v_mt )
		t[k] = t2 -- cache it
		return t2
	elseif k >= 0xac00 and k <= 0xd7a3 and k % 28 == 16 then
		-- Hangul. "k % 28 == 16" picks out just the ones that are
		-- Jamo leading + vowel, no final. Return a second table that combines
		-- with a Jamo final.
		local t2 = { base = k }
		setmetatable( t2, hangul_jamo_mt )
		t[k] = t2 -- cache it
		return t2
	end
	return nil
end } )

-- Compatibility decomposition falls back to the normal decomposition
setmetatable( normal.decompK, { __index = normal.decomp } )

return normal
LUA
);

fclose( $X );
