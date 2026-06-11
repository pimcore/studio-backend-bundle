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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'EditLockUser',
    required: ['name'],
    type: 'object'
)]
final readonly class EditLockUser
{
    public function __construct(
        #[Property(description: 'Name of the user holding the lock', type: 'string', example: 'admin')]
        private string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
