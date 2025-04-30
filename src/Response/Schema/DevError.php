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

namespace Pimcore\Bundle\StudioBackendBundle\Response\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'DevError',
    title: 'DevError',
    description: 'Error with details for developers',
    required: ['message', 'details'],
    type: 'object'
)]
final readonly class DevError
{
    public function __construct(
        #[Property(description: 'Message', type: 'string', example: 'I got a bad feeling about this')]
        protected string $message,
        #[Property(description: 'Details', type: 'string', example: 'Search your feelings. (Stack trace)')]
        protected string $details
    ) {

    }
}
