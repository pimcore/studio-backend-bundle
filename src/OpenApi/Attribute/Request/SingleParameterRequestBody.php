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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class SingleParameterRequestBody extends RequestBody
{
    public function __construct(string $parameterName, mixed $example, string $type = 'string')
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                properties: [
                    new Property(
                        $parameterName,
                        type: $type,
                        example: $example,
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
