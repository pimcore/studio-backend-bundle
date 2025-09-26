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

<<<<<<< HEAD
use Countable;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use function count;
use function is_array;
=======
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\TransformerException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use function count;
>>>>>>> deadcbd0 (Transformers and Testing)

/**
 * @internal
 */
final class ElementCounter implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        $results = [];

        foreach ($value as $val) {
            $data = $val->getValue();

<<<<<<< HEAD
            if (!is_array($data) && !($data instanceof Countable)) {
                $results[] = new AdvancedValue($val->getType(), $data);

=======
            if (!is_array($data) && !($data instanceof \Countable)) {
                $results[] = new AdvancedValue('integer', 0);
>>>>>>> deadcbd0 (Transformers and Testing)
                continue;
            }

            $results[] = new AdvancedValue('integer', count($data));
        }

        return $results;
    }

    public function getName(): string
    {
        return 'Element Counter';
    }

    public function getKey(): string
    {
        return 'elementCounter';
    }

    public function getDescription(): string
    {
        return 'Counts the number of elements in an array or collection.';
    }

    public function getConfigOptions(): array
    {
        return [];
    }
}
