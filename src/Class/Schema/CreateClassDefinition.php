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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'CreateClassDefinition',
    title: 'Schema used to create class definitions',
    required: [
        'name',
        'uid',
    ],
    type: 'object'
)]
final readonly class CreateClassDefinition
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'My Class Definition')]
        private string $name,
        #[Property(description: 'Class definition unique ID', type: 'string', example: 'my_class_definition_uid')]
        private string $uid,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUid(): string
    {
        return $this->uid;
    }
}
