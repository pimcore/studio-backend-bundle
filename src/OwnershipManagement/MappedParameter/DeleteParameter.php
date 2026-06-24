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
    schema: 'OwnershipDeleteParameter',
    title: 'Ownership Delete Parameter',
    required: ['ids'],
    type: 'object',
)]
final readonly class DeleteParameter
{
    /**
     * @param string[] $ids
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        #[Property(
            description: 'Identifiers of the configurations to delete',
            type: 'array',
            items: new Items(type: 'string')
        )]
        private array $ids,
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
}
