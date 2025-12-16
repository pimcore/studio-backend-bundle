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

use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\SaveElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Widget\TreeContextPermissionsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Throwable;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class WidgetValidationService implements WidgetValidationServiceInterface
{
    public function __construct(
        private TreeContextPermissionsServiceInterface $contextPermissionService,
        private ElementServiceInterface $elementService,
        private IconServiceInterface $iconService,
        private SecurityServiceInterface $securityService,
        private array $widgetTypes
    ) {
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
                $this->iconService->getIconForValue($widgetData['icon']),
                $this->contextPermissionService->updatePermissions(
                    $widgetData['elementType'],
                    $widgetData['contextPermissions']
                ),
                $widgetData['elementType'],
                $this->getValidateRootPath($widgetData['elementType'], $widgetData['rootFolder']),
                $widgetData['showRoot'],
                $this->getValidClasses($widgetData),
                $widgetData['pql'],
                $widgetData['pageSize']
            );
        } catch (Throwable $exception) {
            throw new ValidationFailedException(
                sprintf('Could not process data: %s', $exception->getMessage()),
                previous: $exception
            );
        }

        return $configuration;
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    private function getValidateRootPath(string $elementType, string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        $this->elementService->getAllowedElementByPath($elementType, $path, $this->securityService->getCurrentUser());

        return $path;
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
