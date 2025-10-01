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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\TransformerException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use function is_string;

/**
 * @internal
 */
final class Combine implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        if (!isset($config['glue'])) {
            throw new TransformerException(
                $this->getName(),
                'The "glue" configuration is required for the Combine transformer.'
            );
        }

        if (!is_string($config['glue'])) {
            throw new TransformerException(
                $this->getName(),
                'The "glue" configuration must be a string.'
            );
        }

        $values = array_map(function (AdvancedValue $val) {
            return $val->getValue();
        }, $value);

        $fieldName = $config['columnKey'] ?? $this->getKey();


        return [
                new AdvancedValue(
                    'string',
                    implode($config['glue'], $values),
                    $fieldName
                ),
            ];

    }

    public function getName(): string
    {
        return 'Combine Values';
    }

    public function getKey(): string
    {
        return 'combine';
    }

    public function getDescription(): string
    {
        return 'Combines multiple values into a single string.';
    }

    public function getConfigOptions(): array
    {
        return [
            'glue' => [
                'type' => 'string',
                'label' => 'Glue',
                'description' => 'The string used to join the values together.',
                'required' => true,
            ],
        ];
    }
}
