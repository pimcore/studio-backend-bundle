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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Attribute\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Grid\ColumnSchema;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column as DataObjectColumn;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Filter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\ListOfInteger;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleBoolean;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleString;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class SavedSearchRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['name', 'columns'],
                properties: [
                    new SingleString('name'),
                    new SingleString('description'),
                    new SingleString('classId'),
                    new SingleBoolean('shareGlobal'),
                    new SingleBoolean('createMenuShortcut'),
                    new ListOfInteger('sharedUsers'),
                    new ListOfInteger('sharedRoles'),
                    new Property(
                        property: 'columns',
                        type: 'array',
                        items: new Items(
                            anyOf: [
                                new Schema(ref: ColumnSchema::class),
                                new Schema(ref: DataObjectColumn::class),
                            ]
                        ),
                    ),
                    new Property(
                        property: 'filter',
                        ref: Filter::class,
                        type: 'object',
                        nullable: true,
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
