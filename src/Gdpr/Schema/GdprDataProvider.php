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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'GDPR Data Provider',
    description: 'GDPR Data Extractor search source(e.g., "Data Objects", "Pimcore user").',
    required: ['key', 'label', 'deleteOperationId'],
    type: 'object',
)]
final class GdprDataProvider implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;


    public function __construct(
        #[Property(
            description: 'Unique key of the provider',
            type: 'string',
            example: 'data_objects'
        )]
        private readonly string $key,

        #[Property(
            description: 'Label of the provider',
            type: 'string',
            example: 'Data Objects'
        )]
        private readonly string $label,

        #[Property(
            description: 'The Operation ID to call when deleting an item.',
            type: 'string',
            example: 'user_delete_by_id'
        )]
        private readonly string $deleteOperationId,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDeleteOperationId(): string
    {
        return $this->deleteOperationId;
    }
}
