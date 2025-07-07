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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Attribute\Content;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\ActiveBundle;

/**
 * @internal
 */
final class ActiveBundlesJson extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            required: ['bundles'],
            properties: [
                new Property(
                    property: 'bundles',
                    title: 'Active Bundles',
                    description: 'List of active and installed bundles in the system.',
                    type: 'array',
                    items: new Items(ref: ActiveBundle::class),
                ),
            ],
            type: 'object',
        );
    }
}
