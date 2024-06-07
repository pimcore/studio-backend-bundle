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
