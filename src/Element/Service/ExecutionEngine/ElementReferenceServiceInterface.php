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
