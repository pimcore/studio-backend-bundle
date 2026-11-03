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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Resolver\System;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\System\StringResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
final class StringResolverTest extends Unit
{
    /**
     * The grid authorizes the system default language for a localized field without column locale. The client
     * controls the column type, so a system column on a localized key must not fall back to a bare getter, which
     * would read the request locale - a language the permission check never looked at.
     */
    public function testLocalizedFieldKeyYieldsNoValue(): void
    {
        $element = $this->createElement(localized: true);

        $data = (new StringResolver())->resolveForCoreElement($this->createColumn(), $element);

        self::assertNull($data->getValue());
        self::assertSame(0, $element->getterCalls);
    }

    public function testLocalizedFieldKeyInAnotherLetterCaseYieldsNoValue(): void
    {
        $element = $this->createElement(localized: true);
        $column = new Column(key: 'Name', locale: null, type: 'system.string', group: null, config: []);

        $data = (new StringResolver())->resolveForCoreElement($column, $element);

        self::assertNull($data->getValue());
        self::assertSame(0, $element->getterCalls);
    }

    /**
     * The "filename" column of a data object is its key. A localized field that happens to be called "filename"
     * must not blank that system column.
     */
    public function testFilenameColumnReadsTheKeyDespiteLocalizedFilenameField(): void
    {
        $element = $this->createElement(localized: true, localizedName: 'filename', key: 'object-key');
        $column = new Column(key: 'filename', locale: null, type: 'system.string', group: null, config: []);

        $data = (new StringResolver())->resolveForCoreElement($column, $element);

        self::assertSame('object-key', $data->getValue());
    }

    public function testNonLocalizedKeyIsStillRead(): void
    {
        $element = $this->createElement(localized: false);

        $data = (new StringResolver())->resolveForCoreElement($this->createColumn(), $element);

        self::assertSame('value', $data->getValue());
        self::assertSame(1, $element->getterCalls);
    }

    private function createColumn(): Column
    {
        return new Column(key: 'name', locale: null, type: 'system.string', group: null, config: []);
    }

    private function createElement(bool $localized, string $localizedName = 'name', string $key = 'k'): Concrete
    {
        $fieldDefinition = new Input();
        $fieldDefinition->setName($localizedName);

        $classDefinition = new ClassDefinition();
        if ($localized) {
            $classDefinition->addFieldDefinition('localizedfields', $this->makeEmpty(Localizedfields::class, [
                'getFieldDefinition' => static fn (string $key) => $key === $localizedName ? $fieldDefinition : null,
                // Enrichment (provider calls for every localized field) must not run per system cell.
                'getFieldDefinitions' => function (array $context = []) use ($localizedName, $fieldDefinition): array {
                    $this->assertTrue($context['suppressEnrichment'] ?? false);

                    return [$localizedName => $fieldDefinition];
                },
            ]));
        } else {
            $classDefinition->addFieldDefinition($localizedName, $fieldDefinition);
        }

        return new class($classDefinition, $key) extends Concrete {
            public int $getterCalls = 0;

            public function __construct(private readonly ClassDefinition $classDefinition, string $key)
            {
                $this->setKey($key);
            }

            public function getClass(): ClassDefinition
            {
                return $this->classDefinition;
            }

            public function getName(?string $language = null): string
            {
                ++$this->getterCalls;

                return 'value';
            }
        };
    }
}
