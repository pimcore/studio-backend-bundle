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
            type: 'array',
            items: new Items(
                required: ['domain', 'isFrontendDomain'],
                properties: [
                    new Property(
                        'domain',
                        title: 'Domain',
                        description: 'The domain name.',
                        type: 'string',
                        example: 'admin'
                    ),
                    new Property(
                        'isFrontendDomain',
                        title: 'Is Frontend Domain',
                        description: 'If the domain is a frontend or admin domain.',
                        type: 'boolean',
                        example: false
                    ),
                ],
                type: 'object'
            )
        );
    }
}
