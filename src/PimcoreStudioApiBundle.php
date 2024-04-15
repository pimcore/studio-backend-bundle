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

namespace Pimcore\Bundle\StudioApiBundle;

use Pimcore\Bundle\StudioApiBundle\DependencyInjection\CompilerPass\FilterPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PimcoreStudioApiBundle extends AbstractPimcoreBundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcorestudioapi/js/swagger-ui/swagger-ui.js',
            '/bundles/pimcorestudioapi/js/swagger-ui/swagger-ui-bundle.js',
            '/bundles/pimcorestudioapi/js/swagger-ui/swagger-ui-es-bundle.js',
            '/bundles/pimcorestudioapi/js/swagger-ui/swagger-ui-es-bundle-core.js',
            '/bundles/pimcorestudioapi/js/swagger-ui/swagger-ui-standalone-preset.js',
        ];
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcorestudioapi/css/styles.css',
            '/bundles/pimcorestudioapi/css/swagger-ui/index.css',
            '/bundles/pimcorestudioapi/css/swagger-ui/swagger-ui.css',
        ];
    }

    public function getInstaller(): ?InstallerInterface
    {
        parent::getInstaller();

        /** @var InstallerInterface|null */
        return $this->container->get(Installer::class);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new FilterPass());
    }
}
