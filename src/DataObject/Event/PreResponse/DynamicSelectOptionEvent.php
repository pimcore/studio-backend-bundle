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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\SelectOption;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class DynamicSelectOptionEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.data_object.dynamic_select_option';

    public function __construct(
        private readonly SelectOption $selectOption
    ) {
        parent::__construct($this->selectOption);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getSelectOption(): SelectOption
    {
        return $this->selectOption;
    }
}
