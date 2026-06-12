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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Service;

use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SavedSearchParameter;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface ShareServiceInterface
{
    public function setShareOptions(
        SavedSearchConfiguration $configuration,
        SavedSearchParameter $parameter
    ): SavedSearchConfiguration;

    public function isConfigurationAccessibleByUser(
        SavedSearchConfiguration $configuration,
        UserInterface $user
    ): bool;

    public function getUserShares(SavedSearchConfiguration $configuration): array;

    public function getRoleShares(SavedSearchConfiguration $configuration): array;
}
