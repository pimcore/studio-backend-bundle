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
use Pimcore\Bundle\StaticResolverBundle\Models\Element\EditLockResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ElementSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Hydrator\EditLockHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\EditLock;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\EditLockService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Document;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
final class EditLockServiceTest extends Unit
{
    private const int ELEMENT_ID = 7221;

    /**
     * @var string[]
     */
    private array $calls = [];

    protected function _before(): void
    {
        $this->calls = [];
    }

    /**
     * @throws Exception
     */
    public function testLockElementWithoutViewPermissionIsForbidden(): void
    {
        $service = $this->createService(forbidden: true);

        $this->expectException(ForbiddenException::class);

        try {
            $service->lockElement(self::ELEMENT_ID, ElementTypes::TYPE_DATA_OBJECT);
        } finally {
            $this->assertSame([], $this->calls);
        }
    }

    /**
     * @throws Exception
     */
    public function testLockElementOfMissingElementIsNotFound(): void
    {
        $service = $this->createService(elementFound: false);

        $this->expectException(NotFoundException::class);

        try {
            $service->lockElement(self::ELEMENT_ID, ElementTypes::TYPE_DATA_OBJECT);
        } finally {
            $this->assertSame([], $this->calls);
        }
    }

    /**
     * @throws Exception
     */
    public function testLockElementWithViewPermissionLocks(): void
    {
        $service = $this->createService();

        $service->lockElement(self::ELEMENT_ID, ElementTypes::TYPE_DATA_OBJECT);

        $this->assertSame(['lock:' . self::ELEMENT_ID . ':object'], $this->calls);
    }

    /**
     * @throws Exception
     */
    public function testUnlockElementWithoutViewPermissionIsForbidden(): void
    {
        $service = $this->createService(forbidden: true);

        $this->expectException(ForbiddenException::class);

        try {
            $service->unlockElement(self::ELEMENT_ID, ElementTypes::TYPE_ASSET);
        } finally {
            $this->assertSame([], $this->calls);
        }
    }

    /**
     * @throws Exception
     */
    public function testUnlockElementOfMissingElementIsNotFound(): void
    {
        $service = $this->createService(elementFound: false);

        $this->expectException(NotFoundException::class);

        try {
            $service->unlockElement(self::ELEMENT_ID, ElementTypes::TYPE_ASSET);
        } finally {
            $this->assertSame([], $this->calls);
        }
    }

    /**
     * @throws Exception
     */
    public function testUnlockElementWithViewPermissionUnlocks(): void
    {
        $service = $this->createService();

        $service->unlockElement(self::ELEMENT_ID, ElementTypes::TYPE_ASSET);

        $this->assertSame(['unlock:' . self::ELEMENT_ID . ':asset'], $this->calls);
    }

    /**
     * @throws Exception
     */
    public function testGetEditLockWithoutViewPermissionIsForbidden(): void
    {
        $service = $this->createService(forbidden: true);

        $this->expectException(ForbiddenException::class);

        try {
            $service->getEditLock(self::ELEMENT_ID, ElementTypes::TYPE_DOCUMENT);
        } finally {
            $this->assertSame([], $this->calls);
        }
    }

    /**
     * @throws Exception
     */
    public function testGetEditLockOfMissingElementIsNotFound(): void
    {
        $service = $this->createService(elementFound: false);

        $this->expectException(NotFoundException::class);

        try {
            $service->getEditLock(self::ELEMENT_ID, ElementTypes::TYPE_DOCUMENT);
        } finally {
            $this->assertSame([], $this->calls);
        }
    }

    /**
     * @throws Exception
     */
    public function testGetEditLockWithViewPermissionReturnsLock(): void
    {
        $service = $this->createService();

        $editLock = $service->getEditLock(self::ELEMENT_ID, ElementTypes::TYPE_DOCUMENT);

        $this->assertFalse($editLock->getIsLocked());
        $this->assertSame(['isLocked:' . self::ELEMENT_ID . ':document'], $this->calls);
    }

    /**
     * @throws Exception
     */
    private function createService(bool $forbidden = false, bool $elementFound = true): EditLockService
    {
        $element = $elementFound ? $this->makeEmpty(Document::class, ['getId' => self::ELEMENT_ID]) : null;

        $serviceResolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => $element,
        ]);

        $user = $this->makeEmpty(UserInterface::class);

        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $user,
            'hasElementPermission' => function () use ($forbidden): void {
                if ($forbidden) {
                    throw new ForbiddenException('You dont have view permission');
                }
            },
        ]);

        $elementService = new ElementService(
            $this->makeEmpty(ElementSearchServiceInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class),
            $serviceResolver,
            $securityService
        );

        $editLockResolver = $this->makeEmpty(EditLockResolverInterface::class, [
            'lock' => function (int $id, string $type): bool {
                $this->calls[] = 'lock:' . $id . ':' . $type;

                return true;
            },
            'unlock' => function (int $id, string $type): bool {
                $this->calls[] = 'unlock:' . $id . ':' . $type;

                return true;
            },
            'isLocked' => function (int $id, string $type): bool {
                $this->calls[] = 'isLocked:' . $id . ':' . $type;

                return false;
            },
        ]);

        $hydrator = $this->makeEmpty(EditLockHydratorInterface::class, [
            'hydrateEditLock' => new EditLock(false),
        ]);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new EditLockService(
            $editLockResolver,
            $hydrator,
            $elementService,
            $this->makeEmpty(EventDispatcherInterface::class),
            $requestStack,
            $securityService
        );
    }
}
