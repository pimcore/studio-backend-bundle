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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
final readonly class ZipPathParameter
{
    public function __construct(
        #[NotBlank]
        private string $path,
    ) {
        $this->validate();
    }

    public function getPath(): string
    {
        return $this->path;
    }

    private function validate(): void
    {
        // TODO Can this be a security risk?
        if (!is_file($this->path) || strtolower(substr($this->path, -4)) !== '.zip') {
            throw new ForbiddenException();
        }
    }
}
