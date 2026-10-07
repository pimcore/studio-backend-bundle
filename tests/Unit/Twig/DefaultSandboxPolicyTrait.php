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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Twig;

use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use ReflectionMethod;
use Symfony\Component\Config\Definition\Processor;

/**
 * Reads the bundle's default `twig.sandbox_security_policy` allow-lists from the real Configuration, so tests
 * track changes to the policy instead of duplicating it.
 *
 * @internal
 */
trait DefaultSandboxPolicyTrait
{
    /**
     * @return array{tags: list<string>, filters: list<string>, functions: list<string>}
     */
    private function getDefaultSandboxPolicy(): array
    {
        $node = (new ReflectionMethod(Configuration::class, 'addTwigSandboxNode'))
            ->invoke(new Configuration())
            ->getNode(true);

        return (new Processor())->process($node, [])['sandbox_security_policy'];
    }
}
