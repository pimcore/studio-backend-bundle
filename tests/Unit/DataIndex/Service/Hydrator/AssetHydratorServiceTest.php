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
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetSearchResultItem;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\AssetHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\Hydrator\AssetHydratorService;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final class AssetHydratorServiceTest extends Unit
{
    public function testDefaultHydratorCopiesScore(): void
    {
        $result = $this->createService(false)->hydrateAssets($this->createItem(0.63));

        $this->assertSame(0.63, $result->getScore());
    }

    public function testDefaultHydratorKeepsNullScore(): void
    {
        $result = $this->createService(false)->hydrateAssets($this->createItem(null));

        $this->assertNull($result->getScore());
    }

    public function testLocatedHydratorCopiesScore(): void
    {
        $result = $this->createService(true)->hydrateAssets($this->createItem(0.42));

        $this->assertSame(0.42, $result->getScore());
    }

    public function testLocatedHydratorKeepsNullScore(): void
    {
        $result = $this->createService(true)->hydrateAssets($this->createItem(null));

        $this->assertNull($result->getScore());
    }

    private function createItem(?float $score): AssetSearchResultItem
    {
        return (new AssetSearchResultItem())->setScore($score);
    }

    private function createService(bool $located): AssetHydratorService
    {
        $hydrator = $this->makeEmpty(AssetHydratorInterface::class, [
            'hydrate' => fn (): Asset => $this->createDto(),
        ]);

        $locator = $this->makeEmpty(ServiceProviderInterface::class, [
            'has' => $located,
            'get' => $hydrator,
        ]);

        $default = $located ? $this->makeEmpty(AssetHydratorInterface::class) : $hydrator;

        return new AssetHydratorService($default, $locator);
    }

    private function createDto(): Asset
    {
        return $this->getMockBuilder(Asset::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
    }
}
