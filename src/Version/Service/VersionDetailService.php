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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Version\Event\AssetVersionEvent;
use Pimcore\Bundle\StudioBackendBundle\Version\Event\DataObjectVersionEvent;
use Pimcore\Bundle\StudioBackendBundle\Version\Event\DocumentVersionEvent;
use Pimcore\Bundle\StudioBackendBundle\Version\Model\DeletedRelationReference;
use Pimcore\Bundle\StudioBackendBundle\Version\Repository\VersionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\Dimensions;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DocumentVersion;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Version;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final readonly class VersionDetailService implements VersionDetailServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private ServiceProviderInterface $versionHydratorLocator,
        private VersionRepositoryInterface $repository,
        private DeletedRelationServiceInterface $deletedRelationService,
    ) {
    }

    /**
     * @throws ForbiddenException|NotFoundException|InvalidElementTypeException
     */
    public function getVersionData(
        int $id,
        UserInterface $user
    ): AssetVersion|DataObjectVersion|DocumentVersion {
        $version = $this->repository->getVersionById($id);
        $element = $this->repository->getElementFromVersion($version, $user);

        $hydratedVersion = $this->hydrate(
            $element,
            $this->getElementClass($element)
        );

        if ($hydratedVersion instanceof DataObjectVersion && $element instanceof Concrete) {
            $this->addDeletedRelations($hydratedVersion, $element, $version);
        }

        $this->dispatchVersionEvent($hydratedVersion);

        return $hydratedVersion;
    }

    /**
     * We have to get the image dimensions from the local file
     * The Image class returns only dimensions of the latest version
     *
     * @throws ElementStreamResourceNotFoundException
     */
    public function getDimensions(Asset $asset): Dimensions
    {
        $path = $this->getLocalAssetPath($asset);

        if (is_readable($path)) {
            $assetDimensions = getimagesize($path);
            if ($assetDimensions && $assetDimensions[0] && $assetDimensions[1]) {
                return new Dimensions(
                    $assetDimensions[0],
                    $assetDimensions[1]
                );
            }
        }

        return new Dimensions();
    }

    /**
     *
     *  We have to get the asset file size from the local file
     *  The Asset class returns only file size of the latest version
     *
     * @throws ElementStreamResourceNotFoundException
     */
    public function getAssetFileSize(Asset $asset): ?int
    {
        $path = $this->getLocalAssetPath($asset);

        if (is_readable($path)) {
            return filesize($path);
        }

        return null;
    }

    /**
     * @throws ElementStreamResourceNotFoundException
     */
    private function getLocalAssetPath(Asset $asset): string
    {
        try {
            return $asset->getLocalFile();
        } catch (Exception) {
            throw new ElementStreamResourceNotFoundException(
                $asset->getId(),
                $asset->getType()
            );
        }
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function hydrate(
        ElementInterface $element,
        string $class
    ): AssetVersion|DocumentVersion|DataObjectVersion {
        if ($this->versionHydratorLocator->has($class)) {
            return $this->versionHydratorLocator->get($class)->hydrate($element);
        }

        throw new InvalidElementTypeException($class);
    }

    /**
     * Relations to elements deleted after the version was saved can not be loaded with the version data,
     * the version only knows them as unresolved references.
     *
     * @todo drop the guard once pimcore/pimcore ^2026.3.8 (which adds Version::getUnresolvedReferences()) is required
     */
    private function addDeletedRelations(DataObjectVersion $hydratedVersion, Concrete $element, Version $version): void
    {
        // @phpstan-ignore function.impossibleType, function.alreadyNarrowedType
        if (!method_exists($version, 'getUnresolvedReferences')) {
            return;
        }

        $references = array_map(
            static fn (object $reference) => new DeletedRelationReference(
                $reference->getFieldName(),
                $reference->getLanguage(),
                $reference->getIndex(),
                $reference->getElementType(),
                $reference->getElementId()
            ),
            $version->getUnresolvedReferences()
        );
        if ($references === []) {
            return;
        }

        $hydratedVersion->setObjectData(
            $this->deletedRelationService->addDeletedRelations(
                $hydratedVersion->getObjectData(),
                $element->getClass(),
                $references
            )
        );
    }

    private function dispatchVersionEvent(AssetVersion|DocumentVersion|DataObjectVersion $version): void
    {
        $event = match (true) {
            $version instanceof AssetVersion => new AssetVersionEvent($version),
            $version instanceof DocumentVersion => new DocumentVersionEvent($version),
            $version instanceof DataObjectVersion => new DataObjectVersionEvent($version)
        };

        $this->eventDispatcher->dispatch($event, $event::EVENT_NAME);

    }
}
