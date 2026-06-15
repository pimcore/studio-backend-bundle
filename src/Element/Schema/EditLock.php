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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'EditLock',
    required: ['isLocked'],
    type: 'object'
)]
final class EditLock implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Whether the element is currently edit-locked', type: 'boolean', example: true)]
        private readonly bool $isLocked,
        #[Property(description: 'ID of the user holding the lock', type: 'integer', example: 2, nullable: true)]
        private readonly ?int $userId = null,
        #[Property(
            description: 'Timestamp when the lock was created',
            type: 'integer',
            example: 1634025600,
            nullable: true,
        )]
        private readonly ?int $date = null,
        #[Property(ref: EditLockUser::class, description: 'User holding the lock', nullable: true)]
        private readonly ?EditLockUser $user = null,
    ) {
    }

    public function getIsLocked(): bool
    {
        return $this->isLocked;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getDate(): ?int
    {
        return $this->date;
    }

    public function getUser(): ?EditLockUser
    {
        return $this->user;
    }
}
