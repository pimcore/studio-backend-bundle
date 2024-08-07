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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'Data Object Permissions',
    type: 'object'
)]
final readonly class CloneParameters
{
    public function __construct(
        #[Property(description: 'Recursive', type: 'bool', example: false)]
        private bool $recursive = false,
        #[Property(description: 'Update References', type: 'bool', example: false)]
        private bool $updateReferences = false,
    ) {
    }

    public function isRecursive(): bool
    {
        return $this->recursive;
    }

    public function isUpdateReferences(): bool
    {
        return $this->updateReferences;
    }
}
