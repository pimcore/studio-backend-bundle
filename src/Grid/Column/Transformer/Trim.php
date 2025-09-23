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
use function trim;

/**
 * @internal
 */
final class Trim implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        if (!isset($config['mode']) || !is_string($config['mode'])) {
            throw new TransformerException(
                $this->getName(),
                sprintf(
                    'Missing or invalid "mode" configuration (must be a string) for %s transformer.',
                    $this->getKey()
                )
            );
        }

        $results = [];

        foreach ($value as $val) {
            $data = $val->getValue();
            if (!is_string($data)) {
                $results[] = new AdvancedValue($val->getType(), $data);

                continue;
            }

            switch ($config['mode']) {
                case 'left':
                    $results[] = new AdvancedValue('string', ltrim($data));

                    break;
                case 'right':
                    $results[] = new AdvancedValue('string', rtrim($data));

                    break;
                case 'both':
                    $results[] = new AdvancedValue('string', trim($data));

                    break;
                case 'disabled':
                    $results[] = new AdvancedValue('string', $data);

                    break;
                default:
                    throw new TransformerException(
                        $this->getName(),
                        sprintf('Invalid mode "%s" for trim transformer.', $config['mode'])
                    );
            }
        }

        return $results;
    }

    public function getName(): string
    {
        return 'Trim';
    }

    public function getKey(): string
    {
        return 'trim';
    }

    public function getDescription(): string
    {
        return 'Removes whitespace from the beginning and end of a string.';
    }

    public function getConfigOptions(): array
    {
        return [
            'mode' => [
                'type' => 'radio',
                'label' => 'Trim',
                'options' => [
                    ['value' => 'both', 'label' => 'Both'],
                    ['value' => 'left', 'label' => 'Left'],
                    ['value' => 'right', 'label' => 'Right'],
                    ['value' => 'disabled', 'label' => 'Disabled'],
                ],
                'default' => 'both',
            ],
        ];
    }
}
