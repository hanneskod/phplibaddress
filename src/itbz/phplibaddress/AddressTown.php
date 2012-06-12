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
 * Fetch address town information, mock base class
 *
 * @package STB\Communication
 */
class AddressTown
{

	/**
	 * Fetch town, returns nothing
	 * @param string $postcode
	 * @return string
	 */ 
	public function fetchTown($postcode){
		return '';
	}

}
