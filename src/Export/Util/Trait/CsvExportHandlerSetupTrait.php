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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;

/**
 * @internal
 */
trait CsvExportHandlerSetupTrait
{
    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::ELEMENT_CLASS_ID->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ELEMENT_CLASS_ID->value,
            StepConfig::CONFIG_TYPE_STRING->value
        );
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
