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
