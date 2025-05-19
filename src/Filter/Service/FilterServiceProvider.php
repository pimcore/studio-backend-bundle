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

namespace Pimcore\Bundle\StudioBackendBundle\Filter\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterServiceTypeException;
use function array_key_exists;

final class FilterServiceProvider implements FilterServiceProviderInterface
{
    private array $filterServices = [];

    public function __construct(FilterServiceLoaderInterface $taggedIteratorAdapter)
    {
        foreach ($taggedIteratorAdapter->loadFilterServices() as $filterService) {
            $this->filterServices[$filterService->getType()] = $filterService;
        }
    }

    /**
     * @throws InvalidFilterServiceTypeException
     */
    public function create(string $type): mixed
    {
        if (!array_key_exists($type, $this->filterServices)) {
            throw new InvalidFilterServiceTypeException($type);
        }

        return $this->filterServices[$type];
    }
}
