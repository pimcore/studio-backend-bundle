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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\FieldCollection\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\FieldCollectionsAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldCollectionDefinition;
use Pimcore\Model\Factory;
use Pimcore\Model\UserInterface;
use ReflectionMethod;

/**
 * @internal
 *
 * Regression test for PEES-1279: a field collection sub-field value that is
 * legitimately falsy (e.g. a numeric field's value/default of 0) must not be
 * silently dropped by FieldCollectionsAdapter::processCollectionRaw(), and a
 * key that is genuinely absent must be distinguished from one explicitly
 * submitted as null (which the setter-adapter contract allows to clear a
 * field on non-patch saves).
 */
final class FieldCollectionsAdapterTest extends Unit
{
    private const string COLLECTION_KEY = 'peesTestCollection';

    private const string ELEMENT_NAME = 'quantity';

    protected function _after(): void
    {
        RuntimeCache::getInstance()->offsetUnset('fieldcollection_' . self::COLLECTION_KEY);
    }

    /**
     * @throws Exception
     */
    public function testProcessCollectionRawKeepsZeroValue(): void
    {
        $result = $this->callProcessCollectionRaw([self::ELEMENT_NAME => 0], 0);

        $this->assertArrayHasKey(self::ELEMENT_NAME, $result);
        $this->assertSame(0, $result[self::ELEMENT_NAME]);
    }

    /**
     * @throws Exception
     */
    public function testProcessCollectionRawKeepsFalseValue(): void
    {
        $result = $this->callProcessCollectionRaw([self::ELEMENT_NAME => false], false);

        $this->assertArrayHasKey(self::ELEMENT_NAME, $result);
        $this->assertFalse($result[self::ELEMENT_NAME]);
    }

    /**
     * @throws Exception
     */
    public function testProcessCollectionRawKeepsEmptyStringValue(): void
    {
        $result = $this->callProcessCollectionRaw([self::ELEMENT_NAME => ''], '');

        $this->assertArrayHasKey(self::ELEMENT_NAME, $result);
        $this->assertSame('', $result[self::ELEMENT_NAME]);
    }

    /**
     * A key explicitly submitted as null must still reach the setter adapter
     * (which may use null to clear the field on a non-patch save) instead of
     * being conflated with a genuinely absent key.
     *
     * @throws Exception
     */
    public function testProcessCollectionRawPassesExplicitNullThrough(): void
    {
        $result = $this->callProcessCollectionRaw([self::ELEMENT_NAME => null], null);

        $this->assertArrayHasKey(self::ELEMENT_NAME, $result);
        $this->assertNull($result[self::ELEMENT_NAME]);
    }

    /**
     * A genuinely absent sub-field (no key in the submitted data at all) must
     * still be skipped, so the fix doesn't turn into "always include".
     *
     * @throws Exception
     */
    public function testProcessCollectionRawSkipsMissingValue(): void
    {
        $result = $this->callProcessCollectionRaw([]);

        $this->assertArrayNotHasKey(self::ELEMENT_NAME, $result);
    }

    /**
     * A collection item with no 'data' key at all must not be treated as
     * having every sub-field explicitly null.
     *
     * @throws Exception
     */
    public function testProcessCollectionRawSkipsWhenDataKeyMissing(): void
    {
        $result = $this->callProcessCollectionRaw(null);

        $this->assertArrayNotHasKey(self::ELEMENT_NAME, $result);
    }

    /**
     * @throws Exception
     */
    private function callProcessCollectionRaw(?array $blockElementData, mixed $adapterReturnValue = null): array
    {
        $subFieldDefinition = $this->makeEmpty(Data::class, [
            'getFieldType' => 'numeric',
        ]);

        $definition = new FieldCollectionDefinition();
        $definition->setKey(self::COLLECTION_KEY);
        $definition->addFieldDefinition(self::ELEMENT_NAME, $subFieldDefinition);

        RuntimeCache::set('fieldcollection_' . self::COLLECTION_KEY, $definition);

        $subAdapter = $this->makeEmpty(SetterDataInterface::class, [
            'getDataForSetter' => $adapterReturnValue,
        ]);

        $adapter = new FieldCollectionsAdapter(
            $this->makeEmpty(DataAdapterServiceInterface::class, [
                'tryDataAdapter' => $subAdapter,
            ]),
            $this->makeEmpty(DataServiceInterface::class),
            $this->makeEmpty(DefinitionResolverInterface::class),
            new Factory(),
        );

        $method = new ReflectionMethod(FieldCollectionsAdapter::class, 'processCollectionRaw');
        $method->setAccessible(true);

        $collectionRaw = ['type' => self::COLLECTION_KEY];
        if ($blockElementData !== null) {
            $collectionRaw['data'] = $blockElementData;
        }

        return $method->invoke(
            $adapter,
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(UserInterface::class),
            $this->makeEmpty(Data::class),
            $collectionRaw,
            false,
            null,
        );
    }
}
