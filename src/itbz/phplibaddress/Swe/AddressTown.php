<?php
/**
 * This file is part of the STB package
 *
 * Copyright (c) 2012 Hannes Forsgård
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Hannes Forsgård <hannes.forsgard@gmail.com>
 *
 * @package STB\Communication\Swe
 */
namespace itbz\STB\Communication\Swe;
use itbz\STB\Exception;
use itbz\STB\Communication\AddressTown as AddressTownBase;


/**
 * Fetch address town information for swedish addresses from posten.se
 *
 * @package STB\Communication\Swe
 */
class AddressTown extends AddressTownBase
{

	/**
	 * Fetch town from posten.se
	 * @param string $postcode
	 * @return string
     * @throws Exception if unable to reach posten.se, or if returned HTML is broken
	 */ 
	public function fetchTown($postcode){
		assert('is_string($postcode)');
		$postcode = urlencode($postcode);

		$searchUrl = "http://www.posten.se/soktjanst/postnummersok/resultat.jspv?pnr=$postcode";
		$result = @file_get_contents($searchUrl);
		
		if ( !$result ) {
            throw new Exception("Unable to fetch town from $searchUrl");
		}

		preg_match('!<TABLE class="result".*?</TABLE>!s', $result, $match);
		if ( count($match) == 0 ) {
            throw new Exception("Unable to parse content from $searchUrl");
		}

		preg_match('!<TD class="lastcol">([^<]*)</TD>!s', $match[0], $match);
		$town = utf8_encode(end($match));
		if ( !preg_match('/^[a-zA-ZåäöÅÄÖ -]*$/', $town) ) {
            throw new Exception("Unable to parse content from $searchUrl");
		}
		return $town;
	}

}