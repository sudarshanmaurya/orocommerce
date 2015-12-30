<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Stub;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

class AddressCountryAndRegionSubscriberStub extends AddressCountryAndRegionSubscriber
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [];
    }
}
