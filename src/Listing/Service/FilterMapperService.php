<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
