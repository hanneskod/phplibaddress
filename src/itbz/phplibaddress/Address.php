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
 * @package phplibaddress
 */
namespace itbz\phplibaddress;
use itbz\phpcountry\Country;
use itbz\phpcountry\TranslationException;


/**
 * Model postal addresses as of Swedish standard SS 613401:2011 ed. 3
 *
 * @package phplibaddress
 */
class Address
{

    /**
     * Address form
     * 
     * @var string
     */
    private $_form = '';


    /**
     * Address given name
     *
     * @var string
     */
    private $_givenName = '';


    /**
     * Address surname
     *
     * @var string
     */
    private $_surname = '';


    /**
     * Address name of organisation
     * 
     * @var string
     */
    private $_organisationName = '';


    /**
     * Address legal status
     * 
     * @var string
     */
    private $_legalStatus = '';


    /**
     * Address organisational unit
     * 
     * @var string
     */
    private $_organisationalUnit = '';


    /**
     * Address name of mailee
     * 
     * @var string
     */
    private $_mailee = '';


    /**
     * Address mailee role descriptor
     * 
     * @var string
     */
    private $_maileeRoleDescriptor = 'c/o';


    /**
     * Type of delivery service
     *
     * @var string
     */
    private $_deliveryService = '';


    /**
     * Specification of delivery service
     *
     * @var string
     */
    private $_alternateDeliveryService = '';


    /**
     * Address thoroughfare (street name)
     *
     * @var string
     */
    private $_thoroughfare = '';


    /**
     * Address plot (street number)
     *
     * @var string
     */
    private $_plot = '';


    /**
     * Address littera (letter)
     *
     * @var string
     */
    private $_littera = '';


    /**
     * Address stairwell
     *
     * @var string
     */
    private $_stairwell = '';


    /**
     * Address door (apartment number)
     *
     * @var string
     */
    private $_door = '';


    /**
     * Address floor
     *
     * @var string
     */
    private $_floor = '';


    /**
     * Address supplementary data
     *
     * @var string
     */
    private $_supplementaryDeliveryPointData = '';


    /**
     * Address postcode (zip code)
     *
     * @var string
     */
    private $_postcode = '';


    /**
     * Address town
     *
     * @var string
     */
    private $_town = '';


    /**
     * ISO 3166-1 country code translator
     *
     * @var Country
     */
    private $_countryCodes;


    /**
     * ISO 3166 alpha 2 destination country code
     *
     * @var string
     */
    private $_country = '';


    /**
     * ISO 3166 alpha 2 origin country code
     *
     * @var string
     */
    private $_countryOfOrigin = '';


    /**
     * Model postal addresses as of Swedish standard SS 613401:2011 ed. 3
     *
     * @param Country $countryCodeTranslator The translation language should be
     * set either to the language of the originating country, or to one of the
     * colonial languages (eg. english).
     */
    public function __construct(Country $countryCodeTranslator)
    {
        $this->_countryCodes = $countryCodeTranslator;
    }


    /**
     * Get addresse (recipient)
     *
     * @return string
     */
    public function getAddressee()
    {
        // Add legal status to name of organisation if possible
        $org = mb_substr($this->getOrganisationName(), 0, 36);
        if (mb_strlen("$org {$this->getLegalStatus()}") <= 36) {
            $org = trim("$org {$this->getLegalStatus()}");
        }

        // Construct addressee
        $lines = array(
            mb_substr($this->getOrganisationalUnit(), 0, 36),
            self::concatNames(
                $this->getGivenName(),
                $this->getSurname(),
                $this->getForm()
            ),
            $org
        );

        return implode("\n", array_filter($lines));
    }


    /**
     * Get mailee (including role descriptor)
     * 
     * @return string
     */
    public function getMailee()
    {
        if ($this->getNameOfMailee() == '') {
            
            return '';
        }

        return trim(
            sprintf(
                "%s %s",
                $this->getMaileeRoleDescriptor(),
                $this->getNameOfMailee()
            )
        );
    }


    /**
     * Get town and zip-code according to swedish standars.
     *
     * If the address is not domestic country code and name are included.
     *
     * @return string
     */
    public function getLocality()
    {
        if ($this->isDomestic()) {
            
            return trim("{$this->getPostcode()} {$this->getTown()}");
        } else {
            
            return trim(
                sprintf(
                    "%s-%s %s\n%s",
                    $this->getCountryCode(),
                    $this->getPostcode(),
                    $this->getTown(),
                    $this->getCountry()
                )
            );
        }
    }


    /**
     * Get administrative service point address
     *
     * Eg a box or poste restante address
     *
     * @return string Returns the empty string ig isServicePoint returns FALSE
     */
    public function getServicePoint()
    {
        if (!$this->isServicePoint()) {

            return '';
        } else {

            return trim(
                sprintf(
                    "%s %s",
                    $this->getDeliveryService(),
                    $this->getAlternateDeliveryService()
                )
            );
        }
    }


    /**
     * Get geographical address location
     *
     * Eg. street, apartment number and so on.
     *
     * @return string
     */
    public function getDeliveryLocation()
    {
        if (!$this->isDeliveryLocation()) {

            return '';
        }

        $parts = array(
            $this->getThoroughfare(),
            $this->getPlot(),
            $this->getLittera(),
            $this->getStairwell()
        );

        if ($this->getDoor() != '') {
            $parts[] = "lgh {$this->getDoor()}";
        } else {
            $parts[] = $this->getFloor();
        }

        $parts = array_filter($parts);

        // If longer than 36 characters break up into two lines
        if (mb_strlen(implode(' ', $parts)) > 36) {
            $lines = array(
                array_shift($parts),
                implode(' ', $parts)
            );

        // Else include supplementary delivery point above the thoroughfare
        } else {
            $lines = array(implode(' ', $parts));
            if ($this->getSupplementaryData() != '') {
                array_unshift($lines, $this->getSupplementaryData());
            }
        }

        return trim(implode("\n", $lines));
    }


    /**
     * Get the delivery point address.
     *
     * Can be an administrative address (service point) or a geographical
     * address (delivery location). Locality is always included in the deilvery
     * point address.
     *
     * @return string
     */
    public function getDeliveryPoint()
    {
        if ($this->isServicePoint()) {
            $point = $this->getServicePoint();
        } else {
            $point = $this->getDeliveryLocation();
        }

        return trim(
            sprintf(
                "%s\n%s",
                $point,
                $this->getLocality()
            )
        );
    }


    // Den här har jag inte riktigt gjort ännu
    // det finns inte heller några tester som körs på den...

    /**
     * Get complete address
     * 
     * @return string
     */
    public function getAddress($mailerCountry = 'SE')
    {
        // TODO tillfälligt, ska bort helt...
        $this->setCountryOfOrigin($mailerCountry);


        $addr = array();
        $addr[] = $this->getAddressee();
        $addr[] = $this->getMailee();
        $addr[] = $this->getDeliveryPoint();
        $addr = array_filter($addr);
        $addr = implode("\n", $addr);
        $addr = self::sanitize($addr);
       
        return trim($addr);
    }


    // värför är dessa statiska??
    // borde jag inte styra det på något annat sätt...

    /**
     * Validate address syntax
     * 
     * Returns false id address contains more than 6 lines
     * or any line is longer than 36 characters
     * 
     * @param string $addr
     * 
     * @return bool
     */
    static public function validate($addr)
    {
        $arr = explode("\n", $addr);
        if ( count($arr) > 6 ) return false;
        foreach ( $arr as $line ) {
            if ( mb_strlen($line) > 36 ) return false;
        }
        return true;
    }


    /**
     * Sanitiza address syntax
     * 
     * Force address to contain no more than 6 lines
     * and no line that is longer than 36 characters
     * 
     * @param string $addr
     * 
     * @return bool
     */
    static public function sanitize($addr)
    {
        $arr = explode("\n", $addr);
        
        // Remove lines if more then 6
        while ( count($arr) > 6 ) {
            array_shift($arr);
        }
        
        // Force line length
        foreach ( $arr as &$line ) {
            $line = mb_substr($line, 0, 36);        
        }
        
        return implode("\n", $arr);
    }


    // getters och setters under här har jag gjort...
    // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!


    /**
     * Get form
     *
     * @return string
     */
    public function getForm()
    {
        return $this->_form;
    }
    
    
    /**
     * Set form
     *
     * @param string $form
     * 
     * @return void
     */
    public function setForm($form)
    {
        assert('is_string($form)');
        $this->_form = $form;
    }
    

    /**
     * Get given name
     *
     * @return string
     */
    public function getGivenName()
    {
        return $this->_givenName;
    }
    
    
    /**
     * Set given name
     *
     * @param string $givenName
     * 
     * @return void
     */
    public function setGivenName($givenName)
    {
        assert('is_string($givenName)');
        $this->_givenName = $givenName;
    }
    

    /**
     * Get surname
     *
     * @return string
     */
    public function getSurname()
    {
        return $this->_surname;
    }
    
    
    /**
     * Set surname
     *
     * @param string $surname
     *
     * @return void
     */
    public function setSurname($surname)
    {
        assert('is_string($surname)');
        $this->_surname = $surname;
    }
    

    /**
     * Get name of organisation
     *
     * @return string
     */
    public function getOrganisationName()
    {
        return $this->_organisationName;
    }
    
    
    /**
     * Set name of organisation
     *
     * @param string $organisationName
     * 
     * @return void
     */
    public function setOrganisationName($organisationName)
    {
        assert('is_string($organisationName)');
        $this->_organisationName = $organisationName;
    }
    

    /**
     * Get legal status
     *
     * @return string
     */
    public function getLegalStatus()
    {
        return $this->_legalStatus;
    }
    
    
    /**
     * Set legal status
     *
     * @param string $legalStatus
     *
     * @return void
     */
    public function setLegalStatus($legalStatus)
    {
        assert('is_string($legalStatus)');
        $this->_legalStatus = $legalStatus;
    }
    

    /**
     * Get organisational unit
     *
     * @return string
     */
    public function getOrganisationalUnit()
    {
        return $this->_organisationalUnit;
    }
    
    
    /**
     * Set organisational unit
     *
     * @param string $organisationalUnit
     *
     * @return void
     */
    public function setOrganisationalUnit($organisationalUnit)
    {
        assert('is_string($organisationalUnit)');
        $this->_organisationalUnit = $organisationalUnit;
    }
    

    /**
     * Get name of mailee
     *
     * @return string
     */
    public function getNameOfMailee()
    {
        return $this->_mailee;
    }
    
    
    /**
     * Set name of mailee
     *
     * @param string $mailee
     *
     * @return void
     */
    public function setNameOfMailee($mailee)
    {
        assert('is_string($mailee)');
        $this->_mailee = $mailee;
    }
    

    /**
     * Get mailee role descriptor
     *
     * @return string
     */
    public function getMaileeRoleDescriptor()
    {
        return $this->_maileeRoleDescriptor;
    }
    
    
    /**
     * Set mailee role descriptor
     *
     * @param string $maileeRoleDescriptor
     *
     * @return void
     */
    public function setMaileeRoleDescriptor($maileeRoleDescriptor)
    {
        assert('is_string($maileeRoleDescriptor)');
        $this->_maileeRoleDescriptor = $maileeRoleDescriptor;
    }


    /**
     * Get thoroughfare (street name)
     *
     * @return string
     */
    public function getThoroughfare()
    {
        return $this->_thoroughfare;
    }
    
    
    /**
     * Set thoroughfare (street name)
     *
     * @param string $thoroughfare
     * 
     * @return void
     */
    public function setThoroughfare($thoroughfare)
    {
        assert('is_string($thoroughfare)');
        $this->_thoroughfare = $thoroughfare;
    }
    

    /**
     * Get plot (street number)
     *
     * @return string
     */
    public function getPlot()
    {
        return $this->_plot;
    }
    
    
    /**
     * Set plot (street number)
     *
     * @param string $plot
     * 
     * @return void
     */
    public function setPlot($plot)
    {
        assert('is_string($plot)');
        $this->_plot = $plot;
    }
    

    /**
     * Get littera (letter)
     *
     * @return string
     */
    public function getLittera()
    {
        return $this->_littera;
    }
    
    
    /**
     * Set littera (letter)
     *
     * @param string $littera
     * 
     * @return void
     */
    public function setLittera($littera)
    {
        assert('is_string($littera)');
        $this->_littera = $littera;
    }
    

    /**
     * Get stairwell
     *
     * @return string
     */
    public function getStairwell()
    {
        return $this->_stairwell;
    }
    
    
    /**
     * Set stairwell
     *
     * @param string $stairwell
     * 
     * @return void
     */
    public function setStairwell($stairwell)
    {
        assert('is_string($stairwell)');
        $this->_stairwell = $stairwell;
    }
    

    /**
     * Get door (apartment number)
     *
     * @return string
     */
    public function getDoor()
    {
        return $this->_door;
    }
    
    
    /**
     * Set door (apartment number)
     *
     * @param string $door
     * 
     * @return void
     */
    public function setDoor($door)
    {
        assert('is_string($door)');
        $this->_door = $door;
    }
    

    /**
     * Get floor
     *
     * @return string
     */
    public function getFloor()
    {
        return $this->_floor;
    }
    
    
    /**
     * Set floor
     *
     * @param string $floor
     * 
     * @return void
     */
    public function setFloor($floor)
    {
        assert('is_string($floor)');
        $this->_floor = $floor;
    }


    /**
     * Get supplementary delivery point information
     *
     * @return string
     */
    public function getSupplementaryData()
    {
        return $this->_supplementaryDeliveryPointData;
    }
    
    
    /**
     * Set supplementary delivery point information
     *
     * @param string $supplementaryData
     * 
     * @return void
     */
    public function setSupplementaryData($supplementaryData)
    {
        assert('is_string($supplementaryData)');
        $this->_supplementaryDeliveryPointData = $supplementaryData;
    }
    

    /**
     * Get postcode (zip code)
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->_postcode;
    }
    
    
    /**
     * Set postcode (zip code)
     *
     * @param string $postcode
     * 
     * @return void
     */
    public function setPostcode($postcode)
    {
        assert('is_string($postcode)');
        $this->_postcode = $postcode;
    }
    

    /**
     * Get town
     *
     * @return string
     */
    public function getTown()
    {
        return $this->_town;
    }
    
    
    /**
     * Set town
     *
     * @param string $town
     * 
     * @return void
     */
    public function setTown($town)
    {
        assert('is_string($town)');
        $this->_town = $town;
    }


    /**
     * Set destination country code
     *
     * @param string $code Two letter ISO 3166-1 country code
     *
     * @return void
     */
    public function setCountryCode($code)
    {
        assert('is_string($code) && strlen($code) == 2 && ctype_alpha($code)');
        $this->_country = strtoupper($code);
    }


    /**
     * Ǵet current destination country code
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->_country;
    }


    /**
     * Get name of country
     *
     * @return string Returns the empty string if no translation could be found
     */
    public function getCountry()
    {
        try {
            
            return $this->_countryCodes->translate($this->getCountryCode());
        } catch (TranslationException $e) {
            
            return '';
        }
    }


    /**
     * Set orogin country code
     *
     * @param string $code Two letter ISO 3166-1 country code
     *
     * @return void
     */
    public function setCountryOfOrigin($code)
    {
        assert('is_string($code) && strlen($code) == 2 && ctype_alpha($code)');
        $this->_countryOfOrigin = strtoupper($code);
    }


    /**
     * Ǵet code for country of origin
     *
     * @return string
     */
    public function getCountryOfOrigin()
    {
        return $this->_countryOfOrigin;
    }


    /**
     * Check if this is a domestic address
     *
     * @return bool
     */
    public function isDomestic()
    {
        return (
            $this->getCountryCode() === ''
            || $this->getCountryCode() === $this->getCountryOfOrigin()
        );
    }


    /**
     * Set type of delivery service
     *
     * @param string $service
     *
     * @return void
     */
    public function setDeliveryService($service)
    {
        assert('is_string($service)');
        $this->_deliveryService = trim($service);
    }


    /**
     * Get type of delivery service
     *
     * @return string
     */
    public function getDeliveryService()
    {
        return $this->_deliveryService;
    }

    /**
     * Set specification of delivery service
     *
     * @param string $service
     *
     * @return void
     */
    public function setAlternateDeliveryService($service)
    {
        assert('is_string($service)');
        $this->_alternateDeliveryService = trim($service);
    }


    /**
     * Get specification of delivery service
     *
     * @return string
     */
    public function getAlternateDeliveryService()
    {
        return $this->_alternateDeliveryService;
    }


    /**
     * Check if this is an administrative service point address
     *
     * @return bool
     */
    public function isServicePoint()
    {
        return $this->getDeliveryService() != '';
    }


    /**
     * Check if this is a geographical address location
     *
     * @return bool
     */
    public function isDeliveryLocation()
    {
        return $this->getThoroughfare() != '';
    }



    // Här under har jag som inte börjat fixa ännu.....
    // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!


    /**
     * Concat given names, surname and titel so that the target string is not
     * longer than 36 characters. Transform names to initials or remove them
     * if neccesary.
     * @param string $names
     * @param string $surname
     * @param string $title
     * @return string
     */
    static private function concatNames($names = '', $surname = '', $title = '')
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
    static private function abbrNames($names)
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