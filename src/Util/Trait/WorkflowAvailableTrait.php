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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use OpenApi\Attributes\Property;

/**
 * @internal
 */
trait WorkflowAvailableTrait
{
    #[Property(description: 'Has workflow available', type: 'bool', example: false)]
    private bool $hasWorkflowAvailable = false;

    public function getHasWorkflowAvailable(): bool
    {
        return $this->hasWorkflowAvailable;
    }

    public function setHasWorkflowAvailable(bool $hasWorkflowAvailable): void
    {
        $this->hasWorkflowAvailable = $hasWorkflowAvailable;
    }
}
