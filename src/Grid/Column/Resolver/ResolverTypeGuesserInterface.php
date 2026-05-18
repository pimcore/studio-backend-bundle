<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver;

use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface ResolverTypeGuesserInterface
{
    public function guessType(string $key, string $classId, ?UserInterface $user = null): string;

    public function isLocalizable(string $key, string $classId, ?UserInterface $user = null): bool;
}
