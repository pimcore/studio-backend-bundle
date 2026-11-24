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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Data\Adapter;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataDenormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\MetaDataAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\AdapterLoader;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(AdapterLoader::METADATA_ADAPTER_TAG->value)]
final readonly class ManyToOneRelationAdapter implements
    MetaDataAdapterInterface,
    DataNormalizerInterface,
    DataDenormalizerInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ServiceResolverInterface $serviceResolver,
        private ElementDataServiceInterface $elementDataService,
    ) {
    }

    public function normalize(mixed $value, string $type): ?array
    {
        if (!$value instanceof ElementInterface) {
            return null;
        }

        // GHSA-pg5f-74vr-w237: a metadata relation can point at any element id, so path, subtype and
        // publish state of the target are only handed out to a user who is allowed to view it.
        $hasViewAccess = $this->elementDataService->getRelatedElementData($value)->getHasViewAccess();
        $isPublished = ($value instanceof Concrete || $value instanceof Document) ? $value->isPublished() : null;

        return [
            'id' => $value->getId(),
            'type' => $this->getElementType($value, true),
            'fullPath' => $hasViewAccess ? $value->getRealFullPath() : '',
            'subtype' => $hasViewAccess ? $value->getType() : '',
            'isPublished' => $hasViewAccess ? $isPublished : null,
            'hasViewAccess' => $hasViewAccess,
        ];
    }

    public function denormalize(
        array $customMetadata,
        UserInterface $user,
        array $existingMetadata = [],
        bool $isPatch = false
    ): ?ElementInterface {
        $value = $customMetadata['data'] ?? null;
        if (!is_array($value) || empty($value['id'])) {
            return null;
        }

        try {
            return $this->getElement($this->serviceResolver, $value['type'], $value['id']);
        } catch (NotFoundException) {
            return null;
        }
    }
}
