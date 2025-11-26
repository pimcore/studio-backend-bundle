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
use Pimcore\Bundle\StudioBackendBundle\Export\Schema\ExportAllFilter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateBooleanProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateIntegerProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateObjectProperty;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateStringProperty;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class PatchDataObjectFolderRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['data', 'classId'],
                properties: [
                    new Property(
                        property: 'data',
                        properties: [
                            new UpdateIntegerProperty('parentId'),
                            new UpdateIntegerProperty('index', 0),
                            new UpdateStringProperty('key'),
                            new UpdateStringProperty('locked'),
                            new UpdateStringProperty('childrenSortBy'),
                            new UpdateStringProperty('childrenSortOrder'),
                            new UpdateBooleanProperty('published'),
                            new UpdateObjectProperty('editableData'),
                        ],
                        type: 'object',
                    ),
                    new Property(
                        property: 'filters',
                        ref: ExportAllFilter::class,
                        type: 'object'
                    ),
                    new Property(
                        property: 'classId',
                        type: 'string',
                        example: 'CAR',
                        nullable: false
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
