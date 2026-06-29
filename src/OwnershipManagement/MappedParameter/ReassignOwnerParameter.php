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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\MappedParameter;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
#[Schema(
    schema: 'OwnershipReassignOwnerParameter',
    title: 'Ownership Reassign Owner Parameter',
    required: ['ids', 'newOwnerId'],
    type: 'object',
)]
final readonly class ReassignOwnerParameter
{
    /**
     * @param string[] $ids
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        #[Property(
            description: 'Identifiers of the configurations whose owner should be reassigned',
            type: 'array',
            items: new Items(type: 'string')
        )]
        private array $ids,
        #[Property(description: 'User ID of the new owner', type: 'integer', example: 1)]
        private int $newOwnerId,
    ) {
        if (empty($this->ids)) {
            throw new InvalidArgumentException('Ids array cannot be empty.');
        }
    }

    /**
     * @return string[]
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    public function getNewOwnerId(): int
    {
        return $this->newOwnerId;
    }
}
