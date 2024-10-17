<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\Util\Constant\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Filter;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class CsvExportFolderRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            content: new JsonContent(
                properties: [
                    new Property(property: 'folders', type: 'array', items: new Items(type: 'integer'), example: [83]),
                    new Property(
                        property: 'columns',
                        type: 'array',
                        items: new Items(ref: Column::class)
                    ),
                    new Property(
                        property: 'filters',
                        ref: Filter::class,
                        type: 'object'
                    ),
                    new Property(property: 'config', properties: [
                        new Property(property: StepConfig::SETTINGS_DELIMITER->value, type: 'string', example: ';'),
                        new Property(
                            property: StepConfig::SETTINGS_HEADER->value,
                            type: 'enum',
                            enum: StepConfig::values(),
                            example: StepConfig::SETTINGS_HEADER_TITLE->value
                        ),
                    ], type: 'object'),
                ],
                type: 'object'
            )
        );
    }
}
