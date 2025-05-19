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

namespace Pimcore\Bundle\StudioBackendBundle\User\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'User Permission',
    description: 'A permission for a user or role',
    required: ['key', 'category'],
    type: 'object'
)]
final class UserPermission implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Key of the Permission', type: 'string', example: 'objects')]
        private readonly string $key,
        #[Property(description: 'Category og the Permission', type: 'string', example: 'Datahub')]
        private readonly string $category,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getCategory(): string
    {
        return $this->category;
    }
}
