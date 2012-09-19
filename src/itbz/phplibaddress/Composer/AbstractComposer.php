<?php
/**
 * This file is part of the phplibaddress package
 *
 * Copyright (c) 2012 Hannes Forsgård
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Hannes Forsgård <hannes.forsgard@gmail.com>
 *
 * @package phplibaddress\Composer
 */
namespace itbz\phplibaddress\Composer;
use itbz\phplibaddress\Address;
use itbz\phplibaddress\Exception;


/**
 * Compose complete addresses from address components
 *
 * @package phplibaddress
 */
abstract class AbstractComposer
{

    /**
     * Character used to separate lines
     */
    const LINE_SEPARATOR = "\n";


    /**
     * Internal address container
     *
     * @var Address
     */
    protected $_address;


    /**
     * Get formatted address
     *
     * @return string
     */
    abstract public function format();


    /**
     * Get addresse (recipient)
     *
     * @return string
     */
    abstract public function getAddressee();


    /**
     * Get mailee (including role descriptor)
     * 
     * @return string
     */
    abstract public function getMailee();

 
     /**
     * Get formatted locality
     *
     * If the address is not domestic country code and name are included.
     *
     * @return string
     */
    abstract public function getLocality();


    /**
     * Get administrative service point address
     *
     * Eg a box or poste restante address
     *
     * @return string Returns the empty string if address->isServicePoint()
     * returns FALSE
     */
    abstract public function getServicePoint();


    /**
     * Get geographical address location
     *
     * Eg. street, apartment number and so on.
     *
     * @return string
     */
    abstract public function getDeliveryLocation();


    /**
     * Get the delivery point address.
     *
     * Can be an administrative address (service point) or a geographical
     * address (delivery location). Locality is always included in the deilvery
     * point address.
     *
     * @return string
     */
    abstract public function getDeliveryPoint();
 

    /**
     * Optionally load address container at construct
     *
     * @param Address $address
     */
    public function __construct(Address $address = NULL)
    {
        if ($address) {
            $this->setAddress($address);
        }
    }


    /**
     * Set address container
     *
     * @param Address $address
     *
     * @return void
     */
    public function setAddress(Address $address)
    {
        $this->_address = $address;
    }


    /**
     * Get internal address container
     *
     * @return Address
     */
    public function getAddress()
    {
        if (!isset($this->_address)) {
            $msg = "No address loaded.";
            throw new Exception($msg);
        }

        return $this->_address;
    }


    /**
     * Check if address is syntactically valid
     * 
     * Returns false id address contains more than 6 lines
     * or any line is longer than 36 characters
     * 
     * @return bool
     */
    public function isValid()
    {
        $addr = explode(self::LINE_SEPARATOR, $this->format());
        if (count($addr) > 6) {

            return FALSE;
        }

        foreach ($addr as $line) {
            if (mb_strlen($line) > 36) {

                return FALSE;
            }
        }
        
        return TRUE;
    }


    /**
     * Get formatted and sanitized address
     * 
     * Force address to contain no more than 6 lines
     * and no line that is longer than 36 characters
     * 
     * @return bool
     */
    public function getValid()
    {
        $addr = explode(self::LINE_SEPARATOR, $this->format());
        
        // Remove lines if more than 6
        while (count($addr) > 6) {
            array_shift($addr);
        }
        
        // Force line lengths
        foreach ($addr as &$line) {
            $line = mb_substr($line, 0, 36);        
        }
        
        return implode(self::LINE_SEPARATOR, $addr);
    }



    // ska såklart brytas loss till ett eget paket!!
    // detta verkar vara det jag har kvar att göra nu
    // annars har jag nog landat det jag ville göra...


    /**
     * Concat given names, surname and titel so that the target string is not
     * longer than 36 characters. Transform names to initials or remove them
     * if neccesary.
     * @param string $names
     * @param string $surname
     * @param string $title
     * @return string
     */
    static function concatNames($names = '', $surname = '', $title = '')
    {
        assert('is_string($names)');
        assert('is_string($surname)');
        assert('is_string($title)');

        $fullname = "$title $names $surname";
        $fullname = trim($fullname);

        // Recursively remove characters so that strlen($fullname) < 36
        if ( mb_strlen($fullname) > 36 ) {
            if ( mb_strlen($names) > 0 ) {
                // Shorten $names if > 0
                $names = self::abbrNames($names);
            } elseif ( mb_strlen($title) > 0 ) {
                // Remove title
                $title = '';
            } else {
                // Strip one surname
                $newSurname = preg_replace("/^[^ ]* (.*)$/", "$1", $surname);
                if ( mb_strlen($newSurname) >= mb_strlen($surname) ) {
                    // Last fallback, remove one trailing char from surname
                    $newSurname = mb_substr($newSurname, 0, -1);
                }
                $surname = $newSurname;
            }
            return self::concatNames($names, $surname, $title);
        }
    
        return $fullname;
    }


    /**
     * Shorten string of names
     * @param string $names
     * @return string
     */
    static protected function abbrNames($names)
    {
        assert('is_string($names)');

        $arNames = explode(' ', $names);
    
        // If there is only one name, push it to second position
        if ( count($arNames) == 1 ) {
            $arNames[1] = $arNames[0];
            unset($arNames[0]);
        }
    
        // Shorten or remove names, leave first name
        foreach ( $arNames as $key => &$name ) {
            if ( $key == 0 ) continue;
            if ( mb_strlen($name) == 1 ) {
                // Unset initials if they exist
                unset($arNames[$key]);
            } else {
                // Create initials from complete names
                $name = trim($name);
                $name = mb_substr($name, 0, 1);
                $name = mb_strtoupper($name);
            }
        }
    
        $newNames = implode(' ', $arNames);

        // Assert that the returned string really is shorter
        assert('mb_strlen($newNames) < mb_strlen($names)');
        return $newNames;
    }



}