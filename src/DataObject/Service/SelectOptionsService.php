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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinition\Helper\OptionsProviderResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\DynamicSelectOptionEvent;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator\SelectOptionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\SelectOptionsParameter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\SelectOption;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\DotNotationParserInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Helper\OptionsProviderResolver;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class SelectOptionsService implements SelectOptionsServiceInterface
{
    public function __construct(
        private ConcreteObjectResolverInterface $concreteObjectResolver,
        private DataServiceInterface $dataService,
        private SecurityServiceInterface $securityService,
        private OptionsProviderResolverInterface $optionsProviderResolver,
        private SelectOptionHydratorInterface $selectOptionHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private DotNotationParserInterface $dotNotationParser,
    ) {
    }

    /**
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws UserNotFoundException
     */
    public function getSelectOptions(SelectOptionsParameter $selectOptionsParameter): array
    {
        $user = $this->securityService->getCurrentUser();
        $object = $this->getObject(
            $selectOptionsParameter->getObjectId()
        );
        $this->securityService->hasElementPermission($object, $user, ElementPermissions::VIEW_PERMISSION);

        $changedData = $this->getApplicableChangedData($selectOptionsParameter, $object);
        if ($changedData !== []) {
            // changedData arrives in the Studio data format (e.g. localizedfields as attribute -> language),
            // so it has to be applied through the same data adapters as a regular save.
            $this->dataService->updateEditableData($object, $changedData, $user);
        }

        $fieldDefinition = $this->getFieldDefinition($selectOptionsParameter, $object);

        $provider = $this->getProvider($fieldDefinition);

        try {
            $class = $object->getClass();
        } catch (Exception) {
            throw new NotFoundException('class', $object->getClassId());
        }

        $options = $provider->getOptions(
            [
                'object' => $object,
                'fieldname' => $fieldDefinition->getName(),
                'class' => $class,
                'context' => $selectOptionsParameter->getContext(),
            ],
            $fieldDefinition
        );

        $selectOptions = [];
        foreach ($options as $option) {
            $selectOption = $this->selectOptionHydrator->hydrate($option);
            $this->dispatchDataObjectEvent($selectOption);
            $selectOptions[] = $selectOption;
        }

        return $selectOptions;

    }

    /**
     * A lookup only reads the object's own values. Reverse relation fields store on other objects
     * and are therefore never applied here.
     *
     * @throws NotFoundException
     */
    private function getApplicableChangedData(
        SelectOptionsParameter $selectOptionsParameter,
        Concrete $object
    ): array {
        if (!$selectOptionsParameter->hasChangedData()) {
            return [];
        }

        try {
            $class = $object->getClass();
        } catch (Exception $exception) {
            throw new NotFoundException('class', $object->getClassId(), previous: $exception);
        }

        return array_filter(
            $selectOptionsParameter->getChangedData(),
            static fn (string|int $key): bool =>
                !$class->getFieldDefinition((string)$key) instanceof ReverseObjectRelation,
            ARRAY_FILTER_USE_KEY
        );
    }

    private function getMode(Select|Multiselect $fieldDefinition): int
    {
        return $fieldDefinition instanceof Multiselect
            ? OptionsProviderResolver::MODE_MULTISELECT
            : OptionsProviderResolver::MODE_SELECT;
    }

    private function dispatchDataObjectEvent(SelectOption $selectOption): void
    {
        $this->eventDispatcher->dispatch(
            new DynamicSelectOptionEvent($selectOption),
            DynamicSelectOptionEvent::EVENT_NAME
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getProvider(Select|Multiselect $fieldDefinition): SelectOptionsProviderInterface
    {
        $provider = $this->optionsProviderResolver->resolveProvider(
            $fieldDefinition->getOptionsProviderClass(),
            $this->getMode($fieldDefinition)
        );

        if (!$provider instanceof SelectOptionsProviderInterface) {
            throw new InvalidArgumentException('Provider does not implement SelectOptionsProviderInterface');
        }

        return $provider;
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    private function getFieldDefinition(
        SelectOptionsParameter $selectOptionsParameter,
        Concrete $object
    ): Select|Multiselect {
        try {
            $fieldDefinition = $this->dotNotationParser
                ->parse($object, $selectOptionsParameter->getFieldName())
                ->getFieldDefinition();
        } catch (Exception) {
            throw new NotFoundException('class', $object->getClassId());
        }

        if (!$fieldDefinition instanceof Select && !$fieldDefinition instanceof Multiselect) {
            throw new InvalidArgumentException('Field is not a select or multiselect field');
        }

        return $fieldDefinition;
    }

    /**
     * @throws NotFoundException
     */
    private function getObject(int $objectId): Concrete
    {
        $object = $this->concreteObjectResolver->getById($objectId);

        if ($object === null) {
            throw new NotFoundException('Data Object', $objectId);
        }

        return $object;
    }
}
