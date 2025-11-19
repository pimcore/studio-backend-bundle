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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprSearchResultCollection;

/**
 * @internal
 */
final class GdprSearchResultEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.gdpr_search_result';

    public function __construct(private readonly GdprSearchResultCollection $collection)
    {
        parent::__construct($this->collection);
    }

    public function getCollection(): GdprSearchResultCollection
    {
        return $this->collection;
    }
}
