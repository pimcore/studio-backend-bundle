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
