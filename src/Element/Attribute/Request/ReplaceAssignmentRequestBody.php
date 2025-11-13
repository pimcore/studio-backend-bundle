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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Attribute\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\ElementUsageBaseItem;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ReplaceAssignmentRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            content: new JsonContent(
                properties: [
                    new Property(
                        property: 'targetType',
                        type: 'string',
                        enum: ElementTypes::ALLOWED_TYPES,
                        example: ElementTypes::TYPE_DATA_OBJECT
                    ),
                    new Property(
                        property: 'targetId',
                        type: 'integer',
                        example: '8'
                    ),
                    new Property(
                        property: 'elements',
                        type: 'array',
                        items: new Items(ref: ElementUsageBaseItem::class)
                    ),
                ],
                type: 'object'
            )
        );
    }
}
