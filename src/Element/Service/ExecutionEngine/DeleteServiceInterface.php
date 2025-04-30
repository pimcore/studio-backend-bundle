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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service\ExecutionEngine;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface DeleteServiceInterface
{
    public const ELEMENT_TO_DELETE = 'element_to_delete';

    public function deleteElementsWithExecutionEngine(
        ElementInterface $element,
        UserInterface $user,
        string $elementType,
        array $childrenIds,
        bool $useRecycleBin
    ): int;
}
