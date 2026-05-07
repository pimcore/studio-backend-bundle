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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Repository;

use Exception;
use Pimcore\Bundle\CoreBundle\OptionsProvider\SelectOptionsOptionsProvider;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateClassDefinitionParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Listing;
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;
use Pimcore\Model\DataObject\Objectbrick\Definition\Listing as ObjectBrickListing;
use function sprintf;

/**
 * @internal
 */
readonly class ClassDefinitionRepository implements ClassDefinitionRepositoryInterface
{
    private const string NOT_WRITEABLE_EXCEPTION_MESSAGE = 'Class Definition';

    public function __construct(
        private ClassDefinitionServiceResolverInterface $classDefinitionServiceResolver,
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private SecurityServiceInterface $securityService,
    ) {
    }

    public function getClassDefinitions(): array
    {
        $classesList = new Listing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');

        return $classesList->load();
    }

    public function getClassDefinitionsSortedById(): array
    {
        $classesList = new Listing();
        $classesList->setOrderKey('id');
        $classesList->setOrder('asc');

        return $classesList->load();
    }

    public function getClassDefinitionsWithObjectBricks(): array
    {
        $result = [];

        foreach ($this->getClassDefinitions() as $class) {
            foreach ($class->getFieldDefinitions() as $fieldDefinition) {
                if ($fieldDefinition instanceof Objectbricks) {
                    $result[] = $class;

                    break;
                }
            }
        }

        return $result;
    }

    public function getObjectBricksByClassName(string $className): array
    {
        $objectBrickList = new ObjectBrickListing();
        $brickDefinitions = $objectBrickList->load();
        $result = [];

        foreach ($brickDefinitions as $brickDefinition) {
            foreach ($brickDefinition->getClassDefinitions() as $brickClass) {
                if ($className === $brickClass['classname']) {
                    $result[] = [
                        'key' => $brickDefinition->getKey(),
                        'fieldname' => $brickClass['fieldname'],
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassDefinitionById(string $id): ClassDefinition
    {
        $exception = null;
        $cd = null;

        try {
            $cd = $this->classDefinitionResolver->getById($id);
        } catch (Exception $e) {
            $exception = $e;
        }
        if (!$cd || $exception) {
            throw new NotFoundException(type: 'class definition', id: $id, previous: $exception);
        }

        return $cd;
    }

    public function getClassDefinition(string $dataObjectClass): ClassDefinition
    {
        $exception = null;
        $cd = null;

        try {
            $cd = $this->classDefinitionResolver->getByName($dataObjectClass);
        } catch (Exception $e) {
            $exception = $e;
        }
        if (!$cd || $exception) {
            throw new NotFoundException(
                'class definition',
                $dataObjectClass,
                'class name',
                $exception
            );
        }

        return $cd;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(ClassDefinition $classDefinition): void
    {
        try {
            $classDefinition->delete();
        } catch (DefinitionWriteException) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(CreateClassDefinitionParameters $parameters): ClassDefinition
    {
        $name = $this->sanitizeName($parameters->getName());

        try {
            $class = $this->classDefinitionResolver->getById($parameters->getUid());
        } catch (Exception) {
            $class = null;
        }

        if ($class !== null) {
            throw new ElementExistsException(
                error: 'Class definition already exists',
                errorKey: HttpResponseErrorKeys::UID_ALREADY_EXISTS->value
            );
        }

        try {
            $classDefinition = $this->classDefinitionResolver->create(
                [
                    'name' => $name,
                    'userOwner' => $this->securityService->getCurrentUser()->getId(),
                ]
            );
            $classDefinition->setId($parameters->getUid());
            $classDefinition->save();

            return $classDefinition;
        } catch (DefinitionWriteException) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(ClassDefinition $classDefinition, UpdateParameters $updateParameters): ClassDefinition
    {
        try {
            $config = $updateParameters->getConfiguration();
            $values = $updateParameters->getValues();

            $this->validateModificationDate($classDefinition, $values);
            $values = $this->handleClassNameChange($classDefinition, $values);
            $values = $this->sanitizeCompositeIndices($values);
            $values = $this->removeProtectedFields($values);
            $config = $this->prepareLayoutConfiguration($config);
            $config = $this->applyOptionsProviderDefaults($config);

            $classDefinition->setValues($values);

            $layout = $this->classDefinitionServiceResolver->generateLayoutTreeFromArray($config, true);
            $classDefinition->setLayoutDefinitions($layout);
            $classDefinition->setUserModification($this->securityService->getCurrentUser()->getId());
            $classDefinition->setModificationDate(time());

            $this->updatePropertyVisibility($classDefinition, $values);

            $classDefinition->save();

            return $classDefinition;
        } catch (DefinitionWriteException) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    public function exportAsJson(ClassDefinition $classDefinition): string
    {
        return $this->classDefinitionServiceResolver->generateClassDefinitionJson($classDefinition);
    }

    /**
     * {@inheritdoc}
     */
    public function importFromJson(ClassDefinition $classDefinition, string $json): ClassDefinition
    {
        try {
            $success = $this->classDefinitionServiceResolver->importClassDefinitionFromJson(
                $classDefinition,
                $json,
                true,
                true
            );
        } catch (DefinitionWriteException) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        if (!$success) {
            throw new ElementSavingFailedException(
                null,
                'Failed to import class definition from JSON'
            );
        }

        return $classDefinition;
    }

    private function sanitizeName(string $name): string
    {
        $name = preg_replace('/\W+/', '', $name);

        return preg_replace('/^\d+/', '', $name);
    }

    /**
     * @throws ConflictException
     */
    private function validateModificationDate(ClassDefinition $classDefinition, array $values): void
    {
        if ($classDefinition->getModificationDate() !== $values['modificationDate']) {
            throw new ConflictException(
                'The class was modified during editing, please reload the class and make your changes again'
            );
        }
    }

    /**
     * @throws ElementExistsException
     */
    private function handleClassNameChange(ClassDefinition $classDefinition, array $values): array
    {
        if ($classDefinition->getName() === $values['name']) {
            return $values;
        }

        try {
            $existingClass = $this->getClassDefinition($values['name']);
            if ($existingClass->getId() !== $classDefinition->getId()) {
                throw new ElementExistsException(
                    error: sprintf('Another class with name %s already exists', $values['name']),
                    errorKey: HttpResponseErrorKeys::ELEMENT_EXISTS->value
                );
            }
        } catch (NotFoundException) {
            $values['name'] = $this->sanitizeName($values['name']);
            $classDefinition->rename($values['name']);
        }

        return $values;
    }

    private function sanitizeCompositeIndices(array $values): array
    {
        if (!isset($values['compositeIndices']) || !$values['compositeIndices']) {
            return $values;
        }

        foreach ($values['compositeIndices'] as $index => $compositeIndex) {
            $sanitizedKey = preg_replace('/[^a-za-z0-9_\-+]/', '', $compositeIndex['index_key']);
            if ($compositeIndex['index_key'] !== $sanitizedKey) {
                $values['compositeIndices'][$index]['index_key'] = $sanitizedKey;
            }
        }

        return $values;
    }

    private function removeProtectedFields(array $values): array
    {
        unset(
            $values['creationDate'],
            $values['userOwner'],
            $values['layoutDefinitions'],
            $values['fieldDefinitions']
        );

        return $values;
    }

    // TODO: test nesting & multiselect functionality
    private function applyOptionsProviderDefaults(array $config): array
    {
        $type = $config['optionsProviderType'] ?? null;
        $class = $config['optionsProviderClass'] ?? null;

        if ($type === 'select_options' && empty($class)) {
            $config['optionsProviderClass'] = SelectOptionsOptionsProvider::class;
        }

        if (isset($config['children']) && is_array($config['children'])) {
            foreach ($config['children'] as $key => $child) {
                if (is_array($child)) {
                    $config['children'][$key] = $this->applyOptionsProviderDefaults($child);
                }
            }
        }

        return $config;
    }

    private function prepareLayoutConfiguration(array $config): array
    {
        $config['datatype'] = 'layout';
        $config['fieldtype'] = 'panel';
        $config['name'] = 'pimcore_root';

        return $config;
    }

    private function updatePropertyVisibility(ClassDefinition $classDefinition, array $values): void
    {
        $propertyVisibility = $this->extractPropertyVisibility($values);

        if (!empty($propertyVisibility)) {
            $classDefinition->setPropertyVisibility($propertyVisibility);
        }
    }

    private function extractPropertyVisibility(array $values): array
    {
        $propertyVisibility = [];

        foreach ($values as $key => $value) {
            if (false === stripos($key, 'propertyVisibility')) {
                continue;
            }

            if (preg_match("/\.grid\./i", $key)) {
                $sanitizeKey = preg_replace("/propertyVisibility\.grid\./i", '', $key);
                $propertyVisibility['grid'][$sanitizeKey] = (bool)$value;

                continue;
            }

            if (preg_match("/\.search\./i", $key)) {
                $sanitizeKey = preg_replace("/propertyVisibility\.search\./i", '', $key);
                $propertyVisibility['search'][$sanitizeKey] = (bool) $value;
            }
        }

        return $propertyVisibility;
    }
}
