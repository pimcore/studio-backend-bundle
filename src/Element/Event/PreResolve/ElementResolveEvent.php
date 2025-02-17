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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResolve;

final class ElementResolveEvent
{
    public const string EVENT_NAME = 'pre_resolve.element_resolve';

    private ?string $modifiedSearchTerm = null;

    public function __construct(
        private readonly string $elementType,
        private readonly string $searchTerm

    ) {
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getSearchTerm(): string
    {
        return $this->modifiedSearchTerm ?? $this->searchTerm;
    }

    public function setModifiedSearchTerm(string $modifiedSearchTerm): void
    {
        $this->modifiedSearchTerm = $modifiedSearchTerm;
    }
}
