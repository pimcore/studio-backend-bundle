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
