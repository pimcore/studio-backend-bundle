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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Data\Adapter;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\ManyToOneRelationAdapter;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ManyToOneRelationAdapterTest extends Unit
{
    /**
     * GHSA-pg5f-74vr-w237: the field can be pointed at any element id, so nothing but the id and type
     * the user supplied is returned for a target the user may not view.
     *
     * @throws Exception
     */
    public function testNormalizeWithholdsTargetDataWithoutViewAccess(): void
    {
        $data = $this->createAdapter(hasViewAccess: false)->normalize($this->makeTarget(), $this->makeField());

        $this->assertSame('', $data['fullPath']);
        $this->assertSame('', $data['subtype']);
        $this->assertNull($data['isPublished']);
        $this->assertFalse($data['hasViewAccess']);
        $this->assertSame(['type' => 'object', 'id' => 7], [
            'type' => $data['type'],
            'id' => $data['id'],
        ]);
    }

    /**
     * @throws Exception
     */
    public function testNormalizeReturnsTargetDataWithViewAccess(): void
    {
        $data = $this->createAdapter(hasViewAccess: true)->normalize($this->makeTarget(), $this->makeField());

        $this->assertSame('/secret/board-report', $data['fullPath']);
        $this->assertSame('object', $data['subtype']);
        $this->assertTrue($data['isPublished']);
        $this->assertTrue($data['hasViewAccess']);
    }

    /**
     * The withheld data is not needed to save the field again: the target is looked up by the id and
     * type that are still returned, so a relation the user may not view survives a save.
     *
     * @throws Exception
     */
    public function testWithheldTargetIsKeptWhenTheFieldIsSavedAgain(): void
    {
        $target = $this->makeTarget();
        $lookups = [];
        $adapter = $this->createAdapter(
            hasViewAccess: false,
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => static function (string $type, int $id) use (&$lookups, $target): Concrete {
                    $lookups[] = [$type, $id];

                    return $target;
                },
            ])
        );
        $field = $this->makeField();

        $setterValue = $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class),
            $field,
            'relation',
            ['relation' => $adapter->normalize($target, $field)],
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertSame($target, $setterValue);
        $this->assertSame([['object', 7]], $lookups);
    }

    /**
     * @throws Exception
     */
    private function createAdapter(
        bool $hasViewAccess,
        ?ServiceResolverInterface $serviceResolver = null
    ): ManyToOneRelationAdapter {
        return new ManyToOneRelationAdapter(
            $serviceResolver ?? $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(ElementDataServiceInterface::class, [
                'getRelatedElementData' => new RelatedElementData(
                    7,
                    'object',
                    'BoardReport',
                    '/secret/board-report',
                    true,
                    $hasViewAccess
                ),
            ])
        );
    }

    /**
     * @throws Exception
     */
    private function makeTarget(): ElementInterface
    {
        return $this->makeEmpty(Concrete::class, [
            'getId' => 7,
            'isPublished' => true,
            'getRealFullPath' => '/secret/board-report',
            'getType' => 'object',
        ]);
    }

    /**
     * @throws Exception
     */
    private function makeField(): ManyToOneRelation
    {
        return $this->makeEmpty(ManyToOneRelation::class, [
            'normalize' => ['type' => 'object', 'id' => 7],
        ]);
    }
}
