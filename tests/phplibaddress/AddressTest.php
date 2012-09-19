<?php
namespace itbz\phplibaddress;
use itbz\phpcountry\Country;


class AddressTest extends \PHPUnit_Framework_TestCase
{

    function getAddress()
    {
        $country = new Country;
        $country->setLang('en');
        $addr = new Address($country);

        return $addr;
    }


    function testNoCountryName()
    {
        $a = $this->getAddress();
        $this->assertEquals(
            '',
            $a->getCountry()
        );
    }

}