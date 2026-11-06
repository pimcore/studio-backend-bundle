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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
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

    /**
     * @var string[]
     */
    private array $checks = [];

    protected function _before(): void
    {
        $this->calls = [];
        $this->checks = [];
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
            $this->assertSame($this->expectedChecks('object'), $this->checks);
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
            $this->assertSame($this->expectedChecks('object', loaded: false), $this->checks);
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
        $this->assertSame($this->expectedChecks('object'), $this->checks);
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
            $this->assertSame($this->expectedChecks('asset'), $this->checks);
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
            $this->assertSame($this->expectedChecks('asset', loaded: false), $this->checks);
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
        $this->assertSame($this->expectedChecks('asset'), $this->checks);
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
            $this->assertSame($this->expectedChecks('document'), $this->checks);
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
            $this->assertSame($this->expectedChecks('document', loaded: false), $this->checks);
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
        $this->assertSame($this->expectedChecks('document'), $this->checks);
    }

    /**
     * @throws Exception
     */
    public function testLockElementWithInvalidTypeFailsBeforeLookup(): void
    {
        $service = $this->createService(forbidden: true, elementFound: false);

        $this->expectException(InvalidElementTypeException::class);

        try {
            $service->lockElement(self::ELEMENT_ID, 'invalid');
        } finally {
            $this->assertSame([], $this->calls);
            $this->assertSame([], $this->checks);
        }
    }

    /**
     * @throws Exception
     */
    public function testUnlockElementWithInvalidTypeFailsBeforeLookup(): void
    {
        $service = $this->createService(forbidden: true, elementFound: false);

        $this->expectException(InvalidElementTypeException::class);

        try {
            $service->unlockElement(self::ELEMENT_ID, 'invalid');
        } finally {
            $this->assertSame([], $this->calls);
            $this->assertSame([], $this->checks);
        }
    }

    /**
     * @throws Exception
     */
    public function testGetEditLockWithInvalidTypeFailsBeforeLookup(): void
    {
        $service = $this->createService(forbidden: true, elementFound: false);

        $this->expectException(InvalidElementTypeException::class);

        try {
            $service->getEditLock(self::ELEMENT_ID, 'invalid');
        } finally {
            $this->assertSame([], $this->calls);
            $this->assertSame([], $this->checks);
        }
    }

    /**
     * @throws Exception
     */
    private function createService(bool $forbidden = false, bool $elementFound = true): EditLockService
    {
        $serviceResolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => function (string $type, int $id) use ($elementFound): ?ElementInterface {
                $this->checks[] = 'load:' . $type . ':' . $id;

                return $elementFound ? $this->createElement($type) : null;
            },
        ]);

        $user = $this->makeEmpty(UserInterface::class);

        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $user,
            'hasElementPermission' => function (
                ElementInterface $element,
                UserInterface $user,
                string $permission
            ) use ($forbidden): void {
                $this->checks[] = 'permission:' . $this->elementTypeOf($element) . ':' . $permission;
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

    /**
     * @return string[]
     */
    private function expectedChecks(string $type, bool $loaded = true): array
    {
        $checks = ['load:' . $type . ':' . self::ELEMENT_ID];
        if ($loaded) {
            $checks[] = 'permission:' . $type . ':view';
        }

        return $checks;
    }

    /**
     * @throws Exception
     */
    private function createElement(string $type): ElementInterface
    {
        return $this->makeEmpty($this->elementClass($type), ['getId' => self::ELEMENT_ID]);
    }

    private function elementTypeOf(ElementInterface $element): string
    {
        return match (true) {
            $element instanceof Folder => 'object',
            $element instanceof Asset => 'asset',
            default => 'document',
        };
    }

    private function elementClass(string $type): string
    {
        return match ($type) {
            'object' => Folder::class,
            'asset' => Asset::class,
            default => Document::class,
        };
    }
}
