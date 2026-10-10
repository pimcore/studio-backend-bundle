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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Dependency\Repository;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Element\ElementSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\MappedParameter\DependencyParameters;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Repository\DependencyRepository;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\User;

/**
 * @internal
 */
final class DependencyRepositoryViewPermissionTest extends Unit
{
    public function testRequiresDependenciesOfListOnlyElementAreRejected(): void
    {
        $repository = $this->createRepository();

        $this->expectException(ForbiddenException::class);
        $repository->listRequiresDependencies(
            new ElementParameters(ElementTypes::TYPE_OBJECT, 10),
            new DependencyParameters(1, 10, 'requires'),
            new User()
        );
    }

    public function testRequiredByDependenciesOfListOnlyElementAreRejected(): void
    {
        $repository = $this->createRepository();

        $this->expectException(ForbiddenException::class);
        $repository->listRequiredByDependencies(
            new ElementParameters(ElementTypes::TYPE_OBJECT, 10),
            new DependencyParameters(1, 10, 'required_by'),
            new User()
        );
    }

    private function createRepository(): DependencyRepository
    {
        $element = $this->makeEmpty(Folder::class, [
            'isAllowed' => static fn (string $permission): bool => $permission === ElementPermissions::LIST_PERMISSION,
        ]);

        return new DependencyRepository(
            $this->makeEmpty(ElementSearchServiceInterface::class, ['search' => Expected::never()]),
            $this->makeEmpty(SearchProviderInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => $element])
        );
    }
}
