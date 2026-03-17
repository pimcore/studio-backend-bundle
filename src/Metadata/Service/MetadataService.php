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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Event\PreResponse\CustomMetadataEvent;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Event\PreResponse\PredefinedMetadataEvent;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Hydrator\MetadataHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\MappedParameter\MetadataParameters;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Repository\MetadataRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\CustomMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\PredefinedMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\UpdatePredefinedMetadata;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\Metadata\Predefined;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class MetadataService implements MetadataServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private MetadataRepositoryInterface $metadataRepository,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private EventDispatcherInterface $eventDispatcher,
        private MetadataHydratorInterface $hydrator,
    ) {
    }

    /**
     * @return array<int, CustomMetadata>
     *
     * @throws ForbiddenException|NotFoundException
     *
     */
    public function getCustomMetadata(int $id): array
    {
        /** @var Asset $asset */
        $asset = $this->getElement($this->serviceResolver, ElementTypes::TYPE_ASSET, $id);

        $this->securityService->hasElementPermission(
            $asset,
            $this->securityService->getCurrentUser(),
            ElementPermissions::VIEW_PERMISSION
        );

        $customMetadata = [];

        $originalCustomMetadata = $asset->getMetadata();

        if (empty($originalCustomMetadata)) {
            foreach (self::DEFAULT_METADATA as $metadata) {
                $originalCustomMetadata[] = [
                    'name' => $metadata,
                    'language' => '',
                    'type' => 'input',
                    'data' => null,
                ];
            }
        }

        foreach ($originalCustomMetadata as $metadata) {
            $metadata = $this->hydrator->hydrate($metadata);

            $this->eventDispatcher->dispatch(
                new CustomMetadataEvent($metadata),
                CustomMetadataEvent::EVENT_NAME
            );

            $customMetadata[] = $metadata;
        }

        return $customMetadata;
    }

    public function getPredefinedMetadata(MetadataParameters $parameters): Collection
    {
        $definitions = $this->metadataRepository->getAllPredefinedMetadataDefinitions($parameters);

        $items = array_map(
            fn (Predefined $predefined) => $this->hydrateAndDispatch($predefined),
            $definitions,
        );

        return new Collection(count($items), $items);
    }

    public function createPredefinedMetadata(): PredefinedMetadata
    {
        $predefined = $this->metadataRepository->createPredefinedMetadata();

        return $this->hydrateAndDispatch($predefined);
    }

    public function getPredefinedMetadataById(string $id): PredefinedMetadata
    {
        return $this->hydrateAndDispatch(
            $this->metadataRepository->getPredefinedMetadataById($id)
        );
    }

    public function updatePredefinedMetadata(string $id, UpdatePredefinedMetadata $metadata): PredefinedMetadata
    {
        $predefined = $this->metadataRepository->updatePredefinedMetadata($id, $metadata);

        return $this->hydrateAndDispatch($predefined);
    }

    public function deletePredefinedMetadata(string $id): void
    {
        $this->metadataRepository->deletePredefinedMetadata($id);
    }

    public function getAssetPredefinedMetadata(
        ?string $subType,
        ?string $group,
    ): array {
        $items = $this->metadataRepository->getPredefinedMetadataByTargetType($subType, $group);

        return array_map(
            fn (Predefined $item) => $this->hydrateAndDispatch($item),
            $items,
        );
    }

    private function hydrateAndDispatch(Predefined $predefined): PredefinedMetadata
    {
        $metadata = $this->hydrator->hydratePredefined($predefined);

        $this->eventDispatcher->dispatch(
            new PredefinedMetadataEvent($metadata),
            PredefinedMetadataEvent::EVENT_NAME
        );

        return $metadata;
    }
}
