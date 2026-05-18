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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
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
        $this->validateConfigNameLength($configData);
        $this->validateConfigName($configData['name']);

        return htmlspecialchars($configData['name'], ENT_QUOTES, 'UTF-8');
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getValidConfigDisplayName(array $configData): string
    {
        $this->validateConfigNameLength($configData);
        $this->validateYamlSafeName($configData['name']);

        return $configData['name'];
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateConfigNameLength(array $configData): void
    {
        if (!isset($configData['name'])) {
            throw new InvalidArgumentException(
                'Missing configuration name',
                errorKey: HttpResponseErrorKeys::CONFIG_NAME_INVALID->value
            );
        }

        if (strlen($configData['name']) < 3 || strlen($configData['name']) > 80) {
            throw new InvalidArgumentException(
                'Configuration name must be between 3 and 80 characters',
                errorKey: HttpResponseErrorKeys::CONFIG_NAME_INVALID->value
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateConfigName(string $configurationName): void
    {
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9 _-]{2,79}$/', $configurationName)) {
            throw new InvalidArgumentException(
                'Invalid configuration name',
                errorKey: HttpResponseErrorKeys::CONFIG_NAME_INVALID->value
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateYamlSafeName(string $name): void
    {
        try {
            Yaml::parse($name);
        } catch (ParseException) {
            throw new InvalidArgumentException(
                'Invalid configuration name',
                errorKey: HttpResponseErrorKeys::CONFIG_NAME_INVALID->value
            );
        }
    }
}
