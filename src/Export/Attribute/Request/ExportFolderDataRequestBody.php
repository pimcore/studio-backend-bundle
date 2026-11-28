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
use Pimcore\Bundle\StudioBackendBundle\Export\Schema\ExportAllFilter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ExportFolderDataRequestBody extends RequestBody
{
    public function __construct(bool $addDelimiter = true)
    {
        $configProperties = [
            new Property(
                property: StepConfig::SETTINGS_HEADER->value,
                type: 'string',
                enum: [
                    StepConfig::SETTINGS_HEADER_NO_HEADER->value,
                    StepConfig::SETTINGS_HEADER_TITLE->value,
                    StepConfig::SETTINGS_HEADER_NAME->value,
                ],
                example: StepConfig::SETTINGS_HEADER_TITLE->value
            ),
        ];
        if ($addDelimiter) {
            $configProperties[] = new Property(
                property: StepConfig::SETTINGS_DELIMITER->value,
                type: 'string',
                example: ';'
            );
        }
        $configProperties[] = new Property(
            property: StepConfig::SETTINGS_FILE_NAME->value,
            type: 'string',
            example: $addDelimiter ? 'my-grid-export.csv' : 'my-grid-export.xlsx',
            nullable: true
        );

        parent::__construct(
            content: new JsonContent(
                properties: [
                    new Property(
                        property: 'columns',
                        type: 'array',
                        items: new Items(ref: Column::class)
                    ),
                    new Property(
                        property: 'filters',
                        ref: ExportAllFilter::class,
                        type: 'object'
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
