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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\QualifiedValueSerializerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ClassificationStoreConfig;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Concrete;
use function array_map;
use function array_values;
use function json_encode;
use function method_exists;
use function sprintf;
use function ucfirst;

/**
 * @internal
 */
final readonly class QualifiedColumnValueResolver implements QualifiedColumnValueResolverInterface
{
    public function __construct(
        private KeyConfigResolverInterface $keyConfigResolver,
        private KeyQualifierResolverInterface $keyQualifierResolver,
        private QualifiedValueSerializerInterface $qualifiedValueSerializer,
        private DataServiceInterface $dataService,
    ) {
    }

    public function getCellValue(mixed $value, ClassificationStoreConfig $config): mixed
    {
        $keyConfig = $this->keyConfigResolver->getById($config->getKeyId());
        if (!$keyConfig instanceof KeyConfig || !$this->keyQualifierResolver->declaresQualifiers($keyConfig)) {
            return $value;
        }

        $values = $this->qualifiedValueSerializer->getVariantValues($value, $config->getVariant());
        if ($values === []) {
            return null;
        }

        if ($this->keyQualifierResolver->isMultiValue($keyConfig) !== true) {
            return $values[0];
        }

        return $this->toEntries($values, $config->getVariant());
    }

    public function declaresQualifiers(ClassificationStoreConfig $config): bool
    {
        return $this->keyQualifierResolver->declaresQualifiers($this->getKeyConfig($config));
    }

    public function getExportValue(
        Concrete $element,
        string $fieldName,
        ClassificationStoreConfig $config,
        string $locale,
        Data $fieldDefinition,
        array $legacyParameters
    ): mixed {
        // the guard core's own read of this slot makes, so that a saved column naming a field the
        // class no longer has keeps exporting an empty cell rather than failing the export
        if (!method_exists($element, 'get' . ucfirst($fieldName))) {
            return '';
        }

        $container = $element->get($fieldName);

        if (!$container instanceof Classificationstore) {
            return '';
        }

        $keyConfig = $this->getKeyConfig($config);
        $values = $this->qualifiedValueSerializer->getVariantValues(
            $this->qualifiedValueSerializer->toPayload(
                $this->qualifiedValueSerializer->readStoredEntries(
                    $container,
                    $config->getGroupId(),
                    $config->getKeyId(),
                    $locale,
                    $keyConfig
                )
            ),
            $config->getVariant()
        );

        // not reading the slot itself, which would answer the default slot for a variant that holds nothing
        $formatted = array_map(
            fn (mixed $value): mixed => $this->dataService->getExportFieldValue(
                $element,
                $fieldDefinition,
                $fieldName,
                new FieldContextData(legacyParameters: [...$legacyParameters, 'injectedData' => $value])
            ),
            $values
        );

        if ($formatted === []) {
            return '';
        }

        return $this->keyQualifierResolver->isMultiValue($keyConfig) === true
            ? json_encode($formatted)
            : $formatted[0];
    }

    /**
     * @param mixed[] $values
     *
     * @return array<int, array<string, mixed>>
     */
    private function toEntries(array $values, ?string $variant): array
    {
        $entries = [];
        foreach (array_values($values) as $position => $value) {
            $entry = [QualifiedValueSerializerInterface::VALUE_MEMBER => $value];
            if ($variant !== null) {
                $entry[QualifiedValueSerializerInterface::VARIANT_MEMBER] = $variant;
            }
            $entry[QualifiedValueSerializerInterface::POSITION_MEMBER] = $position;
            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getKeyConfig(ClassificationStoreConfig $config): KeyConfig
    {
        $keyConfig = $this->keyConfigResolver->getById($config->getKeyId());

        if (!$keyConfig instanceof KeyConfig) {
            throw new InvalidArgumentException(sprintf('Key with %s not set', $config->getKeyId()));
        }

        return $keyConfig;
    }
}
