<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */


namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;

/**
 * @internal
 */
interface FavoriteServiceInterface
{
    public function setAssetConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration
    ): GridConfiguration;

    public function removeAssetConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration
    ): GridConfiguration;
}