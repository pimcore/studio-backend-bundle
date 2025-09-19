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
use function explode;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final class Explode implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        if (!isset($config['delimiter']) || !is_string($config['delimiter'])) {
            throw new TransformerException(
                $this->getName(),
                sprintf('Missing or invalid "delimiter" configuration for %s transformer.', $this->getKey())
            );
        }

        $delimiter = $config['delimiter'];
        $results = [];

        foreach ($value as $val) {
            $data = $val->getValue();
            if (!is_string($data)) {
                $originalType = $val->getType();
                $results[] = new AdvancedValue($originalType, $data);
                continue;
            }

            $results[] = new AdvancedValue('array', explode($delimiter, $data));
        }

        return $results;
    }

    public function getName(): string
    {
        return 'Explode';
    }

    public function getKey(): string
    {
        return 'explode';
    }

    public function getDescription(): string
    {
        return 'Splits a string by a delimiter into an array.';
    }

    public function getConfigOptions(): array
    {
        return [
            'delimiter' => ['type' => 'input', 'label' => 'Delimiter', 'default' => ','],
        ];
    }
}
