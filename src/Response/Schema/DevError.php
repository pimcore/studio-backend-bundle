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
    schema: 'DevError',
    title: 'DevError',
    description: 'Error with details for developers',
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
