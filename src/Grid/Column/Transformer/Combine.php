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

/**
 * @internal
 */
final class Combine implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        if (!isset($config['clue'])) {
            throw new TransformerException(
                $this->getName(),
                'The "clue" configuration is required for the Combine transformer.'
            );
        }

        if (!is_string($config['clue'])) {
            throw new TransformerException(
                $this->getName(),
                'The "clue" configuration must be a string.'
            );
        }

        $values = array_map(function ($val) {
            return $val->getValue();
        }, $value);

        return [
            new AdvancedValue(
                'string',
                implode($config['clue'], $values)
            )
        ];
    }

    public function getName(): string
    {
        return 'Combine';
    }

    public function getKey(): string
    {
        return 'combine';
    }

    public function getDescription(): string
    {
        return 'Combines multiple values into a single string.';
    }
}
