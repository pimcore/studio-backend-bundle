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
use function md5;
use function sprintf;

/**
 * @internal
 */
final class Anonymizer implements TransformerInterface
{
    public function transform(array $value, array $config): array
    {
        if (!isset($config['rule']) || !is_string($config['rule'])) {
            throw new TransformerException(
                $this->getName(),
                sprintf(
                    'Missing or invalid "rule" configuration (must be a string) for %s transformer.',
                    $this->getKey()
                )
            );
        }

        $results = [];

        foreach ($value as $val) {
            $data = $val->getValue();

            if (!is_string($data)) {
                $results[] = $val;

                continue;
            }

            switch ($config['rule']) {
                case 'md5':
                    $results[] = new AdvancedValue('string', md5($data), $val->getFieldName());

                    break;

                case 'bcrypt':
                    $results[] = new AdvancedValue('string', $this->bcrypt($data), $val->getFieldName());

                    break;

                default:
                    throw new TransformerException(
                        $this->getName(),
                        sprintf('Invalid rule "%s" for anonymizer transformer.', $config['rule'])
                    );
            }
        }

        return $results;
    }

    private function bcrypt(string $value): string
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }

    public function getName(): string
    {
        return 'Anonymizer';
    }

    public function getKey(): string
    {
        return 'anonymizer';
    }

    public function getDescription(): string
    {
        return 'Anonymizes transforms sensitive data using strategies like md5, bcrypt.';
    }

    public function getConfigOptions(): array
    {
        return [
            'rule' => [
                'type' => 'select',
                'label' => 'Anonymization Rule',
                'options' => [
                    ['value' => 'md5', 'label' => 'MD5 Hash'],
                    ['value' => 'bcrypt', 'label' => 'Bcrypt'],
                    ['value' => 'default', 'label' => 'Default Placeholder'],
                ],
                'default' => 'default',
            ],
        ];
    }
}
