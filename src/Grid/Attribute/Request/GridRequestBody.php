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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Attribute\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Filter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleInteger;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class GridRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['folderId'],
                properties: [
                    new SingleInteger(propertyName: 'folderId'),
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
                ],
                type: 'object',
            ),
        );
    }
}
