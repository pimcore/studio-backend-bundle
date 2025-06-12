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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property;

use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class UpdateObjectProperty extends Property
{
    public function __construct(string $propertyName, string|array $example = '{ "someFieldKey": "someValue" }')
    {
        parent::__construct(
            property: $propertyName,
            type: 'object',
            example: $example,
            nullable: true,
        );
    }
}
