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

namespace Pimcore\Bundle\StudioBackendBundle\Authorization\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'Two-factor login step',
    description: 'Answer to a correct password when a two-factor code is needed; no user data',
    required: ['twoFactorRequired', 'twoFactorStep'],
    type: 'object'
)]
final readonly class TwoFactorLoginStep
{
    public function __construct(
        #[Property(description: 'A code is needed', type: 'boolean', example: true)]
        private bool $twoFactorRequired,
        #[Property(description: 'What to do next', type: 'string', enum: ['verify'], example: 'verify')]
        private string $twoFactorStep,
    ) {
    }

    public function isTwoFactorRequired(): bool
    {
        return $this->twoFactorRequired;
    }

    public function getTwoFactorStep(): string
    {
        return $this->twoFactorStep;
    }
}
