<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
