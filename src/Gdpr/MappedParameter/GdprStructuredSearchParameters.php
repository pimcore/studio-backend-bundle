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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @internal
 */
final readonly class GdprStructuredSearchParameters
{
    /**
     * @param string[] $providers
     */
    public function __construct(
        #[NotBlank]
        #[All(new Type('string'))]
        private array $providers,

        #[Valid]
        #[NotNull]
        private SearchTerms $searchTerms,

        #[Valid]
        private FilterParameter $filters
    ) {
    }

    /**
     * @return string[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    public function getSearchTerms(): SearchTerms
    {
        return $this->searchTerms;
    }

    public function getFilters(): FilterParameter
    {
        return $this->filters;
    }
}
