<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getIsAutoSave(): bool
    {
        return $this->isAutoSave;
    }
}
