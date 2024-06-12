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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Version\Repository\VersionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DocumentVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\ImageVersion;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final class VersionDetailService implements VersionDetailServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private readonly VersionRepositoryInterface $repository,
        private readonly ServiceProviderInterface $versionHydratorLocator
    ) {
    }

    /**
     * @throws AccessDeniedException|ElementNotFoundException|InvalidElementTypeException
     */
    public function getVersionData(
        int $id,
        UserInterface $user
    ): AssetVersion|ImageVersion|DataObjectVersion|DocumentVersion {
        $version = $this->repository->getVersionById($id);
        $element = $this->repository->getElementFromVersion($version, $user);

        return $this->hydrate(
            $element,
            $this->getElementClass($element)
        );
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function hydrate(
        ElementInterface $element,
        string $class
    ): AssetVersion|ImageVersion|DocumentVersion|DataObjectVersion {
        if ($this->versionHydratorLocator->has($class)) {
            return $this->versionHydratorLocator->get($class)->hydrate($element);
        }

        throw new InvalidElementTypeException($class);
    }
}
