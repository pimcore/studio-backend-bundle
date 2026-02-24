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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateObjectBrickParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Listing;
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use function array_map;
use function array_unique;
use function array_values;
use function in_array;
use function is_array;
use function sprintf;
use function strtolower;

/**
 * @internal
 */
final readonly class ObjectBrickRepository implements ObjectBrickRepositoryInterface
{
    private const string NOT_WRITEABLE_EXCEPTION_MESSAGE = 'Object Brick';

    public function __construct(
        private ClassDefinitionServiceResolverInterface $classDefinitionServiceResolver,
        private DefinitionResolverInterface $definitionResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listObjectBricks(): array
    {
        return (new Definition\Listing())->load();
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectBrickByKey(string $key): Definition
    {
        $exception = null;
        $definition = null;

        try {
            $definition = $this->definitionResolver->getByKey($key);
        } catch (Exception $e) {
            $exception = $e;
        }
        if (!$definition || $exception) {
            throw new NotFoundException(type: 'Object Brick', id: $key, previous: $exception);
        }

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function create(CreateObjectBrickParameters $parameters): Definition
    {
        $key = $parameters->getKey();
        $existingNames = $this->listObjectBrickNames();

        foreach ($existingNames as $existingName) {
            if (strtolower($key) === strtolower($existingName)) {
                throw new ElementExistsException(
                    sprintf(
                        'ObjectBrick with the same name already exists: %s',
                        $existingName
                    )
                );
            }
        }

        $definition = new Definition();
        $definition->setKey($key);

        try {
            $definition->save();
        } catch (DefinitionWriteException) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function update(Definition $definition, UpdateParameters $parameters): Definition
    {
        try {
            $values = $parameters->getValues();
            $definition->setTitle($values['title'] ?? '');
            $definition->setGroup($values['group'] ?? '');
            $definition->setParentClass($values['parentClass'] ?? '');
            $definition->setImplementsInterfaces($values['implementsInterfaces'] ?? '');

            if (isset($values['classDefinitions']) && is_array($values['classDefinitions'])) {
                $classDefinitions = array_values(
                    array_unique(array_map('serialize', $values['classDefinitions']))
                );
                $classDefinitions = array_map('unserialize', $classDefinitions);
                $definition->setClassDefinitions($classDefinitions);
            }

            $configuration = $parameters->getConfiguration();
            $configuration['datatype'] = 'layout';
            $configuration['fieldtype'] = 'panel';

            $layout = $this->classDefinitionServiceResolver->generateLayoutTreeFromArray(
                $configuration,
                true
            );
            $definition->setLayoutDefinitions($layout);

            $definition->save();

            return $definition;
        } catch (DefinitionWriteException) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Definition $definition): void
    {
        try {
            $definition->delete();
        } catch (DefinitionWriteException) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        }
    }

    public function exportAsJson(Definition $definition): string
    {
        return $this->classDefinitionServiceResolver->generateObjectBrickJson($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function importFromJson(Definition $definition, string $json): Definition
    {
        try {
            $success = $this->classDefinitionServiceResolver->importObjectBrickFromJson(
                $definition,
                $json,
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
                'Failed to import object brick from JSON'
            );
        }

        return $definition;
    }

    public function getObjectBrickUsages(string $key): array
    {
        $result = [];
        $classes = (new Listing())->load();

        foreach ($classes as $class) {
            $fieldDefs = $class->getFieldDefinitions();
            foreach ($fieldDefs as $fieldDef) {
                if ($fieldDef instanceof Objectbricks) {
                    $allowedKeys = $fieldDef->getAllowedTypes();
                    if (in_array($key, $allowedKeys, true)) {
                        $result[] = [
                            'class' => $class->getName(),
                            'field' => $fieldDef->getName(),
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function listObjectBrickNames(): array
    {
        return (new Definition\Listing())->loadNames();
    }
}
