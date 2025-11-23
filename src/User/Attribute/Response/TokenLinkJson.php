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

namespace Pimcore\Bundle\StudioBackendBundle\User\Attribute\Response;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class TokenLinkJson extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            required: ['link'],
            properties: [
                new Property(
                    'link',
                    title: 'Token link URL',
                    description: 'Token link URL including the generated token as parameter.',
                    type: 'string',
                    example: 'https://example.com/login?token=abcdef'
                ),
            ],
            type: 'object'
        );
    }
}
