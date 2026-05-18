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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Attribute\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class MetadataCollectionRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: false,
            content: new JsonContent(
                properties: [
                    new Property(
                        property: 'searchTerm',
                        description: 'Global search term applied across all fields',
                        type: 'string',
                        example: 'author',
                        nullable: true,
                    ),
                    new Property(
                        property: 'columnFilters',
                        description: 'Per-column filters',
                        type: 'array',
                        items: new Items(
                            properties: [
                                new Property(
                                    property: 'key',
                                    type: 'string',
                                    example: 'name',
                                ),
                                new Property(
                                    property: 'type',
                                    type: 'string',
                                    example: 'like',
                                ),
                                new Property(
                                    property: 'filterValue',
                                    type: 'string',
                                    example: 'author',
                                ),
                            ],
                            type: 'object',
                        ),
                        example: '[{"key":"name","type":"like","filterValue":"author"}]',
                        nullable: true,
                    ),
                    new Property(
                        property: 'sortFilter',
                        description: 'Sort configuration',
                        properties: [
                            new Property(
                                property: 'key',
                                type: 'string',
                                example: 'name',
                            ),
                            new Property(
                                property: 'direction',
                                type: 'string',
                                example: 'ASC',
                            ),
                        ],
                        type: 'object',
                        example: '{"key":"name","direction":"ASC"}',
                        nullable: true,
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
