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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Normalizer\NormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ManyToOneRelationAdapter implements SetterDataInterface, DataNormalizerInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ServiceResolverInterface $serviceResolver,
        private ElementDataServiceInterface $elementDataService,
    ) {
    }

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?ElementInterface {
        $relationData = $data[$key];
        if (!is_array($relationData) || empty($relationData['id']) || empty($relationData['type'])) {
            return null;
        }

        try {
            return $this->getElement($this->serviceResolver, $relationData['type'], $relationData['id']);
        } catch (NotFoundException) {
            return null;
        }
    }

    public function normalize(mixed $value, Data $fieldDefinition): mixed
    {
        if (!$fieldDefinition instanceof NormalizerInterface) {
            return null;
        }

        $data = $fieldDefinition->normalize($value);
        $published = null;

        if ($value instanceof Concrete || $value instanceof Document) {
            $published = $value->isPublished();
        }

        if (!empty($data)) {
            // GHSA-pg5f-74vr-w237: a relation can point at any element id, so path, subtype and publish
            // state of the target are only handed out to a user who is allowed to view it.
            $hasViewAccess = $this->elementDataService->getRelatedElementData($value)->getHasViewAccess();

            $data['fullPath'] = $hasViewAccess ? $value->getRealFullPath() : '';
            $data['subtype'] = $hasViewAccess ? $value->getType() : '';
            $data['isPublished'] = $hasViewAccess ? $published : null;
            $data['hasViewAccess'] = $hasViewAccess;
        }

        return $data;
    }
}
