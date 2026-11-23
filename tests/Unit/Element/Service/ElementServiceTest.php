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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ElementSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementFolderPaths;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Folder as DocumentFolder;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function end;
use function sprintf;

/**
 * @internal
 */
final class ElementServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetAllowedElementByPathChecksViewPermission(): void
    {
        $service = $this->createService($checkedPaths);

        $element = $service->getAllowedElementByPath(
            ElementTypes::TYPE_DATA_OBJECT,
            '/some-folder',
            $this->makeUser()
        );

        $this->assertSame(7221, $element->getId());
        $this->assertSame(['/some-folder'], $checkedPaths);
    }

    /**
     * The root path must not be exempted from the check here: this method resolves elements a user
     * opens, so it has to stay unconditional. Tree traversal uses getNavigableElementByPath().
     *
     * @throws Exception
     */
    public function testGetAllowedElementByPathChecksViewPermissionForRootPath(): void
    {
        $service = $this->createService($checkedPaths, forbidden: true);

        $this->expectException(ForbiddenException::class);

        $service->getAllowedElementByPath(
            ElementTypes::TYPE_DATA_OBJECT,
            ElementFolderPaths::ROOT->value,
            $this->makeUser()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetNavigableElementByPathDoesNotCheckPermissions(): void
    {
        // A user only ever navigates through these paths, and an ancestor of an allowed path never
        // carries permissions itself - so no permission check must happen, not even for the root.
        $service = $this->createService($checkedPaths, forbidden: true);

        foreach ([ElementFolderPaths::ROOT->value, '/some-folder'] as $path) {
            $element = $service->getNavigableElementByPath(ElementTypes::TYPE_DATA_OBJECT, $path);

            $this->assertSame(7221, $element->getId());
        }

        $this->assertSame([], $checkedPaths);
    }

    /**
     * @throws Exception
     */
    public function testGetNavigableElementByPathThrowsForUnknownPath(): void
    {
        $service = $this->createService($checkedPaths, elementFound: false);

        $this->expectException(NotFoundException::class);

        $service->getNavigableElementByPath(ElementTypes::TYPE_DATA_OBJECT, '/does-not-exist');
    }

    /**
     * @throws Exception
     */
    public function testDocumentChildrenCountIncludingUnpublished(): void
    {
        $folder = $this->createMock(DocumentFolder::class);
        $folder->expects($this->once())->method('hasChildren')->with(true)->willReturn(true);

        $this->assertTrue($this->createService()->hasElementChildren($folder));
    }

    /**
     * @throws Exception
     */
    public function testDataObjectChildrenCountIncludingUnpublished(): void
    {
        $folder = $this->createMock(DataObject\Folder::class);
        $folder->expects($this->once())
            ->method('hasChildren')
            ->with($this->anything(), true)
            ->willReturn(true);

        $this->assertTrue($this->createService()->hasElementChildren($folder));
    }

    /**
     * @throws Exception
     */
    public function testAssetFolderChildren(): void
    {
        $folder = $this->createMock(Asset\Folder::class);
        $folder->method('hasChildren')->willReturn(false);

        $this->assertFalse($this->createService()->hasElementChildren($folder));
    }

    /**
     * @throws Exception
     */
    public function testOtherElementsHaveNoChildren(): void
    {
        $this->assertFalse($this->createService()->hasElementChildren($this->makeEmpty(ElementInterface::class)));
    }

    /**
     * @param array<int, string>|null $checkedPaths paths the security service was asked about
     *
     * @throws Exception
     */
    /**
     * @throws Exception
     */
    public function testGetElementPublishedStateReportsUnpublishedDocument(): void
    {
        $element = $this->makeEmpty(Page::class, ['getId' => 7221, 'getPublished' => false]);
        $service = $this->createPublishedStateService($element);

        $state = $service->getElementPublishedState(new ElementParameters(ElementTypes::TYPE_DOCUMENT, 7221));

        $this->assertSame(7221, $state->getElementId());
        $this->assertSame(ElementTypes::TYPE_DOCUMENT, $state->getElementType());
        $this->assertFalse($state->getIsPublished());
    }

    /**
     * @throws Exception
     */
    public function testGetElementPublishedStateReportsPublishedDataObject(): void
    {
        $element = $this->makeEmpty(Concrete::class, ['getId' => 7221, 'getPublished' => true]);
        $service = $this->createPublishedStateService($element);

        $state = $service->getElementPublishedState(new ElementParameters(ElementTypes::TYPE_DATA_OBJECT, 7221));

        $this->assertTrue($state->getIsPublished());
        // The response echoes the type of the request path, not the core type ("object") it is resolved with
        $this->assertSame(ElementTypes::TYPE_DATA_OBJECT, $state->getElementType());
    }

    /**
     * @throws Exception
     */
    public function testGetElementPublishedStateIsNullForAssets(): void
    {
        $element = $this->makeEmpty(Asset::class, ['getId' => 7221]);
        $service = $this->createPublishedStateService($element);

        $state = $service->getElementPublishedState(new ElementParameters(ElementTypes::TYPE_ASSET, 7221));

        $this->assertNull($state->getIsPublished());
    }

    /**
     * An element the user may not view must not reveal its state.
     *
     * @throws Exception
     */
    public function testGetElementPublishedStateChecksViewPermission(): void
    {
        $element = $this->makeEmpty(Page::class, ['getId' => 7221, 'getPublished' => false]);
        $service = $this->createPublishedStateService($element, $checkedPermissions, forbidden: true);

        try {
            $service->getElementPublishedState(new ElementParameters(ElementTypes::TYPE_DOCUMENT, 7221));
            $this->fail('Expected a ForbiddenException');
        } catch (ForbiddenException) {
            $this->assertSame(['view'], $checkedPermissions);
        }
    }

    /**
     * @throws Exception
     */
    public function testGetElementPublishedStateThrowsForUnknownElement(): void
    {
        $service = $this->createPublishedStateService(null);

        $this->expectException(NotFoundException::class);

        $service->getElementPublishedState(new ElementParameters(ElementTypes::TYPE_DOCUMENT, 7221));
    }

    private function createService(
        ?array &$checkedPaths = null,
        bool $forbidden = false,
        bool $elementFound = true
    ): ElementService {
        $checkedPaths = [];
        $resolvedPaths = [];

        $element = $elementFound ? $this->makeEmpty(ElementInterface::class, ['getId' => 7221]) : null;

        $serviceResolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementByPath' => function (string $type, string $path) use (&$resolvedPaths, $element) {
                $resolvedPaths[] = $path;

                return $element;
            },
        ]);

        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'hasElementPermission' => function (
                ElementInterface $element,
                UserInterface $user,
                string $permission
            ) use (&$checkedPaths, &$resolvedPaths, $forbidden): void {
                $checkedPaths[] = end($resolvedPaths);

                if ($forbidden) {
                    throw new ForbiddenException(sprintf('You dont have %s permission', $permission));
                }
            },
        ]);

        return new ElementService(
            $this->makeEmpty(ElementSearchServiceInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class),
            $serviceResolver,
            $securityService
        );
    }

    /**
     * @param string[]|null $checkedPermissions
     *
     * @throws Exception
     */
    private function createPublishedStateService(
        ?ElementInterface $element,
        ?array &$checkedPermissions = null,
        bool $forbidden = false
    ): ElementService {
        $checkedPermissions = [];

        $serviceResolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => fn () => $element,
        ]);

        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeUser(),
            'hasElementPermission' => function (
                ElementInterface $element,
                UserInterface $user,
                string $permission
            ) use (&$checkedPermissions, $forbidden): void {
                $checkedPermissions[] = $permission;

                if ($forbidden) {
                    throw new ForbiddenException(sprintf('You dont have %s permission', $permission));
                }
            },
        ]);

        return new ElementService(
            $this->makeEmpty(ElementSearchServiceInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class),
            $serviceResolver,
            $securityService
        );
    }

    /**
     * @throws Exception
     */
    private function makeUser(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, ['getId' => 42]);
    }
}
