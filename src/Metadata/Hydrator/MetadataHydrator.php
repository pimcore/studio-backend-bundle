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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\CustomMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\PredefinedMetadata;
use Pimcore\Bundle\StudioBackendBundle\Resolver\Element\ReferenceResolverInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Metadata\Predefined;

/**
 * @internal
 */
final readonly class MetadataHydrator implements MetadataHydratorInterface
{
    public function __construct(private ReferenceResolverInterface $referenceResolver)
    {
    }

    public function hydrate(array $customMetadata): CustomMetadata
    {
        return new CustomMetadata(
            $customMetadata['name'],
            $customMetadata['language'],
            $customMetadata['type'],
            $this->resolveData(
                $customMetadata['data'],
                $customMetadata['type']
            )
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

    private function resolveData(mixed $data, string $type): mixed
    {
        return match (true) {
            $data instanceof ElementInterface => $this->referenceResolver->resolve($data),
            $type === 'manyToManyRelation' => array_map(
                fn (ElementInterface $element): array => $this->referenceResolver->resolve($element),
                $data
            ),
            $type === 'checkbox' => (bool)$data,
            default => $data,
        };
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
