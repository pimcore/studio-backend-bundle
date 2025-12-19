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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateClassDefinitionParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Listing;
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;

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

}
