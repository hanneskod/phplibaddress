phplibaddress
=============

Render addresses according to national addressing standards

Separates the broken down address data from composers, witch render
render the address according to varying standards. (At present only
the swedish addressing standard SS 613401:2011 ed. 3 is supported.)


Installation
------------

Install using composer to automatically satisfy dependencies and
using the composer auto-loader.


Usage
-----

    namespace iio\phplibaddress;

    use iio\localefacade\LocaleFacade;
    use iio\phplibaddress\Composer\Sv;
    use iio\phplibaddress\Composer\Breviator;

    $addr = new Address(new LocaleFacade('en'));

    $addr->setGivenName('Many many names');
    $addr->setSurname('Surnameone Surnametwo');
    $addr->setForm('Mr');
    $addr->setThoroughfare('streetname');
    $addr->setPlot('1');
    $addr->setPostcode('222 22');
    $addr->setTown('city');
    $addr->setCountryCode('se');
    $addr->setCountryOfOrigin('en');

    // Se the entire documentation for all options

    $composer = new Sv(new Breviator);
    $composer->setAddress($addr);

    echo $composer->getValid();

    /*

    Mr Many M N Surnameone Surnametwo
    streetname 1
    SE-222 22 city
    Sweden
     
    */
