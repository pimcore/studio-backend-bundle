<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 * @license    Pimcore Open Core License (POCL)
 */

declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'GDPR Export Job Collection',
    description: 'A collection of background export jobs that were started.',
    type: 'object',
    required: ['items']
)]
final class GdprExportJobCollection
{
    /**
     * @param array<GdprExportJob> $items
     */
    public function __construct(
        #[Property(
            description: 'List of started export jobs',
            type: 'array',
            items: new Items(ref: GdprExportJob::class)
        )]
    
    private readonly array $items,
    ) {
    }

    /**
     * @return array<GdprExportJob>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}