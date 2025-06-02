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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column;

interface TransformerInterface
{
    /**
     * Transforms a value of the advanced column in the grid.
     * Any string operation can be performed here.
     */
    public function transform(string $value): string;

    public function getName(): string;

    public function getKey(): string;

    public function getDescription(): string;
}
