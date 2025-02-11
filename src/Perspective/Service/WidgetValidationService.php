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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\Permissions\ContextPermissionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\SaveElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\WidgetPositions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Throwable;
use function in_array;
use function sprintf;
use function strlen;

/**
 * @internal
 */
final readonly class WidgetValidationService implements WidgetValidationServiceInterface
{
    public function __construct(
        private ContextPermissionServiceInterface $contextPermissionService,
        private IconServiceInterface $iconService,
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
            !preg_match('/^\p{L}[\p{L}\p{N}\s]+$/u', $configurationName)
        ) {
            throw new InvalidArgumentException(
                'Invalid widget name',
                errorKey: HttpResponseErrorKeys::WIDGET_NAME_INVALID->value
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

    /**
     * @throws ValidationFailedException
     */
    public function validateWidgetConfigData(array $widgetData): SaveElementTreeWidgetConfig
    {
        try {
            $configuration = new SaveElementTreeWidgetConfig(
                $widgetData['id'],
                $widgetData['name'],
                $this->iconService->getIconForWidget($widgetData['icon']),
                $this->contextPermissionService->saveElementContextPermissions(
                    $widgetData['elementType'],
                    $widgetData['contextPermissions']
                ),
                $widgetData['elementType'],
                $widgetData['rootFolder'],
                $widgetData['showRoot'],
                $this->getValidClasses($widgetData),
                $widgetData['pql'],
                $widgetData['position'],
                $widgetData['sort'],
                $widgetData['expanded'],
            );
        } catch (Exception|Throwable $exception) {
            throw new ValidationFailedException(
                sprintf('Could not process data: %s', $exception->getMessage()),
                previous: $exception
            );
        }
        $this->validatePosition($configuration->getPosition());

        return $configuration;
    }

    /**
     * @throws ValidationFailedException
     */
    private function validatePosition(string $position): void
    {
        if (!in_array($position, WidgetPositions::values(), true)
        ) {
            throw new ValidationFailedException(
                sprintf('Invalid widget position provided: %s', $position)
            );
        }
    }

    private function getValidClasses(array $widgetData): array
    {
        if (!isset($widgetData['classes'])) {
            return [];
        }

        if ($widgetData['elementType'] !== ElementTypes::TYPE_DATA_OBJECT) {
            return [];
        }

        return $widgetData['classes'];
    }
}
