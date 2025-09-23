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
use function is_int;
use function is_string;
use function sprintf;
use function substr;

/**
 * @internal
 */
final class SubString implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        if (!isset($config['start']) || !is_int($config['start'])) {
            throw new TransformerException(
                $this->getName(),
                sprintf(
                    'Missing or invalid "start" configuration (must be an integer) for %s transformer.', 
                    $this->getKey()
                    )
            );
        }

        if (!isset($config['length']) || !is_int($config['length'])) {
            throw new TransformerException(
                $this->getName(),
                sprintf(
                    'Missing or invalid "length" configuration (must be an integer) for %s transformer.', 
                    $this->getKey()
                    )
            );
        }

        $start = $config['start'];
        $length = $config['length'];
        $results = [];

        foreach ($value as $val) {
            $data = $val->getValue();
            if (!is_string($data)) {
                $results[] = new AdvancedValue($val->getType(), $data);
                continue;
            }

            $results[] = new AdvancedValue('string', substr($data, $start, $length));
        }

        return $results;
    }

    public function getName(): string
    {
        return 'SubString';
    }

    public function getKey(): string
    {
        return 'substring';
    }

    public function getDescription(): string
    {
        return 'Extracts a part of a string.';
    }

    public function getConfigOptions(): array
    {
        return [
            'start' => ['type' => 'number', 'label' => 'Start', 'default' => 0],
            'length' => ['type' => 'number', 'label' => 'Length', 'default' => 0],
        ];
    }
}
