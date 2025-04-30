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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'Data Object Clone Parameters',
    required: ['recursive', 'updateReferences'],
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
