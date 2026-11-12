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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\InheritanceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\AdapterResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedColumnSourceFieldContext;
use ReflectionMethod;

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
}
