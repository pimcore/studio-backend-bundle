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
    schema: 'ClassificationStoreConfigurationStoreUpdate',
    title: 'Classification Store Configuration Store Update',
    required: ['name', 'description'],
    type: 'object'
)]
final readonly class StoreUpdate
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        #[Property(description: 'Name of the store', type: 'string', example: 'My Store')]
        private string $name,
        #[Property(description: 'Description of the store', type: 'string', example: 'Store description')]
        private ?string $description = null,
    ) {
        if (empty($this->name)) {
            throw new InvalidArgumentException('Name cannot be empty.');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
