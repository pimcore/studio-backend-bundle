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

/**
 * @internal
 */
interface WorkflowStateServiceInterface
{
    /**
     * Sets the workflow states visible in the header on the given elements: only workflows that support the
     * element, read through the workflow's marking store. The stored element decides, not an unpublished version.
     * Elements that do not carry workflow states are skipped.
     *
     * @param iterable<mixed> $elements
     */
    public function applyVisibleStates(iterable $elements, string $elementType): void;
}
