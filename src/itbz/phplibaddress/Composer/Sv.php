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


/**
 * Model postal addresses as of Swedish standard SS 613401:2011 ed. 3
 * 
 * @package phplibaddress\Composer
 */
class Sv extends AbstractComposer
{

    /**
     * {@inheritdoc}
     */
    public function getAddressee()
    {
        // Add legal status to name of organisation if possible
        $org = mb_substr($this->_address->getOrganisationName(), 0, 36);
        if (mb_strlen("$org {$this->_address->getLegalStatus()}") <= 36) {
            $org = trim("$org {$this->_address->getLegalStatus()}");
        }

        // Construct addressee
        $lines = array(
            mb_substr($this->_address->getOrganisationalUnit(), 0, 36),
            self::concatNames(
                $this->_address->getGivenName(),
                $this->_address->getSurname(),
                $this->_address->getForm()
            ),
            $org
        );

        return implode(self::LINE_SEPARATOR, array_filter($lines));
    }


    /**
     * {@inheritdoc}
     */
    public function getMailee()
    {
        if ($this->_address->getNameOfMailee() == '') {
            
            return '';
        }

        return trim(
            sprintf(
                "%s %s",
                $this->_address->getMaileeRoleDescriptor(),
                $this->_address->getNameOfMailee()
            )
        );
    }


    /**
     * {@inheritdoc}
     */
    public function getLocality()
    {
        if ($this->_address->isDomestic()) {
            
            return trim(
                sprintf(
                    "%s %s",
                    $this->_address->getPostcode(),
                    $this->_address->getTown()
                )
            );
        } else {
            
            return trim(
                sprintf(
                    "%s-%s %s%s%s",
                    $this->_address->getCountryCode(),
                    $this->_address->getPostcode(),
                    $this->_address->getTown(),
                    self::LINE_SEPARATOR,
                    $this->_address->getCountry()
                )
            );
        }
    }


    /**
     * {@inheritdoc}
     */
    public function getServicePoint()
    {
        if (!$this->_address->isServicePoint()) {

            return '';
        } else {

            return trim(
                sprintf(
                    "%s %s",
                    $this->_address->getDeliveryService(),
                    $this->_address->getAlternateDeliveryService()
                )
            );
        }
    }


    /**
     * {@inheritdoc}
     */
    public function getDeliveryLocation()
    {
        if (!$this->_address->isDeliveryLocation()) {

            return '';
        }

        $parts = array(
            $this->_address->getThoroughfare(),
            $this->_address->getPlot(),
            $this->_address->getLittera(),
            $this->_address->getStairwell()
        );

        if ($this->_address->getDoor() != '') {
            $parts[] = "lgh {$this->_address->getDoor()}";
        } else {
            $parts[] = $this->_address->getFloor();
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
            if ($this->_address->getSupplementaryData() != '') {
                array_unshift($lines, $this->_address->getSupplementaryData());
            }
        }

        return trim(implode(self::LINE_SEPARATOR, $lines));
    }


    /**
     * {@inheritdoc}
     */
    public function getDeliveryPoint()
    {
        if ($this->_address->isServicePoint()) {
            $point = $this->getServicePoint();
        } else {
            $point = $this->getDeliveryLocation();
        }

        return trim(
            sprintf(
                "%s%s%s",
                $point,
                self::LINE_SEPARATOR,
                $this->getLocality()
            )
        );
    }


    /**
     * {@inheritdoc}
     */
    public function format()
    {
        $addr = array(
            $this->getAddressee(),
            $this->getMailee(),
            $this->getDeliveryPoint()
        );

        return implode(self::LINE_SEPARATOR, array_filter($addr));
    }

}