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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request;

use Attribute;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Property\FilterProperty;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class GdprRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(

                required: ['providers', 'searchTerms'],
                properties: [
                    new Property(
                        property: 'providers',
                        description: 'A list of provider keys to search',
                        type: 'array',
                        items: new Items(
                            type: 'string',
                            example: 'pimcore_users'
                        )
                    ),

                    new Property(
                        property: 'searchTerms',
                        ref: SearchTerms::class,
                        description: 'The object containing the search values.',
                        type: 'object'
                    ),

                    new FilterProperty(),
                ],
                type: 'object',
            ),
        );
    }
}
