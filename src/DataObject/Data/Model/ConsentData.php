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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model;

/**
 * @internal
 */
final readonly class ConsentData
{
    public function __construct(
        private bool $consent,
        private ?int $noteId,
        private string $noteContent,
    ) {
    }

    public function getConsent(): bool
    {
        return $this->consent;
    }

    public function getNoteId(): ?int
    {
        return $this->noteId;
    }

    public function getNoteContent(): string
    {
        return $this->noteContent;
    }
}
