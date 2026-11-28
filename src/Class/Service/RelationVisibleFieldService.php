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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\Event\RelationVisibleFieldEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\RelationVisibleField;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\RelationVisibleFieldsConfiguration;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function is_array;
use function is_scalar;
use function is_string;

/**
 * @internal
 */
final readonly class RelationVisibleFieldService implements RelationVisibleFieldServiceInterface
{
    public function __construct(
        private RelationVisibleFieldDefinitionFactoryInterface $fieldDefinitionFactory,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableVisibleFields(RelationVisibleFieldsConfiguration $configuration): array
    {
        $fieldDefinition = $this->fieldDefinitionFactory->create($configuration);

        $fields = [];
        foreach ($fieldDefinition->getAvailableVisibleFields() as $name => $definition) {
            $field = $this->hydrate((string) $name, $definition);
            $this->eventDispatcher->dispatch(
                new RelationVisibleFieldEvent($field),
                RelationVisibleFieldEvent::EVENT_NAME
            );
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function hydrate(string $name, array $definition): RelationVisibleField
    {
        $title = $definition['title'] ?? null;

        return new RelationVisibleField(
            name: $name,
            title: is_scalar($title) && (string) $title !== '' ? (string) $title : $name,
            fieldtype: (string) ($definition['fieldtype'] ?? 'input'),
            sources: array_values(array_filter(
                is_array($definition['sources'] ?? null) ? $definition['sources'] : [],
                'is_string'
            )),
            localized: ($definition['localized'] ?? false) === true,
            metadataType: is_string($definition['metadataType'] ?? null) ? $definition['metadataType'] : null,
            language: is_string($definition['language'] ?? null) ? $definition['language'] : null,
            options: is_array($definition['options'] ?? null) ? array_values($definition['options']) : null,
            optionsProviderClass: is_string($definition['optionsProviderClass'] ?? null)
                ? $definition['optionsProviderClass']
                : null,
        );
    }
}
