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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Symfony\Component\Uid\Factory\UuidFactory;
use function strlen;

/**
 * @internal
 */
trait ValidateConfigurationTrait
{
    private function getValidConfigId(UuidFactory $uuidFactory): string
    {
        return str_replace('-', '_', (string)$uuidFactory->create());
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getValidConfigName(array $configData): string
    {
        if (!isset($configData['name'])) {
            throw new InvalidArgumentException(
                'Missing widget name',
                errorKey: HttpResponseErrorKeys::CONFIG_NAME_INVALID->value
            );
        }
        $this->validateConfigName($configData['name']);

        return htmlspecialchars($configData['name'], ENT_QUOTES, 'UTF-8');
    }

    private function validateConfigName(
        string $configurationName
    ): void {
        if (strlen($configurationName) < 3 ||
            strlen($configurationName) > 80 ||
            !preg_match('/^\p{L}[\p{L}\p{N}\s]+$/u', $configurationName)
        ) {
            throw new InvalidArgumentException(
                'Invalid configuration name',
                errorKey: HttpResponseErrorKeys::CONFIG_NAME_INVALID->value
            );
        }
    }
}
