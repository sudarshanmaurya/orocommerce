<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Doctrine\Common\Persistence\Proxy;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductProxyStub extends Product implements Proxy
{
    protected $initialized = false;

    /**
     * @param $initialized
     */
    public function setInitialized($initialized)
    {
        $this->initialized = $initialized;
    }

    // @codingStandardsIgnoreStart
    /**
     * {@inheritDoc}
     */
    public function __load()
    {
        $this->initialized = true;
    }

    /**
     * {@inheritDoc}
     */
    public function __isInitialized()
    {
        return $this->initialized;
    }
    // @codingStandardsIgnoreEnd
}
