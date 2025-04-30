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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Collection;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class CollectionEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.classification_store.collection';

    public function __construct(
        private readonly Collection $collection
    ) {
        parent::__construct($collection);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }
}
