<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\Search\Tree\AssetTreeServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Image;
use Pimcore\Bundle\StudioApiBundle\Dto\User;
use Pimcore\Bundle\StudioApiBundle\Filter\AssetParentIdFilter;
use Pimcore\Model\Asset\Image as ImageModel;

final readonly class UserProvider implements ProviderInterface
{
    public function __construct(
        private UserResolverInterface $userResolver,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return null;
        }
        $userModel = $this->userResolver->getById($uriVariables['id']);

        if ($userModel === null) {
            return null;
        }


        return new User($userModel);
    }
}
