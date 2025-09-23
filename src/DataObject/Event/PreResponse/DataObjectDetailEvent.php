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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectDetail;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\CustomAttributes;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class DataObjectDetailEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.data_object_detail';

    public function __construct(
        private readonly DataObjectDetail|DataObjectFolder $dataObject
    ) {
        parent::__construct($this->dataObject);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getDataObject(): DataObjectDetail|DataObjectFolder
    {
        return $this->dataObject;
    }

    public function getCustomAttributes(): ?CustomAttributes
    {
        return $this->dataObject->getCustomAttributes();
    }

    public function setCustomAttributes(CustomAttributes $customAttributes): void
    {
        $this->dataObject->setCustomAttributes($customAttributes);
    }
}
