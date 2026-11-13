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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\EncryptedFieldAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField as EncryptedFieldDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\EncryptedField;
use function get_debug_type;
use function is_scalar;

/**
 * @internal
 */
final class EncryptedFieldAdapterTest extends Unit
{
    private const string FIELD = 'secret';

    /**
     * @throws Exception
     */
    public function testAStoreKeyExportsTheSameStringAsAPlainField(): void
    {
        $value = new EncryptedField(new Input(), 'plain text');
        $store = $this->makeEmpty(Classificationstore::class, [
            'getLocalizedKeyValue' => static fn (int $group, int $key, string $language) => $group === 3
                && $key === 7 && $language === 'en' ? $value : null,
        ]);

        $plainSeen = [];
        $plain = $this->export(
            $this->makeEmpty(Concrete::class, ['get' => $value]),
            new FieldContextData(legacyParameters: ['language' => null]),
            $plainSeen
        );
        $storeSeen = [];
        $fromStore = $this->export(
            $this->makeEmpty(Concrete::class, ['get' => $store]),
            new FieldContextData(legacyParameters: ['context' => $this->address()]),
            $storeSeen
        );

        $this->assertSame('exported plain text', $plain);
        $this->assertSame($plain, $fromStore);
        $this->assertSame(['input', self::FIELD, 'plain text'], $plainSeen);
        $this->assertSame($plainSeen, $storeSeen);
    }

    /**
     * @throws Exception
     */
    public function testAnInjectedValueWinsOverTheStoreAddress(): void
    {
        $store = $this->makeEmpty(Classificationstore::class, [
            'getLocalizedKeyValue' => new EncryptedField(new Input(), 'default slot'),
        ]);

        $seen = [];
        $export = $this->export(
            $this->makeEmpty(Concrete::class, ['get' => $store]),
            new FieldContextData(legacyParameters: [
                'context' => $this->address(),
                'injectedData' => new EncryptedField(new Input(), 'variant'),
            ]),
            $seen
        );

        $this->assertSame('exported variant', $export);
        $this->assertSame(['input', self::FIELD, 'variant'], $seen);
    }

    /**
     * @throws Exception
     */
    public function testWithoutAnAddressThePlainFieldIsRead(): void
    {
        $seen = [];
        $object = $this->makeEmpty(Concrete::class, ['get' => new EncryptedField(new Input(), 'plain text')]);

        $this->assertSame('exported plain text', $this->export($object, null, $seen));
        $this->assertSame(
            'exported plain text',
            $this->export($object, new FieldContextData(legacyParameters: ['x' => 1]), $seen)
        );
        $this->assertSame('', $this->export($this->makeEmpty(Concrete::class, ['get' => null]), null, $seen));
    }

    /**
     * @throws Exception
     */
    public function testANonStringPlainValueIsInjectedNotPassedAsTheKey(): void
    {
        $seen = [];
        $object = $this->makeEmpty(Concrete::class, ['get' => new EncryptedField(new Input(), 12.5)]);

        $this->assertSame('exported 12.5', $this->export($object, null, $seen));
        $this->assertSame(['input', self::FIELD, 12.5], $seen);
    }

    /**
     * @throws Exception
     */
    public function testADelegateLessDefinitionExportsEmpty(): void
    {
        $adapter = new EncryptedFieldAdapter(
            $this->makeEmpty(DataAdapterServiceInterface::class),
            $this->makeEmpty(DataServiceInterface::class)
        );
        $object = $this->makeEmpty(Concrete::class, ['get' => new EncryptedField(new Input(), 'plain text')]);

        $this->assertSame('', $adapter->getExportData($object, new EncryptedFieldDefinition(), self::FIELD));
    }

    /**
     * @throws Exception
     */
    private function export(Concrete $object, ?FieldContextData $contextData, array &$seen): string
    {
        $dataService = $this->makeEmpty(DataServiceInterface::class, [
            'getExportFieldValue' => static function (
                Concrete $object,
                Data $fieldDefinition,
                string $key,
                ?FieldContextData $contextData = null
            ) use (&$seen): string {
                $injected = $contextData?->getLegacyParameters()['injectedData'] ?? null;
                $seen = [$fieldDefinition->getFieldType(), $key, $injected];

                return 'exported ' . (is_scalar($injected) ? $injected : get_debug_type($injected));
            },
        ]);

        $definition = new EncryptedFieldDefinition();
        $definition->setDelegate(new Input());

        return (new EncryptedFieldAdapter(
            $this->makeEmpty(DataAdapterServiceInterface::class),
            $dataService
        ))->getExportData($object, $definition, self::FIELD, $contextData);
    }

    private function address(): array
    {
        return [
            'containerType' => 'classificationstore',
            'fieldname' => 'store',
            'groupId' => 3,
            'keyId' => 7,
            'language' => 'en',
        ];
    }
}
