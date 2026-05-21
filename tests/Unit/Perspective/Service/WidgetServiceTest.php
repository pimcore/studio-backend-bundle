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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Perspective\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\WidgetConfigListHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\WidgetTypeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\WidgetConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Loader\Widget\ConfigHydratorLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Loader\Widget\ConfigRepositoryLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\WidgetService;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\WidgetValidationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\ElementTreeWidgets;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\WidgetTypes;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * @internal
 */
final class WidgetServiceTest extends Unit
{
    public function testListWidgetConfigurationsReturnDefaultWithWrapperFalse(): void
    {
        $defaultWidgetIds = ElementTreeWidgets::values();
        $customWidgetId = 'custom_widget_123';

        $allConfigData = [];
        foreach ($defaultWidgetIds as $widgetId) {
            $allConfigData[] = ['id' => $widgetId, 'name' => 'Default ' . $widgetId];
        }
        $allConfigData[] = ['id' => $customWidgetId, 'name' => 'Custom Widget'];

        $service = $this->createWidgetService($allConfigData);
        $result = $service->listWidgetConfigurations(skipWrapperWidgets: false);

        $this->assertCount(count($defaultWidgetIds) + 1, $result);
    }

    public function testListWidgetConfigsSkipDefaultWithWrapperTrue(): void
    {
        $defaultWidgetIds = ElementTreeWidgets::values();
        $customWidgetId = 'custom_widget_123';

        $allConfigData = [];
        foreach ($defaultWidgetIds as $widgetId) {
            $allConfigData[] = ['id' => $widgetId, 'name' => 'Default ' . $widgetId];
        }
        $allConfigData[] = ['id' => $customWidgetId, 'name' => 'Custom Widget'];

        $service = $this->createWidgetService($allConfigData);
        $result = $service->listWidgetConfigurations(skipWrapperWidgets: true);

        $this->assertCount(1, $result);
        $this->assertSame($customWidgetId, $result[0]->getId());
    }

    public function testListWidgetConfigsSkipDefault(): void
    {
        $allConfigData = [];
        foreach (ElementTreeWidgets::values() as $widgetId) {
            $allConfigData[] = ['id' => $widgetId, 'name' => 'Default ' . $widgetId];
        }

        $service = $this->createWidgetService($allConfigData);
        $result = $service->listWidgetConfigurations(skipWrapperWidgets: true);

        // Default widgets should be treated as wrapper only
        $this->assertCount(0, $result);
    }

    public function testListWidgetConfigsSkipWrapper(): void
    {
        $wrapperRepository = $this->makeEmpty(WidgetConfigRepositoryInterface::class, [
            'getSupportedWidgetType' => 'some_wrapper_type',
            'isWidgetTypeOnlyWrapper' => true,
            'listConfigurations' => [['id' => 'wrapper_1', 'name' => 'Wrapper']],
        ]);

        $elementTreeRepository = $this->makeEmpty(WidgetConfigRepositoryInterface::class, [
            'getSupportedWidgetType' => WidgetTypes::ELEMENT_TREE->value,
            'isWidgetTypeOnlyWrapper' => false,
            'listConfigurations' => [['id' => 'custom_widget', 'name' => 'Custom']],
        ]);

        $configRepositoryLoader = $this->makeEmpty(ConfigRepositoryLoaderInterface::class, [
            'loadRepositories' => [$wrapperRepository, $elementTreeRepository],
        ]);

        $service = $this->createWidgetServiceWithLoader($configRepositoryLoader);
        $result = $service->listWidgetConfigurations(skipWrapperWidgets: true);

        // Wrapper repo skipped entirely, element_tree repo returns only custom widget
        $this->assertCount(1, $result);
        $this->assertSame('custom_widget', $result[0]->getId());
    }

    private function createWidgetService(array $configDataItems): WidgetService
    {
        $repository = $this->makeEmpty(WidgetConfigRepositoryInterface::class, [
            'getSupportedWidgetType' => WidgetTypes::ELEMENT_TREE->value,
            'isWidgetTypeOnlyWrapper' => false,
            'listConfigurations' => $configDataItems,
        ]);

        $configRepositoryLoader = $this->makeEmpty(ConfigRepositoryLoaderInterface::class, [
            'loadRepositories' => [$repository],
        ]);

        return $this->createWidgetServiceWithLoader($configRepositoryLoader);
    }

    private function createWidgetServiceWithLoader(
        ConfigRepositoryLoaderInterface $configRepositoryLoader
    ): WidgetService {
        $configListHydrator = $this->makeEmpty(WidgetConfigListHydratorInterface::class, [
            'hydrate' => function (array $data): WidgetConfig {
                return new WidgetConfig(
                    $data['id'],
                    $data['name'] ?? 'Test',
                    $data['widgetType'] ?? WidgetTypes::ELEMENT_TREE->value,
                    new ElementIcon(ElementIconTypes::NAME->value, 'test-icon'),
                    $data['onlyWrapper'] ?? false,
                );
            },
        ]);

        return new WidgetService(
            $this->makeEmpty(ConfigHydratorLoaderInterface::class),
            $configRepositoryLoader,
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(UuidFactory::class),
            $this->makeEmpty(WidgetTypeHydratorInterface::class),
            $configListHydrator,
            $this->makeEmpty(WidgetValidationServiceInterface::class),
            [],
        );
    }
}
