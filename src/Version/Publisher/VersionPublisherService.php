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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Publisher;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Permissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementPermissionTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Version\RepositoryInterface;
use Pimcore\Model\UserInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final class VersionPublisherService implements VersionPublisherServiceInterface
{
    use ElementPermissionTrait;
    use ElementProviderTrait;

    public function __construct(
        private readonly ServiceResolverInterface $serviceResolver,
        private readonly RepositoryInterface $repository,
        private readonly ServiceProviderInterface $versionPublisherLocator
    ) {
    }

    public function publishVersion(
        int $versionId,
        UserInterface $user
    ): int {
        $version = $this->repository->getVersionById($versionId);
        $element = $this->repository->getElementFromVersion(
            $version,
            $user
        );

        $currentElement = $this->getElement(
            $this->serviceResolver,
            $element->getType(),
            $element->getId(),
        );

        $this->isAllowed($currentElement, $user, Permissions::PUBLISH_PERMISSION);
        $class = $this->getElementClass($currentElement);
        if (!$this->versionPublisherLocator->has($class)) {
            throw new InvalidElementTypeException($class);
        }

        $this->versionPublisherLocator->get($class)->publish(
            $element,
            $user
        );

        return $this->repository->getLastVersion(
            $element->getId(),
            $element->getType(),
            $user
        )->getId();
    }
}
