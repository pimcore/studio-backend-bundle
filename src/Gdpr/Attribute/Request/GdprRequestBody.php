<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 * @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;

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
                required: ['providers'],
                properties: [
                    new Property(
                        property: 'providers',
                        description: 'A list of provider keys to search (e.g., data_objects, emails, etc.)',
                        type: 'array',
                        items: new Items(
                            type: 'string', 
                            example: 'data_objects'
                            )
                    ),
                    new Property(
                        property: 'searchTerms',
                        description: 'The object containing the search values. Can also be empty.',
                        ref: SearchTerms::class,
                        type: 'object',
                        nullable: true
                    ),
                ],
                type: 'object',
            ),
        );
    }
}

