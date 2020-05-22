<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\VisibilityBundle\EventListener\CategoryTreeHandlerListener;
use Oro\Bundle\VisibilityBundle\Visibility\Provider\CategoryVisibilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CategoryTreeHandlerListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CategoryVisibilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryVisibilityProvider;

    /** @var CategoryTreeHandlerListener */
    private $listener;

    /** @var array */
    private static $categories = [
        ['id' => 1, 'parent' => null],
        ['id' => 2, 'parent' => 1],
        ['id' => 3, 'parent' => 1],
        ['id' => 4, 'parent' => 2],
        ['id' => 5, 'parent' => 2],
        ['id' => 6, 'parent' => 4],
        ['id' => 7, 'parent' => 4],
        ['id' => 8, 'parent' => 5],
        ['id' => 9, 'parent' => 5],
        ['id' => 10, 'parent' => 3],
        ['id' => 11, 'parent' => 3],
        ['id' => 12, 'parent' => 10],
        ['id' => 13, 'parent' => 10],
        ['id' => 14, 'parent' => 11],
        ['id' => 15, 'parent' => 11],
    ];

    protected function setUp(): void
    {
        $this->categoryVisibilityProvider = $this->createMock(CategoryVisibilityProvider::class);

        $this->listener = new CategoryTreeHandlerListener($this->categoryVisibilityProvider);
    }

    /**
     * @dataProvider onCreateAfterDataProvider
     *
     * @param array $categories
     * @param array $expected
     * @param array $hiddenCategoryIds
     * @param UserInterface|null $user
     */
    public function testOnCreateAfter(
        array $categories,
        array $expected,
        array $hiddenCategoryIds,
        UserInterface $user = null
    ) {
        $categories = $this->prepareCategories($categories);
        $expected = $this->prepareCategories($expected);
        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);

        if ($user instanceof User) {
            $this->categoryVisibilityProvider->expects($this->never())
                ->method('getHiddenCategoryIds');
        } else {
            $this->categoryVisibilityProvider->expects($this->once())
                ->method('getHiddenCategoryIds')
                ->with($this->identicalTo($user))
                ->willReturn($hiddenCategoryIds);
        }

        $this->listener->onCreateAfter($event);
        $actual = $event->getCategories();
        $this->assertEquals(count($expected), count($actual));

        foreach ($actual as $id => $category) {
            $this->assertEquals($expected[$id]->getId(), $category->getId());
        }
    }

    /**
     * @return array
     */
    public function onCreateAfterDataProvider()
    {
        return [
            'tree for backend user' => [
                'categories' => self::$categories,
                'expected' => self::$categories,
                'hiddenCategoryIds' => [],
                'user' => new User()
            ],
            'tree for anonymous user with visible ids' => [
                'categories' => self::$categories,
                'expected' => [
                    ['id' => 1, 'parent' => null],
                    ['id' => 2, 'parent' => 1],
                ],
                'hiddenCategoryIds' => [3, 4, 5, 8, 9, 10, 11],
                'user' => null
            ],
            'tree for customer user with invisible ids' => [
                'categories' => self::$categories,
                'expected' => [
                    ['id' => 1, 'parent' => null],
                    ['id' => 2, 'parent' => 1],
                    ['id' => 4, 'parent' => 2],
                    ['id' => 5, 'parent' => 2],
                    ['id' => 6, 'parent' => 4],
                    ['id' => 7, 'parent' => 4],
                    ['id' => 8, 'parent' => 5],
                    ['id' => 9, 'parent' => 5],
                ],
                'hiddenCategoryIds' => [3],
                'user' => new CustomerUser()
            ]
        ];
    }

    /**
     * @param array $categories
     * @return Category[]
     */
    private function prepareCategories(array $categories)
    {
        /** @var Category[] $categoriesCollection */
        $categoriesCollection = [];
        foreach ($categories as $item) {
            /** @var Category $category */
            $category = $this->getEntity(Category::class, ['id' => $item['id']]);

            $category->setParentCategory($this->getParent($item['parent'], $categoriesCollection));
            $categoriesCollection[$category->getId()] = $category;
        }
        foreach ($categoriesCollection as $parentCategory) {
            foreach ($categoriesCollection as $category) {
                if ($category->getParentCategory() === $parentCategory) {
                    $parentCategory->addChildCategory($category);
                }
            }
        }

        return $categoriesCollection;
    }

    /**
     * @param int $id
     * @param Category[] $categoriesCollection
     * @return null
     */
    private function getParent($id, $categoriesCollection)
    {
        $parent = null;
        foreach ($categoriesCollection as $category) {
            if ($category->getId() === $id) {
                $parent = $category;
            }
        }

        return $parent;
    }
}
