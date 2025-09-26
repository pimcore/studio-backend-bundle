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
use function sprintf;
use function str_replace;

/**
 * @internal
 */
final class StringReplace implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        if (!isset($config['find']) || !is_string($config['find'])) {
            throw new TransformerException(
                $this->getName(),
                sprintf('Missing or invalid "find" configuration (must be a string) for %s transformer.', $this->getKey())

            );
        }

        if (!isset($config['replace']) || !is_string($config['replace'])) {
            throw new TransformerException(
                $this->getName(),
                sprintf('Missing or invalid "replace" configuration (must be a string) for %s transformer.', $this->getKey())
            );
        }

        $find = $config['find'];
        $replace = $config['replace'];
        $results = [];

        foreach ($value as $val) {
            $data = $val->getValue();
            if (!is_string($data)) {
                $originalType = $val->getType();
                $results[] = new AdvancedValue($originalType, $data);

                continue;
            }

            $results[] = new AdvancedValue('string', str_replace($find, $replace, $data));
        }

        return $results;
    }

    public function getName(): string
    {
        return 'String Replace';
    }

    public function getKey(): string
    {
        return 'stringReplace';
    }

    public function getDescription(): string
    {
        return 'Replaces all occurrences of a substring within a string.';
    }

    public function getConfigOptions(): array
    {
        return [
            'find' => ['type' => 'input', 'label' => 'Find'],
            'replace' => ['type' => 'input', 'label' => 'Replace'],
        ];
    }
}
