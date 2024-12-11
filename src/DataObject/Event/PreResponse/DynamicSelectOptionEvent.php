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
