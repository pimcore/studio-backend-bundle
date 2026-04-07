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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'BundleSeoRobotsTxtConfig',
    title: 'Bundle Seo Robots Txt Config',
    required: ['data', 'onFileSystem'],
    type: 'object'
)]
final class RobotsTxtConfig implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    /**
     * @param RobotsTxtSiteConfig[] $data
     */
    public function __construct(
        #[Property(
            description: 'Robots.txt configuration per site',
            type: 'array',
            items: new Items(ref: RobotsTxtSiteConfig::class),
        )]
        private readonly array $data,
        #[Property(
            description: 'Whether a physical robots.txt file exists on the filesystem',
            type: 'boolean',
            example: false,
        )]
        private readonly bool $onFileSystem,
    ) {
    }

    /**
     * @return RobotsTxtSiteConfig[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function isOnFileSystem(): bool
    {
        return $this->onFileSystem;
    }
}
