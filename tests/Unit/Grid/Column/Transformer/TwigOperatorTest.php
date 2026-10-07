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
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Transformer\TwigOperator;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use Pimcore\Bundle\StudioBackendBundle\Twig\Initializers\SandboxExtensionInitializer;
use Pimcore\Bundle\StudioBackendBundle\Twig\TemplateGenerator;
use ReflectionMethod;
use stdClass;
use Symfony\Component\Config\Definition\Processor;
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

    /**
     * @return array{tags: list<string>, filters: list<string>, functions: list<string>}
     */
    private function getDefaultSandboxPolicy(): array
    {
        $method = new ReflectionMethod(Configuration::class, 'addTwigSandboxNode');
        $method->setAccessible(true);
        $node = $method->invoke(new Configuration())->getNode(true);

        $processed = (new Processor())->process($node, []);

        return $processed['sandbox_security_policy'];
    }
}
