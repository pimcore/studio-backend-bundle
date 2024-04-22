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

namespace Pimcore\Bundle\StudioApiBundle\Response\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'Error',
    title: 'Error',
    description: 'Bad credentials or missing token, bad request, method not allowed, etc.',
    type: 'object'
)]
final readonly class Error
{
    public function __construct(
        #[Property(description: 'Message', type: 'string', example: 'I am an error message')]
        protected string $message
    ) {
    }
}
