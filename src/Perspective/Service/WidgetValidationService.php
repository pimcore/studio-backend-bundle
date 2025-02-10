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
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class WidgetValidationService implements WidgetValidationServiceInterface
{
    public function __construct(
        private array $widgetTypes
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getValidWidgetName(array $configData): string
    {
        if (!isset($configData['name'])) {
            throw new InvalidArgumentException(
                'Missing widget name',
                errorKey: HttpResponseErrorKeys::WIDGET_NAME_MISSING->value
            );
        }
        $this->validateWidgetName($configData['name']);

        return htmlspecialchars($configData['name'], ENT_QUOTES, 'UTF-8');
    }

    public function validateWidgetName(
        string $configurationName
    ): void {
        if (strlen($configurationName) < 3 ||
            strlen($configurationName) > 80 ||
            !preg_match('/^[a-zA-Z][\w\s]+$/', $configurationName)
        ) {
            throw new InvalidArgumentException(
                'Invalid widget name', errorKey: HttpResponseErrorKeys::WIDGET_NAME_INVALID->value
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validateWidgetType(string $widgetType): void
    {
        if (!in_array($widgetType, $this->widgetTypes, true)) {
            throw new InvalidArgumentException(sprintf('Invalid widget type: %s', $widgetType));
        }
    }
}
