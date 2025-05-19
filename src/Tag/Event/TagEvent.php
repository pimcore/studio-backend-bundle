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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\Tag;

final class TagEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.tag';

    public function __construct(
        private readonly Tag $tag
    ) {
        parent::__construct($tag);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getTag(): Tag
    {
        return $this->tag;
    }
}
