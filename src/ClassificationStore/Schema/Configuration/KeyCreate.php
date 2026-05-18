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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
#[Schema(
    schema: 'ClassificationStoreConfigurationKeyCreate',
    title: 'Classification Store Configuration Key Create',
    required: ['name', 'storeId'],
    type: 'object'
)]
final readonly class KeyCreate
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        #[Property(description: 'Name of the key', type: 'string', example: 'My Key')]
        private string $name,
        #[Property(description: 'ID of the store this key belongs to', type: 'integer', example: 1)]
        private int $storeId,
    ) {
        if (empty($this->name)) {
            throw new InvalidArgumentException('Name cannot be empty.');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }
}
