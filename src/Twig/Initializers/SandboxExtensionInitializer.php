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

use Pimcore\Twig\Sandbox\SecurityPolicy;
use Twig\Environment;
use Twig\Extension\SandboxExtension;

final class SandboxExtensionInitializer implements SandboxExtensionInitializerInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly array $allowedTags,
        private readonly array $allowedFilters,
        private readonly array $allowedFunctions
    ) {
    }

    public function initialize(): SandboxExtension
    {
        $securityPolicy = new SecurityPolicy(
            $this->allowedTags,
            $this->allowedFilters,
            $this->allowedFunctions
        );
        $sandbox = $this->twig->getExtension(SandboxExtension::class);
        $sandbox->setSecurityPolicy($securityPolicy);

        return $sandbox;
    }
}
