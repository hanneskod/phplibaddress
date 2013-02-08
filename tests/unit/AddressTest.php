<?php
namespace iio\phplibaddress;

use iio\localefacade\LocaleFacade;

class AddressTest extends \PHPUnit_Framework_TestCase
{
    public function testNoCountryName()
    {
        $a = new Address(new LocaleFacade('en'));
        $this->assertEquals(
            '',
            $a->getCountry()
        );
    }
}
