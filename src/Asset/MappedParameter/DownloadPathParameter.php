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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\DownloadFormats;
use Symfony\Component\Validator\Constraints\NotBlank;
use function in_array;

/**
 * @internal
 */
final readonly class DownloadPathParameter
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
        if (!is_file($this->path) && !$this->inDownloadFormats()) {
            throw new ForbiddenException();
        }
    }

    private function inDownloadFormats(): bool
    {
        return in_array(
            strtolower(substr($this->path, -3)),
            DownloadFormats::values(),
            true
        );
    }
}
