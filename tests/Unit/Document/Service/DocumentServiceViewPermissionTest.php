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

use Codeception\Stub\Expected;
use Codeception\Stub\StubMarshaler;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DocumentQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentDetail;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\CreateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\Document\Folder as CoreFolder;
use Pimcore\Model\User;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class DocumentServiceViewPermissionTest extends Unit
{
    private const int ELEMENT_ID = 9;

    private User $user;

    protected function _before(): void
    {
        $this->user = new User();
    }

    public function testGetDocumentRejectsElementWithoutViewPermission(): void
    {
        $service = $this->createService(false, Expected::never());

        $this->expectException(ForbiddenException::class);
        $service->getDocument(self::ELEMENT_ID);
    }

    public function testGetDocumentForUserRejectsElementWithoutViewPermission(): void
    {
        $service = $this->createService(false, Expected::never());

        $this->expectException(ForbiddenException::class);
        $service->getDocumentForUser(self::ELEMENT_ID, $this->user);
    }

    public function testGetDocumentForUserReturnsViewableElement(): void
    {
        $element = $this->createElement();
        $service = $this->createService(true, Expected::once(static fn (object $event) => $event), $element);

        $this->assertSame($element, $service->getDocumentForUser(self::ELEMENT_ID, $this->user));
    }

    public function testGetDocumentReturnsViewableElement(): void
    {
        $element = $this->createElement();
        $service = $this->createService(true, Expected::once(static fn (object $event) => $event), $element);

        $this->assertSame($element, $service->getDocument(self::ELEMENT_ID, false));
    }

    private function createElement(): DocumentDetail
    {
        return (new ReflectionClass(DocumentDetail::class))->newInstanceWithoutConstructor();
    }

    private function createService(bool $view, StubMarshaler $dispatch, ?DocumentDetail $element = null): DocumentService
    {
        // Without view permission, the search index must not be queried at all.
        $lookup = $view ? Expected::once(function (int $id, ?User $user) use ($element) {
            $this->assertSame(self::ELEMENT_ID, $id);
            $this->assertSame($this->user, $user);

            return $element;
        }) : Expected::never();
        $searchService = $this->makeEmpty(DocumentSearchServiceInterface::class, ['getDocumentById' => $lookup]);
        $coreElement = $this->makeEmpty(CoreFolder::class, [
            'isAllowed' => function (string $permission, User $user) use ($view): bool {
                $this->assertSame(ElementPermissions::VIEW_PERMISSION, $permission);
                $this->assertSame($this->user, $user);

                return $view;
            },
        ]);

        return new DocumentService(
            $this->makeEmpty(CreateServiceInterface::class),
            $this->makeEmpty(DataServiceInterface::class, ['setDocumentDetailData' => Expected::never()]),
            $this->makeEmpty(DocumentQueryProviderInterface::class),
            $searchService,
            $this->makeEmpty(DocumentServiceResolverInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class, ['dispatch' => $dispatch]),
            $this->makeEmpty(FilterServiceProviderInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $this->user]),
            $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => $coreElement]),
        );
    }
}
