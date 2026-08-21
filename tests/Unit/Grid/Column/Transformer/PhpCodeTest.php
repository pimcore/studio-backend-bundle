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

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\PhpCodeTransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Transformer\PhpCode;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\PhpCodeTransformerLoaderInterface;

/**
 * @internal
 */
final class PhpCodeTest extends Unit
{
    public function testGetConfigOptionsExposesSelectableValueForEachTransformer(): void
    {
        // Regression test for https://github.com/pimcore/platform-version/issues/295:
        // the options were built with a "key" entry instead of "value", so the
        // frontend antd Select never received a selectable value.
        $lowercase = $this->makeEmpty(PhpCodeTransformerInterface::class, [
            'getKey' => 'lowercase',
            'getName' => 'Lowercase',
        ]);
        $uppercase = $this->makeEmpty(PhpCodeTransformerInterface::class, [
            'getKey' => 'uppercase',
            'getName' => 'Uppercase',
        ]);

        $resolver = $this->makeEmpty(PhpCodeTransformerLoaderInterface::class, [
            'getTransformers' => [$lowercase, $uppercase],
        ]);

        $transformer = new PhpCode($resolver);

        $options = $transformer->getConfigOptions()['phpCodeKey']['options'];

        $this->assertSame(
            [
                ['value' => 'lowercase', 'label' => 'Lowercase'],
                ['value' => 'uppercase', 'label' => 'Uppercase'],
            ],
            $options
        );
    }

    public function testGetConfigOptionsReturnsEmptyOptionsWhenNoTransformersAreRegistered(): void
    {
        $resolver = $this->makeEmpty(PhpCodeTransformerLoaderInterface::class, [
            'getTransformers' => [],
        ]);

        $transformer = new PhpCode($resolver);

        $this->assertSame([], $transformer->getConfigOptions()['phpCodeKey']['options']);
    }
}
