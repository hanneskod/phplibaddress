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
 * Fetch address town information, mock base class
 *
 * @package phplinaddress
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