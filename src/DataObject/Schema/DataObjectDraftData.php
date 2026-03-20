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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'DataObjectDraftData',
    required: [
        'id',
        'modificationDate',
        'isAutoSave',
    ],
    type: 'object'
)]
final readonly class DataObjectDraftData
{
    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 83)]
        private int $id,
        #[Property(description: 'Modification date', type: 'integer', example: 1634025600)]
        private int $modificationDate,
        #[Property(description: 'Is auto save', type: 'boolean', example: false)]
        private bool $isAutoSave
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getModificationDate(): int
    {
        return $this->modificationDate;
    }

    public function getIsAutoSave(): bool
    {
        return $this->isAutoSave;
    }
}
