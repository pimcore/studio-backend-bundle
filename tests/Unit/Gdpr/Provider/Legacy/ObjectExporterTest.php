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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Gdpr\Provider\Legacy;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Exporter\ObjectExporter;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final class ObjectExporterTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testDoExportObjectNormalizesFieldsViaDataService(): void
    {
        $fieldDefinition = $this->makeEmpty(Input::class, [
            'getName' => 'id',
        ]);

        $classDefinition = new ClassDefinition();
        $classDefinition->addFieldDefinition('id', $fieldDefinition);

        $object = $this->makeEmpty(Concrete::class, [
            'getClass' => $classDefinition,
            'getId' => 263,
        ]);

        $capturedValue = null;
        $dataService = $this->makeEmpty(DataServiceInterface::class, [
            'getNormalizedValue' => function (mixed $value) use (&$capturedValue) {
                $capturedValue = $value;

                return ['raw' => $value];
            },
        ]);

        // Adapter results are flattened to plain arrays via the normalizer before being exported.
        $normalizer = $this->makeEmpty(NormalizerInterface::class, [
            'normalize' => 'normalized-value',
        ]);

        $capturedInheritFlag = null;
        $resolver = $this->makeEmpty(DataObjectServiceResolverInterface::class, [
            'useInheritedValues' => Expected::once(
                function (bool $inheritValues, callable $fn) use (&$capturedInheritFlag) {
                    $capturedInheritFlag = $inheritValues;

                    return $fn();
                }
            ),
        ]);

        $result = [];
        (new ObjectExporter($resolver, $dataService, $normalizer))->doExportObject($object, $result);

        $this->assertTrue($capturedInheritFlag);
        $this->assertSame(263, $capturedValue);
        $this->assertSame(['id' => 'normalized-value'], $result);
    }

    /**
     * @throws Exception
     */
    public function testDoExportObjectHasNoFields(): void
    {
        $object = $this->makeEmpty(Concrete::class, [
            'getClass' => new ClassDefinition(),
        ]);

        $resolver = $this->makeEmpty(DataObjectServiceResolverInterface::class, [
            'useInheritedValues' => Expected::once(
                static fn (bool $inheritValues, callable $fn) => $fn()
            ),
        ]);

        $exporter = new ObjectExporter(
            $resolver,
            $this->makeEmpty(DataServiceInterface::class),
            $this->makeEmpty(NormalizerInterface::class),
        );

        $result = [];
        $exporter->doExportObject($object, $result);

        $this->assertSame([], $result);
    }
}
