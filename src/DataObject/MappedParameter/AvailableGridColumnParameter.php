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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter;

/**
 * @internal
 */
final readonly class AvailableGridColumnParameter
{
    public function __construct(
        private ?string $classId = null,
        private ?int $folderId = null,
    ) {
    }

    public function getClassId(): ?string
    {
        return $this->classId;
    }

    public function getFolderId(): ?int
    {
        return $this->folderId;
    }
}
