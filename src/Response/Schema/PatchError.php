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
    schema: 'PatchError',
    title: 'PatchError',
    description: 'Response for PATCH requests with errors',
    type: 'object'
)]
final readonly class PatchError
{
    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 83)]
        protected int $id,
        #[Property(description: 'Message', type: 'string', example: 'I am an error message')]
        protected string $message
    ) {
    }
}
