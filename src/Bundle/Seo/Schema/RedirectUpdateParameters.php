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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\SeoBundle\Model\Redirect as CoreRedirect;

/**
 * @internal
 */
#[Schema(
    schema: 'BundleSeoRedirectUpdate',
    title: 'Bundle Seo Redirect Update',
    required: [
        'type', 'sourceSite', 'source', 'targetSite', 'target',
        'statusCode', 'priority', 'regex', 'active', 'passThroughParameters', 'expiry',
    ],
    type: 'object'
)]
final readonly class RedirectUpdateParameters
{
    public function __construct(
        #[Property(description: 'Type of redirect', type: 'string', example: CoreRedirect::TYPE_ENTIRE_URI)]
        public string $type = CoreRedirect::TYPE_ENTIRE_URI,
        #[Property(description: 'ID of the source site', type: 'integer', example: 1)]
        public ?int $sourceSite = null,
        #[Property(description: 'Source URL', type: 'string', example: '/old-path')]
        public ?string $source = null,
        #[Property(description: 'ID of the target site', type: 'integer', example: 1)]
        public ?int $targetSite = null,
        #[Property(description: 'Target URL', type: 'string', example: '/new-path')]
        public ?string $target = null,
        #[Property(description: 'Status code', type: 'integer', example: 301)]
        public int $statusCode = 301,
        #[Property(description: 'Priority', type: 'integer', example: 8)]
        public int $priority = 1,
        #[Property(description: 'Whether the redirect uses regex', type: 'boolean', example: false)]
        public bool $regex = false,
        #[Property(description: 'Whether the redirect is active', type: 'boolean', example: true)]
        public bool $active = true,
        #[Property(description: 'Whether to pass through parameters', type: 'boolean', example: false)]
        public bool $passThroughParameters = false,
        #[Property(description: 'Expiry date in timestamp format', type: 'integer', example: 1712345678)]
        public int|string|null $expiry = null,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSourceSite(): ?int
    {
        return $this->sourceSite;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getTargetSite(): ?int
    {
        return $this->targetSite;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isRegex(): bool
    {
        return $this->regex;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isPassThroughParameters(): bool
    {
        return $this->passThroughParameters;
    }

    public function getExpiry(): int|string|null
    {
        return $this->expiry;
    }
}
