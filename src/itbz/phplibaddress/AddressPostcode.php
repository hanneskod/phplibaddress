<?php
/**
 * This file is part of the phplinaddress package
 *
 * Copyright (c) 2012 Hannes Forsgård
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Hannes Forsgård <hannes.forsgard@gmail.com>
 *
 * @package phplinaddress
 */
namespace itbz\phplinaddress;


/**
 * Fetch address postcode, mock base class
 *
 * @package phplinaddress
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