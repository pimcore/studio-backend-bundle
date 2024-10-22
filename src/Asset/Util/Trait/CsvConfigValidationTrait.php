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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;

trait CsvConfigValidationTrait
{
    private function validateConfig(): void
    {

        if (empty($this->getColumns())) {
            throw new InvalidArgumentException('No columns provided');
        }

        if (empty($this->getConfig())) {
            throw new InvalidArgumentException('No settings provided');
        }

        if (!isset($this->getConfig()[StepConfig::SETTINGS_DELIMITER->value])) {
            throw new InvalidArgumentException('No delimiter provided');
        }
    }
}
