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
 *
 * @deprecated since 2025.4.20 and without effect, only kept for the deprecated TokenLinkRequestBody.
 *             Will be removed in the next major version.
 */
#[Schema(
    title: 'TokenLink',
    description: 'Token link parameters',
    required: ['tokenLoginUrl'],
    type: 'object'
)]
final readonly class TokenLink
{
    public function __construct(
        #[Property(description: 'Token login URL', type: 'string', example: 'https://example.com/login')]
        private string $tokenLoginUrl,
    ) {
    }

    public function getTokenLoginUrl(): string
    {
        return $this->tokenLoginUrl;
    }
}
