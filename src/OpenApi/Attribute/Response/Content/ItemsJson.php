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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class ItemsJson extends JsonContent
{
    public function __construct(string $itemsClass)
    {
        parent::__construct(
            required: ['items'],
            properties: [
                new Property(
                    'items',
                    type: 'array',
                    items: new Items(ref: $itemsClass)
                ),
            ],
            type: 'object',
        );
    }
}
