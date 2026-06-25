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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter\WorkflowElementsParameters;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowElement;

/**
 * @internal
 */
interface WorkflowElementsServiceInterface
{
    /**
     * @throws DatabaseException|UserNotFoundException
     *
     * @return Collection<WorkflowElement>
     */
    public function getElements(WorkflowElementsParameters $parameters): Collection;
}
