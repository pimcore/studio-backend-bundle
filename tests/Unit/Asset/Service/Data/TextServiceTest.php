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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Service\Data;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Encoder\TextEncoderInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\Data\TextService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class TextServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetUTF8EncodedDataChecksElementPermissionBeforeReturningData(): void
    {
        $service = $this->createService(checkedPermission: $checkedPermission);

        $data = $service->getUTF8EncodedData(7221);

        $this->assertSame('file contents', $data);
        $this->assertSame(ElementPermissions::VIEW_PERMISSION, $checkedPermission);
    }

    /**
     * This is the regression case for the missing authorization check: a user holding only the
     * coarse "assets" permission must not be able to read the contents of a text asset they have
     * no element-level permission for.
     *
     * @throws Exception
     */
    public function testGetUTF8EncodedDataThrowsForbiddenAndNeverReturnsDataWhenPermissionDenied(): void
    {
        $encoderCalled = false;
        $service = $this->createService(forbidden: true, encoderCalled: $encoderCalled);

        $this->expectException(ForbiddenException::class);

        try {
            $service->getUTF8EncodedData(7221);
        } finally {
            $this->assertFalse($encoderCalled);
        }
    }

    /**
     * @throws Exception
     */
    private function createService(
        ?string &$checkedPermission = null,
        bool $forbidden = false,
        bool &$encoderCalled = false
    ): TextService {
        $element = $this->makeEmpty(ElementInterface::class, ['getId' => 7221]);
        $user = $this->makeEmpty(UserInterface::class, ['getId' => 42]);

        $serviceResolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => fn (string $type, int $id) => $element,
        ]);

        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => fn () => $user,
            'hasElementPermission' => function (
                ElementInterface $element,
                UserInterface $user,
                string $permission
            ) use (&$checkedPermission, $forbidden): void {
                $checkedPermission = $permission;

                if ($forbidden) {
                    throw new ForbiddenException();
                }
            },
        ]);

        $textEncoder = $this->makeEmpty(TextEncoderInterface::class, [
            'encodeUTF8' => function () use (&$encoderCalled) {
                $encoderCalled = true;

                return 'file contents';
            },
        ]);

        return new TextService($securityService, $serviceResolver, $textEncoder);
    }
}
