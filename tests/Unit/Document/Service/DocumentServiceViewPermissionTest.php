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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Document\Service;

use Closure;
use Codeception\Stub\Expected;
use Codeception\Stub\StubMarshaler;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DocumentQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentDetail;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentPermissions;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\CreateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\User;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class DocumentServiceViewPermissionTest extends Unit
{
    private const int ELEMENT_ID = 9;

    public function testGetDocumentRejectsElementWithoutViewPermission(): void
    {
        $service = $this->createService($this->createElement(false), Expected::never());

        $this->expectException(ForbiddenException::class);
        $service->getDocument(self::ELEMENT_ID);
    }

    public function testGetDocumentForUserRejectsElementWithoutViewPermission(): void
    {
        $service = $this->createService($this->createElement(false), Expected::never());

        $this->expectException(ForbiddenException::class);
        $service->getDocumentForUser(self::ELEMENT_ID, new User());
    }

    public function testGetDocumentForUserReturnsViewableElement(): void
    {
        $element = $this->createElement(true);
        $service = $this->createService($element, Expected::once(static fn (object $event) => $event));

        $this->assertSame($element, $service->getDocumentForUser(self::ELEMENT_ID, new User()));
    }

    private function createElement(bool $view): DocumentDetail
    {
        $element = (new ReflectionClass(DocumentDetail::class))->newInstanceWithoutConstructor();
        // The permissions property is declared private in the parent schema class.
        Closure::bind(
            function ($permissions): void {
                $this->permissions = $permissions;
            },
            $element,
            Document::class
        )(new DocumentPermissions(view: $view));

        return $element;
    }

    private function createService(DocumentDetail $element, StubMarshaler $dispatch): DocumentService
    {
        $searchService = $this->makeEmpty(DocumentSearchServiceInterface::class, [
            'getDocumentById' => Expected::once(function (int $id) use ($element) {
                $this->assertSame(self::ELEMENT_ID, $id);

                return $element;
            }),
        ]);

        return new DocumentService(
            $this->makeEmpty(CreateServiceInterface::class),
            $this->makeEmpty(DataServiceInterface::class, ['setDocumentDetailData' => Expected::never()]),
            $this->makeEmpty(DocumentQueryProviderInterface::class),
            $searchService,
            $this->makeEmpty(DocumentServiceResolverInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class, ['dispatch' => $dispatch]),
            $this->makeEmpty(FilterServiceProviderInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => new User()]),
            $this->makeEmpty(ServiceResolverInterface::class),
        );
    }
}
