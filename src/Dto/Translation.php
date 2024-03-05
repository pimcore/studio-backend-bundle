<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Dto;

/**
 * @internal
 */
final readonly class Translation
{
    public function __construct(
        private string $locale = 'en',
        private array $keys = []
    ) {
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getKeys(): array
    {
        return $this->keys;
    }
}
