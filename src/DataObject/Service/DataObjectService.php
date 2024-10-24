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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\OpenSearchFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\DataObjectParameters;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\DataObjectEvent;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectAddParameters;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterServiceTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\UserPermissionTrait;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject as DataObjectModel;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\FactoryInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class DataObjectService implements DataObjectServiceInterface
{
    use ElementProviderTrait;
    use UserPermissionTrait;

    private const ALLOWED_TYPES = [
        AbstractObject::OBJECT_TYPE_OBJECT,
        AbstractObject::OBJECT_TYPE_VARIANT,
    ];

    private const INDEX_SORT = 'index';

    public function __construct(
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private DataObjectSearchServiceInterface $dataObjectSearchService,
        private DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private FactoryInterface $factory,
        private FilterServiceProviderInterface $filterServiceProvider,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    /**
     * @throws AccessDeniedException
     * @throws DatabaseException
     * @throws ElementSavingFailedException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function addDataObject(
        int $parentId,
        DataObjectAddParameters $parameters,
    ): int {
        $user = $this->securityService->getCurrentUser();
        $parent = $this->getValidParent($user, $parentId);
        if ($this->dataObjectServiceResolver->pathExists($parent->getFullPath() . '/' . $parameters->getKey())) {
            throw new ElementSavingFailedException(null, 'Element with the same key and path already exists');
        }

        $class = $this->getValidClass($parameters->getClassId());
        $object = $this->getValidObjectByClass($parameters->getType(), $class->getName());

        return $this->createNewObject(
            $parent->getId(),
            $object,
            $class,
            $user,
            $parameters
        );
    }

    /**
     * @throws AccessDeniedException|InvalidFilterServiceTypeException|InvalidQueryTypeException
     * @throws InvalidFilterTypeException|NotFoundException|SearchException|UserNotFoundException
     */
    public function getDataObjects(DataObjectParameters $parameters): Collection
    {
        /** @var OpenSearchFilterInterface $filterService */
        $filterService = $this->filterServiceProvider->create(OpenSearchFilterInterface::SERVICE_TYPE);

        $query = $filterService->applyFilters(
            $parameters,
            ElementTypes::TYPE_DATA_OBJECT
        );

        $this->setTreeSorting($parameters->getParentId() ?? 1, $query);

        $result = $this->dataObjectSearchService->searchDataObjects($query);

        $items = $result->getItems();

        foreach ($items as $item) {
            $this->dispatchDataObjectEvent($item);
        }

        return new Collection($result->getTotalItems(), $items);
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDataObject(int $id, bool $checkPermissionsForCurrentUser = true): DataObject
    {
        $dataObject = $this->dataObjectSearchService->getDataObjectById(
            $id,
            $this->getUserForPermissionCheck($this->securityService, $checkPermissionsForCurrentUser)
        );

        $this->dispatchDataObjectEvent($dataObject);

        return $dataObject;
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDataObjectForUser(int $id, UserInterface $user): DataObject
    {
        $dataObject = $this->dataObjectSearchService->getDataObjectById($id, $user);

        $this->dispatchDataObjectEvent($dataObject);

        return $dataObject;
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDataObjectFolder(int $id, bool $checkPermissionsForCurrentUser = true): DataObjectFolder
    {
        $dataObject = $this->dataObjectSearchService->getDataObjectById(
            $id,
            $this->getUserForPermissionCheck($this->securityService, $checkPermissionsForCurrentUser)
        );

        if (!$dataObject instanceof DataObjectFolder) {
            throw new NotFoundException(ElementTypes::TYPE_FOLDER, $id);
        }

        $this->dispatchDataObjectEvent($dataObject);

        return $dataObject;
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getDataObjectFolderForUser(int $id, UserInterface $user): DataObjectFolder
    {
        $dataObject = $this->dataObjectSearchService->getDataObjectById($id, $user);

        if (!$dataObject instanceof DataObjectFolder) {
            throw new NotFoundException(ElementTypes::TYPE_FOLDER, $id);
        }

        $this->dispatchDataObjectEvent($dataObject);

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

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    private function getValidParent(UserInterface $user, int $parentId): DataObjectModel
    {
        $parent = $this->getDataObjectElement($user, $parentId);
        $this->securityService->hasElementPermission($parent, $user, ElementPermissions::CREATE_PERMISSION);

        return $parent;
    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    private function getValidClass(string $classId): ClassDefinition
    {
        try {
            $class = $this->classDefinitionResolver->getById($classId);
        } catch (Exception $exception) {
            throw new DatabaseException($exception->getMessage());
        }

        if (!$class) {
            throw new NotFoundException(ElementTypes::TYPE_CLASS_DEFINITION, $classId);
        }

        return $class;
    }

    /**
     * @throws DatabaseException|InvalidElementTypeException
     */
    private function getValidObjectByClass(string $objectType, string $className): Concrete
    {
        if (!in_array($objectType, self::ALLOWED_TYPES, true)) {
            throw new InvalidElementTypeException($objectType);
        }

        $object = $this->factory->build('Pimcore\\Model\\DataObject\\' . $className);
        if (!$object instanceof Concrete) {
            throw new DatabaseException(sprintf('Class %s is not a valid data object class', $className));
        }

        return $object;
    }

    /**
     * @throws ElementSavingFailedException
     */
    private function createNewObject(
        int $parentId,
        Concrete $object,
        ClassDefinition $class,
        UserInterface $user,
        DataObjectAddParameters $parameters,
    ): int {
        try {
            $object->setClassId($class->getId());
            $object->setClassName($class->getName());
            $object->setParentId($parentId);
            $object->setKey($parameters->getKey());
            $object->setType($parameters->getType());
            $object->setCreationDate(time());
            $object->setUserOwner($user->getId());
            $object->setUserModification($user->getId());
            $object->setPublished(false);
            $object->save();

            return $object->getId();
        } catch (Exception $exception) {
            throw new ElementSavingFailedException(null, $exception->getMessage());
        }
    }

    /**
     * @throws AccessDeniedException|InvalidQueryTypeException|NotFoundException|UserNotFoundException
     */
    private function setTreeSorting(int $parentId, QueryInterface $dataObjectQuery): void
    {
        if (!$dataObjectQuery instanceof DataObjectQuery) {
            throw new InvalidQueryTypeException(
                HttpResponseCodes::BAD_REQUEST->value,
                'Query type has to be instance of ' . DataObjectQuery::class
            );
        }

        $parent = $this->getDataObjectElement($this->securityService->getCurrentUser(), $parentId);
        if ($parent->getChildrenSortBy() === self::INDEX_SORT) {
            $dataObjectQuery->orderByIndex();

            return;
        }

        $dataObjectQuery->orderByPath(strtolower($parent->getChildrenSortOrder()));
    }

    private function dispatchDataObjectEvent(mixed $dataObject): void
    {
        $this->eventDispatcher->dispatch(
            new DataObjectEvent($dataObject),
            DataObjectEvent::EVENT_NAME
        );
    }
}
