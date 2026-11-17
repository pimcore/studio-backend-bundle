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

        return $this->qualifiedValueSerializer->getVariantValues($value, $config->getVariant())[0] ?? null;
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

        $values = $this->qualifiedValueSerializer->getVariantValues(
            $this->qualifiedValueSerializer->toPayload(
                $this->qualifiedValueSerializer->readStoredEntries(
                    $container,
                    $config->getGroupId(),
                    $config->getKeyId(),
                    $locale,
                    $this->getKeyConfig($config)
                )
            ),
            $config->getVariant()
        );

        if ($values === []) {
            return '';
        }

        // not reading the slot itself, which would answer the default slot for a variant that holds nothing
        return $this->dataService->getExportFieldValue(
            $element,
            $fieldDefinition,
            $fieldName,
            new FieldContextData(legacyParameters: [...$legacyParameters, 'injectedData' => $values[0]])
        );
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
