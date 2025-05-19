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

use Exception;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

interface ElementReferenceServiceInterface
{
    /**
     * @throws Exception
     */
    public function rewriteElementReferences(
        UserInterface $user,
        ElementInterface $element,
        array $rewriteConfiguration,
        array $parameters = []
    ): void;

    public function rewriteReferencesWithExecutionEngine(
        UserInterface $user,
        array $rewriteConfiguration,
        array $ids,
        string $type
    ): int;
}
