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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;

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
                    new Property(
                        property: 'name',
                        type: 'string',
                        example: 'Quality_Attributes'
                    ),
                    new Property(
                        property: 'sortOrder',
                        type: 'string',
                        example: 'ASC',
                    ),
                    new Property(
                        property: 'sortBy',
                        type: 'string',
                        example: 'ASC'
                    ),
                    new Property(
                        property: 'filter',
                        type: 'string',
                        example: 'quality',
                    ),
                    new Property(
                        property: 'reportLimit',
                        type: 'integer',
                        example: 10
                    ),
                    new Property(
                        property: 'reportOffset',
                        type: 'integer',
                        example: 100
                    ),
                    new Property(
                        property: 'includeHeaders',
                        type: 'bool',
                        example: false
                    ),
                ],
                type: 'object'
            )
        );
    }
}
