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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\Permissions\ContextPermissionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\ElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\WidgetTypes;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementFolderIds;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementFolderPaths;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final readonly class ElementTreeWidgetConfigHydrator implements WidgetConfigHydratorInterface
{
    public function __construct(
        private ContextPermissionServiceInterface $contextPermissionService,
        private ElementDataServiceInterface $elementDataService,
        private ElementServiceInterface $elementService,
        private IconServiceInterface $iconService,
        private LoggerInterface $pimcoreLogger,
        private SecurityServiceInterface $securityService
    ) {
    }

    public function getSupportedWidgetType(): string
    {
        return WidgetTypes::ELEMENT_TREE->value;
    }

    public function hydrate(array $widgetData): ElementTreeWidgetConfig
    {
        return new ElementTreeWidgetConfig(
            $widgetData['id'],
            $widgetData['name'],
            $this->contextPermissionService->setElementContextPermissions(
                $widgetData['elementType'],
                $widgetData['contextPermissions']
            ),
            $this->iconService->getIconForValue($widgetData['icon']),
            $widgetData['elementType'],
            $this->getRootFolder($widgetData),
            $widgetData['showRoot'],
            $widgetData['classes'],
            $widgetData['pql'],
            $widgetData['pageSize'],
            $widgetData['isWriteable']
        );
    }

    private function getRootFolder(array $widgetData): RelatedElementData
    {
        $elementType = $widgetData['elementType'] === ElementTypes::TYPE_DATA_OBJECT ?
            ElementTypes::TYPE_OBJECT :
            $widgetData['elementType']
        ;

        if ($this->isDefaultRootFolder($widgetData['rootFolder'])) {
            return $this->createDefaultRootFolder($elementType);
        }

        return $this->resolveCustomRootFolder($widgetData, $elementType) ??
            $this->createDefaultRootFolder($elementType);
    }

    private function isDefaultRootFolder(string $rootFolder): bool
    {
        return $rootFolder === '' || $rootFolder === ElementFolderPaths::ROOT->value;
    }

    private function createDefaultRootFolder(string $elementType): RelatedElementData
    {
        return new RelatedElementData(
            ElementFolderIds::ROOT->value,
            $elementType,
            ElementTypes::TYPE_FOLDER,
            '/',
            null
        );
    }

    private function resolveCustomRootFolder(array $widgetData, string $elementType): ?RelatedElementData
    {
        try {
            $folder = $this->elementService->getAllowedElementByPath(
                $elementType,
                $widgetData['rootFolder'],
                $this->securityService->getCurrentUser()
            );

            return $this->elementDataService->getRelatedElementData($folder);
        } catch (NotFoundException $exception) {
            $this->pimcoreLogger->error(
                'Failed to resolve custom root folder: {path} for element type: {elementType}. Error: {error}',
                [
                    'path' => $widgetData['rootFolder'],
                    'elementType' => $elementType,
                    'error' => $exception->getMessage(),
                ]
            );

            return null;
        }
    }
}
