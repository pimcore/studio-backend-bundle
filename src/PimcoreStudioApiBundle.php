<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;

class PimcoreStudioApiBundle extends AbstractPimcoreBundle
{

    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function getJsPaths(): array
    {
        return [];
    }

    public function getCssPaths(): array
    {
        return [];
    }

    public function getInstaller(): ?InstallerInterface
    {
        parent::getInstaller();

        /** @var InstallerInterface|null */
        return $this->container->get(Installer::class);
    }

}
