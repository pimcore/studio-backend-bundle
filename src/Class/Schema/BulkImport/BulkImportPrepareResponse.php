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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkImport;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkExport\BulkExportAvailableItem;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'BulkImportPrepareResponse',
    title: 'Bulk Import Prepare Response',
    required: ['fileId', 'items'],
    type: 'object'
)]
final class BulkImportPrepareResponse implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    /**
     * @param BulkExportAvailableItem[] $items
     */
    public function __construct(
        #[Property(
            description: 'Unique file identifier for the stored import file',
            type: 'string',
            example: '6792e2b43f0a7'
        )]
        private readonly string $fileId,
        #[Property(
            description: 'List of importable items found in the file',
            type: 'array',
            items: new Items(ref: BulkExportAvailableItem::class)
        )]
        private readonly array $items,
    ) {
    }

    public function getFileId(): string
    {
        return $this->fileId;
    }

    /**
     * @return BulkExportAvailableItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
