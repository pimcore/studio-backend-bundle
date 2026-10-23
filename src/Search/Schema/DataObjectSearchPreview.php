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
