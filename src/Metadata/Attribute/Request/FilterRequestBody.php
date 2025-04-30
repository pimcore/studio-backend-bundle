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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: false,
            content: new JsonContent(
                properties: [
                    new Property(
                        property: 'filter',
                        type: 'string',
                        example: 'author',
                        nullable: true,
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
