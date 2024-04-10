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

namespace Pimcore\Bundle\StudioApiBundle\Dto;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'Unauthorized',
    title: 'Unauthorized',
    description: 'Bad credentials or missing token',
    type: 'object'
)]
final readonly class Unauthorized
{
    public function __construct(
        #[Property(description: 'Message', type: 'string')]
        private string $message
    ) {

    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
