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
use function is_bool;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final class BooleanFormatter implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        if (!isset($config['trueLabel']) || !is_string($config['trueLabel'])) {
            throw new TransformerException(
                $this->getName(),
                sprintf('Missing or invalid "trueLabel" configuration for %s transformer.', $this->getKey())
            );
        }

        if (!isset($config['falseLabel']) || !is_string($config['falseLabel'])) {
            throw new TransformerException(
                $this->getName(),
                sprintf('Missing or invalid "falseLabel" configuration for %s transformer.', $this->getKey())
            );
        }

        $trueLabel = $config['trueLabel'];
        $falseLabel = $config['falseLabel'];
        $results = [];

        foreach ($value as $val) {
            $data = $val->getValue();
            if (!is_bool($data)) {
                $results[] = new AdvancedValue('string', '');
                continue;
            }

            $results[] = new AdvancedValue('string', $data ? $trueLabel : $falseLabel);
        }

        return $results;
    }

    public function getName(): string
    {
        return 'Boolean Formatter';
    }

    public function getKey(): string
    {
        return 'booleanFormatter';
    }

    public function getDescription(): string
    {
        return 'Converts a boolean value to a human-readable string.';
    }

    public function getConfigOptions(): array
    {
        return [
            'trueLabel' => ['type' => 'input', 'label' => 'True Label', 'default' => 'Yes'],
            'falseLabel' => ['type' => 'input', 'label' => 'False Label', 'default' => 'No'],
        ];
    }
}