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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;

#[Schema(
    title: 'Blocklist',
    required: ['email'],
    type: 'object'
)]
final readonly class BlocklistEntryRequest
{
    public function __construct(
        #[Property(description: 'email address', type: 'string', example: 'blockedEmail@fishy-domain.com')]
        private string $email
    ) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new EnvironmentException('Invalid email format');
        }
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
