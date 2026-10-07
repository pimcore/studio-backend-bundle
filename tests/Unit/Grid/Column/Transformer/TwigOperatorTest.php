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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Transformer;

use Carbon\Carbon;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\ConsentData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Transformer\TwigOperator;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\Perspectives;
use Pimcore\Bundle\StudioBackendBundle\Twig\Initializers\SandboxExtensionInitializer;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Twig\DefaultSandboxPolicyTrait;
use Pimcore\Bundle\StudioBackendBundle\Twig\TemplateGenerator;
use JsonSerializable;
use Random\IntervalBoundary;
use stdClass;
use Stringable;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Runs templates through the real pipeline (TwigOperator -> TemplateGenerator -> isolated Environment),
 * the same way a grid's advanced column does.
 *
 * @internal
 */
final class TwigOperatorTest extends Unit
{
    use DefaultSandboxPolicyTrait;

    public function testDateValueReachesTheTemplateAsAString(): void
    {
        $result = $this->transform(
            [$this->dateRangeValue()],
            '{{ value.range[0] is iterable ? "iterable" : "scalar" }}[{{ value.range[0].format("Y") }}]'
        );

        $this->assertSame('scalar[]', $result);
    }

    public function testDateFormattingOfTheConvertedValueStillWorks(): void
    {
        $result = $this->transform(
            [$this->dateRangeValue()],
            '{{ value.range[0]|date("Y-m-d") }} - {{ value.range[1]|date("Y-m-d") }}'
        );

        $this->assertSame('2020-01-01 - 2020-01-05', $result);
    }

    public function testNonDateObjectIsDroppedRatherThanExposed(): void
    {
        $value = new AdvancedValue('object', new stdClass(), 'mystery');

        $result = $this->transform([$value], '{{ value.mystery is null ? "dropped" : "leaked" }}');

        $this->assertSame('dropped', $result);
    }

    public function testObjectsNestedInRelationsAreSanitizedToo(): void
    {
        $value = new AdvancedValue('date', Carbon::parse('2020-06-15'), 'purchasedAt', 'order');

        $result = $this->transform([$value], '{{ value.order.purchasedAt|date("Y-m-d") }}');

        $this->assertSame('2020-06-15', $result);
    }

    private function dateRangeValue(): AdvancedValue
    {
        return new AdvancedValue(
            'daterange',
            [Carbon::parse('2020-01-01'), Carbon::parse('2020-01-05')],
            'range'
        );
    }

    public function testConsentValueIsConvertedToData(): void
    {
        $value = new AdvancedValue('consent', new ConsentData(true, 5, 'Signed up at the fair'), 'newsletter');

        $result = $this->transform(
            [$value],
            '{{ value.newsletter.consent ? "yes" : "no" }}|{{ value.newsletter.noteId }}|' .
            '{{ value.newsletter.noteContent }}'
        );

        $this->assertSame('yes|5|Signed up at the fair', $result);
    }

    public function testJsonSerializableIsConvertedToItsData(): void
    {
        $object = new class implements JsonSerializable {
            public function jsonSerialize(): array
            {
                return ['label' => 'Red', 'createdAt' => Carbon::parse('2020-01-01T00:00:00+00:00')];
            }
        };

        $result = $this->transform(
            [new AdvancedValue('object', $object, 'color')],
            '{{ value.color.label }}|{{ value.color.createdAt }}'
        );

        $this->assertSame('Red|2020-01-01T00:00:00+00:00', $result);
    }

    public function testEnumsAreConvertedToScalars(): void
    {
        $result = $this->transform(
            [
                new AdvancedValue('enum', Perspectives::DEFAULT_ID, 'backed'),
                new AdvancedValue('enum', IntervalBoundary::ClosedOpen, 'pure'),
            ],
            '{{ value.backed }}|{{ value.pure }}'
        );

        $this->assertSame('studio_default_perspective|ClosedOpen', $result);
    }

    public function testStringableObjectIsNotCastToString(): void
    {
        $object = new class implements Stringable {
            public function __toString(): string
            {
                return 'cast';
            }
        };

        $result = $this->transform([new AdvancedValue('object', $object, 'thing')], '[{{ value.thing }}]');

        $this->assertSame('[]', $result);
    }

    public function testSelfReferencingJsonSerializableDoesNotRecurseForever(): void
    {
        $object = new class implements JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return $this;
            }
        };

        $result = $this->transform([new AdvancedValue('object', $object, 'loop')], '[{{ value.loop }}]');

        $this->assertSame('[]', $result);
    }

    /**
     * @param list<AdvancedValue> $values
     */
    private function transform(array $values, string $template): string
    {
        $policy = $this->getDefaultSandboxPolicy();

        $initializer = new SandboxExtensionInitializer(
            new Environment(new ArrayLoader()),
            $policy['tags'],
            $policy['filters'],
            $policy['functions']
        );
        $operator = new TwigOperator(new TemplateGenerator(new Environment(new ArrayLoader()), $initializer));

        $result = $operator->transform($values, ['template' => $template]);

        return (string) $result[0]->getValue();
    }
}
