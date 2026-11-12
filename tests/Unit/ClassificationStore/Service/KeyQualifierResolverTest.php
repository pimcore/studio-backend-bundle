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

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function array_key_exists;
use function json_encode;

/**
 * The one place the declaration is read, and it is the key's type: a key declares qualifiers when
 * its field definition - resolved from its type and its `definition` blob - is a `multiValue`
 * definition, whatever members the blob of any other key carries.
 *
 * @internal
 */
final class KeyQualifierResolverTest extends Unit
{
    private const string MULTI_VALUE_TYPE = 'multiValue';

    private const string OTHER_TYPE = 'quantityValue';

    private KeyQualifierResolver $resolver;

    public function _before(): void
    {
        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method('getFieldDefinitionFromJson')->willReturnCallback(
            static function (array $definition, string $type): ?Data {
                if ($type !== self::MULTI_VALUE_TYPE) {
                    return new Input();
                }

                $multiValue = new MultiValue();
                $multiValue->setVariants($definition['variants'] ?? []);
                $multiValue->setMultiValue($definition['multiValue'] ?? false);
                $multiValue->setMaximum($definition['maximum'] ?? null);

                return $multiValue;
            }
        );

        $this->resolver = new KeyQualifierResolver($serviceResolver);
    }

    public function testAKeyOfAnotherTypeDeclaresNothingWhateverItsBlobCarries(): void
    {
        $keyConfig = $this->createKeyConfig(
            self::OTHER_TYPE,
            ['variants' => ['metric', 'imperial'], 'multiValue' => true]
        );

        $this->assertFalse($this->resolver->declaresQualifiers($keyConfig));
        $this->assertNull($this->resolver->getVariants($keyConfig));
        $this->assertNull($this->resolver->getVariantDeclarations($keyConfig));
        $this->assertNull($this->resolver->isMultiValue($keyConfig));
        $this->assertNull($this->resolver->getMaximum($keyConfig));
    }

    public function testAMultiValueKeyDeclaresAndReadsItsVariantsInDeclaredOrder(): void
    {
        $keyConfig = $this->createKeyConfig(
            self::MULTI_VALUE_TYPE,
            ['variants' => [['name' => 'metric', 'label' => 'Metric'], 'imperial'], 'multiValue' => true, 'maximum' => 3]
        );

        $this->assertTrue($this->resolver->declaresQualifiers($keyConfig));
        $this->assertSame(['metric', 'imperial'], $this->resolver->getVariants($keyConfig));
        $this->assertSame(
            [['name' => 'metric', 'label' => 'Metric'], ['name' => 'imperial']],
            $this->resolver->getVariantDeclarations($keyConfig)
        );
        $this->assertTrue($this->resolver->isMultiValue($keyConfig));
        $this->assertSame(3, $this->resolver->getMaximum($keyConfig));
    }

    public function testAMultiValueKeyWithAnEmptyVariantsListStillDeclares(): void
    {
        $keyConfig = $this->createKeyConfig(self::MULTI_VALUE_TYPE, ['variants' => []]);

        $this->assertTrue($this->resolver->declaresQualifiers($keyConfig));
        $this->assertSame([], $this->resolver->getVariants($keyConfig));
        $this->assertFalse($this->resolver->isMultiValue($keyConfig));
        $this->assertNull($this->resolver->getMaximum($keyConfig));
    }

    /**
     * A declaration entry carrying no usable `name` is no variant the key declares: core's own
     * setter drops it ({@see MultiValue::setVariants()}), so every entry that reaches a reader
     * carries a name - which is what the group layout reads off each of them.
     */
    public function testADeclarationEntryWithoutAUsableNameIsNotDeclared(): void
    {
        $keyConfig = $this->createKeyConfig(
            self::MULTI_VALUE_TYPE,
            ['variants' => [['label' => 'Metric'], ['name' => '', 'label' => 'None'], ['name' => 'imperial']]]
        );

        $this->assertSame(['imperial'], $this->resolver->getVariants($keyConfig));
        $this->assertSame([['name' => 'imperial']], $this->resolver->getVariantDeclarations($keyConfig));
    }

    /**
     * A key config that was never hydrated has no type, and declares nothing rather than failing.
     */
    public function testAKeyConfigWithoutATypeDeclaresNothing(): void
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('weight');

        $this->assertFalse($this->resolver->declaresQualifiers($keyConfig));
        $this->assertNull($this->resolver->getVariants($keyConfig));
        $this->assertNull($this->resolver->getMaximum($keyConfig));
    }

    /**
     * A definition that is not readable as JSON declares nothing rather than failing the read.
     */
    public function testADefinitionThatIsNotJsonDeclaresNothing(): void
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setType(self::MULTI_VALUE_TYPE);
        $keyConfig->setDefinition('{not json');

        $this->assertFalse($this->resolver->declaresQualifiers($keyConfig));
        $this->assertNull($this->resolver->getVariants($keyConfig));
    }

    /**
     * The declaration is rebuilt from the blob once per (type, blob) and served from memory
     * afterwards: one group layout asks three times per key and the object data path asks again per
     * key and language, and rebuilding a whole core field definition each time is what that costs.
     */
    public function testTheDeclarationIsResolvedOncePerKey(): void
    {
        $resolved = 0;
        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method('getFieldDefinitionFromJson')->willReturnCallback(
            static function () use (&$resolved): Data {
                $resolved++;

                return new MultiValue();
            }
        );
        $resolver = new KeyQualifierResolver($serviceResolver);
        $keyConfig = $this->createKeyConfig(self::MULTI_VALUE_TYPE, ['variants' => [['name' => 'metric']]]);

        $resolver->declaresQualifiers($keyConfig);
        $resolver->getVariants($keyConfig);
        $resolver->getVariantDeclarations($keyConfig);
        $resolver->isMultiValue($keyConfig);
        $resolver->getMaximum($keyConfig);

        $this->assertSame(1, $resolved);

        // another blob is another declaration, which is why nothing has to invalidate the memo
        $resolver->declaresQualifiers($this->createKeyConfig(self::MULTI_VALUE_TYPE, ['multiValue' => true]));

        $this->assertSame(2, $resolved);
    }

    /**
     * A core declaring the setting answers it, with its label; a blob omitting it and a core that
     * does not carry the accessor at all answer the same permitted default; any other type answers null.
     */
    public function testTheDefaultVariantIsReadFromTheMemoizedDeclaration(): void
    {
        $resolved = 0;
        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method('getFieldDefinitionFromJson')->willReturnCallback(
            static function (array $definition, string $type) use (&$resolved): Data {
                $resolved++;
                if ($type !== self::MULTI_VALUE_TYPE) {
                    return new Input();
                }

                return array_key_exists('defaultVariant', $definition)
                    ? new DefaultVariantMultiValue($definition['defaultVariant'], $definition['defaultVariantLabel'] ?? null)
                    : new MultiValue();
            }
        );
        $resolver = new KeyQualifierResolver($serviceResolver);

        $declared = $this->createKeyConfig(
            self::MULTI_VALUE_TYPE,
            ['defaultVariant' => false, 'defaultVariantLabel' => 'Standard']
        );
        $withoutAccessor = $this->createKeyConfig(self::MULTI_VALUE_TYPE, ['variants' => []]);

        $this->assertFalse($resolver->getDefaultVariant($declared)?->isPermitted());
        $this->assertSame('Standard', $resolver->getDefaultVariant($declared)->getLabel());
        $this->assertSame(1, $resolved);

        $this->assertTrue($resolver->getDefaultVariant($withoutAccessor)?->isPermitted());
        $this->assertNull($resolver->getDefaultVariant($withoutAccessor)->getLabel());

        $this->assertNull($resolver->getDefaultVariant($this->createKeyConfig(self::OTHER_TYPE, [])));
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function createKeyConfig(string $type, array $definition): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setName('weight');
        $keyConfig->setType($type);
        $keyConfig->setDefinition((string) json_encode($definition));

        return $keyConfig;
    }
}
