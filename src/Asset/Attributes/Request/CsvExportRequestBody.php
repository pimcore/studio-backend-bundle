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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\Util\Constants\Csv;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class CsvExportRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            content: new JsonContent(
                properties: [
                    new Property(property: 'assets', type: 'array', items: new Items(type: 'integer'), example: [83]),
                    new Property(
                        property: 'gridConfig',
                        type: 'array',
                        items: new Items(ref: Column::class)
                    ),
                    new Property(property: 'settings', properties: [
                        new Property(property: Csv::SETTINGS_DELIMITER->value, type: 'string', example: ';'),
                        new Property(
                            property: Csv::SETTINGS_HEADER->value,
                            type: 'enum',
                            enum: Csv::values(),
                            example: Csv::SETTINGS_HEADER_TITLE->value
                        ),
                    ], type: 'object'),
                ],
                type: 'object'
            )
        );
    }
}
