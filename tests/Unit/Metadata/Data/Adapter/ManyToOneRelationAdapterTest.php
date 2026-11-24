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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Metadata\Data\Adapter;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\Adapter\ManyToOneRelationAdapter;
use Pimcore\Model\Document\Page;

/**
 * @internal
 */
final class ManyToOneRelationAdapterTest extends Unit
{
    /**
     * GHSA-pg5f-74vr-w237: a metadata relation can be pointed at any element id, so nothing but the
     * id and type is returned for a target the user may not view.
     *
     * @throws Exception
     */
    public function testNormalizeWithholdsTargetDataWithoutViewAccess(): void
    {
        $data = $this->normalize(hasViewAccess: false);

        $this->assertSame(6, $data['id']);
        $this->assertSame('document', $data['type']);
        $this->assertSame('', $data['fullPath']);
        $this->assertSame('', $data['subtype']);
        $this->assertNull($data['isPublished']);
        $this->assertFalse($data['hasViewAccess']);
    }

    /**
     * @throws Exception
     */
    public function testNormalizeReturnsTargetDataWithViewAccess(): void
    {
        $data = $this->normalize(hasViewAccess: true);

        $this->assertSame('/secret-docs/board', $data['fullPath']);
        $this->assertSame('page', $data['subtype']);
        $this->assertFalse($data['isPublished']);
        $this->assertTrue($data['hasViewAccess']);
    }

    /**
     * @throws Exception
     */
    public function testNormalizeIgnoresValuesThatAreNoElements(): void
    {
        $adapter = new ManyToOneRelationAdapter(
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(ElementDataServiceInterface::class)
        );

        $this->assertNull($adapter->normalize('not an element', 'document'));
    }

    /**
     * @throws Exception
     */
    private function normalize(bool $hasViewAccess): array
    {
        $document = $this->makeEmpty(Page::class, [
            'getId' => 6,
            'isPublished' => false,
            'getRealFullPath' => '/secret-docs/board',
            'getType' => 'page',
        ]);

        $adapter = new ManyToOneRelationAdapter(
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(ElementDataServiceInterface::class, [
                'getRelatedElementData' => new RelatedElementData(
                    6,
                    'document',
                    'page',
                    '/secret-docs/board',
                    false,
                    $hasViewAccess
                ),
            ])
        );

        return $adapter->normalize($document, 'document');
    }
}
