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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\SaveElementTreeWidgetConfig;

/**
 * @internal
 */
interface WidgetValidationServiceInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function validateWidgetType(string $widgetType): void;

    /**
     * @throws ValidationFailedException
     */
    public function validateWidgetConfigData(array $widgetData): SaveElementTreeWidgetConfig;
}
