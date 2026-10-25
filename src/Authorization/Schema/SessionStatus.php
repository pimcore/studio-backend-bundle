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
    schema: 'SessionStatus',
    title: 'Session Status',
    description: 'Whether the request carries an authenticated Studio session. A login that waits for its two-factor code is not authenticated yet and carries the same fields as the login answer',
    required: ['authenticated'],
    type: 'object'
)]
final readonly class SessionStatus
{
    public function __construct(
        #[Property(description: 'True if a Studio user is logged in', type: 'boolean', example: true)]
        private bool $authenticated,
        #[Property(description: 'A two-factor code is needed', type: 'boolean', example: true, nullable: true)]
        private ?bool $twoFactorRequired = null,
        #[Property(
            description: 'What to do next',
            type: 'string',
            enum: ['verify', 'setup'],
            example: 'verify',
            nullable: true
        )]
        private ?string $twoFactorStep = null,
        #[Property(description: 'Number of digits in a code', type: 'integer', example: 6, nullable: true)]
        private ?int $digits = null,
    ) {
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    public function getTwoFactorRequired(): ?bool
    {
        return $this->twoFactorRequired;
    }

    public function getTwoFactorStep(): ?string
    {
        return $this->twoFactorStep;
    }

    public function getDigits(): ?int
    {
        return $this->digits;
    }
}
