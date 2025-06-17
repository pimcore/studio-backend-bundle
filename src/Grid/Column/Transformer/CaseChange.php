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
use function is_string;
use function sprintf;

/**
 * @internal
 */
final class CaseChange implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        foreach ($value as $val) {
            if (!is_string($val->getValue())) {
                continue;
            }

            match ($config['mode']) {
                'lowercase' => $val->setValue(strtolower($val->getValue())),
                'uppercase' => $val->setValue(strtoupper($val->getValue())),
                default => throw new TransformerException(
                    $this->getName(),
                    sprintf('Invalid mode "%s" for caseChange transformer.', $config['mode'])
                ),
            };
        }

        return $value;
    }

    public function getName(): string
    {
        return 'Change Case';
    }

    public function getKey(): string
    {
        return 'caseChange';
    }

    public function getDescription(): string
    {
        return 'Changes the case of the value to lower case. Available modes: lower, upper.';
    }
}
