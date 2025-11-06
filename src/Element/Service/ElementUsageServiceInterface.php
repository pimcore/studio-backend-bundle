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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StudioBackendBundle\Element\MappedParameter\ReplaceAssignmentParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\MappedParameter\UsageParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\ElementUsage;

interface ElementUsageServiceInterface
{
    public function replaceUsage(
        string $elementType,
        int $elementId,
        ReplaceAssignmentParameter $replaceAssignmentParameter
    ): int;

    public function getUsages(
        string $elementType,
        int $elementId,
        UsageParameter $usageParameter
    ): ElementUsage;
}
