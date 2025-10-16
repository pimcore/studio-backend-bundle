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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\WidgetConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\MappedParameter\WidgetDataParameter;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\WidgetConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetType;

/**
 * @internal
 */
interface WidgetServiceInterface
{
    /**
     * @return WidgetType[]
     */
    public function listWidgetTypes(): array;

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function addWidgetConfig(string $widgetType, WidgetDataParameter $widgetData): string;

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException|ValidationFailedException
     */
    public function updateWidgetConfig(string $widgetType, string $widgetId, WidgetDataParameter $widgetData): void;

    /**
     * @throws ForbiddenException|InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function getWidgetConfigData(string $widgetType, string $widgetId): WidgetConfig;

    /**
     * @throws InvalidArgumentException|NotFoundException
     *
     * @return WidgetConfig[]
     */
    public function listWidgetConfigurations(bool $skipWrapperWidgets): array;

    /**
     * @throws InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function deleteWidgetConfig(string $widgetType, string $widgetId): void;

    /**
     * @throws InvalidArgumentException
     */
    public function loadHydratorByType(string $widgetType): WidgetConfigHydratorInterface;

    /**
     * @throws InvalidArgumentException
     */
    public function loadRepositoryByType(string $widgetType): WidgetConfigRepositoryInterface;
}
