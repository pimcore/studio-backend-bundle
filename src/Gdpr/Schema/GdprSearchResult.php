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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'GDPR Search Result',
    description: 'Represents search results from a single provider',
    required: ['providerKey', 'results'],
    type: 'object',
)]
final class GdprSearchResult
{
    /**
     * @param array<array<string, mixed>> $results
     */
    public function __construct(
        #[Property(
            description: 'The key of the provider these results came from single provider',
            type: 'string',
            example: 'data_objects'
        )]
        private string $providerKey,

        #[Property(
            description: 'The list of results found by this provider',
            type: 'array',
            items: new Items(type: 'object', example: '{"id": 1, "path": "/data/customer/1"}')
        )]
        private array $results,
    ) {
    }

    public function getProviderKey(): string
    {
        return $this->providerKey;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
