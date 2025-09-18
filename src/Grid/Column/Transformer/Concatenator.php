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
use function implode;
use function is_string;
use function array_map;

/**
 * @internal
 */
final class Concatenator implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        if (!isset($config['separator']) || !is_string($config['separator'])) {
            throw new TransformerException(
                $this->getName(),
                'The "separator" configuration is required and must be a string.'
            );
        }

        // Extract raw values from AdvancedValue objects
        $values = array_map(function ($val) {
            return $val->getValue();
        }, $value);

        // Concatenate using the separator
        $concatenated = implode($config['separator'], $values);

        return [
            new AdvancedValue('string', $concatenated),
        ];
    }

    public function getName(): string
    {
        return 'Concatenator';
    }

    public function getKey(): string
    {
        return 'concatenator';
    }

    public function getDescription(): string
    {
        return 'Concatenates multiple values into a single string using a specified separator.';
    }

    public function getConfigOptions(): array
    {
        return [
            'separator' => [
                'type' => 'string',
                'label' => 'Separator',
                'description' => 'The string used to join the values together.',
                'required' => true,
                'default' => ' - ',
            ],
        ];
    }
}
