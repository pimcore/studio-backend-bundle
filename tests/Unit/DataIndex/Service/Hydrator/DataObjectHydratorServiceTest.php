<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Service\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResultItem;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\DataObjectHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\Hydrator\DataObjectHydratorService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final class DataObjectHydratorServiceTest extends Unit
{
    public function testDefaultHydratorCopiesScore(): void
    {
        $result = $this->createService(false)->hydrateDataObjects($this->createItem(0.63));

        $this->assertSame(0.63, $result->getScore());
    }

    public function testDefaultHydratorKeepsNullScore(): void
    {
        $result = $this->createService(false)->hydrateDataObjects($this->createItem(null));

        $this->assertNull($result->getScore());
    }

    public function testLocatedHydratorCopiesScore(): void
    {
        $result = $this->createService(true)->hydrateDataObjects($this->createItem(0.42));

        $this->assertSame(0.42, $result->getScore());
    }

    public function testLocatedHydratorKeepsNullScore(): void
    {
        $result = $this->createService(true)->hydrateDataObjects($this->createItem(null));

        $this->assertNull($result->getScore());
    }

    private function createItem(?float $score): DataObjectSearchResultItem
    {
        return (new DataObjectSearchResultItem())->setScore($score);
    }

    private function createService(bool $located): DataObjectHydratorService
    {
        $hydrator = $this->makeEmpty(DataObjectHydratorInterface::class, [
            'hydrate' => fn (): DataObject => $this->createDto(),
        ]);

        $locator = $this->makeEmpty(ServiceProviderInterface::class, [
            'has' => $located,
            'get' => $hydrator,
        ]);

        $default = $located ? $this->makeEmpty(DataObjectHydratorInterface::class) : $hydrator;

        return new DataObjectHydratorService($default, $locator);
    }

    private function createDto(): DataObject
    {
        return $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
    }
}
