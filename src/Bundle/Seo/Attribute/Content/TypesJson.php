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
use Pimcore\Bundle\SeoBundle\Model\Redirect;

/**
 * @internal
 */
final class TypesJson extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            required: ['types'],
            properties: [
                new Property(
                    'types',
                    title: 'Redirect types',
                    description: 'List of redirect types used in the PimcoreSeoBundle.',
                    type: 'array',
                    items: new Items(
                        type: 'string',
                        example: Redirect::TYPE_PATH
                    ),
                ),
            ],
            type: 'object'
        );
    }
}
