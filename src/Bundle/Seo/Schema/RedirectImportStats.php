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

#[Schema(
    schema: 'BundleSeoRedirectImportStats',
    title: 'Bundle Seo Redirects Import Statistics',
    required: ['total', 'imported', 'created', 'updated', 'errored', 'errors'],
    type: 'object'
)]
final class RedirectImportStats implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Number of total redirects', type: 'integer', example: 20)]
        public readonly int $total,
        #[Property(description: 'Number of imported redirects', type: 'integer', example: 15)]
        public readonly int $imported,
        #[Property(description: 'Number of created redirects', type: 'integer', example: 10)]
        public readonly int $created,
        #[Property(description: 'Number of updated redirects', type: 'integer', example: 5)]
        public readonly int $updated,
        #[Property(description: 'Number of errored redirects', type: 'integer', example: 2)]
        public readonly int $errored,
        #[Property(
            description: 'List of errors where index is the index of import line',
            type: 'array',
            items: new Items(
                type: 'object',
                example: ['3' => 'Invalid source URL', '5' => 'Target URL already exists']
            )
        )]
        public readonly array $errors = [],
    ) {

    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getImported(): int
    {
        return $this->imported;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function getErrored(): int
    {
        return $this->errored;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
