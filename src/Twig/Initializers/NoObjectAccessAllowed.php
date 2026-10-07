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

namespace Pimcore\Bundle\StudioBackendBundle\Twig\Initializers;

/**
 * Never instantiated. Its sole purpose is to be the one entry in
 * {@see SandboxExtensionInitializer}'s {@see \Pimcore\Twig\Sandbox\SecurityPolicy} allowlist,
 * which switches the policy from denylist mode into allowlist mode
 * ({@see \Pimcore\Twig\Sandbox\SecurityPolicy::checkMethodAllowed()}): once
 * `$allowedClasses` is non-empty, every object that is not an instance of one of its entries
 * is denied ALL method and property access, and the denylist (blockedClasses) is no longer
 * consulted at all.
 *
 * No real value is, or ever will be, an instance of this class - so no object reaching a
 * TwigOperator template can have its methods or properties accessed, full stop. This is
 * deliberate defense-in-depth: {@see \Pimcore\Bundle\StudioBackendBundle\Grid\Column\Transformer\TwigOperator}
 * converts every value to plain data before it reaches the template (see its
 * `sanitizeForTemplate()`), so no legitimate object should ever arrive here in the first
 * place. If one does anyway - a bug, a new adapter that forgets to normalize, a future
 * regression - this allowlist denies it regardless.
 *
 * @internal
 */
final class NoObjectAccessAllowed
{
}
