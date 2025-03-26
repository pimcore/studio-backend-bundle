<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Export\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;

trait CsvExportHandlerSetupTrait
{
    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::ELEMENT_TO_EXPORT->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ELEMENT_TO_EXPORT->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
        $this->stepConfiguration->setRequired(StepConfig::CONFIG_COLUMNS->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CONFIG_COLUMNS->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
        $this->stepConfiguration->setRequired(StepConfig::ELEMENT_TYPE->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ELEMENT_TYPE->value,
            StepConfig::CONFIG_TYPE_STRING->value
        );
    }
}