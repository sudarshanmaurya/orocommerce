<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional;

use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

class ShoppingListFrontendActionsTest extends FrontendActionTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                LoadShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
            ]
        );
    }

    public function testCreateRequest()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $this->assertFalse($shoppingList->getLineItems()->isEmpty());

        $this->executeOperation($shoppingList, 'oro_shoppinglist_frontend_request_quote');

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('redirectUrl', $data);
        $this->assertTrue($data['success']);

        $crawler = $this->client->request('GET', $data['redirectUrl']);

        $lineItems = $crawler->filter('.request-form-editline__product');
        $this->assertNotEmpty($lineItems);
        $content = $lineItems->html();
        foreach ($shoppingList->getLineItems() as $lineItem) {
            static::assertStringContainsString($lineItem->getProduct()->getSku(), $content);
        }
    }

    /**
     * @param ShoppingList $shoppingList
     * @param string $operationName
     */
    protected function executeOperation(ShoppingList $shoppingList, $operationName)
    {
        $this->assertExecuteOperation(
            $operationName,
            $shoppingList->getId(),
            'Oro\Bundle\ShoppingListBundle\Entity\ShoppingList',
            ['route' => 'oro_shopping_list_frontend_view']
        );
    }
}
