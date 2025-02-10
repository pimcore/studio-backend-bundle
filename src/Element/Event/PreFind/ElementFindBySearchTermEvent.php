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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Event\PreFind;

final readonly class ElementFindBySearchTermEvent
{
    public const string EVENT_NAME = 'pre_find.element.search_term';

    private ?string $modifiedSearchTerm;

    public function __construct(private string $searchTerm)
    {

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
