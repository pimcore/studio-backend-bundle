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

use InvalidArgumentException;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'AuthenticationToken',
    title: 'Authentication Token',
    description: 'Token for authentication',
    required: ['token'],
    type: 'object'
)]
final readonly class AuthenticationToken
{
    public function __construct(
        #[Property(description: 'Token', type: 'string', example: 'abc123xyz')]
        private string $token
    ) {
        if (empty($this->token)) {
            throw new InvalidArgumentException('Token cannot be empty');
        }
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
