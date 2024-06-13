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

namespace Pimcore\Bundle\StudioBackendBundle\Filter\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterServiceTypeException;

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
            throw new InvalidFilterServiceTypeException(400, "Unknown filter type: $type");
        }

        return $this->filterServices[$type];
    }
}
