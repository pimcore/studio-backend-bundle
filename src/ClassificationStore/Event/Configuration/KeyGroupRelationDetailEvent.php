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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Event\Configuration;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationDetail;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class KeyGroupRelationDetailEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.classification_store.configuration.key_group_relation';

    public function __construct(
        private readonly KeyGroupRelationDetail $keyGroupRelationDetail
    ) {
        parent::__construct($keyGroupRelationDetail);
    }

    public function getKeyGroupRelationDetail(): KeyGroupRelationDetail
    {
        return $this->keyGroupRelationDetail;
    }
}
