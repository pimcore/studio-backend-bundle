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

namespace Pimcore\Bundle\StudioBackendBundle\Listing\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Symfony\Contracts\Service\ServiceProviderInterface;
use function get_class;

final readonly class FilterMapperService implements FilterMapperServiceInterface
{
    public function __construct(
        private ServiceProviderInterface $filterMapperLocator,
    ) {
    }

    public function map(mixed $parameters): FilterParameter
    {
        if (!$this->filterMapperLocator->has(get_class($parameters))) {
            throw new InvalidArgumentException('Invalid parameters type provided');
        }

        return $this->filterMapperLocator->get(get_class($parameters))->map($parameters);
    }
}
