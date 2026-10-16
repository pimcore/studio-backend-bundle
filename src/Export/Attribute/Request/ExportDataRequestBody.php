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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Attribute\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ExportDataRequestBody extends RequestBody
{
    public function __construct(bool $addDelimiter = true)
    {
        $configProperties = [
            new Property(
                property: StepConfig::SETTINGS_HEADER->value,
                type: 'string',
                enum: StepConfig::values(),
                example: StepConfig::SETTINGS_HEADER_TITLE->value
            ),
        ];
        if ($addDelimiter) {
            $configProperties[] = new Property(
                property: StepConfig::SETTINGS_DELIMITER->value,
                type: 'string',
                example: ';'
            );
        } else {
            $configProperties[] = new Property(
                property: StepConfig::SETTINGS_SHEET_NAME->value,
                type: 'string',
                example: 'Sheet1'
            );
        }
        parent::__construct(
            content: new JsonContent(
                properties: [
                    new Property(property: 'elements', type: 'array', items: new Items(type: 'integer'), example: [83]),
                    new Property(
                        property: 'columns',
                        type: 'array',
                        items: new Items(ref: Column::class)
                    ),
                    new Property(property: 'config', properties: $configProperties, type: 'object'),
                    new Property(
                        property: 'elementType',
                        type: 'string',
                        enum: ElementTypes::ALLOWED_TYPES,
                        example: ElementTypes::TYPE_ASSET
                    ),
                    new Property(
                        property: 'classId',
                        type: 'string',
                        example: 'CAR',
                        nullable: true
                    ),
                ],
                type: 'object'
            )
        );
    }
}
