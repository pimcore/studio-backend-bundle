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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\FormatedPath;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class FormatedPathEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.data_object.formated_path';

    public function __construct(
        private readonly FormatedPath $formatedPath
    ) {
        parent::__construct($this->formatedPath);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getFormatedPath(): FormatedPath
    {
        return $this->formatedPath;
    }
}
