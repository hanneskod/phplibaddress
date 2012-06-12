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
use itbz\STB\Communication\AddressPostcode as AddressPostcodeBase;


/**
 * Fetch address postcode for swedish addresses from posten.se
 *
 * @package STB\Communication\Swe
 */
class AddressPostcode extends AddressPostcodeBase
{

	/**
	 * Fetch postcode from posten.se
	 * @param string $deliveryPoint
	 * @param string $town
	 * @return string
     * @throws Exception if unable to reach posten.se, or if returned HTML is broken
	 */
	public function fetchPostcode($deliveryPoint, $town){
		assert('is_string($deliveryPoint)');
		assert('is_string($town)');

		if ( !$deliveryPoint || !$town ) return '';

		$rfc2396 = array(
			'Å' => '%C5', 'Ä' => '%C4', 'Ö' => '%D6',
			'å' => '%E5', 'ä' => '%E4', 'ö' => '%F6',
			' ' => '+'
		);

		$deliveryPoint = strtr($deliveryPoint, $rfc2396);
		$town = strtr($town, $rfc2396);

		$searchUrl = "http://www.posten.se/soktjanst/postnummersok/resultat.jspv?gatunamn=$deliveryPoint&po=$town";
		$result = @file_get_contents($searchUrl);

		if ( !$result ) {
            throw new Exception("Unable to fetch postcode from $searchUrl");
		}

		preg_match('!<TABLE class="result".*?</TABLE>!s', $result, $match);
		if ( count($match) == 0 ) {
            throw new Exception("Unable to parse content from $searchUrl");
		}

		preg_match('!<TD>([0-9 ]{5,6})</TD><TD class="lastcol">!s', $match[0], $match);
		$postcode = end($match);
		if ( !preg_match('/^\d\d\d ?\d\d$/', $postcode) ) {
            throw new Exception("Unable to parse content from $searchUrl");
		}

		return $postcode;
	}

}
