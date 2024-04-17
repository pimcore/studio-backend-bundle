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
    title: 'Token',
    description: 'Token Scheme for API',
    type: 'object'
)]
final readonly class Token
{
    public function __construct(
        #[Property(description: 'Token', type: 'string', example: 'This could be your token')]
        protected string $token,
        #[Property(description: 'Lifetime in seconds', type: 'integer', format: 'int', example: 3600)]
        protected int $lifetime,
        #[Property(description: 'Username', type: 'string', example: 'shaquille.oatmeal')]
        protected string $username
    ) {
    }
}
