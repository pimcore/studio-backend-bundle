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

use PHPUnit\Framework\TestCase;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\ElementType;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Hydrator\DependencyHydrator;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Hydrator\DependencyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Schema\Dependency;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[CoversClass(DependencyHydrator::class)]
#[UsesClass(Dependency::class)]
#[UsesClass(AdditionalAttributesTrait::class)]
/**
 * @internal
 */
final class DependencyHydratorTest extends TestCase
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
        $mock = $this->createMock(ElementSearchResultItemInterface::class);
        $mock->method('getId')->willReturn(1);
        $mock->method('getFullPath')->willReturn('/testtest');
        $mock->method('getType')->willReturn('page');
        $mock->method('getElementType')->willReturn(ElementType::DOCUMENT);
        
        return $mock;
    }
}
