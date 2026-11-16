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

/**
 * @internal
 */
interface WorkflowStateServiceInterface
{
    /**
     * Sets the workflow states visible in the header on the given elements, using one lookup for all of them.
     * Elements that do not carry workflow states are skipped.
     *
     * @param iterable<mixed> $elements
     *
     * @throws DatabaseException
     */
    public function applyVisibleStates(iterable $elements, string $elementType): void;
}
