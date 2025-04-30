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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class CollectionJson extends JsonContent
{
    public function __construct(Property $collection)
    {
        parent::__construct(
            required: ['totalItems', $collection->property],
            properties: [
                new Property('totalItems', title: 'totalItems', type: 'integer', example: 666),
                $collection,
            ],
            type: 'object',
        );
    }
}
