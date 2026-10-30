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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Collector\DataObject;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Collector\DataObject\ObjectBrickCollector;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ClassDefinitionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ColumnFieldDefinition;
use Pimcore\Bundle\StudioBackendBundle\ObjectBrick\Service\ObjectBrickServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectBrickDefinition;
use Pimcore\Model\User;
use Psr\Log\NullLogger;
use ReflectionMethod;

/**
 * @internal
 */
final class ObjectBrickCollectorTest extends Unit
{
    /**
     * Select fields backed by an options provider (e.g. "Select Options") only get their options
     * through layout enrichment. Without it the grid column is built with options: null and
     * dynamicOptions: false, so inline editing shows "No Data Available".
     *
     * @see https://github.com/pimcore/platform-version/issues/716
     */
    public function testBuildColumnConfigurationsUsesEnrichedBrickLayout(): void
    {
        $select = new Select();
        $select->setName('engineType');
        $select->setOptionsProviderType('select_options');

        $layout = new Panel();
        $layout->addChild($select);

        $objectBrick = new ObjectBrickDefinition();
        $objectBrick->setKey('Engine');
        $objectBrick->setLayoutDefinitions($layout);

        $user = new User();
        $folder = new Folder();
        $enrichedOptions = [['key' => 'Diesel', 'value' => 'diesel']];
        $capturedContext = null;
        $capturedUser = null;
        $capturedDefinition = null;

        $collector = new ObjectBrickCollector(
            $this->makeEmpty(ClassDefinitionServiceInterface::class),
            $this->makeEmpty(ColumnConfigurationServiceInterface::class, [
                'buildDataObjectAdapterColumnConfiguration' => static function (
                    ColumnFieldDefinition $definition
                ) use (&$capturedDefinition): ColumnConfiguration {
                    $capturedDefinition = $definition;

                    return new ColumnConfiguration(
                        'attributes.Engine.engineType',
                        [],
                        true,
                        true,
                        true,
                        true,
                        false,
                        null,
                        'dataobject.objectbrick',
                        'select',
                        [],
                    );
                },
            ]),
            $this->makeEmpty(ObjectBrickServiceInterface::class, [
                'getDataFields' => static fn (Panel $layout) => [
                    new ColumnFieldDefinition($layout->getChildren()[0], [], false),
                ],
            ]),
            $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'enrichLayoutDefinition' => static function (
                    mixed &$layout,
                    mixed $object = null,
                    array $context = [],
                    mixed $user = null
                ) use (&$capturedContext, &$capturedUser, $enrichedOptions): void {
                    $capturedContext = $context;
                    $capturedUser = $user;
                    $layout->getChildren()[0]->setOptions($enrichedOptions);
                },
            ]),
            $this->makeEmpty(DataObjectResolverInterface::class, [
                'getById' => $folder,
            ]),
            new NullLogger()
        );
        $collector->setFolderId(8);
        $collector->setUser($user);

        $buildColumnConfigurations = new ReflectionMethod($collector, 'buildColumnConfigurations');
        $buildColumnConfigurations->invoke($collector, $objectBrick, 'attributes', 'Attributes');

        self::assertSame($enrichedOptions, $capturedDefinition?->getFieldDefinition()->getOptions());
        self::assertSame($user, $capturedUser);
        self::assertSame([
            'object' => $folder,
            'purpose' => 'gridconfig',
            'containerType' => 'objectbrick',
            'containerKey' => 'Engine',
            'outerFieldname' => 'attributes',
        ], $capturedContext);
    }
}
