<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Repository;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Listing;

/**
 * @internal
 */
class ClassDefinitionRepository implements ClassDefinitionRepositoryInterface
{

    /**
     * @return ClassDefinition[]
     */
    public function getClassDefinitions(): array
    {
        $classesList = new Listing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');
        return $classesList->load();
    }
}