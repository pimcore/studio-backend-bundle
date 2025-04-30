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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\CustomMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\PredefinedMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\DataResolverServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Resolver\Element\ReferenceResolverInterface;
use Pimcore\Model\Metadata\Predefined;

/**
 * @internal
 */
final readonly class MetadataHydrator implements MetadataHydratorInterface
{
    public function __construct(
        private DataResolverServiceInterface $dataResolverService,
        private ReferenceResolverInterface $referenceResolver
    ) {
    }

    public function hydrate(array $customMetadata): CustomMetadata
    {
        return new CustomMetadata(
            $customMetadata['name'],
            $customMetadata['language'],
            $customMetadata['type'],
            $this->dataResolverService->normalizeData($customMetadata),
        );
    }

    public function hydratePredefined(Predefined $predefined): PredefinedMetadata
    {
        return new PredefinedMetadata(
            $predefined->getId(),
            $predefined->getName(),
            $predefined->getDescription(),
            $predefined->getType(),
            $predefined->getTargetSubType(),
            $this->resolveDefinitionData(
                $predefined->getData(),
                $predefined->getType(),
            ),
            $predefined->getConfig(),
            $predefined->getLanguage(),
            $predefined->getGroup(),
            $predefined->getCreationDate(),
            $predefined->getModificationDate(),
            $predefined->isWriteable()
        );
    }

    private function resolveDefinitionData(mixed $data, string $type): mixed
    {
        if (!$data) {
            return $data;
        }

        return match ($type) {
            'asset', 'document', 'object' => $this->referenceResolver->resolveData($type, (int)$data),
            'checkbox' => (bool)$data,
            default => $data
        };
    }
}
