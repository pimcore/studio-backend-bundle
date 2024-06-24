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

namespace Pimcore\Bundle\StudioBackendBundle\User\MappedParameter;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
final readonly class UpdatePasswordParameter
{
    public function __construct(
        #[NotBlank]
        private string $password,
        #[NotBlank]
        private string $password_confirmation
    ) {
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getPasswordConfirmation(): string
    {
        return $this->password_confirmation;
    }
}
