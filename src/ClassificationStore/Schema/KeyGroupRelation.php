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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'Classification Store KeyGroupRelation',
    required: [
        'keyId',
        'groupId',
        'keyName',
        'groupName',
    ],
    type: 'object'
)]
final class KeyGroupRelation implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Key ID', type: 'integer', example: 42)]
        private readonly int $keyId,
        #[Property(description: 'Group ID', type: 'integer', example: 42)]
        private readonly int $groupId,
        #[Property(description: 'Key Name', type: 'string', example: 'value')]
        private readonly string $keyName,
        #[Property(description: 'Group Name', type: 'string', example: 'value')]
        private readonly string $groupName,
        #[Property(description: 'Key Description', type: 'string', example: 'value')]
        private readonly ?string $keyDescription,
        #[Property(description: 'Key Description', type: 'string', example: 'value')]
        private readonly ?string $groupDescription,
    ) {
    }

    public function getKeyId(): int
    {
        return $this->keyId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function getKeyName(): string
    {
        return $this->keyName;
    }

    public function getGroupName(): string
    {
        return $this->groupName;
    }

    public function getKeyDescription(): ?string
    {
        return $this->keyDescription;
    }

    public function getGroupDescription(): ?string
    {
        return $this->groupDescription;
    }
}
