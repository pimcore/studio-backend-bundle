<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */


namespace Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter;

/**
 * @internal
 */
final readonly class SortFilter
{
    public function __construct(
        private string $key,
        private string $direction,
    )
    {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }
}