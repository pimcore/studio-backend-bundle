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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'CustomLayoutNew',
    title: 'Schema used to create custom layouts',
    required: [
        'name',
        'classId',
    ],
    type: 'object'
)]
final readonly class CustomLayoutNew
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'My Custom Layout')]
        private string $name,
        #[Property(description: 'Data object class id', type: 'integer', example: 'CAR')]
        private string $classId
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClassId(): string
    {
        return $this->classId;
    }
}
