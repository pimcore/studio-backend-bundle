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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfig;

/**
 * @internal
 */
final readonly class PerspectiveConfigHydrator implements PerspectiveConfigHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function hydrate(array $perspectiveData): PerspectiveConfig
    {

        return new PerspectiveConfig(
            $perspectiveData['id'],
            $perspectiveData['name'],
            $this->iconService->getIconForValue($perspectiveData['icon']),
            $perspectiveData['isWriteable'],
        );
    }
}
