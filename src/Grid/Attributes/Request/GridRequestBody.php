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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Attributes\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Property\SingleInteger;

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
                required: ['folderId', 'gridConfig'],
                properties: [
                    new SingleInteger(propertyName: 'folderId'),
                    new Property(
                        property: 'gridConfig',
                        properties: [
                            new Property(
                                property: 'columns',
                                type: 'array',
                                items: new Items(ref: Column::class)
                            ),
                        ],
                        type: 'object'
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
