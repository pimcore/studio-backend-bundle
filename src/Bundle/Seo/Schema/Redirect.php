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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'BundleSeoRedirect',
    title: 'Bundle Seo Redirect',
    required: [
        'id', 'type', 'source', 'sourceSite', 'passThroughParameters', 'target', 'targetSite',
        'statusCode', 'priority', 'regex', 'active', 'expiry', 'creationDate', 'modificationDate',
        'userOwner', 'userModification',
    ],
    type: 'object'
)]
final class Redirect implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 1)]
        public readonly ?int $id,
        #[Property(description: 'Type of redirect', type: 'string', example: CoreRedirect::TYPE_AUTO_CREATE)]
        public readonly string $type,
        #[Property(description: 'Source URL', type: 'string', example: '/old-path')]
        public readonly ?string $source,
        #[Property(description: 'ID of the source site', type: 'integer', example: 1)]
        public readonly ?int $sourceSite,
        #[Property(description: 'Whether to pass through parameters', type: 'boolean', example: true)]
        public readonly bool $passThroughParameters,
        #[Property(description: 'Target URL', type: 'string', example: '/new-path')]
        public readonly ?string $target,
        #[Property(description: 'ID of the target site', type: 'integer', example: 1)]
        public readonly ?int $targetSite,
        #[Property(description: 'Status code', type: 'integer', example: 301)]
        public readonly int $statusCode,
        #[Property(description: 'Priority', type: 'integer', example: 8)]
        public readonly int $priority,
        #[Property(description: 'Whether the redirect uses regex', type: 'boolean', example: true)]
        public readonly ?bool $regex,
        #[Property(description: 'Whether the redirect is active', type: 'boolean', example: true)]
        public readonly bool $active,
        #[Property(description: 'Expiry date in timestamp format', type: 'integer', example: 1712345678)]
        public readonly int|string|null $expiry,
        #[Property(description: 'Creation date in timestamp format', type: 'integer', example: 1712345678)]
        public readonly ?int $creationDate,
        #[Property(description: 'Modification date in timestamp format', type: 'integer', example: 1712345678)]
        public readonly ?int $modificationDate,
        #[Property(description: 'ID of the user who owns the redirect', type: 'integer', example: 1)]
        public readonly ?int $userOwner,
        #[Property(description: 'ID of the user who last modified the redirect', type: 'integer', example: 1)]
        public readonly ?int $userModification
    ) {

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getSourceSite(): ?int
    {
        return $this->sourceSite;
    }

    public function isPassThroughParameters(): bool
    {
        return $this->passThroughParameters;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function getTargetSite(): ?int
    {
        return $this->targetSite;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getRegex(): ?bool
    {
        return $this->regex;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getExpiry(): int|string|null
    {
        return $this->expiry;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getUserOwner(): ?int
    {
        return $this->userOwner;
    }

    public function getUserModification(): ?int
    {
        return $this->userModification;
    }
}
