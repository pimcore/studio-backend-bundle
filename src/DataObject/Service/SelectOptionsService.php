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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ParseException;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\FieldDefinitionWrapper;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\DotNotationParserInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
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
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws UserNotFoundException
     */
    public function getSelectOptions(SelectOptionsParameter $selectOptionsParameter): array
    {
        $object = $this->getObject(
            $selectOptionsParameter->getObjectId()
        );

        if ($selectOptionsParameter->hasChangedData()) {
            // changedData arrives in the Studio data format (e.g. localizedfields as attribute -> language),
            // so it has to be applied through the same data adapters as a regular save.
            $this->dataService->updateEditableData(
                $object,
                $selectOptionsParameter->getChangedData(),
                $this->securityService->getCurrentUser()
            );
        }

        $fieldDefinitionWrapper = $this->parseFieldName($selectOptionsParameter, $object);
        $fieldDefinition = $this->getSelectFieldDefinition($fieldDefinitionWrapper);

        $provider = $this->getProvider($fieldDefinition);

        try {
            $class = $object->getClass();
        } catch (Exception) {
            throw new NotFoundException('class', $object->getClassId());
        }

        $options = $provider->getOptions(
            [
                ...$fieldDefinitionWrapper->getOptionsProviderContext(),
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
    private function parseFieldName(
        SelectOptionsParameter $selectOptionsParameter,
        Concrete $object
    ): FieldDefinitionWrapper {
        try {
            return $this->dotNotationParser->parse($object, $selectOptionsParameter->getFieldName());
        } catch (ParseException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), $exception);
        } catch (Exception) {
            throw new NotFoundException('class', $object->getClassId());
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getSelectFieldDefinition(FieldDefinitionWrapper $fieldDefinitionWrapper): Select|Multiselect
    {
        $fieldDefinition = $fieldDefinitionWrapper->getFieldDefinition();

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
