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
 * @package STB\Communication
 */
namespace itbz\STB\Communication;


/**
 * Fetch address postcode, mock base class
 *
 * @package STB\Communication
 */
class AddressPostcode
{

	/**
	 * Fetch postcode, does nothing
	 * @param string $deliveryPoint
	 * @param string $town
	 * @return string
	 */
	public function fetchPostcode($deliveryPoint, $town){
		assert('is_string($deliveryPoint)');
		assert('is_string($town)');
		return '';
	}

}
