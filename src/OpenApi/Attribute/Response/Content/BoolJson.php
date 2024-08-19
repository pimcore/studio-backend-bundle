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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class BoolJson extends JsonContent
{
    public function __construct(string $name = '', string $description = '')
    {
        parent::__construct(
            required: [$name],
            properties: [
                new Property(
                    $name,
                    title: $name,
                    description: $description,
                    type: 'boolean',
                    example: true
                ),
            ],
            type: 'object',
        );
    }
}
