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
use Pimcore\Bundle\SeoBundle\Model\ElementType;
use Pimcore\Bundle\SeoBundle\Model\Redirect as RedirectModel;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Hydrator\RedirectHydrator;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;

/**
 * @internal
 */
final class RedirectHydratorTest extends Unit
{
    public function testNumericDocumentTargetIsResolvedToItsPath(): void
    {
        $document = $this->createMock(Document::class);
        $document->method('getRealFullPath')->willReturn('/en/landing-page');

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->expects($this->once())
            ->method('getElementById')
            ->with('document', 5)
            ->willReturn($document);

        $result = (new RedirectHydrator($serviceResolver))->hydrate(
            $this->redirect('5', ElementType::Document)
        );

        $this->assertSame('/en/landing-page', $result->getTarget());
        $this->assertSame('document', $result->getTargetType());
    }

    public function testNumericTargetWithoutTargetTypeIsResolvedAsDocument(): void
    {
        $document = $this->createMock(Document::class);
        $document->method('getRealFullPath')->willReturn('/en/legacy');

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->expects($this->once())
            ->method('getElementById')
            ->with('document', 5)
            ->willReturn($document);

        $result = (new RedirectHydrator($serviceResolver))->hydrate($this->redirect('5', null));

        $this->assertSame('/en/legacy', $result->getTarget());
        $this->assertNull($result->getTargetType());
    }

    public function testNumericAssetTargetIsResolvedToItsPath(): void
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getRealFullPath')->willReturn('/manual.pdf');

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->expects($this->once())
            ->method('getElementById')
            ->with('asset', 7)
            ->willReturn($asset);

        $result = (new RedirectHydrator($serviceResolver))->hydrate(
            $this->redirect('7', ElementType::Asset)
        );

        $this->assertSame('/manual.pdf', $result->getTarget());
        $this->assertSame('asset', $result->getTargetType());
    }

    public function testNonNumericTargetIsPassedThrough(): void
    {
        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->expects($this->never())->method('getElementById');

        $result = (new RedirectHydrator($serviceResolver))->hydrate(
            $this->redirect('https://example.com', null)
        );

        $this->assertSame('https://example.com', $result->getTarget());
        $this->assertNull($result->getTargetType());
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
