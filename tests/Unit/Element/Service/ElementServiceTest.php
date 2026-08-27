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
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementFolderPaths;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
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
     * @param array<int, string>|null $checkedPaths paths the security service was asked about
     *
     * @throws Exception
     */
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
     * @throws Exception
     */
    private function makeUser(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, ['getId' => 42]);
    }
}
