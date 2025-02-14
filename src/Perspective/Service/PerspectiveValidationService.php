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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\SavePerspectiveConfig;
use Throwable;
use function sprintf;

/**
 * @internal
 */
final readonly class PerspectiveValidationService implements PerspectiveValidationServiceInterface
{
    public function __construct(
        private IconServiceInterface $iconService
    ) {
    }

    /**
     * @throws ValidationFailedException
     */
    public function validatePerspectiveConfigData(array $perspectiveData): SavePerspectiveConfig
    {
        //TODO: Implement logic for validating and setting widgets and permissions
        try {
            $configuration = new SavePerspectiveConfig(
                $perspectiveData['id'],
                $perspectiveData['name'],
                $this->iconService->getIconForValue($perspectiveData['icon']),
                $perspectiveData['contextPermissions'],
                $perspectiveData['widgetsLeft'],
                $perspectiveData['widgetsRight'],
                $perspectiveData['widgetsBottom'],
                $perspectiveData['expandedLeft'],
                $perspectiveData['expandedRight']
            );
        } catch (Throwable $exception) {
            throw new ValidationFailedException(
                sprintf('Could not process perspective data: %s', $exception->getMessage()),
                previous: $exception
            );
        }

        return $configuration;
    }
}
