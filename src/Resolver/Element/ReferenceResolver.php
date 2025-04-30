<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Resolver\Element;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\ElementInterface;

final class ReferenceResolver implements ReferenceResolverInterface
{
    use ElementProviderTrait;

    private const ALLOWED_MODEL_PROPERTIES = [
        'key',
        'filename',
        'path',
        'id',
        'type',
    ];

    /**
     * @var array<int, array>
     */
    private array $cache = [];

    public function __construct(private ServiceResolverInterface $serviceResolver)
    {
    }

    public function resolve(ElementInterface $element): array
    {
        if (isset($this->cache[$element->getId()])) {
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

    public function resolveData(string $type, int $id): mixed
    {
        $element = $this->getElement($this->serviceResolver, $type, $id);

        return $this->resolve($element);
    }
}
