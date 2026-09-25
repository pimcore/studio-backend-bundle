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

use Twig\Environment;
use Twig\Extension\SandboxExtension;

/**
 * Builds the sandbox used to render TwigOperator templates. Implementations must not reuse
 * or mutate the application's shared `twig` service/`SandboxExtension`: TwigOperator
 * templates are authored by Studio grid users - and, once Output Channels ships, by
 * document editors - so the environment they render in must be fully isolated from the one
 * core uses for its own Twig rendering.
 *
 * @internal
 */
interface SandboxExtensionInitializerInterface
{
    public function initialize(): SandboxExtension;

    /**
     * The isolated environment {@see initialize()}'s SandboxExtension is registered on.
     * TemplateGenerator must render templates through this environment, never through the
     * application's shared one.
     */
    public function getEnvironment(): Environment;
}
