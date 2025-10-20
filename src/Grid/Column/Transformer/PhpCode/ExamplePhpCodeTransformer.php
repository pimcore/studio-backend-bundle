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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Transformer\PhpCode;

use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;

use function is_string;

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
        ),
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

    public function getDescription(): string
    {
        return 'Transforms string values to uppercase for demonstration purposes.';
    }

    public function getConfigOptions(): array
    {
        return [
            'enabled' => [
                'type' => 'boolean',
                'label' => 'Enable Transformation',
                'default' => true,
            ],
            'arguments' => [
                'type' => 'keyValue',
                'label' => 'Arguments',
                'default' => [],
            ],
        ];
    }
}
