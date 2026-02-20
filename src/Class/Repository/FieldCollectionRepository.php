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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\FieldCollection\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateFieldCollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Listing;
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;
use Pimcore\Model\DataObject\Fieldcollection\Definition;
use function sprintf;
use function strtolower;

/**
 * @internal
 */
final readonly class FieldCollectionRepository implements FieldCollectionRepositoryInterface
{
    private const string NOT_WRITEABLE_EXCEPTION_MESSAGE = 'Field Collection';

    public function __construct(
        private ClassDefinitionServiceResolverInterface $classDefinitionServiceResolver,
        private DefinitionResolverInterface $definitionResolver
    ) {
    }

    public function listFieldCollections(): array
    {
        return (new Definition\Listing())->load();
    }

    public function getFieldCollectionByKey(string $key): Definition
    {
        $exception = null;
        $definition = null;

        try {
            $definition = $this->definitionResolver->getByKey($key);
        } catch (Exception $e) {
            $exception = $e;
        }
        if (!$definition || $exception) {
            throw new NotFoundException(type: 'Field Collection', id: $key, previous: $exception);
        }

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function create(CreateFieldCollectionParameters $parameters): Definition
    {
        $key = $parameters->getKey();
        $existingNames = $this->listFieldCollectionNames();

        foreach ($existingNames as $existingName) {
            if (strtolower($key) === strtolower($existingName)) {
                throw new ElementExistsException(
                    sprintf(
                        'FieldCollection with the same name already exists: %s',
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
        return $this->classDefinitionServiceResolver->generateFieldCollectionJson($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function importFromJson(Definition $definition, string $json): Definition
    {
        try {
            $success = $this->classDefinitionServiceResolver->importFieldCollectionFromJson(
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
                'Failed to import field collection from JSON'
            );
        }

        return $definition;
    }

    public function getFieldCollectionUsages(string $key): array
    {
        $result = [];
        $classes = (new Listing())->load();

        foreach ($classes as $class) {
            $fieldDefs = $class->getFieldDefinitions();
            foreach ($fieldDefs as $fieldDef) {
                if ($fieldDef instanceof Fieldcollections) {
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
    private function listFieldCollectionNames(): array
    {
        return (new Definition\Listing())->loadNames();
    }
}
