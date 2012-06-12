<?php
namespace itbz\STB\Communication;
use PDO;
use itbz\Cache\VoidCacher;


// Using the mreg autoloader for now...
require_once __DIR__ . "/../../../../../libs/autoload.php";


class AddressTest extends \PHPUnit_Framework_TestCase
{

    function getPdo()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query('CREATE TABLE lookup__Iso3166(country_code, country_code_alpha3, country_code_num, country_name_se, country_name_en, cc, PRIMARY KEY(country_code ASC));');
        $pdo->query("INSERT INTO lookup__Iso3166 VALUES ('CA', 'CAN', '124', 'Kanada', 'Canada', '1')");
        $pdo->query("INSERT INTO lookup__Iso3166 VALUES ('US', 'USA', '840', 'Usa', 'United States', '1')");
        $pdo->query("INSERT INTO lookup__Iso3166 VALUES ('SE', 'SWE', '752', 'Sverige', 'Sweden', '46')");
        return  $pdo;
    }

    
    function getAddress()
    {
        $pdo = $this->getPdo();
        $country = new Country($pdo, new VoidCacher());
        $addr = new Address($country);
        return $addr;
    }


    function testValidateSanitize()
    {
        $a = $this->getAddress();
        $a->thoroughfare = 'Very very very long street name 12345';
        $loc = $a->getDeliveryLocation();
        $this->assertEquals($loc, "Very very very long street name 12345");
        $this->assertFalse($a::validate($loc));
        $loc = $a::sanitize($loc);
        $this->assertTrue($a::validate($loc));
    }


    function testGetAddressee()
    {
        $a = $this->getAddress();
        $this->assertEquals($a->getAddressee(), '');

        $a->form = 'Mr';
        $a->given_name = 'Karl Hannes Gustav';
        $a->surname = 'Forsgård';
        $this->assertEquals($a->getAddressee(), 'Mr Karl Hannes Gustav Forsgård');

        $a->surname = 'Forsgård Eriksson';
        $this->assertEquals($a->getAddressee(), 'Mr Karl H G Forsgård Eriksson');

        $a->surname = 'Forsgård Eriksson Eriksson';
        $this->assertEquals($a->getAddressee(), 'Mr Karl Forsgård Eriksson Eriksson');

        $a->surname = 'Forsgård Eriksson Eriksson Eriksson';
        $this->assertEquals($a->getAddressee(), 'Forsgård Eriksson Eriksson Eriksson');

        $a->surname = 'Forsgård Eriksson Eriksson Eriksson Eriksson';
        $this->assertEquals($a->getAddressee(), 'Eriksson Eriksson Eriksson Eriksson');

        $a->surname = 'Forsgård ErikssonErikssonErikssonErikssonEriksson';
        $this->assertEquals($a->getAddressee(), 'ErikssonErikssonErikssonErikssonErik');

        $a->organisational_unit = 'Unit';
        $a->surname = 'Forsgård';
        $this->assertEquals($a->getAddressee(), "Unit\nMr Karl Hannes Gustav Forsgård");

        $a->organisation_name = 'Itbrigaden';
        $this->assertEquals($a->getAddressee(), "Unit\nMr Karl Hannes Gustav Forsgård\nItbrigaden");

        $a->legal_status = 'AB';
        $this->assertEquals($a->getAddressee(), "Unit\nMr Karl Hannes Gustav Forsgård\nItbrigaden AB");

        $a->organisation_name = 'Itbrigaden 1234567890123456789012345';
        $this->assertEquals($a->getAddressee(), "Unit\nMr Karl Hannes Gustav Forsgård\nItbrigaden 1234567890123456789012345");
    }


    function testGetMailee()
    {
        $a = $this->getAddress();
        $this->assertEquals($a->getMailee(), '');

        $a->mailee = 'Foo Bar';
        $this->assertEquals($a->getMailee(), 'c/o Foo Bar');

        $a->mailee_role_descriptor = 'bar';
        $this->assertEquals($a->getMailee(), 'bar Foo Bar');
    }

    
    function testGetServicePoint()
    {
        $a = $this->getAddress();
        $this->assertEquals($a->getServicePoint(), '');

        $a->delivery_service = 'Poste restante';
        $this->assertEquals($a->getServicePoint(), 'Poste restante');

        $a->delivery_service = 'Box';
        $a->alternate_delivery_service = '123';
        $this->assertEquals($a->getServicePoint(), 'Box 123');
    }


    function testGetDeliveryLocation()
    {
        $a = $this->getAddress();
        $this->assertEquals($a->getDeliveryLocation(), '');

        $a->thoroughfare = 'Yostreet';
        $this->assertEquals($a->getDeliveryLocation(), 'Yostreet');

        $a->plot = '1';
        $this->assertEquals($a->getDeliveryLocation(), 'Yostreet 1');

        $a->littera = 'A';
        $this->assertEquals($a->getDeliveryLocation(), 'Yostreet 1 A');
        
        $a->stairwell = 'UH';
        $this->assertEquals($a->getDeliveryLocation(), 'Yostreet 1 A UH');

        $a->floor = '2tr';
        $this->assertEquals($a->getDeliveryLocation(), 'Yostreet 1 A UH 2tr');

        $a->door = '11';
        $this->assertEquals($a->getDeliveryLocation(), 'Yostreet 1 A UH lgh 11');

        $a->supplementary_delivery_point_data = 'Across A street';
        $this->assertEquals($a->getDeliveryLocation(), "Across A street\nYostreet 1 A UH lgh 11");

        $a->thoroughfare = 'Very very very long street name';
        $this->assertEquals($a->getDeliveryLocation(), "Very very very long street name\n1 A UH lgh 11");
    }


    function testGetLocality()
    {
        $a = $this->getAddress();
        $this->assertEquals($a->getLocality(), '');

        $a->town = 'xtown';
        $this->assertEquals($a->getLocality(), 'xtown');

        $a->postcode = '12345';
        $this->assertEquals($a->getLocality(), '12345 xtown');

        $a->country_code = 'us';
        $this->assertEquals($a->getLocality(), "US-12345 xtown\nUsa");
        $this->assertEquals($a->getLocality('se'), "US-12345 xtown\nUsa");
        $this->assertEquals($a->getLocality('SE'), "US-12345 xtown\nUsa");
        $this->assertEquals($a->getLocality('en'), "US-12345 xtown\nUnited States");
        $this->assertEquals($a->getLocality('fr'), "US-12345 xtown\nUnited States");
        $this->assertEquals($a->getLocality('us'), "12345 xtown");

        $a->country_code = 'xx';
        $this->assertEquals($a->getLocality(), "XX-12345 xtown");
    }


    function testGetDeliveryPoint()
    {
        $a = $this->getAddress();
        $this->assertEquals($a->getDeliveryPoint(), '');

        $a->postcode = '12345';
        $a->town = 'xtown';
        $this->assertEquals($a->getDeliveryPoint(), '12345 xtown');

        $a->thoroughfare = 'Yostreet';
        $a->plot = '1';
        $this->assertEquals($a->getDeliveryPoint(), "Yostreet 1\n12345 xtown");

        $a->delivery_service = 'Box';
        $a->alternate_delivery_service = '123';
        $this->assertEquals($a->getDeliveryPoint(), "Box 123\n12345 xtown");
    }


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

        $a->country_code = 'us';
        $this->assertEquals($a->getAddress(), "Hannes Forsgård\nItbrigaden\nc/o Foo Bar\nYostreet 1\nUS-12345 xtown\nUsa");
    }

}
