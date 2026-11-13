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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use function array_key_exists;
use function is_array;
use function json_decode;

/**
 * The resolution core's service makes for a key, as far as the qualifier declaration is concerned: a
 * key of type `multiValue` resolves to a `multiValue` definition built from its blob, and a key of
 * any other type resolves to a plain definition, whatever members its blob carries.
 *
 * @internal
 */
final class MultiValueServiceResolver implements ServiceResolverInterface
{
    public const string MULTI_VALUE_TYPE = 'multiValue';

    public function clearDefinitionsCache(): void
    {
    }

    public function getFieldDefinitionFromKeyConfig(KeyConfig|KeyGroupRelation $keyConfig): ?Data
    {
        $decoded = json_decode($keyConfig->getDefinition(), true);

        return $this->getFieldDefinitionFromJson(is_array($decoded) ? $decoded : [], $keyConfig->getType());
    }

    public function getFieldDefinitionFromJson(array $definition, string $type): ?Data
    {
        if ($type !== self::MULTI_VALUE_TYPE) {
            return new Input();
        }

        $multiValue = array_key_exists('defaultVariant', $definition)
            ? new DefaultVariantMultiValue($definition['defaultVariant'], $definition['defaultVariantLabel'] ?? null)
            : new MultiValue();
        $multiValue->setVariants($definition['variants'] ?? []);
        $multiValue->setMultiValue($definition['multiValue'] ?? false);
        $multiValue->setMaximum($definition['maximum'] ?? null);

        return $multiValue;
    }
}
