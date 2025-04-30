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

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Repository;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\ElementType;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Element\SearchResult\ElementSearchResult;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Dependency\RequiredByFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Dependency\RequiresFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Element\ElementSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\MappedParameter\DependencyParameters;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class DependencyRepository implements DependencyRepositoryInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ElementSearchServiceInterface $elementSearchService,
        private SearchProviderInterface $searchProvider,
        private ServiceResolverInterface $serviceResolver
    ) {
    }

    public function listRequiresDependencies(
        ElementParameters $elementParameters,
        DependencyParameters $parameters,
        UserInterface $user
    ): ElementSearchResult {
        $element = $this->getElement(
            $this->serviceResolver,
            $elementParameters->getType(),
            $elementParameters->getId()
        );

        $search = $this->searchProvider->createElementSearch();
        $search->setUser($this->getUser($user));
        $search->setPage($parameters->getPage());
        $search->setPageSize($parameters->getPageSize());
        $search->addModifier(
            new RequiresFilter(
                $element->getId(),
                ElementType::fromShortValue($elementParameters->getType())
            )
        );

        return $this->elementSearchService->search($search);
    }

    public function listRequiredByDependencies(
        ElementParameters $elementParameters,
        DependencyParameters $parameters,
        UserInterface $user
    ): ElementSearchResult {
        $element = $this->getElement(
            $this->serviceResolver,
            $elementParameters->getType(),
            $elementParameters->getId()
        );

        $search = $this->searchProvider->createElementSearch();
        $search->setUser($this->getUser($user));
        $search->setPage($parameters->getPage());
        $search->setPageSize($parameters->getPageSize());
        $search->addModifier(
            new RequiredByFilter(
                $element->getId(),
                ElementType::fromShortValue($elementParameters->getType())
            )
        );

        return $this->elementSearchService->search($search);
    }

    private function getUser(UserInterface $user): User
    {
        /** @var User $user */
        return $user;
    }
}
