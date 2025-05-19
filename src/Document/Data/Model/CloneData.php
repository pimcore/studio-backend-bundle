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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Data\Model;

/**
 * @internal
 */
final readonly class CloneData
{
    public function __construct(
        private ?string $language = null,
        private bool $enableInheritance = false,
        private bool $updateReferences = false,
    ) {
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function isEnableInheritance(): bool
    {
        return $this->enableInheritance;
    }

    public function isUpdateReferences(): bool
    {
        return $this->updateReferences;
    }
}
