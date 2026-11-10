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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use JsonException;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use ReflectionProperty;
use function is_array;
use function json_decode;

/**
 * @internal
 */
final readonly class KeyQualifierResolver implements KeyQualifierResolverInterface
{
    private const string TYPE_PROPERTY = 'type';

    public function __construct(
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    public function declaresQualifiers(KeyConfig $keyConfig): bool
    {
        return $this->getDeclaration($keyConfig) !== null;
    }

    public function getVariants(KeyConfig $keyConfig): ?array
    {
        return $this->getDeclaration($keyConfig)?->getVariantNames();
    }

    public function getVariantDeclarations(KeyConfig $keyConfig): ?array
    {
        return $this->getDeclaration($keyConfig)?->getVariants();
    }

    public function isMultiValue(KeyConfig $keyConfig): ?bool
    {
        return $this->getDeclaration($keyConfig)?->isMultiValue();
    }

    public function getMaximum(KeyConfig $keyConfig): ?int
    {
        return $this->getDeclaration($keyConfig)?->getMaximum();
    }

    /**
     * The key's field definition, resolved from its type and its `definition` blob, when that is a
     * core `multiValue` definition. A blob that is not readable as JSON declares nothing.
     */
    private function getDeclaration(KeyConfig $keyConfig): ?MultiValue
    {
        // `$type` has no default: a key config that was never hydrated has no type to resolve by
        if (!(new ReflectionProperty($keyConfig, self::TYPE_PROPERTY))->isInitialized($keyConfig)) {
            return null;
        }

        try {
            $decoded = json_decode($keyConfig->getDefinition(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        $definition = $this->serviceResolver->getFieldDefinitionFromJson($decoded, $keyConfig->getType());

        return $definition instanceof MultiValue ? $definition : null;
    }
}
