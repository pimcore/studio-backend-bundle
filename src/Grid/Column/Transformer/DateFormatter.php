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

use DateTimeImmutable;
use DateTimeInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\TransformerException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
<<<<<<< HEAD
use function is_int;
=======
>>>>>>> deadcbd0 (Transformers and Testing)
use function is_string;
use function sprintf;

/**
 * @internal
 */
final class DateFormatter implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        if (!isset($config['format']) || !is_string($config['format'])) {
            throw new TransformerException(
                $this->getName(),
<<<<<<< HEAD
                sprintf(
                    'Missing or invalid "format" configuration (must be a string) for %s transformer.',
                    $this->getKey()
                )
=======
                sprintf('Missing or invalid "format" configuration for %s transformer.', $this->getKey())
>>>>>>> deadcbd0 (Transformers and Testing)
            );
        }

        $format = $config['format'];
        $results = [];

<<<<<<< HEAD
=======
        
>>>>>>> deadcbd0 (Transformers and Testing)
        foreach ($value as $val) {
            $data = $val->getValue();

            if (is_int($data)) {
                $data = (new DateTimeImmutable())->setTimestamp($data);
            }

            if (!($data instanceof DateTimeInterface)) {
<<<<<<< HEAD
                $results[] = new AdvancedValue($val->getType(), $data);

=======
                $results[] = new AdvancedValue('string', '');
>>>>>>> deadcbd0 (Transformers and Testing)
                continue;
            }

            $results[] = new AdvancedValue('string', $data->format($format));
        }

<<<<<<< HEAD
=======

>>>>>>> deadcbd0 (Transformers and Testing)
        return $results;
    }

    public function getName(): string
    {
        return 'Date Formatter';
    }

    public function getKey(): string
    {
        return 'dateFormatter';
    }

    public function getDescription(): string
    {
        return 'Formats a date into a specified string format.';
    }

    public function getConfigOptions(): array
    {
        return [
            'format' => ['type' => 'input', 'label' => 'Format', 'default' => 'Y-m-d'],
        ];
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> deadcbd0 (Transformers and Testing)
