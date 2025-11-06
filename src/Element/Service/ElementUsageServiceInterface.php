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
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;

interface ElementUsageServiceInterface
{
    public const string REPLACE_ELEMENT_USAGE_TARGET_TYPE = 'targetElementType';

    public const string REPLACE_ELEMENT_USAGE_TARGET_ID = 'targetElementId';

    public const string REPLACE_ELEMENT_USAGE_SOURCE_TYPE = 'sourceElementType';

    public const string REPLACE_ELEMENT_USAGE_SOURCE_ID = 'sourceElementId';

    public function createReplaceUsageJobRun(
        string $elementType,
        int $elementId,
        ReplaceAssignmentParameter $replaceAssignmentParameter
    ): int;

    public function replaceElementUsage(
        ElementInterface $sourceElement,
        ElementInterface $targetElement,
        ElementInterface $element,
        User $user
    ): void;

    public function getUsages(
        string $elementType,
        int $elementId,
        UsageParameter $usageParameter
    ): ElementUsage;

    public function getElementById(
        string $elementType,
        int $elementId
    ): ElementInterface;
}
