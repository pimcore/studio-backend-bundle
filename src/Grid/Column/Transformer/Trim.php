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

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use function is_string;
use function trim;

/**
 * @internal
 */
final class Trim implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        $results = [];

        foreach ($value as $val) {
            $data = $val->getValue();
            if (!is_string($data)) {
                $results[] = new AdvancedValue('string', $data);

                continue;
            }

            $results[] = new AdvancedValue('string', trim($data));
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
        return [];
    }
}
