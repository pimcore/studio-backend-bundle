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
