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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Transformer;

use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\PhpCodeTransformerInterface;

/**
 * @internal
 */
final class ExamplePhpCodeTransformer implements PhpCodeTransformerInterface
{
    public function transform(mixed $value, array $arguments): array
    {
        // A demonstration example of how new transformers can be created with PHP code.
        // This example simply transforms string values to uppercase.
        $transformed = is_string($value) ? strtoupper($value) : $value;

        return [
        new AdvancedValue(
            'string',
            $transformed,
            'null'
        )
    ];
    }

    public function getName(): string
    {
        return 'Example PHP Code Transformer';
    }

    public function getKey(): string
    {
        return 'examplePhpCode';
    }


}