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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Attribute\Content;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class PrioritiesJson extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            required: ['priorities'],
            properties: [
                new Property(
                    'priorities',
                    title: 'Redirect priority',
                    description: 'Redirect priority used in the PimcoreSeoBundle.',
                    type: 'array',
                    items: new Items(
                        type: 'integer',
                        example: 1
                    ),
                ),
            ],
            type: 'object'
        );
    }
}
