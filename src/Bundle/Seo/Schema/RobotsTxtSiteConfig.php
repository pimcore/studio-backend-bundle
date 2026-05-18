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

/**
 * @internal
 */
#[Schema(
    schema: 'BundleSeoRobotsTxtSiteConfig',
    title: 'Bundle Seo Robots Txt Site Config',
    required: ['siteId', 'content'],
    type: 'object'
)]
final readonly class RobotsTxtSiteConfig
{
    public function __construct(
        #[Property(description: 'Site ID (0 for default site)', type: 'integer', example: 0)]
        private int $siteId,
        #[Property(description: 'Robots.txt content for this site', type: 'string', example: '')]
        private string $content,
    ) {
    }

    public function getSiteId(): int
    {
        return $this->siteId;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
