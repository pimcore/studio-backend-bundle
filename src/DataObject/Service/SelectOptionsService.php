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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinition\Helper\OptionsProviderResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\DynamicSelectOptionEvent;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator\SelectOptionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Legacy\ApplyChangesHelperInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\SelectOptionsParameter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\SelectOption;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
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
        private ApplyChangesHelperInterface $applyChangesHelper,
        private OptionsProviderResolverInterface $optionsProviderResolver,
        private SelectOptionHydratorInterface $selectOptionHydrator,
        private EventDispatcherInterface $eventDispatcher,

    ) {
    }

    /**
     * @throws DatabaseException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function getSelectOptions(SelectOptionsParameter $selectOptionsParameter): array
    {
        $object = $this->getObject(
            $selectOptionsParameter->getObjectId()
        );

        if ($selectOptionsParameter->hasChangedData()) {
            $this->applyChangesHelper->applyChanges($object, $selectOptionsParameter->getChangedData());
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
            $fieldDefinition = $object->getClass()->getFieldDefinition($selectOptionsParameter->getFieldName());
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
