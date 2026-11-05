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
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\WorkspaceHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UserWorkspace;
use Pimcore\Model\User\ServiceUser;

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
        $serviceUser = new ServiceUser();
        $serviceUser->setId(42);
        $serviceUser->setName('svc-erp-sync');
        $serviceUser->setEmail('erp-team@example.com');
        $serviceUser->setActive(true);
        $serviceUser->setPermissions(['assets']);
        $serviceUser->setRoles([5]);
        $serviceUser->setClasses(['CAR']);
        $serviceUser->setDocTypes(['3']);
        $serviceUser->setLastLogin(1718757677);

        $hydrator = new ServiceUserHydrator($this->makeEmpty(WorkspaceHydratorInterface::class, [
            'hydrateAssetWorkspace' => [$assetWorkspace],
            'hydrateDataObjectWorkspace' => [],
            'hydrateDocumentWorkspace' => [],
        ]));

        $schema = $hydrator->hydrate($serviceUser, 'svc_client');

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
    }
}
