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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\MultiValueAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue as MultiValueDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

/**
 * The wrapper hands one value of its inner type over to that type, and never answers null in the
 * place of a value: a `multiValue` key's values are written at core's slots one entry at a time, so
 * a null coming back out of here is stored AS the value of the slot the entry addressed.
 *
 * @internal
 */
final class MultiValueAdapterTest extends Unit
{
    private const string FIELD = 'height';

    /**
     * A `multiValue` definition carrying no inner type - which core builds for a blob that names
     * none rather than refusing the blob - holds the value as it stands.
     *
     * @throws Exception
     */
    public function testAKeyWithNoInnerTypeKeepsTheSuppliedValue(): void
    {
        $this->assertSame(
            '80',
            $this->write(new MultiValueDefinition(), $this->createAdapterService(null))
        );
    }

    /**
     * An inner type this bundle has no adapter for converts nothing of its own, so the supplied
     * value is the value - the same answer the unqualified path gives by skipping such a field.
     *
     * @throws Exception
     */
    public function testAnInnerTypeWithoutAnAdapterKeepsTheSuppliedValue(): void
    {
        $this->assertSame(
            '80',
            $this->write($this->createDefinition(), $this->createAdapterService(null))
        );
    }

    /**
     * An inner type with an adapter converts the value, and that is what is stored.
     *
     * @throws Exception
     */
    public function testAnInnerTypeWithAnAdapterConvertsTheValue(): void
    {
        $seen = [];
        $adapterService = $this->createAdapterService(
            $this->makeEmpty(SetterDataInterface::class, [
                'getDataForSetter' => static function (
                    Concrete $element,
                    Data $fieldDefinition,
                    string $key,
                    array $data
                ) use (&$seen): mixed {
                    $seen[] = $fieldDefinition->getFieldType();

                    return 'converted ' . $data[$key];
                },
            ])
        );

        $this->assertSame('converted 80', $this->write($this->createDefinition(), $adapterService));
        $this->assertSame(['input'], $seen, 'the inner type is what the value is converted by');
    }

    /**
     * The export of one value is formatted by the INNER type, so the grid cell - normalized by that
     * same type - and the export of the same slot agree. Without this the value falls through to the
     * wrapper's own CSV export, which core does not delegate.
     *
     * @throws Exception
     */
    public function testTheExportIsFormattedByTheInnerType(): void
    {
        $seen = [];
        $dataService = $this->makeEmpty(DataServiceInterface::class, [
            'getExportFieldValue' => static function (
                Concrete $object,
                Data $fieldDefinition,
                string $key,
                ?FieldContextData $contextData = null
            ) use (&$seen): string {
                $seen = [$fieldDefinition->getFieldType(), $key, $contextData?->getLegacyParameters() ?? []];

                return '80 kg';
            },
        ]);

        $export = (new MultiValueAdapter($this->createAdapterService(null), $dataService))->getExportData(
            $this->makeEmpty(Concrete::class),
            $this->createDefinition(),
            self::FIELD,
            new FieldContextData(legacyParameters: ['injectedData' => '80 kg'])
        );

        $this->assertSame('80 kg', $export);
        $this->assertSame(
            ['input', self::FIELD, ['injectedData' => '80 kg']],
            $seen,
            'the inner type is asked, and the value the caller injected travels to it untouched'
        );
    }

    /**
     * A definition that is not a `multiValue` one is not this adapter's, on either path.
     *
     * @throws Exception
     */
    public function testADefinitionOfAnotherTypeIsNotAnswered(): void
    {
        $adapter = new MultiValueAdapter(
            $this->createAdapterService(null),
            $this->makeEmpty(DataServiceInterface::class)
        );

        $this->assertNull($adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class),
            new Input(),
            self::FIELD,
            [self::FIELD => '80'],
            $this->makeEmpty(UserInterface::class)
        ));
        $this->assertSame('', $adapter->getExportData(
            $this->makeEmpty(Concrete::class),
            new Input(),
            self::FIELD
        ));
    }

    /**
     * @throws Exception
     */
    private function write(MultiValueDefinition $definition, DataAdapterServiceInterface $adapterService): mixed
    {
        return (new MultiValueAdapter(
            $adapterService,
            $this->makeEmpty(DataServiceInterface::class)
        ))->getDataForSetter(
            $this->makeEmpty(Concrete::class),
            $definition,
            self::FIELD,
            [self::FIELD => '80'],
            $this->makeEmpty(UserInterface::class)
        );
    }

    private function createDefinition(): MultiValueDefinition
    {
        $definition = new MultiValueDefinition();
        $definition->setDelegate(new Input());

        return $definition;
    }

    /**
     * @throws Exception
     */
    private function createAdapterService(mixed $adapter): DataAdapterServiceInterface
    {
        return $this->makeEmpty(DataAdapterServiceInterface::class, [
            'tryDataAdapter' => $adapter,
        ]);
    }
}
