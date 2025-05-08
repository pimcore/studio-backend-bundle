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

namespace Pimcore\Bundle\StudioBackendBundle\User\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Two Factor Authentication Data',
    description: 'Two Factor Authentication Data for a User',
    required: ['required', 'enabled', 'type', 'active'],
    type: 'object',
)]
final readonly class TwoFactorAuth
{
    public function __construct(
        #[Property(description: 'Required', type: 'boolean', example: true)]
        private bool $required,
        #[Property(description: 'Enabled', type: 'boolean', example: true)]
        private bool $enabled,
        #[Property(description: 'Type', type: 'string', example: 'totp')]
        private string $type,
        #[Property(description: 'Active', type: 'boolean', example: true)]
        private bool $active,
    ) {
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
