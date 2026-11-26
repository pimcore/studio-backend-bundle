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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Bundle\Seo\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\SeoBundle\Model\Redirect as RedirectModel;
use Pimcore\Bundle\SeoBundle\Redirect\TargetResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Hydrator\RedirectHydrator;
use Pimcore\Model\Element\ElementType;

/**
 * @internal
 */
final class RedirectHydratorTest extends Unit
{
    public function testStoredElementTargetIsShownAsItsPath(): void
    {
        $resolver = $this->createMock(TargetResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolvePath')
            ->with('5', ElementType::Asset)
            ->willReturn('/manual.pdf');

        $result = (new RedirectHydrator($resolver))->hydrate($this->redirect('5', ElementType::Asset));

        $this->assertSame('/manual.pdf', $result->getTarget());
        $this->assertSame('asset', $result->getTargetType());
    }

    public function testTargetTypeIsPassedOnForLegacyUntypedTargets(): void
    {
        $resolver = $this->createMock(TargetResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolvePath')
            ->with('5', null)
            ->willReturn('/en/legacy');

        $result = (new RedirectHydrator($resolver))->hydrate($this->redirect('5', null));

        $this->assertSame('/en/legacy', $result->getTarget());
        $this->assertNull($result->getTargetType());
    }

    public function testUnresolvableTargetIsPassedThrough(): void
    {
        $resolver = $this->createMock(TargetResolverInterface::class);
        $resolver->method('resolvePath')->willReturn(null);

        $result = (new RedirectHydrator($resolver))->hydrate($this->redirect('https://example.com', null));

        $this->assertSame('https://example.com', $result->getTarget());
        $this->assertNull($result->getTargetType());
    }

    public function testEmptyTargetIsNotResolved(): void
    {
        $resolver = $this->createMock(TargetResolverInterface::class);
        $resolver->expects($this->never())->method('resolvePath');

        $redirect = new RedirectModel();
        $redirect->setType(RedirectModel::TYPE_PATH);
        $redirect->setSource('/old');

        $this->assertNull((new RedirectHydrator($resolver))->hydrate($redirect)->getTarget());
    }

    private function redirect(string $target, ?ElementType $targetType): RedirectModel
    {
        $redirect = new RedirectModel();
        $redirect->setType(RedirectModel::TYPE_PATH);
        $redirect->setSource('/old');
        $redirect->setTarget($target);
        $redirect->setTargetType($targetType);

        return $redirect;
    }
}
