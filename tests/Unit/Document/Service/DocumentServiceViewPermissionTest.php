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
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DocumentQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\CreateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\Document\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class DocumentServiceViewPermissionTest extends Unit
{
    private const int DOCUMENT_ID = 9;

    public function testGetDocumentRequiresViewPermission(): void
    {
        $folder = $this->makeEmpty(Folder::class);

        $service = $this->createService($this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => new User(),
            'hasElementPermission' => Expected::once(
                function (ElementInterface $element, User $user, string $permission) use ($folder) {
                    $this->assertSame($folder, $element);
                    $this->assertSame(ElementPermissions::VIEW_PERMISSION, $permission);

                    throw new ForbiddenException();
                }
            ),
        ]), $folder);

        $this->expectException(ForbiddenException::class);
        $service->getDocument(self::DOCUMENT_ID);
    }

    public function testGetDocumentForUserRequiresViewPermission(): void
    {
        $service = $this->createService($this->makeEmpty(SecurityServiceInterface::class, [
            'hasElementPermission' => static function () {
                throw new ForbiddenException();
            },
        ]), $this->makeEmpty(Folder::class));

        $this->expectException(ForbiddenException::class);
        $service->getDocumentForUser(self::DOCUMENT_ID, new User());
    }

    private function createService(
        SecurityServiceInterface $securityService,
        ElementInterface $element
    ): DocumentService {
        return new DocumentService(
            $this->makeEmpty(CreateServiceInterface::class),
            $this->makeEmpty(DataServiceInterface::class, ['setDocumentDetailData' => Expected::never()]),
            $this->makeEmpty(DocumentQueryProviderInterface::class),
            $this->makeEmpty(DocumentSearchServiceInterface::class, [
                // Without view permission, the search index must not be queried at all.
                'getDocumentById' => Expected::never(),
            ]),
            $this->makeEmpty(DocumentServiceResolverInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class, ['dispatch' => Expected::never()]),
            $this->makeEmpty(FilterServiceProviderInterface::class),
            $securityService,
            $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => $element]),
        );
    }
}
