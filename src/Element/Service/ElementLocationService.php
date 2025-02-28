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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResponse\ElementLocateEvent;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\LocationData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Model\WidgetElementData;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\ElementTreeWidgetRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\PerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class ElementLocationService implements ElementLocationServiceInterface
{
    public function __construct(
        private ElementServiceInterface $elementService,
        private ElementTreeWidgetRepositoryInterface $treeRepository,
        private EventDispatcherInterface $eventDispatcher,
        private FilterServiceProviderInterface $filterServiceProvider,
        private PerspectiveServiceInterface $perspectiveService,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getElementLocation(string $elementType, int $elementId, string $perspectiveId): LocationData
    {
        $user = $this->securityService->getCurrentUser();
        $this->elementService->getAllowedElementById($elementType, $elementId, $user);

        $perspective = $this->perspectiveService->getConfigData($perspectiveId);
        $filterService = $this->filterServiceProvider->create(SearchIndexFilterInterface::SERVICE_TYPE);
        $widgetElementData = $this->getWidgetElement($elementType, $elementId, $perspective, $filterService, $user);
        $treeLevelData = $this->treeRepository->getTreeLevelData($widgetElementData, $filterService, $user);

        return $this->hydrateAndDispatch($widgetElementData, $treeLevelData);
    }

    /**
     * @throws InvalidArgumentException|InvalidQueryTypeException|InvalidFilterTypeException|NotFoundException
     */
    private function getWidgetElement(
        string $elementType,
        int $elementId,
        PerspectiveConfigDetail $perspective,
        SearchIndexFilterInterface $filterService,
        UserInterface $user
    ): WidgetElementData {
        $widgetElementData = $this->treeRepository->getWidgetByElement(
            [$perspective->getWidgetsLeft(), $perspective->getWidgetsRight(), $perspective->getWidgetsBottom()],
            $elementType,
            $elementId,
            $filterService,
            $user
        );

        if ($widgetElementData === null) {
            throw new NotFoundException(
                'Element',
                sprintf('(id: %d) (type: %s)', $elementId, $elementType),
                'Id and Type'
            );
        }

        return $widgetElementData;
    }

    private function hydrateAndDispatch(
        WidgetElementData $widgetElementData,
        array $treeLevelData
    ): LocationData {
        $widget = $widgetElementData->getWidgetConfig();

        $locationData = new LocationData($widget->getId(), $treeLevelData);

        $this->eventDispatcher->dispatch(
            new ElementLocateEvent($locationData),
            ElementLocateEvent::EVENT_NAME
        );

        return $locationData;
    }
}
