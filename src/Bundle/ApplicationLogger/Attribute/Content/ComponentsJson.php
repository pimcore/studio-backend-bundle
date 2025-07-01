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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Attribute\Content;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class ComponentsJson extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            required: ['items'],
            properties: [
                new Property(
                    property: 'items',
                    title: 'Components',
                    description: 'Log components used in the ApplicationLogger.',
                    type: 'array',
                    items: new Items(type: 'string', example: 'application'),
                ),
            ],
            type: 'object',
        );
    }
}
