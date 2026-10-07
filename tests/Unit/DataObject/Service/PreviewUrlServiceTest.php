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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\PreviewParameter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\PreviewUrlService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\PreviewGeneratorInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class PreviewUrlServiceTest extends Unit
{
    /**
     * This is the regression case for the missing authorization check: a user holding only the
     * coarse "data-objects" permission must not be able to reach the preview of a data object they
     * have no element-level permission for.
     *
     * @throws Exception
     */
    public function testGetPreviewUrlThrowsForbiddenWhenPermissionDenied(): void
    {
        $service = $this->createService(forbidden: true, checkedPermission: $checkedPermission);

        $this->expectException(ForbiddenException::class);

        try {
            $service->getPreviewUrl(new PreviewParameter(7221));
        } finally {
            $this->assertSame(ElementPermissions::VIEW_PERMISSION, $checkedPermission);
        }
    }

    /**
     * Legitimate behavior check: once the (new) permission check passes, the existing flow must
     * still run unchanged - a non-Concrete element continues to result in NotFoundException, same
     * as before this fix.
     *
     * @throws Exception
     */
    public function testGetPreviewUrlStillAppliesExistingLogicWhenPermissionGranted(): void
    {
        $service = $this->createService(checkedPermission: $checkedPermission);

        $this->expectException(NotFoundException::class);

        $service->getPreviewUrl(new PreviewParameter(7221));

        $this->assertSame(ElementPermissions::VIEW_PERMISSION, $checkedPermission);
    }

    /**
     * @throws Exception
     */
    private function createService(
        bool $forbidden = false,
        ?string &$checkedPermission = null
    ): PreviewUrlService {
        // Not a Pimcore\Model\DataObject\Concrete, so PreviewUrlService::getPreviewUrl() falls
        // through to its existing NotFoundException branch once the permission check passes.
        $element = $this->makeEmpty(AbstractObject::class, ['getId' => 7221]);
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

        return new PreviewUrlService(
            $this->makeEmpty(PreviewGeneratorInterface::class),
            $securityService,
            $serviceResolver,
        );
    }
}
