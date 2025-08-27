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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\CsvSettings;

final class CsvSettingsEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.translations.import.csv-settings';

    public function __construct(
        private readonly CsvSettings $csvSettings
    ) {
        parent::__construct($this->csvSettings);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getCsvSettings(): CsvSettings
    {
        return $this->csvSettings;
    }
}
