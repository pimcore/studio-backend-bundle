<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Dependency;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Hydrator\DependencyHydrator;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Hydrator\DependencyHydratorInterface;
use Pimcore\Model\Document;

final class DependencyHydratorTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testHydrate(): void
    {
        $hydrator = $this->getHydrator();

        $dependency = $hydrator->hydrate(['id' => 1, 'type' => 'document']);

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
        return new DependencyHydrator($this->mockServiceResolver());
    }

    /**
     * @throws Exception
     */
    private function mockServiceResolver(): ServiceResolverInterface
    {
        return $this->makeEmpty(ServiceResolverInterface::class,
            [
                'getElementById' => $this->getDocument(),
                'getElementType' => 'document',
                'isPublished' => true,
            ]);
    }

    private function getDocument(): Document
    {
        $document = new Document();
        $document->setPath('/test');
        $document->setId(1);
        $document->setType('page');
        $document->setKey('test');

        return $document;
    }
}
