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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Listing;

/**
 * @internal
 */
readonly class ClassDefinitionRepository implements ClassDefinitionRepositoryInterface
{
    public function __construct(
        private ClassDefinitionResolverInterface $classDefinitionResolver
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
}
