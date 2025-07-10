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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Attribute\Response;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class DomainList extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            required: ['domains'],
            properties: [
                new Property(
                    'domains',
                    title: 'Domain list',
                    description: 'List if all available domains in the system for translations.',
                    type: 'array',
                    items: new Items(
                        type: 'string',
                        example: 'studio'
                    ),
                ),
            ],
            type: 'object'
        );
    }
}
