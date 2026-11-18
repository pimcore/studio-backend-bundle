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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ServiceUser\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Hydrator\ServiceUserHydrator;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserMcpTokens;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\WorkspaceHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;
use Pimcore\Model\User;

/**
 * @internal
 */
final class ServiceUserHydratorTest extends Unit
{
    public function testHydratesTheServiceUser(): void
    {
        $assetWorkspace = new UserWorkspace(
            1,
            '/products',
            true,
            true,
            false,
            false,
            false,
            false,
            false,
            false,
            false
        );
        $serviceUser = User::newServiceUser();
        $serviceUser->setId(42);
        $serviceUser->setName('svc-erp-sync');
        $serviceUser->setEmail('erp-team@example.com');
        $serviceUser->setActive(true);
        $serviceUser->setPermissions(['assets']);
        $serviceUser->setRoles([5]);
        $serviceUser->setClasses(['CAR']);
        $serviceUser->setDocTypes(['3']);
        $serviceUser->setLastLogin(1718757677);
        $serviceUser->setResponsibleUsers([3, 4]);
        $serviceUser->setResponsibleRoles([5]);

        $hydrator = new ServiceUserHydrator($this->makeEmpty(WorkspaceHydratorInterface::class, [
            'hydrateAssetWorkspace' => [$assetWorkspace],
            'hydrateDataObjectWorkspace' => [],
            'hydrateDocumentWorkspace' => [],
        ]), clientCredentialsEnabled: true);

        $schema = $hydrator->hydrate($serviceUser, 'svc_client', new ServiceUserMcpTokens(2, 1900000000));

        $this->assertSame(42, $schema->getId());
        $this->assertSame('svc-erp-sync', $schema->getName());
        $this->assertSame('erp-team@example.com', $schema->getEmail());
        $this->assertTrue($schema->isActive());
        $this->assertSame(['assets'], $schema->getPermissions());
        $this->assertSame([5], $schema->getRoles());
        $this->assertSame(['CAR'], $schema->getClasses());
        $this->assertSame(['3'], $schema->getDocTypes());
        $this->assertSame([$assetWorkspace], $schema->getAssetWorkspaces());
        $this->assertSame([], $schema->getDataObjectWorkspaces());
        $this->assertSame([], $schema->getDocumentWorkspaces());
        $this->assertSame('svc_client', $schema->getClientId());
        $this->assertSame(1718757677, $schema->getLastLogin());
        $this->assertTrue($schema->isClientCredentialsEnabled());
        $this->assertSame([3, 4], $schema->getResponsibleUsers());
        $this->assertSame([5], $schema->getResponsibleRoles());
        $this->assertSame(2, $schema->getMcpTokens()->getActive());
        $this->assertSame(1900000000, $schema->getMcpTokens()->getLatestExpiresAt());
    }

    public function testClientCredentialsFlagFollowsTheConfiguration(): void
    {
        $serviceUser = User::newServiceUser();
        $serviceUser->setId(42);
        $serviceUser->setName('svc');
        $hydrator = new ServiceUserHydrator($this->makeEmpty(WorkspaceHydratorInterface::class, [
            'hydrateAssetWorkspace' => [],
            'hydrateDataObjectWorkspace' => [],
            'hydrateDocumentWorkspace' => [],
        ]), clientCredentialsEnabled: false);

        $schema = $hydrator->hydrate($serviceUser, null, new ServiceUserMcpTokens(0, null));

        $this->assertFalse($schema->isClientCredentialsEnabled());
        $this->assertSame(0, $schema->getMcpTokens()->getActive());
        $this->assertNull($schema->getMcpTokens()->getLatestExpiresAt());
    }
}
