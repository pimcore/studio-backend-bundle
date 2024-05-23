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

namespace Pimcore\Bundle\StudioBackendBundle;

use Pimcore\Bundle\GenericDataIndexBundle\PimcoreGenericDataIndexBundle;
use Pimcore\Bundle\StaticResolverBundle\PimcoreStaticResolverBundle;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\CompilerPass\FilterPass;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\CompilerPass\SettingsProviderPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PimcoreStudioBackendBundle extends AbstractPimcoreBundle implements DependentBundleInterface
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/pimcorestudiobackend/js/swagger-ui/swagger-ui.js',
            '/bundles/pimcorestudiobackend/js/swagger-ui/swagger-ui-bundle.js',
            '/bundles/pimcorestudiobackend/js/swagger-ui/swagger-ui-es-bundle.js',
            '/bundles/pimcorestudiobackend/js/swagger-ui/swagger-ui-es-bundle-core.js',
            '/bundles/pimcorestudiobackend/js/swagger-ui/swagger-ui-standalone-preset.js',
        ];
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/pimcorestudiobackend/css/styles.css',
            '/bundles/pimcorestudiobackend/css/swagger-ui/index.css',
            '/bundles/pimcorestudiobackend/css/swagger-ui/swagger-ui.css',
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
        $container->addCompilerPass(new SettingsProviderPass());
    }

    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->addBundle(new PimcoreStaticResolverBundle());
        $collection->addBundle(new PimcoreGenericDataIndexBundle());
    }
}
