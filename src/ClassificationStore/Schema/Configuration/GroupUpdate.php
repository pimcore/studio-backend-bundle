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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\AbsentValue;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function is_array;

/**
 * @internal
 */
#[Schema(
    schema: 'ClassificationStoreConfigurationGroupUpdate',
    title: 'Classification Store Configuration Group Update',
    required: ['name', 'description'],
    type: 'object'
)]
final readonly class GroupUpdate
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        #[Property(description: 'Name of the group', type: 'string', example: 'My Group')]
        private string $name,
        #[Property(
            description: 'Description of the group',
            type: 'string',
            example: 'Group description',
            nullable: true
        )]
        private ?string $description = null,
        #[Property(
            description: 'Free-form pairs of the group. Pimcore reads no member of it and no member is'
                . ' reserved. Omitting it keeps a stored object alone, a supplied object replaces it whole'
                . ' rather than merging into it - so one rebuilt from a form without a member drops that'
                . ' member - and an explicit null clears it altogether.',
            type: 'object',
            example: ['source' => 'erp'],
            nullable: true
        )]
        private mixed $customMetadata = AbsentValue::ABSENT,
        // only refused here, never a property of this schema; the default stands for an absent key
        mixed $identifier = AbsentValue::ABSENT,
    ) {
        if (empty($this->name)) {
            throw new InvalidArgumentException('Name cannot be empty.');
        }

        if ($this->customMetadata !== AbsentValue::ABSENT
            && $this->customMetadata !== null
            && !is_array($this->customMetadata)) {
            throw new InvalidArgumentException('customMetadata has to be an object or null.');
        }

        if ($identifier !== AbsentValue::ABSENT) {
            throw new InvalidArgumentException('Identifier is assigned by the system and cannot be set.');
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

    public function hasCustomMetadata(): bool
    {
        return $this->customMetadata !== AbsentValue::ABSENT;
    }

    /**
     * Carries a value only when {@see self::hasCustomMetadata()} reports one.
     *
     * @return array<string, mixed>|null
     */
    public function getCustomMetadata(): ?array
    {
        return $this->customMetadata;
    }
}
