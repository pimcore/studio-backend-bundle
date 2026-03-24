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

/**
 * @internal
 */
#[Schema(
    schema: 'BundleSeoRobotsTxtUpdate',
    title: 'Bundle Seo Robots Txt Update',
    required: ['data'],
    type: 'object'
)]
final readonly class RobotsTxtUpdateParameters
{
    /**
     * @param RobotsTxtSiteConfig[] $data
     */
    public function __construct(
        #[Property(
            description: 'Robots.txt configuration per site',
            type: 'array',
            items: new Items(ref: RobotsTxtSiteConfig::class),
        )]
        private array $data = [],
    ) {
    }

    /**
     * @return RobotsTxtSiteConfig[]
     */
    public function getData(): array
    {
        return $this->data;
    }
}
