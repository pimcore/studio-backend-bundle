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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Provider;

use Pimcore\Bundle\StudioBackendBundle\Exception\ParameterNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Setting\Request\SettingsRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use UnitEnum;

/**
 * @internal
 */
final class SettingsProvider implements SettingsProviderInterface
{
    private const BLACKLIST_PATTERN = [
        'firewalls',
        'key',
        'password',
        'secret',
        'token'
    ];

    public function __construct(private readonly ParameterBagInterface $parameters) {
    }

    public function getParameters(SettingsRequest $settingsRequest): array
    {
        return array_combine(
            $settingsRequest->getParameters(),
            array_map(
                fn($parameterKey) => $this->getParameter($parameterKey),
                $settingsRequest->getParameters()
            )
        );
    }

    /**
     * @throws ParameterNotFoundException
     */
    protected function getParameter(string $parameterKey): array|bool|float|int|null|string|UnitEnum
    {
        $this->isValidParameter($parameterKey);

        return $this->parameters->get($parameterKey);
    }

    /**
     * @throws ParameterNotFoundException
     */
    private function isValidParameter(string $parameterName): void
    {
        foreach(self::BLACKLIST_PATTERN as $pattern) {
            if (str_contains($parameterName, $pattern)) {
                throw new ParameterNotFoundException($parameterName);
            }
        }

        if(!$this->parameters->has($parameterName)) {
            throw new ParameterNotFoundException($parameterName);
        }
    }
}
