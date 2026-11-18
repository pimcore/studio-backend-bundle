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

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Site\SiteResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Hydrator\SiteHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Repository\SiteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\UpdateSiteParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\SiteService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\Document\Folder as CoreFolder;
use Pimcore\Model\User;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * Regression test for GHSA-r8v3-9phw-8m68.
 *
 * Site update/delete create, modify and delete Site configuration (domains, main domain,
 * redirect-to-main-domain). Before the fix, the only element-level check was VIEW_PERMISSION
 * (via DocumentService::getDocumentElement), so a user who could merely read a document could
 * rebind its domains. The settings-level permission must be enforced on the target document.
 *
 * @internal
 */
final class SiteServicePermissionTest extends Unit
{
    private const int DOCUMENT_ID = 3;

    private User $user;

    private int $permissionChecks = 0;

    protected function _before(): void
    {
        $this->user = new User();
        $this->permissionChecks = 0;
    }

    public function testUpdateSiteRejectsElementWithoutSettingsPermission(): void
    {
        $document = new CoreFolder();
        $service = $this->createService($document, false);

        $this->expectException(ForbiddenException::class);
        $service->updateSite(self::DOCUMENT_ID, new UpdateSiteParameters());
    }

    public function testDeleteSiteRejectsElementWithoutSettingsPermission(): void
    {
        $document = new CoreFolder();
        $service = $this->createService($document, false);

        $this->expectException(ForbiddenException::class);
        $service->deleteSite(self::DOCUMENT_ID);
    }

    public function testUpdateSiteProceedsToSiteCreationWhenSettingsPermissionGranted(): void
    {
        $document = new CoreFolder();
        // Site is final and its save() needs a booted kernel, so a unit test cannot persist one.
        // A failing create() proves the settings check passed and execution reached the mutation.
        $service = $this->createService($document, true, [
            'getByRootId' => null,
            'create' => function (): never {
                throw new RuntimeException('create reached');
            },
        ]);

        try {
            $service->updateSite(self::DOCUMENT_ID, new UpdateSiteParameters());
            $this->fail('Expected the site creation to be reached.');
        } catch (ElementSavingFailedException) {
            $this->assertSame(1, $this->permissionChecks);
        }
    }

    public function testDeleteSiteProceedsToSiteLookupWhenSettingsPermissionGranted(): void
    {
        $document = new CoreFolder();
        // No site is bound to the document: reaching NotFoundException proves the settings
        // check passed and execution got to the site lookup, without calling Site::delete().
        $service = $this->createService($document, true, ['getByRootId' => null]);

        try {
            $service->deleteSite(self::DOCUMENT_ID);
            $this->fail('Expected the site lookup to be reached.');
        } catch (NotFoundException) {
            $this->assertSame(1, $this->permissionChecks);
        }
    }

    private function createService(
        CoreFolder $document,
        bool $hasSettingsPermission,
        array $siteResolverStubs = []
    ): SiteService
    {
        $hasElementPermission = function (
            CoreFolder $element,
            User $user,
            string $permission
        ) use ($hasSettingsPermission): void {
            $this->permissionChecks++;
            $this->assertSame($this->user, $user);
            $this->assertSame(
                ElementPermissions::SETTINGS_PERMISSION,
                $permission,
                'Site write operations must be gated on the settings permission, not just view.'
            );

            if (!$hasSettingsPermission) {
                throw new ForbiddenException(sprintf('You dont have %s permission', $permission));
            }
        };

        return new SiteService(
            $this->makeEmpty(DocumentServiceInterface::class, [
                'getDocumentElement' => $document,
            ]),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->user,
                'hasElementPermission' => $hasElementPermission,
            ]),
            $this->makeEmpty(SiteHydratorInterface::class),
            $this->makeEmpty(SiteRepositoryInterface::class),
            $this->makeEmpty(SiteResolverInterface::class, $siteResolverStubs),
            $this->makeEmpty(ToolResolverInterface::class),
        );
    }
}
