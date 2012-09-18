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

    
    function testValidateSanitize()
    {
        $a = $this->getAddress();
        $a->setThoroughfare('Very very very long street name 12345');
        $loc = $a->getDeliveryLocation();
        $this->assertEquals($loc, "Very very very long street name 12345");
        $this->assertFalse($a::validate($loc));
        $loc = $a::sanitize($loc);
        $this->assertTrue($a::validate($loc));
    }


    function testGetAddressee()
    {
        $a = $this->getAddress();
        $this->assertEquals('', $a->getAddressee());

        $a->setForm('Mr');
        $a->setGivenName('Karl Hannes Gustav');
        $a->setSurname('Forsgård');
        $this->assertEquals(
            'Mr Karl Hannes Gustav Forsgård',
            $a->getAddressee()
        );

        $a->setSurname('Forsgård Eriksson');
        $this->assertEquals(
            'Mr Karl H G Forsgård Eriksson',
            $a->getAddressee()
        );

        $a->setSurname('Forsgård Eriksson Eriksson');
        $this->assertEquals(
            'Mr Karl Forsgård Eriksson Eriksson',
            $a->getAddressee()
        );

        $a->setSurname('Forsgård Eriksson Eriksson Eriksson');
        $this->assertEquals(
            'Forsgård Eriksson Eriksson Eriksson',
            $a->getAddressee()
        );

        $a->setSurname('Forsgård Eriksson Eriksson Eriksson Eriksson');
        $this->assertEquals(
            'Eriksson Eriksson Eriksson Eriksson',
            $a->getAddressee()
        );

        $a->setSurname('Forsgård ErikssonErikssonErikssonErikssonEriksson');
        $this->assertEquals(
            'ErikssonErikssonErikssonErikssonErik',
            $a->getAddressee()
        );

        $a->setOrganisationalUnit('Unit');
        $a->setSurname('Forsgård');
        $this->assertEquals(
            "Unit\nMr Karl Hannes Gustav Forsgård",
            $a->getAddressee()
        );

        $a->setOrganisationName('Itbrigaden');
        $this->assertEquals(
            "Unit\nMr Karl Hannes Gustav Forsgård\nItbrigaden",
            $a->getAddressee()
        );

        $a->setLegalStatus('AB');
        $this->assertEquals(
            "Unit\nMr Karl Hannes Gustav Forsgård\nItbrigaden AB",
            $a->getAddressee()
        );

        $a->setOrganisationName('Itbrigaden 1234567890123456789012345');
        $this->assertEquals(
            "Unit\nMr Karl Hannes Gustav Forsgård\nItbrigaden 1234567890123456789012345",
            $a->getAddressee()
        );
    }


    function testGetMailee()
    {
        $a = $this->getAddress();
        $this->assertEquals($a->getMailee(), '');

        $a->setNameOfMailee('Foo Bar');
        $this->assertEquals('c/o Foo Bar', $a->getMailee());

        $a->setMaileeRoleDescriptor('bar');
        $this->assertEquals('bar Foo Bar', $a->getMailee());
    }

    
    function testGetServicePoint()
    {
        $a = $this->getAddress();
        $this->assertEquals($a->getServicePoint(), '');

        $a->setDeliveryService('Poste restante');
        $this->assertEquals('Poste restante', $a->getServicePoint());

        $a->setDeliveryService('Box');
        $a->setAlternateDeliveryService('123');
        $this->assertEquals('Box 123', $a->getServicePoint());
    }


    function testGetDeliveryLocation()
    {
        $a = $this->getAddress();
        $this->assertEquals('', $a->getDeliveryLocation());

        $a->setThoroughfare('Yostreet');
        $this->assertEquals('Yostreet', $a->getDeliveryLocation());

        $a->setPlot('1');
        $this->assertEquals('Yostreet 1', $a->getDeliveryLocation());

        $a->setLittera('A');
        $this->assertEquals('Yostreet 1 A', $a->getDeliveryLocation());
        
        $a->setStairwell('UH');
        $this->assertEquals('Yostreet 1 A UH', $a->getDeliveryLocation());

        $a->setFloor('2tr');
        $this->assertEquals('Yostreet 1 A UH 2tr', $a->getDeliveryLocation());

        $a->setDoor('11');
        $this->assertEquals(
            'Yostreet 1 A UH lgh 11',
            $a->getDeliveryLocation()
        );

        $a->setSupplementaryData('Across A street');
        $this->assertEquals(
            "Across A street\nYostreet 1 A UH lgh 11",
            $a->getDeliveryLocation()
        );

        $a->setThoroughfare('Very very very long street name');
        $this->assertEquals(
            "Very very very long street name\n1 A UH lgh 11",
            $a->getDeliveryLocation()
        );
    }


    function testGetCountry()
    {
        $a = $this->getAddress();
        
        $a->setCountryCode('xx');
        $this->assertEquals(
            '',
            $a->getCountry(),
            "xx is not a valid country code so country name should be empty"
        );

        $a->setCountryCode('se');
        $this->assertEquals(
            'Sweden',
            $a->getCountry()
        );
    }


    function testGetLocality()
    {
        $a = $this->getAddress();
        $this->assertEquals('', $a->getLocality());

        $a->setTown('xtown');
        $this->assertEquals('xtown', $a->getLocality());

        $a->setPostcode('12345');
        $this->assertEquals('12345 xtown', $a->getLocality());

        $a->setCountryCode('us');
        $this->assertEquals("US-12345 xtown\nUnited States", $a->getLocality());
        
        $a->setCountryOfOrigin('SE');
        $this->assertEquals("US-12345 xtown\nUnited States", $a->getLocality());

        $a->setCountryOfOrigin('US');
        $this->assertEquals("12345 xtown", $a->getLocality());
    }


    function testGetDeliveryPoint()
    {
        $a = $this->getAddress();
        $this->assertEquals('', $a->getDeliveryPoint());

        $a->setPostcode('12345');
        $a->setTown('xtown');
        $this->assertEquals('12345 xtown', $a->getDeliveryPoint());

        $a->setThoroughfare('Yostreet');
        $a->setPlot('1');
        $this->assertEquals("Yostreet 1\n12345 xtown", $a->getDeliveryPoint());

        $a->setDeliveryService('Box');
        $a->setAlternateDeliveryService('123');
        $this->assertEquals("Box 123\n12345 xtown", $a->getDeliveryPoint());
    }


    /*
    function testGetAddress()
    {
        $a = $this->getAddress();
        $this->assertEquals($a->getAddress(), '');

        $a->mailee = 'Foo Bar';
        $this->assertEquals($a->getAddress(), 'c/o Foo Bar');

        $a->thoroughfare = 'Yostreet';
        $a->plot = '1';
        $a->postcode = '12345';
        $a->town = 'xtown';
        $this->assertEquals($a->getAddress(), "c/o Foo Bar\nYostreet 1\n12345 xtown");

        $a->given_name = 'Hannes';
        $a->surname = 'Forsgård';
        $this->assertEquals($a->getAddress(), "Hannes Forsgård\nc/o Foo Bar\nYostreet 1\n12345 xtown");

        $a->organisational_unit = 'Unit';
        $a->organisation_name = 'Itbrigaden';
        $this->assertEquals($a->getAddress(), "Unit\nHannes Forsgård\nItbrigaden\nc/o Foo Bar\nYostreet 1\n12345 xtown");

        $a->setCountryCode('us');
        $this->assertEquals($a->getAddress(), "Hannes Forsgård\nItbrigaden\nc/o Foo Bar\nYostreet 1\nUS-12345 xtown\nUsa");
    }
    */

}