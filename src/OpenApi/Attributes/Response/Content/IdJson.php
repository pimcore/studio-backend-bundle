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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class IdJson extends JsonContent
{
    public function __construct(string $description = '', string $property = 'id')
    {
        parent::__construct(
            required: [$property],
            properties: [
                new Property(
                    property: $property,
                    title: $property,
                    description: $description,
                    type: 'integer',
                    example: 66
                ),
            ],
            type: 'object',
        );
    }
}
