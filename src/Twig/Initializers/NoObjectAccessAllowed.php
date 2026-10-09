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
 * Never instantiated. As the only entry of the TwigOperator sandbox's class allowlist it switches
 * {@see \Pimcore\Twig\Sandbox\SecurityPolicy} into allowlist mode with nothing allowed, so every method and
 * property access on an object is denied. TwigOperator already converts values to plain data; this covers
 * objects that reach the template anyway.
 *
 * @internal
 */
final class NoObjectAccessAllowed
{
}
