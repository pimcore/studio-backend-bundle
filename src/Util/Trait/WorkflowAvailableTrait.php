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
