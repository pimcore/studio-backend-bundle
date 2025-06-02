<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 * @license    Pimcore Open Core License (POCL)
 */


namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Transformer;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;

/**
 * @internal
 */
final class Uppercase implements TransformerInterface
{

    public function transform(string $value): string
    {
        return strtoupper($value);
    }

    public function getName(): string
    {
        return 'Uppercase';
    }

    public function getKey(): string
    {
        return 'uppercase';
    }

    public function getDescription(): string
    {
        return 'Transforms the value to uppercase.';
    }
}