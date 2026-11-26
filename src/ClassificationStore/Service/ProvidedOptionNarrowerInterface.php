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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * @internal
 */
interface ProvidedOptionNarrowerInterface
{
    /**
     * Narrows the list an options provider has just filled in to the subset the assignment
     * declares, and reconciles the default value against what is left - in place, on the
     * definition that answers for the value.
     *
     * To be called once the enrichment has run: the provider replaces the whole list, so a subset
     * applied before it is gone by the time the editor sees the definition.
     */
    public function narrow(Data $definition, KeyGroupRelation $relation): void;
}
