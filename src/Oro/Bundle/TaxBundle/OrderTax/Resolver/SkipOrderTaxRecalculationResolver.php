<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Resolver;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\OrderTax\Specification\OrderRequiredTaxRecalculationSpecification;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;
use Oro\Bundle\TaxBundle\Resolver\StopPropagationException;

/**
 * Resolver which should stop tax recalculation for Order and OrderLineItem entities which taxes
 * are already calculated. It should stop tax recalculation in case if entities has no changes which
 * could lead to the different result of tax calculation for them
 */
class SkipOrderTaxRecalculationResolver implements ResolverInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var TaxManager
     */
    protected $taxManager;

    /**
     * @param ManagerRegistry $doctrine
     * @param TaxManager      $taxManager
     */
    public function __construct(ManagerRegistry $doctrine, TaxManager $taxManager)
    {
        $this->doctrine = $doctrine;
        $this->taxManager = $taxManager;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Taxable $taxable)
    {
        if (!$taxable->getIdentifier() || !$taxable->getClassName()) {
            return;
        }

        /** @var EntityManager|null $entityManager */
        $entityManager = $this->doctrine->getManagerForClass($taxable->getClassName());
        if (!$entityManager) {
            return;
        }

        $uow = $entityManager->getUnitOfWork();
        $entity = $uow->tryGetById($taxable->getIdentifier(), $taxable->getClassName());
        if ($entity instanceof Order) {
            $this->resolveOrderTaxable($uow, $entity, $taxable);
        } elseif ($entity instanceof OrderLineItem) {
            $this->resolveOrderLineItemTaxable($uow, $entity);
        }
    }

    /**
     * @param UnitOfWork $uow
     * @param Order      $order
     * @param Taxable    $taxable
     *
     * @throws StopPropagationException
     * @throws \Oro\Bundle\TaxBundle\Exception\TaxationDisabledException
     */
    private function resolveOrderTaxable(UnitOfWork $uow, Order $order, Taxable $taxable)
    {
        $specification = new OrderRequiredTaxRecalculationSpecification($uow);
        if ($specification->isSatisfiedBy($order)) {
            return;
        }

        $taxResult = $taxable->getResult();
        /**
         * Tax items not always stored along with the order, so in some cases
         * we need to load them separately
         */
        if ($order->getLineItems() && !$taxResult->getItems()) {
            $itemsResult = [];
            foreach ($order->getLineItems() as $lineItem) {
                $itemsResult[] = $this->taxManager->loadTax($lineItem);
            }
            if ($itemsResult) {
                $taxResult->offsetSet(Result::ITEMS, $itemsResult);
            }
        }

        throw new StopPropagationException();
    }

    /**
     * @param UnitOfWork    $uow
     * @param OrderLineItem $orderLineItem
     * @throws StopPropagationException
     */
    private function resolveOrderLineItemTaxable(UnitOfWork $uow, OrderLineItem $orderLineItem)
    {
        $specification = new OrderRequiredTaxRecalculationSpecification($uow);
        if ($specification->isSatisfiedBy($orderLineItem->getOrder())) {
            return;
        }

        throw new StopPropagationException();
    }
}
