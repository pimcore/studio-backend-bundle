<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\Event\AvailableVisibleFieldEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\AvailableVisibleFieldHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\AvailableVisibleFieldsParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\AvailableVisibleField;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class ClassService implements ClassServiceInterface
{
    private const array SYSTEM_COLUMNS = [
        'id',
        'fullpath',
        'key',
        'published',
        'creationDate',
        'modificationDate',
        'filename',
        'classname',
    ];

    public function __construct(
        private AvailableVisibleFieldHydratorInterface $availableVisibleFieldHydrator,
        private ClassDefinitionRepositoryInterface $classDefinitionRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableVisibleFields(AvailableVisibleFieldsParameters $parameters): array
    {
        $classNames = $parameters->getClassNamesArray();

        if (empty($classNames)) {
            return [];
        }

        $availableFields = $this->buildSystemFields();
        $commonFields = $this->getCommonFieldDefinitions($classNames);

        foreach (array_keys($commonFields) as $fieldKey) {
            $availableFields[] = $this->hydrateAvailableField($fieldKey);
        }

        return $availableFields;
    }

    /**
     * @return AvailableVisibleField[]
     */
    private function buildSystemFields(): array
    {
        return array_map(
            function (string $fieldKey): AvailableVisibleField {
                return $this->hydrateAvailableField($fieldKey);
            },
            self::SYSTEM_COLUMNS
        );
    }

    /**
     * @throws NotFoundException
     */
    private function getCommonFieldDefinitions(array $classNames): array
    {
        $commonFields = [];
        $firstIteration = true;

        foreach ($classNames as $className) {
            $class = $this->classDefinitionRepository->getClassDefinition($className);

            $fieldDefinitions = $class->getFieldDefinitions();
            $allFieldNames = $this->collectAllFieldNames($class, $fieldDefinitions);

            if (!$firstIteration) {
                $commonFields = $this->filterCommonFields($commonFields, $allFieldNames);
            }

            $commonFields = $this->processAvailableFieldDefinitions(
                $fieldDefinitions,
                $firstIteration,
                $commonFields
            );

            $firstIteration = false;
        }

        return $commonFields;
    }

    private function processAvailableFieldDefinitions(
        array $fieldDefinitions,
        bool $firstIteration,
        array $commonFields
    ): array {
        foreach ($fieldDefinitions as $fieldDefinition) {

            if ($fieldDefinition instanceof Fieldcollections
                || $fieldDefinition instanceof Objectbricks
                || $fieldDefinition instanceof Block
            ) {
                continue;
            }

            // Recursively process localized fields
            if ($fieldDefinition instanceof Localizedfields) {
                $localizedFieldDefinitions = $fieldDefinition->getFieldDefinitions();
                $commonFields = $this->processAvailableFieldDefinitions(
                    $localizedFieldDefinitions,
                    $firstIteration,
                    $commonFields
                );
                continue;
            }

            $fieldName = $fieldDefinition->getName();
            if (
                $firstIteration ||
                (
                    isset($commonFields[$fieldName]) &&
                    $commonFields[$fieldName]->getFieldtype() === $fieldDefinition->getFieldtype()
                )
            ) {
                $commonFields[$fieldName] = $fieldDefinition;
            }
        }

        return $commonFields;
    }

    /**
     * @return string[]
     */
    private function collectAllFieldNames(ClassDefinition $class, array $fieldDefinitions): array
    {
        $fieldNames = array_keys($fieldDefinitions);

        $localizedFields = $class->getFieldDefinition('localizedfields');
        if ($localizedFields instanceof Localizedfields) {
            $localizedFieldNames = array_keys($localizedFields->getFieldDefinitions());
            $fieldNames = array_merge($fieldNames, $localizedFieldNames);
        }

        return $fieldNames;
    }

    private function filterCommonFields(array $commonFields, array $currentFieldNames): array
    {
        return array_filter(
            $commonFields,
            static fn(string $fieldKey): bool => in_array($fieldKey, $currentFieldNames, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function hydrateAvailableField(string $fieldKey): AvailableVisibleField
    {
        $field = $this->availableVisibleFieldHydrator->hydrate($fieldKey);
        $this->eventDispatcher->dispatch(
            new AvailableVisibleFieldEvent($field),
            AvailableVisibleFieldEvent::EVENT_NAME
        );

        return $field;
    }
}