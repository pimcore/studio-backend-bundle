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


namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Service;

/**
 * @internal
 */
final class OpenSearchFieldMapping implements OpenSearchFieldMappingInterface
{
    public function getOpenSearchKey(string $pimcoreKey): string
    {
        if (!array_key_exists($pimcoreKey, $this->getMapping())) {
            return $pimcoreKey;
        }

        return $this->getMapping()[$pimcoreKey];
    }

    private function getMapping(): array
    {
        return [
            'fullpath' => 'fullPath',
            'filename' => 'key',
        ];
    }
}