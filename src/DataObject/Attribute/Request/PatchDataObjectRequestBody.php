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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Attribute\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateBooleanProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateIntegerProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateObjectProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateStringProperty;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class PatchDataObjectRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['data'],
                properties: [
                    new Property(
                        property: 'data',
                        type: 'array',
                        items: new Items(
                            required: ['id'],
                            properties: [
                                new Property(
                                    property: 'id',
                                    description: 'Data Object ID',
                                    type: 'integer',
                                    example: 83
                                ),
                                new UpdateIntegerProperty('parentId'),
                                new UpdateIntegerProperty('index', 0),
                                new UpdateStringProperty('key'),
                                new Property(property:'task', type: 'string', enum: ElementSaveTasks::values()),
                                new Property(
                                    property: 'coauthorType',
                                    description: 'Optional coauthor type stored on versions created by this save '
                                        . '(e.g. agent)',
                                    type: 'string',
                                    example: 'agent'
                                ),
                                new Property(
                                    property: 'coauthor',
                                    description: 'Optional coauthor identifier stored on versions created by this save',
                                    type: 'string',
                                    example: 'product-data-agent'
                                ),
                                new UpdateStringProperty('locked'),
                                new UpdateStringProperty('childrenSortBy'),
                                new UpdateStringProperty('childrenSortOrder'),
                                new UpdateBooleanProperty('published'),
                                new UpdateObjectProperty('editableData'),
                            ],
                            type: 'object',
                        ),
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
