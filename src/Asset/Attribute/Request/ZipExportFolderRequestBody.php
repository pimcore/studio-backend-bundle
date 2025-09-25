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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Export\Schema\ExportAllFilter;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ZipExportFolderRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            content: new JsonContent(
                properties: [
                    new Property(property: 'folders', type: 'array', items: new Items(type: 'integer'), example: [83]),
                    new Property(
                        property: 'filters',
                        ref: ExportAllFilter::class,
                        type: 'object'
                    ),
                ],
                type: 'object'
            )
        );
    }
}
