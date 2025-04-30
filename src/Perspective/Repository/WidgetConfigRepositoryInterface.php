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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Repository;

interface WidgetConfigRepositoryInterface
{
    public function getSupportedWidgetType(): string;

    public function createConfiguration(array $widgetData): string;

    public function updateConfiguration(array $widgetData): void;

    public function getConfiguration(string $widgetId): array;

    public function listConfigurations(): array;

    public function deleteConfiguration(
        string $widgetId
    ): void;
}
