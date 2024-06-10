<?php

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

namespace Pimcore\Bundle\StudioBackendBundle\Resolver\Element;

use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\ElementInterface;

final class ReferenceResolver implements ReferenceResolverInterface
{
    private const ALLOWED_MODEL_PROPERTIES = [
        'key',
        'filename',
        'path',
        'id',
        'type',
    ];

    private array $cache = [];

    public function resolve(ElementInterface $element): array
    {
        if ($this->cache[$element->getId()] ?? false) {
            return $this->cache[$element->getId()];
        }

        /**
         * @var AbstractElement $element
         */
        $data = array_intersect_key(
            $element->getObjectVars(),
            array_flip(self::ALLOWED_MODEL_PROPERTIES)
        );

        $data['fullPath'] = $element->getFullPath();

        $this->cache[$element->getId()] = $data;

        return $data;
    }
}
