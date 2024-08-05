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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\OpenSearchFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\DataObjectEvent;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterServiceTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\DataObject as DataObjectModel;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class DataObjectService implements DataObjectServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private DataObjectSearchServiceInterface $dataObjectSearchService,
        private FilterServiceProviderInterface $filterServiceProvider,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    /**
     * @throws InvalidFilterServiceTypeException|SearchException|InvalidQueryTypeException|InvalidFilterTypeException
     */
    public function getDataObjects(ElementParameters $parameters): Collection
    {
        /** @var OpenSearchFilterInterface $filterService */
        $filterService = $this->filterServiceProvider->create(OpenSearchFilterInterface::SERVICE_TYPE);

        $dataObjectQuery = $filterService->applyFilters(
            $parameters,
            ElementTypes::TYPE_DATA_OBJECT
        );

        $dataObjectQuery->orderByPath('asc');

        $result = $this->dataObjectSearchService->searchDataObjects($dataObjectQuery);

        $items = $result->getItems();

        foreach ($items as $item) {
            $this->eventDispatcher->dispatch(
                new DataObjectEvent($item),
                DataObjectEvent::EVENT_NAME
            );
        }

        return new Collection($result->getTotalItems(), $items);
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDataObject(int $id): DataObject
    {
        $dataObject = $this->dataObjectSearchService->getDataObjectById($id);

        $this->eventDispatcher->dispatch(
            new DataObjectEvent($dataObject),
            DataObjectEvent::EVENT_NAME
        );

        return $dataObject;
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDataObjectFolder(int $id): DataObjectFolder
    {
        $dataObject = $this->dataObjectSearchService->getDataObjectById($id);

        if (!$dataObject instanceof DataObjectFolder) {
            throw new NotFoundException(ElementTypes::TYPE_FOLDER, $id);
        }

        $this->eventDispatcher->dispatch(
            new DataObjectEvent($dataObject),
            DataObjectEvent::EVENT_NAME
        );

        return $dataObject;
    }

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getDataObjectElement(
        UserInterface $user,
        int $dataObjectId,
    ): DataObjectModel {
        $dataObject = $this->getElement($this->serviceResolver, ElementTypes::TYPE_OBJECT, $dataObjectId);
        $this->securityService->hasElementPermission($dataObject, $user, ElementPermissions::VIEW_PERMISSION);

        if (!$dataObject instanceof DataObjectModel) {
            throw new InvalidElementTypeException($dataObject->getType());
        }

        return $dataObject;
    }

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getDataObjectElementByPath(
        UserInterface $user,
        string $path,
    ): DataObjectModel {
        $dataObject = $this->getElementByPath($this->serviceResolver, ElementTypes::TYPE_OBJECT, $path);
        $this->securityService->hasElementPermission($dataObject, $user, ElementPermissions::VIEW_PERMISSION);

        if (!$dataObject instanceof DataObjectModel) {
            throw new InvalidElementTypeException($dataObject->getType());
        }

        return $dataObject;
    }
}
