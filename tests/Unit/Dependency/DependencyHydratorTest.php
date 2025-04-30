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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Dependency;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\ElementType;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Hydrator\DependencyHydrator;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Hydrator\DependencyHydratorInterface;

final class DependencyHydratorTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testHydrate(): void
    {
        $hydrator = $this->getHydrator();

        $dependency = $hydrator->hydrate($this->mockElementSearchResultItemInterface());

        $this->assertSame(1, $dependency->getId());
        $this->assertSame('/testtest', $dependency->getPath());
        $this->assertSame('document', $dependency->getType());
        $this->assertSame('page', $dependency->getSubType());
        $this->assertTrue($dependency->isPublished());
        $this->assertEmpty($dependency->getAdditionalAttributes());
    }

    /**
     * @throws Exception
     */
    private function getHydrator(): DependencyHydratorInterface
    {
        return new DependencyHydrator();
    }

    /**
     * @throws Exception
     */
    private function mockElementSearchResultItemInterface(): ElementSearchResultItemInterface
    {
        return $this->makeEmpty(
            ElementSearchResultItemInterface::class,
            [
                'getId' => 1,
                'getFullPath' => '/testtest',
                'getType' => 'page',
                'getElementType' => ElementType::DOCUMENT,
                'isPublished' => true,
            ]
        );
    }
}
