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
 * Model postal addresses as of Swedish standard SS 613401:2011 ed. 3
 *
 * @package phplinaddress
 */
class Address
{

    /**
     * Object for fetching countries
     * @var Country $countries
     */
    private $countries;

    /**
     * Address field
     * @property string $form
     */
    public $form = '';


    /**
     * Address field
     * @property string $given_name
     */
    public $given_name = '';


    /**
     * Address field
     * @property string $surname
     */
    public $surname = '';


    /**
     * Address field
     * @property string $organisation_name
     */
    public $organisation_name = '';


    /**
     * Address field
     * @property string $legal_status
     */
    public $legal_status = '';


    /**
     * Address field
     * @property string $organisational_unit
     */
    public $organisational_unit = '';


    /**
     * Address field
     * @property string $mailee
     */
    public $mailee = '';


    /**
     * Address field
     * @property string $mailee_role_descriptor
     */
    public $mailee_role_descriptor = 'c/o';


    /**
     * Address field
     * @property string $delivery_service
     */
    public $delivery_service = '';


    /**
     * Address field
     * @property string $alternate_delivery_service
     */
    public $alternate_delivery_service = '';


    /**
     * Address field
     * @property string $thoroughfare
     */
    public $thoroughfare = '';


    /**
     * Address field
     * @property string $plot
     */
    public $plot = '';


    /**
     * Address field
     * @property string $littera
     */
    public $littera = '';


    /**
     * Address field
     * @property string $stairwell
     */
    public $stairwell = '';


    /**
     * Address field
     * @property string $door
     */
    public $door = '';


    /**
     * Address field
     * @property string $floor
     */
    public $floor = '';


    /**
     * Address field
     * @property string $supplementary_delivery_point_data
     */
    public $supplementary_delivery_point_data = '';


    /**
     * Address field
     * @property string $postcode
     */
    public $postcode = '';


    /**
     * Address field
     * @property string $town
     */
    public $town = '';


    /**
     * Address field
     * @property string $country_code
     */
    public $country_code = '';


    /**
     * Set dependencies
     * @param Country $countries
     */
    public function __construct(Country $countries)
    {
        $this->countries = $countries;
    }


    /**
     * Mottagare.
     * @return string
     */
    public function getAddressee()
    {
        $addressee = array();
        $addressee[] = mb_substr($this->organisational_unit, 0, 36);
        $addressee[] = self::concatNames($this->given_name, $this->surname, $this->form);
        $org = mb_substr($this->organisation_name, 0, 36);

        if (mb_strlen("$org {$this->legal_status}") <= 36) {
            $org = trim("$org {$this->legal_status}");
        }

        $addressee[] = $org;
        $addressee = array_filter($addressee);
        return implode("\n", $addressee);
    }


    /**
     * Förmedlande mottagare.
     * @return string
     */
    public function getMailee()
    {
        if ( empty($this->mailee) ) return '';
        return trim("{$this->mailee_role_descriptor} {$this->mailee}");
    }


    /**
     * Administrativt avlämningsställe. Exempelvis Box eller Poste restante adresser
     * @return string
     */
    public function getServicePoint()
    {
        if (empty($this->delivery_service)) {
            return '';
        }
        
        return trim("{$this->delivery_service} {$this->alternate_delivery_service}");
    }
    

    /**
     * Geografiskt avlämningsställe. Exempelvis gata, lägenhetsnummer osv..
     * @return string
     */
    public function getDeliveryLocation()
    {
        $elements = array();
        if ( empty($this->thoroughfare) ) return '';

        // Get elements
        $elements[] = $this->thoroughfare;
        if ( !empty($this->plot) ) $elements[] = $this->plot;
        if ( !empty($this->littera) ) $elements[] = $this->littera;
        if ( !empty($this->stairwell) ) $elements[] = $this->stairwell;
        if ( !empty($this->door) ) {
            $elements[] = "lgh {$this->door}";
        } elseif ( !empty($this->floor) ) {
            $elements[] = $this->floor;
        }

        // Convert to strings
        if ( mb_strlen(implode(' ', $elements)) > 36 ) {
            $lines = array(
                array_shift($elements),
                implode(' ', $elements)
            );
        } else {
            $lines = array(implode(' ', $elements));
            // Supplementary data goes above thoroughfare
            if ( !empty($this->supplementary_delivery_point_data) ) {
                array_unshift($lines, $this->supplementary_delivery_point_data);
            }
        }

        $location = implode("\n", $lines);

        return trim($location);
    }


    /**
     * Postadress enligt svensk standard. Innehåller även landsinformation
     * om försändelsen ska skickas till utlandet. Ex. SE-214 20 Malmö
     * @param string $mailerCountry Land försändelsen skickas från,
     * ISO 3166 Alpha 2 landskod.
     * @return string
     */
    public function getLocality($mailerCountry = 'SE')
    {
        $locality = trim("{$this->postcode} {$this->town}");

        // Foreign addressee ?
        if ( !empty($this->country_code) && strcasecmp($this->country_code, $mailerCountry) != 0 ) {
            $cc = mb_strtoupper($this->country_code);
            $locality = "$cc-$locality\n";
            $locality .= $this->countries->fetchByAlpha2($this->country_code, $mailerCountry);
        }

        return trim($locality);
    }


    /**
     * Utdelningsadress. Administrativt eller geografiskt avlämningsställe.
     * Inklusive postadress och landsinformation om försändelsen ska skickas
     * till utlandet.
     * @param string $mailerCountry Land försändelsen skickas från,
     * ISO 3166 Alpha 2 landskod.
     * @return string
     */
    public function getDeliveryPoint($mailerCountry = 'SE')
    {
        $point = $this->getServicePoint();
        if ( empty($point) ) $point =  $this->getDeliveryLocation();
        $point .= "\n" . $this->getLocality($mailerCountry);
        return trim($point);
    }


    /**
     * Komplett adress.
     * @param string $mailerCountry Land försändelsen skickas från,
     * ISO 3166 Alpha 2 landskod.
     * @return string
     */
    public function getAddress($mailerCountry = 'SE')
    {
        $addr = array();
        $addr[] = $this->getAddressee();
        $addr[] = $this->getMailee();
        $addr[] = $this->getDeliveryPoint($mailerCountry);
        $addr = array_filter($addr);
        $addr = implode("\n", $addr);
        $addr = self::sanitize($addr);
        return trim($addr);
    }


    /**
     * Returns false id address contains more than 6 lines
     * or any line is longer than 36 characters
     * @param string $addr
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
     * Force address to contain no more than 6 lines
     * and no line that is longer than 36 characters
     * @param string $addr
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
    static private function abbrNames($names){
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