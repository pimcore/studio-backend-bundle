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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;

#[Schema(
    title: 'Blocklist',
    required: ['email'],
    type: 'object'
)]
final readonly class EmailAddressParameter
{
    public function __construct(
        #[Property(description: 'email address', type: 'string', example: 'someEmail@email-domain.com')]
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
