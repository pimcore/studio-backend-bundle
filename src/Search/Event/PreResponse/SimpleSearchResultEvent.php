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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\CustomAttributes;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\SimpleSearchResult;

final class SimpleSearchResultEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.simple_search.result';

    public function __construct(private readonly SimpleSearchResult $result)
    {
        parent::__construct($this->result);
    }

    public function getSimpleSearchResult(): SimpleSearchResult
    {
        return $this->result;
    }

    public function getCustomAttributes(): ?CustomAttributes
    {
        return $this->result->getCustomAttributes();
    }

    public function setCustomAttributes(CustomAttributes $customAttributes): void
    {
        $this->result->setCustomAttributes($customAttributes);
    }
}
