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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Resolver\DataObject;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\AdapterResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedColumnSourceFieldContext;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use ReflectionMethod;
use function in_array;

/**
 * @internal
 */
final class AdapterResolverTest extends Unit
{
    /**
     * @see \Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait::allowDefaultLanguageFallback()
     */
    public function testAllowDefaultLanguageFallbackFollowsTheSharedSourceFieldContext(): void
    {
        $sourceFieldContext = new AdvancedColumnSourceFieldContext();

        $resolver = new AdapterResolver(
            $this->makeEmpty(DataServiceInterface::class),
            $this->makeEmpty(InheritanceServiceInterface::class),
            $this->makeEmpty(DataObjectServiceResolverInterface::class),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            $sourceFieldContext,
        );

        $method = new ReflectionMethod($resolver, 'allowDefaultLanguageFallback');

        self::assertTrue(
            $method->invoke($resolver),
            'a plain grid column is not an advanced column source field - the default-language jump stays allowed'
        );

        $sourceFieldContext->setResolvingSourceField(true);
        self::assertFalse(
            $method->invoke($resolver),
            'resolving an advanced column source field must suppress the default-language jump'
        );

        $sourceFieldContext->setResolvingSourceField(false);
        self::assertTrue($method->invoke($resolver), 'the jump must be allowed again once resolution finishes');
    }

    /**
     * A localized column without locale ("Default") must read the system default language. Without an
     * explicit locale core would use the request locale, which in Studio's API is the framework locale.
     */
    public function testLocalizedColumnWithoutLocaleReadsTheSystemDefaultLanguage(): void
    {
        $element = $this->createElement(localizedKeys: ['name']);

        $this->createResolver('de')->resolveForCoreElement($this->createColumn(null), $element);

        self::assertSame([['de']], $element->calls);
    }

    public function testExplicitColumnLocaleIsNotReplacedByTheDefaultLanguage(): void
    {
        $element = $this->createElement(localizedKeys: ['name']);

        $this->createResolver('de')->resolveForCoreElement($this->createColumn('fr'), $element);

        self::assertSame([['fr']], $element->calls);
    }

    public function testNonLocalizedColumnWithoutLocaleIsReadWithoutLanguage(): void
    {
        $element = $this->createElement(localizedKeys: []);

        $this->createResolver('de')->resolveForCoreElement($this->createColumn(null), $element);

        self::assertSame([[]], $element->calls);
    }

    /**
     * The export read must use the same language the permission check authorized: the system default language,
     * not the request locale a missing "language" parameter would fall back to.
     */
    public function testExportOfLocalizedColumnWithoutLocaleReadsTheSystemDefaultLanguage(): void
    {
        $exportParameters = null;
        $resolver = $this->createResolver('de', [
            'getExportFieldValue' => static function (
                $element,
                $fieldDefinition,
                string $key,
                FieldContextData $context
            ) use (&$exportParameters): string {
                $exportParameters = $context->getLegacyParameters();

                return 'value';
            },
        ]);

        $resolver->resolveForExport(
            $this->createColumn(null),
            $this->createElement(localizedKeys: ['name']),
            $this->makeEmpty(UserInterface::class)
        );

        self::assertSame(['language' => 'de'], $exportParameters);
    }

    public function testInheritanceDataOfLocalizedColumnWithoutLocaleUsesTheSystemDefaultLanguage(): void
    {
        $inheritanceLanguage = 'not called';
        $resolver = new AdapterResolver(
            $this->makeEmpty(DataServiceInterface::class, [
                'getNormalizedValue' => static fn (mixed $value): mixed => $value,
            ]),
            $this->makeEmpty(InheritanceServiceInterface::class, [
                'processFieldDefinition' => static function (
                    $element,
                    $fieldDefinition,
                    string $key,
                    FieldContextData $context
                ) use (&$inheritanceLanguage): array {
                    $inheritanceLanguage = $context->getLanguage();

                    return [];
                },
            ]),
            $this->makeEmpty(DataObjectServiceResolverInterface::class, [
                'useInheritedValues' => static fn (bool $inherit, callable $callback): mixed => $callback(),
            ]),
            $this->makeEmpty(ToolResolverInterface::class, ['getDefaultLanguage' => 'de']),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            new AdvancedColumnSourceFieldContext(),
        );

        $resolver->resolveForCoreElement(
            $this->createColumn(null),
            $this->createElement(localizedKeys: ['name'], allowInherit: true)
        );

        self::assertSame('de', $inheritanceLanguage);
    }

    private function createColumn(?string $locale): Column
    {
        return new Column(key: 'name', locale: $locale, type: 'dataobject.adapter', group: null, config: []);
    }

    /**
     * @param array<string, mixed> $dataServiceOverrides
     */
    private function createResolver(?string $defaultLanguage, array $dataServiceOverrides = []): AdapterResolver
    {
        return new AdapterResolver(
            $this->makeEmpty(DataServiceInterface::class, [
                'getNormalizedValue' => static fn (mixed $value): mixed => $value,
            ] + $dataServiceOverrides),
            $this->makeEmpty(InheritanceServiceInterface::class),
            $this->makeEmpty(DataObjectServiceResolverInterface::class),
            $this->makeEmpty(ToolResolverInterface::class, ['getDefaultLanguage' => $defaultLanguage]),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            new AdvancedColumnSourceFieldContext(),
        );
    }

    /**
     * @param list<string> $localizedKeys
     */
    private function createElement(array $localizedKeys, bool $allowInherit = false): Concrete
    {
        $fieldDefinition = new Input();
        $fieldDefinition->setName('name');

        // ClassDefinition is final and cannot be doubled - a real instance seeded through
        // addFieldDefinition() behaves like a loaded definition for the field lookup.
        $classDefinition = new ClassDefinition();
        $classDefinition->setAllowInherit($allowInherit);

        if ($localizedKeys === []) {
            $classDefinition->addFieldDefinition('name', $fieldDefinition);
        } else {
            $classDefinition->addFieldDefinition('localizedfields', $this->makeEmpty(Localizedfields::class, [
                'getFieldDefinition' => static fn (string $key) => in_array($key, $localizedKeys, true)
                    ? $fieldDefinition
                    : null,
            ]));
        }

        return new class($classDefinition) extends Concrete {
            /** @var list<list<string>> */
            public array $calls = [];

            public function __construct(private readonly ClassDefinition $classDefinition)
            {
            }

            public function getClass(): ClassDefinition
            {
                return $this->classDefinition;
            }

            public function getName(?string $language = null): string
            {
                $this->calls[] = $language === null ? [] : [$language];

                return 'value';
            }
        };
    }
}
