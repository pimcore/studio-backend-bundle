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
use function str_repeat;
use function strlen;
use function substr;
use function trim;

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
            $fieldName = $val->getFieldName() ?? ($config['columnKey'] ?? $this->getKey());

            if (!is_string($data)) {
                $results[] = new AdvancedValue($val->getType(), $data, $fieldName);

                continue;
            }

            switch ($config['rule']) {
                case 'mask':
                    $results[] = new AdvancedValue('string', $this->mask($data), $fieldName);

                    break;

                case 'initials':
                    $results[] = new AdvancedValue('string', $this->initials($data), $fieldName);

                    break;

                case 'partial':
                    $results[] = new AdvancedValue('string', $this->partial($data), $fieldName);

                    break;

                case 'hide':
                    $results[] = new AdvancedValue('string', '[hidden]', $fieldName);

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

    private function mask(string $value): string
    {
        // Hardcoded defaults; should be configurable via transformer config if necessary
        $visiblePrefix = 1;
        $visibleDomainSuffix = 4;
        $minMaskLength = 3;
        $minDomainMaskLength = 5;

        if (!str_contains($value, '@')) {
            $length = strlen($value);
            $maskedLength = max($length - $visiblePrefix, $minMaskLength);

            return substr($value, 0, $visiblePrefix) . str_repeat('*', $maskedLength);
        }

        [$local, $domain] = explode('@', $value, 2);

        $maskedLocal = substr($local, 0, $visiblePrefix)
            . str_repeat('*', max(strlen($local) - $visiblePrefix, $minMaskLength));

        $domainLength = strlen($domain);
        $maskedDomain = $domainLength <= $visibleDomainSuffix + 1
            ? str_repeat('*', max($domainLength, $minDomainMaskLength))
            : substr($domain, 0, 1)
                . str_repeat('*', max($domainLength - $visibleDomainSuffix - 1, $minDomainMaskLength))
                . substr($domain, -$visibleDomainSuffix);

        return $maskedLocal . '@' . $maskedDomain;
    }

    private function initials(string $name): string
    {
        $segments = explode(',', trim($name));
        $initials = [];

        foreach ($segments as $segment) {
            $parts = preg_split('/\s+/', trim($segment));
            $initials[] = implode('.', array_map(
                static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)),
                array_filter($parts)
            )) . '.';
        }

        return implode(', ', $initials);

    }

    private function partial(string $value): string
    {
        // Hardcoded defaults; should be configurable via transformer config if necessary
        $visiblePrefix = 2;
        $visibleSuffix = 4;
        $maskChar = '*';
        $minMaskLength = 4;

        $length = strlen($value);

        if ($length <= $visiblePrefix + $visibleSuffix) {
            return substr($value, 0, 1) . str_repeat($maskChar, max($length - 1, $minMaskLength));
        }

        $maskedLength = $length - $visiblePrefix - $visibleSuffix;

        return substr($value, 0, $visiblePrefix)
            . str_repeat($maskChar, max($maskedLength, $minMaskLength))
            . substr($value, -$visibleSuffix);
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
        return 'Anonymizes sensitive data using strategies like mask, initials, partial, or hide.';
    }

    public function getConfigOptions(): array
    {
        return [
            'rule' => [
                'type' => 'select',
                'label' => 'Anonymization Rule',
                'options' => [
                    ['value' => 'mask', 'label' => 'Mask (e.g. j***@e******.com)'],
                    ['value' => 'initials', 'label' => 'Initials (e.g. J.D.)'],
                    ['value' => 'partial', 'label' => 'Partial (e.g. 98****3210)'],
                    ['value' => 'hide', 'label' => 'Hide (e.g. [hidden])'],
                ],
                'default' => 'hide',
            ],
        ];
    }
}
