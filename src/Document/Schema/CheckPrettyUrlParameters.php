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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
#[Schema(
    schema: 'CheckPrettyUrl',
    title: 'Check Pretty URL',
    required: ['prettyUrl'],
    type: 'object'
)]
final readonly class CheckPrettyUrlParameters
{
    public function __construct(
        #[Property(description: 'Pretty URL to check', type: 'string', example: '/my-pretty-url')]
        #[NotBlank(message: 'The prettyUrl must not be blank.')]
        private string $prettyUrl,
    ) {
    }

    public function getPrettyUrl(): string
    {
        return $this->prettyUrl;
    }
}
