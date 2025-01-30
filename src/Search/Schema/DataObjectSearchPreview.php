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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'SimpleSearchDataObjectDetail',
    required: ['class', 'objectData'],
    type: 'object'
)]
final class DataObjectSearchPreview extends SimpleSearchPreview
{
    public function __construct(
        int $id,
        string $elementType,
        string $type,
        ?int $userOwner,
        ?string $userOwnerName,
        ?int $userModification,
        ?string $userModificationName,
        ?int $creationDate,
        ?int $modificationDate,
        #[Property(description: 'Class name and Id', type: 'string', example: 'Car [CAR]')]
        private readonly ?string $class,
        #[Property(description: 'Detail object data', type: 'object', example: ['fieldKey' => 'field value'])]
        private readonly array $objectData = [],
    ) {
        parent::__construct(
            $id,
            $elementType,
            $type,
            $userOwner,
            $userOwnerName,
            $userModification,
            $userModificationName,
            $creationDate,
            $modificationDate
        );
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getObjectData(): array
    {
        return $this->objectData;
    }
}
